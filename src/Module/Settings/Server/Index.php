<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings\Server;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Friendica\App;
use Friendica\Content\Pager;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\User\Settings\Entity\UserGServer;
use Friendica\User\Settings\Repository;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Index extends BaseSettings
{
	/** @var Repository\UserGServer */
	private $repository;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var IManageConfigValues */
	private $config;

	public function __construct(SystemMessages $systemMessages, Repository\UserGServer $repository, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->repository     = $repository;
		$this->systemMessages = $systemMessages;
		$this->pConfig        = $pConfig;
		$this->config         = $config;
	}

	protected function post(array $request = [])
	{
		self::checkFormSecurityTokenRedirectOnError($this->args->getQueryString(), 'settings-server');

		if (!empty($request['udp_regenerate_join_token'])) {
			$uid       = $this->session->getLocalUserId();
			$old_token = $this->pConfig->get($uid, 'udp_join_req', 'token');
			if ($old_token) {
				$this->config->delete('udp_join_req', $old_token);
			}
			$this->pConfig->set($uid, 'udp_join_req', 'token', '');
			$this->pConfig->set($uid, 'udp_join_req', 'expires_at', 0);
			$this->baseUrl->redirect($this->args->getQueryString());
		}

		foreach ($request['delete'] ?? [] as $gsid => $delete) {
			if ($delete) {
				unset($request['ignored'][$gsid]);

				try {
					$userGServer = $this->repository->selectOneByUserAndServer($this->session->getLocalUserId(), $gsid, false);
					$this->repository->delete($userGServer);
				} catch (NotFoundException $e) {
					// Nothing to delete
				}
			}
		}

		foreach ($request['ignored'] ?? [] as $gsid => $ignored) {
			$userGServer = $this->repository->getOneByUserAndServer($this->session->getLocalUserId(), $gsid, false);
			if ($userGServer->ignored != $ignored) {
				$userGServer->toggleIgnored();
				$this->repository->save($userGServer);
			}
		}

		$this->systemMessages->addInfo($this->t('Settings saved'));

		$this->baseUrl->redirect($this->args->getQueryString());
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 30);

		$total = $this->repository->countByUser($this->session->getLocalUserId());

		$servers = $this->repository->selectByUserWithPagination($this->session->getLocalUserId(), $pager);

		$ignoredCheckboxes = array_map(function (UserGServer $server) {
			return ['ignored[' . $server->gsid . ']', '', $server->ignored];
		}, $servers->getArrayCopy());

		$deleteCheckboxes = array_map(function (UserGServer $server) {
			return ['delete[' . $server->gsid . ']'];
		}, $servers->getArrayCopy());

		$join_qr_svg = '';
		$join_url    = '';

		$uid  = $this->session->getLocalUserId();
		$user = User::getById($uid, ['username', 'email', 'nickname']);

		if ($user) {
			$token   = $this->pConfig->get($uid, 'udp_join_req', 'token');
			$expires = $this->pConfig->get($uid, 'udp_join_req', 'expires_at');

			if (!$token || !$expires || $expires < time()) {
				if ($token) {
					$this->config->delete('udp_join_req', $token);
				}
				$token   = bin2hex(random_bytes(24));
				$expires = time() + 86400 * 7;
				$this->pConfig->set($uid, 'udp_join_req', 'token', $token);
				$this->pConfig->set($uid, 'udp_join_req', 'expires_at', $expires);
				$this->config->set('udp_join_req', $token, json_encode([
					'uid'        => $uid,
					'name'       => $user['username'],
					'nick'       => $user['nickname'],
					'email'      => $user['email'],
					'node'       => $this->baseUrl->getHost(),
					'expires_at' => $expires,
				]));
			}

			$join_url = (string)$this->baseUrl . '/udp/join-request/' . $token;
			$renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
			$join_qr_svg = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', (new Writer($renderer))->writeString($join_url));
		}

		$tpl = Renderer::getMarkupTemplate('settings/server/index.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'         => $this->t('Remote server settings'),
				'desc1'         => $this->t('Here you can find all the remote servers you have taken individual moderation actions against. For a list of servers your node has blocked, please check out the <a href="friendica">Information</a> page.'),
				'desc2'         => $this->t('This includes ignored servers. You can ignore a server by clicking the "More" options button on a post, and selecting the option to "Ignore" the server the given post is from.'),
				'siteName'      => $this->t('Server Name'),
				'ignored'       => $this->t('Ignored'),
				'ignored_title' => $this->t("You won't see any content from this server including reshares in your Network page, the community pages and individual conversations."),
				'delete'        => $this->t('Delete'),
				'delete_title'  => $this->t('Delete all your settings for the remote server'),
				'submit'        => $this->t('Save changes'),
				'join_header'   => $this->t('Join another node'),
				'join_desc'     => $this->t('Share this QR code with the admin of another UDP Social node to request an invitation. The code is valid for 7 days.'),
			],

			'$count'      => $total,
			'$no_servers' => $this->t('You have not taken individual moderation actions against any servers.'),

			'$servers' => $servers,

			'$form_security_token' => self::getFormSecurityToken('settings-server'),

			'$ignoredCheckboxes' => $ignoredCheckboxes,
			'$deleteCheckboxes'  => $deleteCheckboxes,

			'$paginate'    => $pager->renderFull($total),
			'$join_qr_svg'     => $join_qr_svg,
			'$join_url'        => $join_url,
			'$join_expires_at' => $expires ?? 0,
		]);
	}
}
