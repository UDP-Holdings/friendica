<?php

// UDP Social — media manager page (Cat2 — new file)

namespace Friendica\Module\Udp\Media;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Model\UserSession;
use Friendica\DI;
use Friendica\Model\UdpMedia;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Manager extends BaseModule
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

	protected function content(array $request = []): string
	{
		$uid = $this->userSession->getLocalUserId();
		if (!$uid) {
			DI::baseUrl()->redirect('login');
		}

		$albums = UdpMedia::getAlbums($uid);

		$tpl = Renderer::getMarkupTemplate('udp/media_manager.tpl');
		return Renderer::replaceMacros($tpl, [
			'$albums'        => $albums,
			'$list_api_url'  => (string) DI::baseUrl() . '/udp/media/list',
			'$album_api_url' => (string) DI::baseUrl() . '/udp/media/album',
		]);
	}
}
