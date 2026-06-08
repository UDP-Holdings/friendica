<?php

// UDP Social customization — cross-node directory merging local + paired nodes
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\Contact as ContactModule;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPException;

/**
 * Replaces /directory with a cross-node view: local users + users from every
 * paired node (system.allowed_sites). No global Friendica directory link.
 *
 * Remote contacts already known locally get their full local contact record;
 * new ones get a minimal display entry with a Follow link.
 *
 * New file = zero upstream merge conflict risk.
 */
class Directory extends BaseModule
{
	protected function content(array $request = []): string
	{
		if (!DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Public access denied.'));
		}

		Nav::setSelected('directory');

		DI::page()['aside'] .= Widget::findPeople();
		DI::page()['aside'] .= Widget::follow();

		$search = trim(rawurldecode($request['search'] ?? ''));
		$uid    = DI::userSession()->getLocalUserId();
		$pager  = new Pager(DI::l10n(), DI::args()->getQueryString(), 60);

		// ── Local users ───────────────────────────────────────────────────
		$profiles  = Model\Profile::searchProfiles($pager->getStart(), $pager->getItemsPerPage(), $search ?: null);
		$entries   = [];
		$seenUrls  = [];

		foreach ($profiles['entries'] as $entry) {
			$contact = Model\Contact::getByURLForUser($entry['url'], $uid);
			if (!empty($contact)) {
				$entries[$entry['url']] = ContactModule::getContactTemplateVars($contact);
				$seenUrls[$entry['url']] = true;
			}
		}

		// ── Paired nodes ─────────────────────────────────────────────────
		foreach ($this->getPairedDomains() as $domain) {
			foreach ($this->fetchRemoteDirectory($domain, $search) as $user) {
				$url = $user['url'] ?? '';
				if (!$url || isset($seenUrls[$url])) {
					continue;
				}
				$seenUrls[$url] = true;

				// Use the local contact record if we already know this person
				$contact = Model\Contact::getByURLForUser($url, $uid);
				$entries[$url] = !empty($contact)
					? ContactModule::getContactTemplateVars($contact)
					: $this->makeRemoteContactVars($user, $domain);
			}
		}

		// Sort merged list by display name, case-insensitive
		usort($entries, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));

		$total = count($entries);
		if ($total === 0) {
			DI::sysmsg()->addNotice(DI::l10n()->t('No entries found.'));
		}

		$tpl = Renderer::getMarkupTemplate('directory_header.tpl');
		return Renderer::replaceMacros($tpl, [
			'$search'     => $search,
			'$globaldir'  => '',
			'$gDirPath'   => '',
			'$desc'       => DI::l10n()->t('Find people on this network'),
			'$contacts'   => array_values($entries),
			'$finding'    => DI::l10n()->t('Results for:'),
			'$findterm'   => $search,
			'$title'      => DI::l10n()->t('Network Directory'),
			'$search_mod' => 'directory',
			'$submit'     => DI::l10n()->t('Find'),
			'$paginate'   => $pager->renderFull($total),
		]);
	}

	private function getPairedDomains(): array
	{
		$allowed = DI::config()->get('system', 'allowed_sites') ?? '';
		return array_filter(array_map('trim', explode(',', $allowed)));
	}

	private function fetchRemoteDirectory(string $domain, string $search): array
	{
		$secret = DI::config()->get('udp_shared_secret', $domain) ?? '';
		if (!$secret) {
			// Not yet paired with a shared secret — skip silently (re-pair required)
			return [];
		}

		$myHost = DI::baseUrl()->getHost();
		$ts     = (string) time();
		$sig    = hash_hmac('sha256', $myHost . '|' . $ts, $secret);

		$url = 'https://' . $domain . '/udp/directory';
		if ($search !== '') {
			$url .= '?search=' . urlencode($search);
		}

		try {
			$result = DI::httpClient()->get($url, HttpClientAccept::JSON, [
				HttpClientOptions::HEADERS => [
					'X-UDP-Node' => $myHost,
					'X-UDP-Ts'   => $ts,
					'X-UDP-Sig'  => $sig,
				],
			]);
		} catch (\Throwable $e) {
			return [];
		}

		if (!$result->isSuccess()) {
			return [];
		}

		$data = json_decode($result->getBodyString(), true);
		return is_array($data) ? $data : [];
	}

	private function makeRemoteContactVars(array $user, string $domain): array
	{
		$url       = $user['url'] ?? '';
		$handle    = ($user['nickname'] ?? '') . '@' . $domain;
		$followUrl = (string) DI::baseUrl() . '/contact/follow?url=' . urlencode($url);

		return [
			'id'           => abs(crc32($url)),
			'url'          => $url,
			'itemurl'      => $handle,
			'thumb'        => $user['photo'] ?? '',
			'sparkle'      => '',
			'name'         => $user['name'] ?: $handle,
			'alt_text'     => $domain,
			'tags'         => $user['pub_keywords'] ?? '',
			'details'      => '',
			'network'      => '',
			'account_type' => '',
			'photo_menu'   => [
				'follow' => ['Follow', $followUrl],
			],
		];
	}
}
