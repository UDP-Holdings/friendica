<?php

// UDP Social — chunked video/attachment upload handler
// Receives individual 50 MB chunks from Dropzone.js and assembles them
// into a single file on the last chunk, then feeds the result into the
// standard Attach::storeFile() pipeline.
//
// Video transcoding is done asynchronously via UdpTranscodeVideo worker.
// A thumbnail is extracted synchronously (~1s) so the media card populates
// immediately while the transcode is still pending.

namespace Friendica\Module\Media\Attachment;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Photo;
use Friendica\Model\UdpMedia;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Object\Image;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class UploadChunk extends BaseModule
{
	private UserSession $userSession;
	private IManageConfigValues $config;

	public function __construct(
		UserSession $userSession,
		IManageConfigValues $config,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = []
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
		$this->userSession = $userSession;
		$this->config      = $config;
	}

	protected function post(array $request = [])
	{
		$owner = User::getOwnerDataById($this->userSession->getLocalUserId());
		if (!$owner) {
			$this->jsonError(401, ['error' => 'Not authenticated.']);
		}

		if (empty($_FILES['userfile'])) {
			$this->jsonError(400, ['error' => 'No file chunk received.']);
		}

		// Dropzone chunk metadata
		$uuid        = (string) ($request['dzuuid']           ?? '');
		$chunkIndex  = (int)   ($request['dzchunkindex']      ?? 0);
		$totalChunks = (int)   ($request['dztotalchunkcount'] ?? 1);
		$fileName    = basename((string) ($_FILES['userfile']['name'] ?? 'upload'));
		$mimeType    = (string) ($_FILES['userfile']['type']          ?? '');

		// Sanitize UUID to safe filesystem chars
		$safeUuid = preg_replace('/[^a-zA-Z0-9\-]/', '', $uuid);
		if (empty($safeUuid)) {
			$this->jsonError(400, ['error' => 'Invalid upload session ID.']);
		}

		$uploadDir = sys_get_temp_dir() . '/udp_upload_' . $safeUuid;
		if (!is_dir($uploadDir) && !mkdir($uploadDir, 0700, true)) {
			$this->jsonError(500, ['error' => 'Could not create temporary upload directory.']);
		}

		// Write chunk to disk; zero-pad index so glob() sorts numerically
		$chunkPath = $uploadDir . '/chunk_' . sprintf('%06d', $chunkIndex);
		if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $chunkPath)) {
			$this->jsonError(500, ['error' => 'Failed to save chunk.']);
		}

		// Not the last chunk — acknowledge and wait for the rest
		if ($chunkIndex < $totalChunks - 1) {
			$this->jsonExit(['ok' => true, 'partial' => true]);
		}

		// Last chunk received — stream-assemble into a single temp file.
		// Use fopen/stream_copy_to_stream to avoid loading each chunk into memory.
		$assembledPath = $uploadDir . '/assembled';
		$out = fopen($assembledPath, 'wb');
		if ($out === false) {
			$this->jsonError(500, ['error' => 'Could not open assembly file for writing.']);
		}
		foreach (range(0, $totalChunks - 1) as $i) {
			$part = $uploadDir . '/chunk_' . sprintf('%06d', $i);
			if (!file_exists($part)) {
				fclose($out);
				$this->jsonError(500, ['error' => 'Missing chunk ' . $i . ' during assembly.']);
			}
			$in = fopen($part, 'rb');
			stream_copy_to_stream($in, $out);
			fclose($in);
		}
		fclose($out);

		$dbgLog = '/tmp/udp_chunk_debug.log';
		$dbg = fn(string $msg) => file_put_contents($dbgLog, date('H:i:s') . " $msg\n", FILE_APPEND);
		$dbg("assembled OK size=" . filesize($assembledPath) . " mime=$mimeType");

		// Probe to determine whether this is a video and what codec it uses.
		// Empty/octet-stream MIME from Android is treated as unknown and probed.
		$codec    = '';
		$isVideo  = false;
		if ($this->config->get('system', 'ffmpeg_installed')) {
			$dbg("probing codec...");
			$codec   = $this->probeVideoCodec($assembledPath, $mimeType);
			$isVideo = ($codec !== '');
			$dbg("codec=$codec isVideo=" . ($isVideo ? 'y' : 'n'));
		}

		// Normalise MIME for videos where the browser didn't report a type
		if ($isVideo && (empty($mimeType) || $mimeType === 'application/octet-stream')) {
			$mimeType = 'video/mp4';
		}

		// Generate a thumbnail synchronously (~1s) so the media card populates
		// immediately, before the async transcode completes.
		$thumbResourceId = '';
		if ($isVideo) {
			$dbg("generating thumbnail...");
			$thumbResourceId = $this->generateThumbnail($assembledPath, $owner['uid'], (int) $owner['id'], $dbg);
			$dbg("thumbnail rid=$thumbResourceId");
		}

		// Build attachment ACL from the compose form's visibility selection.
		// Dropzone sends visibility/contact_allow/circle_allow/contact_deny/circle_deny
		// with each chunk so we can store the file with the correct permissions
		// before the post itself is created.
		$dbg("building ACL...");
		$acl = DI::aclFormatter();
		if (($request['visibility'] ?? '') === 'public') {
			$allowCid = $allowGid = $denyCid = $denyGid = '';
		} else {
			$allowCid = $acl->toString($request['contact_allow'] ?? '');
			$allowGid = $acl->toString($request['circle_allow']  ?? '');
			$denyCid  = $acl->toString($request['contact_deny']  ?? '');
			$denyGid  = $acl->toString($request['circle_deny']   ?? '');
		}
		$dbg("ACL done. calling Attach::storeFile...");

		$newId = Attach::storeFile($assembledPath, $owner['uid'], $fileName, $mimeType, $allowCid, $allowGid, $denyCid, $denyGid);
		$dbg("storeFile returned id=$newId");

		// Clean up temp directory
		foreach (glob($uploadDir . '/*') as $f) {
			@unlink($f);
		}
		@rmdir($uploadDir);

		if ($newId === false) {
			$this->jsonError(500, ['error' => 'File storage failed.']);
		}

		// Index in udp-media
		$udpMediaType = str_starts_with($mimeType, 'audio/') ? UdpMedia::TYPE_AUDIO : UdpMedia::TYPE_VIDEO;
		$udpMediaId   = UdpMedia::create($owner['uid'], $udpMediaType, UdpMedia::REF_ATTACH, (int) $newId, '', '', $thumbResourceId);

		// Queue async transcode for video files that aren't already H.264
		if ($isVideo && $codec !== 'h264' && $udpMediaId !== false) {
			Worker::add(Worker::PRIORITY_LOW, 'UdpTranscodeVideo', (int) $newId, (int) $udpMediaId);
		}

		$this->jsonExit(['ok' => true, 'id' => $newId]);
	}

	/**
	 * Probe a file for a video stream and return the codec name (e.g. 'hevc', 'h264').
	 * Returns '' if the file has no video stream or ffprobe is unavailable.
	 *
	 * Android often sends empty MIME or application/octet-stream for HEVC files,
	 * so we probe regardless of the reported type when it's ambiguous.
	 */
	private function probeVideoCodec(string $path, string $mimeType): string
	{
		// Known non-video types — skip without probing
		if (!empty($mimeType)
			&& !str_starts_with($mimeType, 'video/')
			&& $mimeType !== 'application/octet-stream') {
			return '';
		}

		$ffprobe = trim((string) shell_exec('which ffprobe'));
		if (empty($ffprobe)) {
			return '';
		}

		return trim((string) shell_exec(
			escapeshellarg($ffprobe)
			. ' -v quiet -select_streams v:0'
			. ' -show_entries stream=codec_name -of csv=p=0 '
			. escapeshellarg($path)
			. ' 2>/dev/null'
		));
	}

	/**
	 * Extract a single frame from a video file and store it as a photo row.
	 * Returns the photo resource-id on success, '' on failure.
	 *
	 * The thumbnail is generated from the assembled original so it is always
	 * available immediately, even before the async transcode completes.
	 */
	private function generateThumbnail(string $videoPath, int $uid, int $ownerContactId, callable $dbg = null): string
	{
		$dbg ??= fn(string $msg) => null;

		$ffmpeg = trim((string) shell_exec('which ffmpeg'));
		if (empty($ffmpeg)) {
			$dbg("thumb: no ffmpeg");
			return '';
		}

		$thumbPath = $videoPath . '_thumb.jpg';
		$dbg("thumb: running ffmpeg on $videoPath");

		exec(
			escapeshellarg($ffmpeg)
			. ' -ss 0 -i ' . escapeshellarg($videoPath)
			. ' -frames:v 1 -vf scale=640:-1 -q:v 5 '
			. escapeshellarg($thumbPath)
			. ' 2>/dev/null',
			$cmdOut,
			$exitCode
		);

		$dbg("thumb: exec done exit=$exitCode exists=" . (file_exists($thumbPath) ? filesize($thumbPath) : 'no'));

		if ($exitCode !== 0 || !file_exists($thumbPath) || filesize($thumbPath) === 0) {
			$this->logger->warning('UDP: thumbnail extraction failed', ['exit' => $exitCode]);
			@unlink($thumbPath);
			return '';
		}

		$dbg("thumb: reading jpeg data");
		$data  = @file_get_contents($thumbPath);
		@unlink($thumbPath);

		if (empty($data)) {
			$dbg("thumb: empty data after file_get_contents");
			return '';
		}

		$dbg("thumb: new Image len=" . strlen($data));
		$image = new Image($data, 'image/jpeg');
		if (!$image->isValid()) {
			$dbg("thumb: Image invalid");
			return '';
		}

		$resourceId = Strings::getRandomHex();
		$dbg("thumb: calling Photo::storeWithPreview rid=$resourceId");

		try {
			Photo::storeWithPreview(
				$image,
				$uid,
				$resourceId,
				'video-thumb-' . $resourceId . '.jpg',
				strlen($data),
				'Video Thumbnails',
				'',
				'<' . $ownerContactId . '>',
				'',
				'',
				''
			);
			$dbg("thumb: storeWithPreview done");
		} catch (\Throwable $e) {
			$dbg("thumb: storeWithPreview THREW " . get_class($e) . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
			return '';
		}

		return $resourceId;
	}
}
