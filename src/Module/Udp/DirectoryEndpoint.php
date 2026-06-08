<?php

// UDP Social customization — inter-node directory JSON endpoint
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;

/**
 * Returns a JSON list of local public users for cross-node directory aggregation.
 *
 * GET /udp/directory[?search=<term>]
 *
 * Gated: the requester must send X-UDP-Node: <domain> and that domain must be
 * in system.allowed_sites. This is not cryptographic but is appropriate for a
 * closed family network where federation is already allowlist-gated.
 *
 * Response: JSON array of { name, nickname, url, photo, about, pub_keywords }
 *
 * New file = zero upstream merge conflict risk.
 */
class DirectoryEndpoint extends BaseModule
{
	protected function rawContent(array $request = []): void
	{
		$requestingNode = $_SERVER['HTTP_X_UDP_NODE'] ?? '';
		$ts             = (int) ($_SERVER['HTTP_X_UDP_TS']  ?? 0);
		$sig            = $_SERVER['HTTP_X_UDP_SIG']  ?? '';

		// Constant-time 403 for any missing field so callers learn nothing
		if (!$requestingNode || !$ts || !$sig) {
			$this->jsonExit(['error' => 'forbidden'], 'application/json', 403);
		}

		// Reject stale or future-dated requests (replay / clock-skew window: ±5 min)
		if (abs(time() - $ts) > 300) {
			$this->jsonExit(['error' => 'forbidden'], 'application/json', 403);
		}

		$secret = DI::config()->get('udp_shared_secret', $requestingNode) ?? '';
		if (!$secret) {
			$this->jsonExit(['error' => 'forbidden'], 'application/json', 403);
		}

		$expected = hash_hmac('sha256', $requestingNode . '|' . $ts, $secret);
		if (!hash_equals($expected, $sig)) {
			$this->jsonExit(['error' => 'forbidden'], 'application/json', 403);
		}

		$search   = trim($request['search'] ?? '');
		$profiles = Profile::searchProfiles(0, 200, $search ?: null);

		$users = [];
		foreach ($profiles['entries'] as $entry) {
			$users[] = [
				'name'         => $entry['name']         ?? '',
				'nickname'     => $entry['nickname']      ?? '',
				'url'          => $entry['url']           ?? '',
				'photo'        => Contact::getThumb($entry),
				'about'        => $entry['about']         ?? '',
				'pub_keywords' => $entry['pub_keywords']  ?? '',
			];
		}

		$this->jsonExit($users);
	}

}
