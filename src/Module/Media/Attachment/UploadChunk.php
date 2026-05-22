<?php

// UDP Social — chunked video/attachment upload handler
// Receives individual 50 MB chunks from Dropzone.js and assembles them
// into a single file on the last chunk, then feeds the result into the
// standard Attach::storeFile() pipeline.

namespace Friendica\Module\Media\Attachment;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Model\Attach;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
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

		// Last chunk received — assemble into a single temp file
		$assembledPath = $uploadDir . '/assembled';
		foreach (range(0, $totalChunks - 1) as $i) {
			$part = $uploadDir . '/chunk_' . sprintf('%06d', $i);
			if (!file_exists($part)) {
				$this->jsonError(500, ['error' => 'Missing chunk ' . $i . ' during assembly.']);
			}
			// Append each chunk; each is at most 50 MB so this is memory-safe
			file_put_contents($assembledPath, file_get_contents($part), FILE_APPEND);
		}

		// Transcode HEVC/other non-H.264 video to H.264 MP4 for universal browser support
		$pathToStore = $this->maybeTranscodeVideo($assembledPath, $mimeType, $fileName);

		// Store via existing Attach pipeline (reads assembled file into memory once)
		$newId = Attach::storeFile($pathToStore, $owner['uid'], $fileName, $mimeType, '<' . $owner['id'] . '>');

		// Clean up temp directory regardless of outcome
		foreach (glob($uploadDir . '/*') as $f) {
			@unlink($f);
		}
		@rmdir($uploadDir);

		if ($newId === false) {
			$this->jsonError(500, ['error' => 'File storage failed.']);
		}

		$this->jsonExit(['ok' => true, 'id' => $newId]);
	}

	/**
	 * Transcode a video file to H.264/AAC MP4 for universal browser compatibility.
	 * Skips transcode if ffmpeg is unavailable, the file is already H.264, or
	 * ffmpeg returns a non-zero exit code. Falls back to the original on any failure.
	 * Updates $mimeType and $fileName by reference on success.
	 *
	 * Android often sends an empty MIME type or application/octet-stream for HEVC
	 * files, so we treat those as "unknown" and let ffprobe determine whether the
	 * file is actually a video before deciding whether to transcode.
	 */
	private function maybeTranscodeVideo(string $inputPath, string &$mimeType, string &$fileName): string
	{
		// Explicitly non-video MIME types (image/*, audio/*, text/*, …) — skip without probing.
		// Empty or application/octet-stream means the browser didn't report a type; fall through.
		if (!empty($mimeType)
			&& !str_starts_with($mimeType, 'video/')
			&& $mimeType !== 'application/octet-stream') {
			return $inputPath;
		}

		if (!$this->config->get('system', 'ffmpeg_installed')) {
			return $inputPath;
		}

		$ffmpeg  = trim((string) shell_exec('which ffmpeg'));
		$ffprobe = trim((string) shell_exec('which ffprobe'));

		if (empty($ffmpeg)) {
			return $inputPath;
		}

		// Probe the file to determine whether it has a video stream and what codec it uses.
		// This is the authoritative check — it handles both explicit video/* MIME types and
		// the empty/octet-stream case common for HEVC uploads from Android.
		$codec = '';
		if (!empty($ffprobe)) {
			$codec = trim((string) shell_exec(
				escapeshellarg($ffprobe)
				. ' -v quiet -select_streams v:0'
				. ' -show_entries stream=codec_name -of csv=p=0 '
				. escapeshellarg($inputPath)
				. ' 2>/dev/null'
			));
		}

		if (empty($codec)) {
			// ffprobe found no video stream — not a video file, nothing to transcode.
			return $inputPath;
		}

		// File is a video. Normalise MIME type if the browser didn't report one.
		if (empty($mimeType) || $mimeType === 'application/octet-stream') {
			$mimeType = 'video/mp4';
		}

		// Already H.264 — no transcode needed.
		if ($codec === 'h264') {
			return $inputPath;
		}

		$outputPath = $inputPath . '_h264.mp4';
		exec(
			escapeshellarg($ffmpeg)
			. ' -i ' . escapeshellarg($inputPath)
			. ' -c:v libx264 -preset fast -crf 23'
			. ' -c:a aac -map 0:v -map 0:a?'
			. ' -movflags +faststart '
			. escapeshellarg($outputPath)
			. ' 2>/dev/null',
			$cmdOut,
			$exitCode
		);

		if ($exitCode !== 0 || !file_exists($outputPath) || filesize($outputPath) === 0) {
			$this->logger->warning('UDP: video transcode failed, serving original', [
				'input' => basename($inputPath),
				'exit'  => $exitCode,
			]);
			@unlink($outputPath);
			return $inputPath;
		}

		$mimeType = 'video/mp4';
		$fileName = preg_replace('/\.[^.]+$/', '', $fileName) . '.mp4';
		return $outputPath;
	}
}
