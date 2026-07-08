<?php

// UDP Social — context-aware help page (Cat2 — new file)

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\User;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class UdpHelp extends BaseModule
{
	private IHandleUserSessions $session;
	private IManageConfigValues $config;

	public function __construct(
		IHandleUserSessions $session,
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
		$this->session = $session;
		$this->config  = $config;
	}

	protected function content(array $request = []): string
	{
		if ($this->session->isSiteAdmin()) {
			return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/help.tpl'), [
				'$is_admin' => true,
			]);
		}

		// Regular user — resolve the first registered admin (lowest uid wins)
		$admins = User::getAdminList(['nickname', 'username', 'email']);
		$admin  = $admins[0] ?? null;

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/help.tpl'), [
			'$is_admin'         => false,
			'$admin_name'       => $admin['username']  ?? 'your admin',
			'$admin_email'      => $admin['email']      ?? '',
			'$admin_profile'    => $admin['nickname']   ? ((string) $this->baseUrl . '/profile/' . $admin['nickname']) : '',
		]);
	}
}
