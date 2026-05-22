<?php

// UDP Social — album assignment endpoint (Cat2 — new file)
// POST { ids: [1,2,3], album: "Vacation" } to move media items to an album.

namespace Friendica\Module\Udp\Media;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Model\UdpMedia;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class AlbumUpdate extends BaseModule
{
	private UserSession $userSession;

	public function __construct(
		UserSession $userSession,
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
	}

	protected function post(array $request = [])
	{
		$uid = $this->userSession->getLocalUserId();
		if (!$uid) {
			$this->jsonError(401, ['error' => 'Not authenticated.']);
		}

		$rawIds = $request['ids'] ?? [];
		if (is_string($rawIds)) {
			$rawIds = json_decode($rawIds, true) ?? [];
		}
		$ids   = array_map('intval', (array) $rawIds);
		$album = trim((string) ($request['album'] ?? ''));

		if (empty($ids)) {
			$this->jsonError(400, ['error' => 'No ids provided.']);
		}

		$ok = UdpMedia::setAlbum($ids, $uid, $album);
		$this->jsonExit(['ok' => $ok]);
	}
}
