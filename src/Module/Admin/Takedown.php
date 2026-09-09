<?php

// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\BaseAdmin;

/**
 * Admin UI for receiving and acting on content takedown requests (DMCA, GDPR erasure, etc.).
 *
 * Routes:
 *   GET  /admin/takedown           — list of all requests (open first, then closed)
 *   GET  /admin/takedown/new       — intake form for a new request
 *   POST /admin/takedown/new       — submit a new request
 *   GET  /admin/takedown/review/{id} — review a specific request and its claimed content
 *   POST /admin/takedown/review/{id} — act on the request (delete post, block account, dismiss)
 *
 * The optional UDP webhook (config key udp_takedown_webhook_url) is called on every status
 * change so the operator's Fleet UI can maintain a central log.  If the key is absent the
 * module works identically for a standalone Friendica instance.
 */
class Takedown extends BaseAdmin
{
	// ── Status constants ────────────────────────────────────────────────────

	const STATUS_OPEN      = 'open';
	const STATUS_ACTIONED  = 'actioned';
	const STATUS_DISMISSED = 'dismissed';

	// ── POST handlers ───────────────────────────────────────────────────────

	protected function post(array $request = []): void
	{
		parent::post();

		$action = $this->parameters['action'] ?? '';
		$id     = (int) ($this->parameters['id'] ?? 0);

		if ($action === 'new') {
			self::checkFormSecurityTokenRedirectOnError('/admin/takedown/new', 'admin_takedown_new');

			$complainant_name  = trim($request['complainant_name']  ?? '');
			$complainant_email = trim($request['complainant_email'] ?? '');
			$claimed_work      = trim($request['claimed_work']      ?? '');
			$claimed_url       = trim($request['claimed_url']       ?? '');
			$notes             = trim($request['notes']             ?? '');

			if (!$complainant_name || !$claimed_work || !$claimed_url) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Complainant name, claimed work, and content URL are required.'));
				DI::baseUrl()->redirect('admin/takedown/new');
			}

			$post_uri_id = self::resolveUriId($claimed_url);

			DI::dba()->insert('udp_takedown', [
				'status'            => self::STATUS_OPEN,
				'complainant_name'  => $complainant_name,
				'complainant_email' => $complainant_email,
				'claimed_work'      => $claimed_work,
				'claimed_url'       => $claimed_url,
				'post_uri_id'       => $post_uri_id,
				'notes'             => $notes,
				'received_at'       => date('Y-m-d H:i:s'),
			]);

			$new_id = DI::dba()->lastInsertId();
			self::notifyWebhook($new_id, self::STATUS_OPEN);

			DI::sysmsg()->addInfo(DI::l10n()->t('Takedown request #%d recorded. Review the content before taking action.', $new_id));
			DI::baseUrl()->redirect('admin/takedown/review/' . $new_id);
		}

		if ($action === 'review' && $id) {
			self::checkFormSecurityTokenRedirectOnError('/admin/takedown/review/' . $id, 'admin_takedown_review_' . $id);

			$row = DI::dba()->selectFirst('udp_takedown', [], ['id' => $id]);
			if (!$row) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Takedown request not found.'));
				DI::baseUrl()->redirect('admin/takedown');
			}

			$decision = $request['decision'] ?? '';
			$notes    = trim($request['notes'] ?? $row['notes']);

			if ($decision === 'delete_post') {
				if ($row['post_uri_id']) {
					// Delete by uri-id — affects all copies of this post on the node
					DI::dba()->update('item', ['deleted' => true, 'visible' => false, 'changed' => date('Y-m-d H:i:s')], ['uri-id' => $row['post_uri_id']]);
					Item::markForDeletion(['uri-id' => $row['post_uri_id']]);
				}
				$action_taken = 'post_deleted';
			} elseif ($decision === 'delete_post_block_account') {
				if ($row['post_uri_id']) {
					DI::dba()->update('item', ['deleted' => true, 'visible' => false, 'changed' => date('Y-m-d H:i:s')], ['uri-id' => $row['post_uri_id']]);
					Item::markForDeletion(['uri-id' => $row['post_uri_id']]);
				}
				// Block the account that owns the post
				$author_id = self::getAuthorContactId($row['post_uri_id']);
				if ($author_id) {
					DI::dba()->update('contact', ['blocked' => true], ['id' => $author_id]);
				}
				$action_taken = 'post_deleted_account_blocked';
			} elseif ($decision === 'dismiss') {
				$action_taken = 'dismissed_no_infringement';
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Please select an action before submitting.'));
				DI::baseUrl()->redirect('admin/takedown/review/' . $id);
			}

			$new_status = ($decision === 'dismiss') ? self::STATUS_DISMISSED : self::STATUS_ACTIONED;

			DI::dba()->update('udp_takedown', [
				'status'       => $new_status,
				'action_taken' => $action_taken,
				'notes'        => $notes,
				'reviewer_uid' => DI::userSession()->getLocalUserId(),
				'actioned_at'  => date('Y-m-d H:i:s'),
			], ['id' => $id]);

			self::notifyWebhook($id, $new_status, $action_taken);

			DI::sysmsg()->addInfo(DI::l10n()->t('Takedown request #%d has been resolved (%s).', $id, $action_taken));
			DI::baseUrl()->redirect('admin/takedown');
		}
	}

	// ── GET handlers ────────────────────────────────────────────────────────

	protected function content(array $request = []): string
	{
		parent::content();

		$action = $this->parameters['action'] ?? '';
		$id     = (int) ($this->parameters['id'] ?? 0);

		if ($action === 'new') {
			return $this->renderNew();
		}

		if ($action === 'review' && $id) {
			return $this->renderReview($id);
		}

		return $this->renderList();
	}

	// ── Views ────────────────────────────────────────────────────────────────

	private function renderList(): string
	{
		$open   = DI::dba()->selectToArray('udp_takedown', [], ['status' => self::STATUS_OPEN],   ['order' => ['received_at' => true]]);
		$closed = DI::dba()->selectToArray('udp_takedown', [], ['status' => [self::STATUS_ACTIONED, self::STATUS_DISMISSED]], ['order' => ['actioned_at' => true], 'limit' => 50]);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/takedown.tpl'), [
			'$title'    => DI::l10n()->t('Administration'),
			'$page'     => DI::l10n()->t('Takedown Requests'),
			'$view'     => 'list',
			'$open'     => $open,
			'$closed'   => $closed,
			'$baseurl'  => (string) DI::baseUrl(),
		]);
	}

	private function renderNew(): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/takedown.tpl'), [
			'$title'                => DI::l10n()->t('Administration'),
			'$page'                 => DI::l10n()->t('New Takedown Request'),
			'$view'                 => 'new',
			'$baseurl'              => (string) DI::baseUrl(),
			'$form_security_token'  => self::getFormSecurityToken('admin_takedown_new'),
		]);
	}

	private function renderReview(int $id): string
	{
		$row = DI::dba()->selectFirst('udp_takedown', [], ['id' => $id]);
		if (!$row) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Takedown request not found.'));
			DI::baseUrl()->redirect('admin/takedown');
		}

		// Attempt to load the post for inline preview
		$post_preview = null;
		if ($row['post_uri_id']) {
			$post = Post::selectFirst(['guid', 'body', 'author-name', 'author-link', 'created', 'plink', 'deleted'], ['uri-id' => $row['post_uri_id']]);
			if ($post && !$post['deleted']) {
				$post_preview = [
					'guid'        => $post['guid'],
					'author_name' => $post['author-name'],
					'author_link' => $post['author-link'],
					'created'     => $post['created'],
					'plink'       => $post['plink'],
					'body'        => BBCode::toPlaintext($post['body'] ?? ''),
					'found'       => true,
				];
			} else {
				$post_preview = ['found' => false, 'deleted' => !empty($post['deleted'])];
			}
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/takedown.tpl'), [
			'$title'                => DI::l10n()->t('Administration'),
			'$page'                 => DI::l10n()->t('Review Takedown Request #%d', $id),
			'$view'                 => 'review',
			'$row'                  => $row,
			'$post_preview'         => $post_preview,
			'$baseurl'              => (string) DI::baseUrl(),
			'$form_security_token'  => self::getFormSecurityToken('admin_takedown_review_' . $id),
		]);
	}

	// ── Helpers ──────────────────────────────────────────────────────────────

	/**
	 * Attempt to resolve a URL or GUID string to a uri-id in the local post store.
	 * Returns null if the post cannot be found.
	 */
	private static function resolveUriId(string $url): ?int
	{
		// Try exact URI match first
		$row = DI::dba()->selectFirst('item-uri', ['id'], ['uri' => $url]);
		if ($row) {
			return (int) $row['id'];
		}

		// Try GUID (last path segment)
		$guid = basename(parse_url($url, PHP_URL_PATH) ?? '');
		if ($guid) {
			$post = Post::selectFirst(['uri-id'], ['guid' => $guid]);
			if ($post) {
				return (int) $post['uri-id'];
			}
		}

		return null;
	}

	private static function getAuthorContactId(?int $uri_id): ?int
	{
		if (!$uri_id) {
			return null;
		}
		$post = Post::selectFirst(['author-id'], ['uri-id' => $uri_id]);
		return $post ? (int) $post['author-id'] : null;
	}

	/**
	 * POST a status-change record to the operator's central Fleet UI webhook, if configured.
	 * Silently skips if udp_takedown_webhook_url is not set — safe for standalone instances.
	 */
	private static function notifyWebhook(int $id, string $status, string $action_taken = ''): void
	{
		$webhook_url = DI::config()->get('system', 'udp_takedown_webhook_url');
		if (!$webhook_url) {
			return;
		}

		$row = DI::dba()->selectFirst('udp_takedown', [], ['id' => $id]);
		if (!$row) {
			return;
		}

		$payload = json_encode([
			'id'                => $id,
			'slot_domain'       => DI::baseUrl()->getHost(),
			'status'            => $status,
			'action_taken'      => $action_taken,
			'complainant_name'  => $row['complainant_name'],
			'complainant_email' => $row['complainant_email'],
			'claimed_work'      => $row['claimed_work'],
			'claimed_url'       => $row['claimed_url'],
			'received_at'       => $row['received_at'],
			'actioned_at'       => $row['actioned_at'],
			'timestamp'         => date('c'),
		]);

		try {
			DI::httpClient()->post(
				$webhook_url,
				$payload,
				['Content-Type' => 'application/json'],
				10
			);
		} catch (\Throwable $e) {
			DI::logger()->notice('UDP takedown webhook failed', ['id' => $id, 'error' => $e->getMessage()]);
		}
	}
}
