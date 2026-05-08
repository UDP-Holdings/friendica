<?php

// UDP Social customization — unified feed for private family/friends instances
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Conversation;

use Friendica\Content\Nav;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;

/**
 * UDP Social unified feed — merges the followed-accounts (/network) feed with
 * locally-originated public and unlisted posts so everyone on a private family
 * instance sees everything in one stream.
 *
 * Block/ignore lists are respected: a blocked contact's posts are excluded even
 * if they posted publicly on this server.
 *
 * New file = zero upstream merge conflict risk (no core files patched).
 */
class UdpFeed extends Network
{
	protected function content(array $request = []): string
	{
		$o = parent::content($request);

		// parent::content() calls Nav::setSelected('feed'); override to highlight Network nav item
		Nav::setSelected('network');

		$toggle = '<nav class="widget"><ul>'
			. '<li class="selected"><a href="/timeline">Unified Feed</a></li>'
			. '<li><a href="/network">Following Only</a></li>'
			. '</ul></nav>';

		$this->page['aside'] = $toggle . $this->page['aside'];

		return $o;
	}

	protected function getItems(): array
	{
		$uid = $this->session->getLocalUserId();

		// Fetch 2× network items to leave room after deduplication against community items
		$savedLimit         = $this->itemsPerPage;
		$this->itemsPerPage = $savedLimit * 2;
		$networkItems       = parent::getItems();
		$this->itemsPerPage = $savedLimit;

		// Index by uri-id; network items take precedence (they carry the full row from network-thread-view)
		$merged = [];
		foreach ($networkItems as $item) {
			$merged[$item['uri-id']] = $item;
		}

		// Local-origin posts: PUBLIC (private=0) and UNLISTED (private=2)
		// TODO: circle back to map all database-side `private` values and `post-reason` codes to their UI labels
		//   private=0 (Item::PUBLIC)    → visible to everyone, appears on public timelines
		//   private=2 (Item::UNLISTED)  → Mastodon "unlisted"; federated but not on public timelines
		//   private=1 (Item::PRIVATE)   → followers-only; intentionally excluded here
		$condition = ["`wall` AND `origin` AND `private` IN (?, ?)", Item::PUBLIC, Item::UNLISTED];

		// Respect per-user block and ignore lists so blocked contacts' posts don't sneak in via community
		$condition = DBA::mergeConditions($condition, [
			"NOT `owner-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`blocked` OR `ignored`))",
			$uid,
		]);

		// Apply the same cursor constraints the network feed uses for pagination
		if (isset($this->maxId)) {
			$condition = DBA::mergeConditions($condition, ["`received` < ?", $this->maxId]);
		}
		if (isset($this->minId)) {
			$condition = DBA::mergeConditions($condition, ["`received` > ?", $this->minId]);
		}

		$params = ['order' => ['received' => true], 'limit' => $savedLimit * 2];
		$result = Post::selectOriginThread(['uri-id', 'received'], $condition, $params);
		while ($row = $this->database->fetch($result)) {
			if (!isset($merged[$row['uri-id']])) {
				$merged[$row['uri-id']] = $row;
			}
		}
		$this->database->close($result);

		// Unified sort: newest first, trimmed to one page
		uasort($merged, fn($a, $b) => strcmp($b['received'] ?? '', $a['received'] ?? ''));

		return array_slice(array_values($merged), 0, $savedLimit);
	}
}
