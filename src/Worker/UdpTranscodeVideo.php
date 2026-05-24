<?php

// UDP Social — async video transcode worker (Cat2 — new file)
//
// Transcodes a stored video attachment to H.264/AAC MP4 in-place.
// Queued by UploadChunk after every non-H.264 video upload.
//
// Arguments: $attachId (int), $udpMediaId (int)
//
// The attach row already exists in the database and the file is already
// stored in the configured storage backend. This worker:
//   1. Reads the file from storage to a temp path
//   2. Probes codec with ffprobe — exits early if already H.264
//   3. Transcodes to H.264/AAC MP4 with ffmpeg
//   4. Writes the transcoded bytes back to the same storage reference
//   5. Updates the attach row metadata (filetype, filename, filesize)

namespace Friendica\Worker;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Util\DateTimeFormat;

class UdpTranscodeVideo
{
	public static function execute(int $attachId, int $udpMediaId)
	{
		$logger = DI::logger();
		$config = DI::config();

		if (!$config->get('system', 'ffmpeg_installed')) {
			return;
		}

		$ffmpeg  = trim((string) shell_exec('which ffmpeg'));
		$ffprobe = trim((string) shell_exec('which ffprobe'));
		if (empty($ffmpeg) || empty($ffprobe)) {
			$logger->warning('UDP transcode: ffmpeg/ffprobe not found', ['attach_id' => $attachId]);
			return;
		}

		$attach = DBA::selectFirst('attach', ['id', 'uid', 'filename', 'filetype', 'filesize', 'backend-class', 'backend-ref'], ['id' => $attachId]);
		if (empty($attach)) {
			$logger->warning('UDP transcode: attach row not found', ['attach_id' => $attachId]);
			return;
		}

		// Read file from storage into a temp file
		$data = Attach::getData($attach);
		if (empty($data)) {
			$logger->warning('UDP transcode: could not read attach data', ['attach_id' => $attachId]);
			return;
		}

		$tmpDir  = sys_get_temp_dir() . '/udp_transcode_' . $attachId;
		@mkdir($tmpDir, 0700, true);
		$srcPath = $tmpDir . '/source';
		file_put_contents($srcPath, $data);
		unset($data);

		// Probe codec
		$codec = trim((string) shell_exec(
			escapeshellarg($ffprobe)
			. ' -v quiet -select_streams v:0'
			. ' -show_entries stream=codec_name -of csv=p=0 '
			. escapeshellarg($srcPath)
			. ' 2>/dev/null'
		));

		if (empty($codec)) {
			// No video stream — nothing to transcode
			self::cleanup($tmpDir);
			return;
		}

		if ($codec === 'h264') {
			// Already H.264 — no work needed
			self::cleanup($tmpDir);
			return;
		}

		$outPath = $tmpDir . '/output.mp4';
		// nice -n 19 + ionice -c 3 keep the transcode from competing with web requests
		exec(
			'nice -n 19 ionice -c 3 '
			. escapeshellarg($ffmpeg)
			. ' -i ' . escapeshellarg($srcPath)
			. ' -c:v libx264 -preset fast -crf 23'
			. ' -c:a aac -map 0:v -map 0:a?'
			. ' -movflags +faststart '
			. escapeshellarg($outPath)
			. ' 2>/dev/null',
			$cmdOut,
			$exitCode
		);

		if ($exitCode !== 0 || !file_exists($outPath) || filesize($outPath) === 0) {
			$logger->warning('UDP transcode: ffmpeg failed', [
				'attach_id' => $attachId,
				'exit'      => $exitCode,
			]);
			self::cleanup($tmpDir);
			return;
		}

		$newData = file_get_contents($outPath);
		self::cleanup($tmpDir);

		if (empty($newData)) {
			return;
		}

		// Overwrite the stored blob in-place using the same backend reference
		try {
			$backendClass = DI::storageManager()->getWritableStorageByName($attach['backend-class'] ?? '');
			$backendClass->put($newData, $attach['backend-ref'] ?? '');
		} catch (\Throwable $e) {
			$logger->warning('UDP transcode: storage write failed', [
				'attach_id' => $attachId,
				'error'     => $e->getMessage(),
			]);
			return;
		}

		// Update attach metadata to reflect the transcoded file
		$origName = $attach['filename'] ?? 'video';
		$newName  = preg_replace('/\.[^.]+$/', '', $origName) . '.mp4';

		DBA::update('attach', [
			'filetype' => 'video/mp4',
			'filename' => $newName,
			'filesize' => strlen($newData),
			'edited'   => DateTimeFormat::utcNow(),
		], ['id' => $attachId]);

		$logger->info('UDP transcode: completed', [
			'attach_id'   => $attachId,
			'udp_media_id'=> $udpMediaId,
			'src_codec'   => $codec,
			'new_size'    => strlen($newData),
		]);
	}

	private static function cleanup(string $dir): void
	{
		foreach (glob($dir . '/*') as $f) {
			@unlink($f);
		}
		@rmdir($dir);
	}
}
