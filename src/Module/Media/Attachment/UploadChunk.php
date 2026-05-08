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
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Attach;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class UploadChunk extends BaseModule
{
	private IHandleUserSessions $userSession;
	private IManageConfigValues $config;

	public function __construct(
		IHandleUserSessions $userSession,
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
			$this->jsonReturn(401, ['error' => 'Not authenticated.']);
		}

		if (empty($_FILES['userfile'])) {
			$this->jsonReturn(400, ['error' => 'No file chunk received.']);
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
			$this->jsonReturn(400, ['error' => 'Invalid upload session ID.']);
		}

		$uploadDir = sys_get_temp_dir() . '/udp_upload_' . $safeUuid;
		if (!is_dir($uploadDir) && !mkdir($uploadDir, 0700, true)) {
			$this->jsonReturn(500, ['error' => 'Could not create temporary upload directory.']);
		}

		// Write chunk to disk; zero-pad index so glob() sorts numerically
		$chunkPath = $uploadDir . '/chunk_' . sprintf('%06d', $chunkIndex);
		if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $chunkPath)) {
			$this->jsonReturn(500, ['error' => 'Failed to save chunk.']);
		}

		// Not the last chunk — acknowledge and wait for the rest
		if ($chunkIndex < $totalChunks - 1) {
			$this->jsonReturn(200, ['ok' => true, 'partial' => true]);
		}

		// Last chunk received — assemble into a single temp file
		$assembledPath = $uploadDir . '/assembled';
		foreach (range(0, $totalChunks - 1) as $i) {
			$part = $uploadDir . '/chunk_' . sprintf('%06d', $i);
			if (!file_exists($part)) {
				$this->jsonReturn(500, ['error' => 'Missing chunk ' . $i . ' during assembly.']);
			}
			// Append each chunk; each is at most 50 MB so this is memory-safe
			file_put_contents($assembledPath, file_get_contents($part), FILE_APPEND);
		}

		// Store via existing Attach pipeline (reads assembled file into memory once)
		$newId = Attach::storeFile($assembledPath, $owner['uid'], $fileName, $mimeType, '<' . $owner['id'] . '>');

		// Clean up temp directory regardless of outcome
		foreach (glob($uploadDir . '/*') as $f) {
			@unlink($f);
		}
		@rmdir($uploadDir);

		if ($newId === false) {
			$this->jsonReturn(500, ['error' => 'File storage failed.']);
		}

		$this->jsonReturn(200, ['ok' => true, 'id' => $newId]);
	}

	private function jsonReturn(int $httpCode, array $payload): void
	{
		if ($httpCode >= 400) {
			$this->response->setStatus($httpCode);
		}
		$this->response->setType(Response::TYPE_JSON, 'application/json');
		$this->response->addContent(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		System::echoResponse($this->response->generate());
		System::exit();
	}
}
