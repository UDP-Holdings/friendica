<?php

// UDP Social — profile media manager (Cat2 — new file)

namespace Friendica\Module\Profile;

use Friendica\App;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\UdpMedia as UdpMediaModel;
use Friendica\Module\BaseProfile;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class UdpMedia extends BaseProfile
{
	private IHandleUserSessions $session;
	private IManageConfigValues $config;
	private array $owner;

	public function __construct(
		AppHelper $appHelper,
		IManageConfigValues $config,
		IHandleUserSessions $session,
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

		$this->session = $session;
		$this->config  = $config;

		$owner = ProfileModel::load($appHelper, $this->parameters['nickname'] ?? '', false);
		if (!$owner || $owner['account_removed'] || $owner['account_expired']) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		$this->owner = $owner;
	}

	protected function content(array $request = []): string
	{
		parent::content($request);

		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\ForbiddenException($this->t('Public access denied.'));
		}

		if (($this->owner['hidewall'] || $this->config->get('udp', 'gateway_enabled', true)) && !$this->session->isAuthenticated()) {
			$this->baseUrl->redirect('profile/' . $this->owner['nickname'] . '/restricted');
		}

		$uid      = $this->owner['uid'];
		$is_owner = $this->session->getLocalUserId() == $uid;

		if (!$is_owner) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		// Suppress the vcard aside — media manager uses full width
		DI::page()['aside'] = '';

		$albums = UdpMediaModel::getAlbums($uid);

		$o  = self::getTabsHTML('media', true, $this->owner['nickname'], ProfileModel::getByUID($uid)['hide-friends'] ?? false);
		$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/media_manager.tpl'), [
			'$albums'        => $albums,
			'$list_api_url'  => (string) $this->baseUrl . '/udp/media/list',
			'$album_api_url' => (string) $this->baseUrl . '/udp/media/album',
		]);

		return $o;
	}
}
