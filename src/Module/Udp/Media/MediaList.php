<?php

// UDP Social — media list JSON API (Cat2 — new file)
// Powers the compose media drawer and the /udp/media/manager page.

namespace Friendica\Module\Udp\Media;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Model\UdpMedia;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class MediaList extends BaseModule
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

	protected function rawContent(array $request = [])
	{
		$uid = $this->userSession->getLocalUserId();
		if (!$uid) {
			$this->jsonError(401, ['error' => 'Not authenticated.']);
		}

		// ?album=  — filter to a specific album; omit for all media
		$album  = isset($request['album']) ? (string) $request['album'] : null;
		$limit  = min((int) ($request['limit']  ?? 200), 500);
		$offset = max((int) ($request['offset'] ?? 0), 0);

		$rows   = UdpMedia::listForUser($uid, $album, $limit, $offset);
		$groups = UdpMedia::groupByDate($rows);
		$albums = UdpMedia::getAlbums($uid);

		$this->jsonExit([
			'ok'     => true,
			'albums' => $albums,
			'groups' => $groups,
		]);
	}
}
