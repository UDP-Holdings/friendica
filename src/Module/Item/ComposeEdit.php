<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

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
use Friendica\Event\HtmlFilterEvent;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\UdpGroupCircle;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\NotImplementedException;
use Friendica\Util\ACLFormatter;
use Friendica\Util\Crypto;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Edit an existing post using the compose UI.
 */
class ComposeEdit extends BaseModule
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

		$this->systemMessages = $systemMessages;
		$this->ACLFormatter   = $ACLFormatter;
		$this->page           = $page;
		$this->pConfig        = $pConfig;
		$this->config         = $config;
		$this->session        = $session;
		$this->appHelper      = $appHelper;
		$this->eventDispatcher = $eventDispatcher;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form('compose/edit/' . ($this->parameters['post_id'] ?? 0));
		}

		if (!Theme::isDescendantOf($this->appHelper->getCurrentTheme(), 'frio')) {
			throw new NotImplementedException($this->l10n->t('This feature is only available with the frio theme.'));
		}

		$postId = (int)($this->parameters['post_id'] ?? 0);
		if (!$postId) {
			throw new HTTPException\BadRequestException($this->l10n->t('Post not found.'));
		}

		$fields = [
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'gravity', 'sensitive',
			'body', 'title', 'content-warning', 'uri-id', 'wall', 'post-type', 'guid',
		];

		$item = Post::selectFirstForUser($this->session->getLocalUserId(), $fields, [
			'id'  => $postId,
			'uid' => $this->session->getLocalUserId(),
		]);

		if (empty($item)) {
			throw new HTTPException\NotFoundException($this->l10n->t('Post not found.'));
		}

		$item['body'] = Post\Media::addAttachmentsToBody($item['uri-id'], $item['body']);
		$item         = Post\Media::addHTMLAttachmentToItem($item);

		$body     = $this->undoPostTagging($item['body']);
		$title    = $item['title'];
		$summary  = $item['content-warning'];
		$category = Post\Category::getCSVByURIId($item['uri-id'], $this->session->getLocalUserId(), Post\Category::CATEGORY);

		$contact_allow_list = $this->ACLFormatter->expand($item['allow_cid']);
		$circle_allow_list  = $this->ACLFormatter->expand($item['allow_gid']);
		$contact_deny_list  = $this->ACLFormatter->expand($item['deny_cid']);
		$circle_deny_list   = $this->ACLFormatter->expand($item['deny_gid']);

		$jotplugins = $this->eventDispatcher->dispatch(
			new HtmlFilterEvent(HtmlFilterEvent::JOT_TOOL, ''),
		)->getHtml();

		$this->page->registerFooterScript(Theme::getPathForFile('js/ajaxupload.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/linkPreview.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/compose.js'));

		// Prevent compose.js from restoring a new-post draft over the pre-filled edit body.
		$this->page['htmlhead'] .= '<script>try{sessionStorage.removeItem("compose_draft");sessionStorage.removeItem("compose_post_submitted");}catch(e){}</script>';

		$contact = Contact::getById($this->appHelper->getContactId());

		$groupCircleActorsJson = json_encode(UdpGroupCircle::getAllActors());

		$tpl = Renderer::getMarkupTemplate('item/compose.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'compose_title'        => $this->l10n->t('Edit post'),
				'default'              => '',
				'summary'              => $this->l10n->t('Summary'),
				'visibility_title'     => $this->l10n->t('Visibility'),
				'mytitle'              => $this->l10n->t('This is you'),
				'submit'               => $this->l10n->t('Save'),
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
				'always_open_compose'  => '',
			],

			'$id'          => $postId,
			'$post_id'     => $postId,
			'$form_action' => 'item',
			'$posttype'    => $item['post-type'],
			'$type'        => 'post',
			'$wall'        => $item['wall'],
			'$mylink'      => $this->baseUrl->remove($contact['url']),
			'$myphoto'     => $this->baseUrl->remove($contact['thumb']),
			'$sensitive'   => ['sensitive', $this->l10n->t('Sensitive post'), !empty($item['sensitive'])],
			'$scheduled_at' => '',
			'$created_at'   => '',
			'$title'        => $title,
			'$summary'      => $summary,
			'$category'     => $category,
			'$body'         => $body,
			'$location'     => '',

			'$contact_allow' => implode(',', $contact_allow_list),
			'$circle_allow'  => implode(',', $circle_allow_list),
			'$contact_deny'  => implode(',', $contact_deny_list),
			'$circle_deny'   => implode(',', $circle_deny_list),

			'$group_circle_id'          => 0,
			'$group_circle_name'        => '',
			'$group_circle_actors_json' => $groupCircleActorsJson,
			'$keep_original_default'    => false,

			'$jotplugins'   => $jotplugins,
			'$rand_num'     => Crypto::randomDigits(12),
			'$acl_selector' => ACL::getFullSelectorHTML($this->page, $this->session->getLocalUserId(), true, [
				'allow_cid' => $contact_allow_list,
				'allow_gid' => $circle_allow_list,
				'deny_cid'  => $contact_deny_list,
				'deny_gid'  => $circle_deny_list,
			]),
		]);
	}

	private function undoPostTagging(string $body): string
	{
		$matches = null;
		$content = preg_match_all('/([!#@])\[url=(.*?)\](.*?)\[\/url\]/ism', $body, $matches, PREG_SET_ORDER);
		if ($content) {
			foreach ($matches as $match) {
				if (in_array($match[1], ['!', '@'])) {
					$contact  = Contact::getByURL($match[2], false, ['addr']);
					$match[3] = empty($contact['addr']) ? $match[2] : $contact['addr'];
				}
				$body = str_replace($match[0], $match[1] . $match[3], $body);
			}
		}
		return $body;
	}
}
