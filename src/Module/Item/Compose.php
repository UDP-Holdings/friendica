<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use DateTime;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\ACL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\Theme;
use Friendica\Database\DBA;
use Friendica\Event\HtmlFilterEvent;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\UdpGroupCircle;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\NotImplementedException;
use Friendica\Util\ACLFormatter;
use Friendica\Util\Crypto;
use Friendica\Util\Profiler;
use Friendica\Util\Temporal;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class Compose extends BaseModule
{
	/** @var SystemMessages */
	private $systemMessages;

	/** @var ACLFormatter */
	private $ACLFormatter;

	/** @var Page */
	private $page;

	/** @var IManagePersonalConfigValues */
	private $pConfig;

	/** @var IManageConfigValues */
	private $config;

	/** @var UserSession */
	private $session;

	/** @var AppHelper */
	private $appHelper;

	private EventDispatcherInterface $eventDispatcher;

	public function __construct(EventDispatcherInterface $eventDispatcher, AppHelper $appHelper, UserSession $session, IManageConfigValues $config, IManagePersonalConfigValues $pConfig, Page $page, ACLFormatter $ACLFormatter, SystemMessages $systemMessages, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->systemMessages  = $systemMessages;
		$this->ACLFormatter    = $ACLFormatter;
		$this->page            = $page;
		$this->pConfig         = $pConfig;
		$this->config          = $config;
		$this->session         = $session;
		$this->appHelper       = $appHelper;
		$this->eventDispatcher = $eventDispatcher;
	}

	protected function post(array $request = [])
	{
		if (!empty($request['body'])) {
			$uid           = DI::userSession()->getLocalUserId();
			$groupCircleId = (int)($request['group_circle_id'] ?? 0);

			// !! (bang-bang) syntax: resolve !!nick to a Group Circle from any compose surface.
			// Takes precedence if group_circle_id is not already set.
			if (!$groupCircleId && preg_match('/!!(\w+)/', $request['body'], $m)) {
				$resolved = UdpGroupCircle::findByActorNick($m[1], $uid);
				if ($resolved) {
					$groupCircleId = (int)$resolved['id'];
				} else {
					$this->systemMessages->addNotice($this->l10n->t(
						'!!%s did not match any of your circles — post was not sent to a circle. Circle names cannot contain spaces.',
						$m[1]
					));
				}
			}

			if ($groupCircleId) {
				UdpGroupCircle::setPendingCircleId($groupCircleId);

				// Force private ACL: restrict post to the group actor so it never leaks
				// to the poster's public followers. localFanOut() handles member delivery.
				$circle = UdpGroupCircle::getById($groupCircleId);
				if ($circle) {
					$actorSelf = Contact::selectFirst(['url'], ['uid' => $circle['actor-uid'], 'self' => true]);
					if (DBA::isResult($actorSelf)) {
						$actorCid = Contact::getIdForURL($actorSelf['url'], $uid, false);
						if ($actorCid) {
							$_REQUEST['contact_allow'] = (string)$actorCid;
							$_REQUEST['contact_deny']  = '';
							$_REQUEST['circle_allow']  = '';
							$_REQUEST['circle_deny']   = '';
							$_REQUEST['visibility']    = 'custom';
						}
					}
				}
			}
			// Strip !! routing directive from body — it's a send-time signal, not display content.
			$_REQUEST['body'] = trim(preg_replace('/\s*!!(\w+)\s*/u', ' ', $request['body']));

			$_REQUEST['return'] = 'network';
			require_once 'mod/item.php';
			item_post();
		} else {
			$this->systemMessages->addNotice($this->l10n->t('Please enter a post body.'));
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form('compose');
		}

		if (!Theme::isDescendantOf($this->appHelper->getCurrentTheme(), 'frio')) {
			throw new NotImplementedException($this->l10n->t('This feature is only available with the frio theme.'));
		}

		$posttype = $this->parameters['type'] ?? Item::PT_ARTICLE;
		if (!in_array($posttype, [Item::PT_ARTICLE, Item::PT_PERSONAL_NOTE])) {
			switch ($posttype) {
				case 'note':
					$posttype = Item::PT_PERSONAL_NOTE;
					break;
				default:
					$posttype = Item::PT_ARTICLE;
					break;
			}
		}

		$user = User::getById($this->session->getLocalUserId(), ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'default-location']);

		// UDP: neighborhood (public) is the platform default. Ignore the user's stored
		// account-level ACL — Friendica creates a "Friends" circle and sets it as the
		// default allow_gid for new accounts, which would silently make all posts private.
		$contact_allow_list = [];
		$circle_allow_list  = [];
		$contact_deny_list  = [];
		$circle_deny_list   = [];

		switch ($posttype) {
			case Item::PT_PERSONAL_NOTE:
				$compose_title      = $this->l10n->t('Compose new personal note');
				$type               = 'note';
				$doesFederate       = false;
				$contact_allow_list = [$this->appHelper->getContactId()];
				$circle_allow_list  = [];
				$contact_deny_list  = [];
				$circle_deny_list   = [];
				break;
			default:
				$compose_title = $this->l10n->t('Compose new post');
				$type          = 'post';
				$doesFederate  = true;

				$contact_allow = $request['contact_allow'] ?? '';
				$circle_allow  = $request['circle_allow']  ?? '';
				$contact_deny  = $request['contact_deny']  ?? '';
				$circle_deny   = $request['circle_deny']   ?? '';

				if ($contact_allow
					. $circle_allow
					. $contact_deny
					. $circle_deny) {
					$contact_allow_list = $contact_allow ? explode(',', $contact_allow) : [];
					$circle_allow_list  = $circle_allow  ? explode(',', $circle_allow)  : [];
					$contact_deny_list  = $contact_deny  ? explode(',', $contact_deny)  : [];
					$circle_deny_list   = $circle_deny   ? explode(',', $circle_deny)   : [];
				}

				break;
		}

		// UDP Group Circle: read group_circle_id from GET (set by group page link or JS @mention)
		$groupCircleId   = (int)($request['group_circle_id'] ?? 0);
		$groupCircleName = '';
		if ($groupCircleId) {
			$circle = UdpGroupCircle::getById($groupCircleId);
			if ($circle) {
				$selfContact = Contact::selectFirst(['id'], ['uid' => $this->session->getLocalUserId(), 'self' => true]);
				if (!$selfContact || !UdpGroupCircle::isMember($groupCircleId, $selfContact['id'])) {
					$groupCircleId = 0; // viewer is not a member
				} else {
					$groupCircleName    = $circle['name'];
					$contact_allow_list = []; // clear URL-based ACL; fan-out handles delivery
					$circle_allow_list  = [];
				}
			} else {
				$groupCircleId = 0;
			}
		}
		$groupCircleActorsJson = json_encode(UdpGroupCircle::getAllActors());

		$title     = $request['title']     ?? '';
		$summary   = $request['summary']   ?? '';
		$sensitive = $request['sensitive'] ?? false;
		$category  = $request['category']  ?? '';
		$body      = $request['body']      ?? '';
		$location  = $request['location']  ?? $user['default-location'];
		$wall      = $request['wall']      ?? $type == 'post';

		$jotplugins = $this->eventDispatcher->dispatch(
			new HtmlFilterEvent(HtmlFilterEvent::JOT_TOOL, ''),
		)->getHtml();

		// Output
		$this->page->registerFooterScript(Theme::getPathForFile('js/ajaxupload.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/linkPreview.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/compose.js'));

		$contact = Contact::getById($this->appHelper->getContactId());

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'set_creation_date')) {
			$created_at = Temporal::getDateTimeField(
				new DateTime(DBA::NULL_DATETIME),
				new DateTime('now'),
				null,
				$this->l10n->t('Created at'),
				'created_at',
			);
		} else {
			$created_at = '';
		}

		$tpl = Renderer::getMarkupTemplate('item/compose.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'compose_title'        => $compose_title,
				'default'              => '',
				'summary'              => $this->l10n->t('Summary'),
				'visibility_title'     => $this->l10n->t('Visibility'),
				'mytitle'              => $this->l10n->t('This is you'),
				'submit'               => $this->l10n->t('Post'),
				'edbold'               => $this->l10n->t('Bold'),
				'editalic'             => $this->l10n->t('Italic'),
				'eduline'              => $this->l10n->t('Underline'),
				'edquote'              => $this->l10n->t('Quote'),
				'edemojis'             => $this->l10n->t('Add emojis'),
				'contentwarn'          => $this->l10n->t('Content Warning'),
				'edcode'               => $this->l10n->t('Code'),
				'edimg'                => $this->l10n->t('Image'),
				'edurl'                => $this->l10n->t('Link'),
				'edattach'             => $this->l10n->t('Link or Media'),
				'uploadmedia'          => $this->l10n->t('Upload media'),
				'prompttext'           => $this->l10n->t('Please enter a image/video/audio/webpage URL:'),
				'preview'              => $this->l10n->t('Preview'),
				'preview_placeholder'  => $this->l10n->t('Your post preview will appear here as you type.'),
				'location_set'         => $this->l10n->t('Set your location'),
				'location_clear'       => $this->l10n->t('Clear the location'),
				'location_unavailable' => $this->l10n->t('Location services are unavailable on your device'),
				'location_disabled'    => $this->l10n->t('Location services are disabled. Please check the website\'s permissions on your device'),
				'wait'                 => $this->l10n->t('Please wait'),
				'placeholdertitle'     => $this->l10n->t('Set title'),
				'placeholdersummary'   => Feature::isEnabled($this->session->getLocalUserId(), Feature::SUMMARY) ? $this->l10n->t('Set summary, abstract or spoiler text') : '',
				'placeholdercategory'  => Feature::isEnabled($this->session->getLocalUserId(), Feature::CATEGORIES) ? $this->l10n->t('Categories (comma-separated list)') : '',
				'always_open_compose'  => $this->pConfig->get(
					$this->session->getLocalUserId(),
					'frio',
					'always_open_compose',
					$this->config->get('frio', 'always_open_compose', false),
				) ? ''
						: $this->l10n->t('If you want to always use this editor for posting, you can configure the New Post button to always open it in your <a href="/settings/display">Theme settings</a>.'),
			],

			'$id'           => 0,
			'$posttype'     => $posttype,
			'$type'         => $type,
			'$wall'         => $wall,
			'$mylink'       => $this->baseUrl->remove($contact['url']),
			'$myphoto'      => $this->baseUrl->remove($contact['thumb']),
			'$sensitive'    => ['sensitive', $this->l10n->t('Sensitive post'), $request['sensitive'] ?? false],
			'$scheduled_at' => Temporal::getDateTimeField(
				new DateTime(),
				new DateTime('now + 6 months'),
				null,
				$this->l10n->t('Scheduled at'),
				'scheduled_at',
			),
			'$created_at' => $created_at,
			'$title'      => $title,
			'$summary'    => $summary,
			'sensitive'   => $sensitive,
			'$category'   => $category,
			'$body'       => $body,
			'$location'   => $location,

			'$contact_allow' => implode(',', $contact_allow_list),
			'$circle_allow'  => implode(',', $circle_allow_list),
			'$contact_deny'  => implode(',', $contact_deny_list),
			'$circle_deny'   => implode(',', $circle_deny_list),

			'$group_circle_id'          => $groupCircleId,
			'$group_circle_name'        => $groupCircleName,
			'$group_circle_actors_json' => $groupCircleActorsJson,

			'$jotplugins'   => $jotplugins,
			'$rand_num'     => Crypto::randomDigits(12),
			'$acl_selector' => ACL::getFullSelectorHTML($this->page, $this->session->getLocalUserId(), $doesFederate, [
				'allow_cid' => $contact_allow_list,
				'allow_gid' => $circle_allow_list,
				'deny_cid'  => $contact_deny_list,
				'deny_gid'  => $circle_deny_list,
			]),
		]);
	}
}
