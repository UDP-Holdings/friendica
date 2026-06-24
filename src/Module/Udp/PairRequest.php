<?php

// UDP Social customization — public endpoint for incoming server-to-server pairing requests
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Util\Network;

/**
 * Receives a pairing request from another UDP Social node.
 *
 * POST /udp/pair-request
 * Body (JSON): {
 *   "requesting_domain": "test3.udp.social",
 *   "pairing_token":     "<48-hex>",
 *   "context": {
 *     "requester_handle": "alice",
 *     "target_handle":    "@emily@test4.udp.social"
 *   }
 * }
 * Response: { "success": true } | { "success": false, "error": "..." }
 *
 * The request is stored in config under cat=udp_pair_request, key=requesting_domain.
 * A second request from the same domain overwrites the first (one pending slot per domain).
 * All local admins can see and accept pending requests from /admin/node-pair.
 *
 * TODO (multiple admins): fan out an in-app notification to every admin on this node,
 * not just the contact_account. See project_flow_b_pair_request memory for design notes.
 */
class PairRequest extends BaseModule
{
	protected function rawContent(array $request = []): void
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->jsonExit(['success' => false, 'error' => 'POST required'], 'application/json', 405);
		}

		$body = Network::postdata();
		$data = json_decode($body, true);

		$requestingDomain = $data['requesting_domain'] ?? '';
		$pairingToken     = $data['pairing_token']     ?? '';
		$context          = $data['context']            ?? [];

		if (!$requestingDomain || !$pairingToken) {
			$this->jsonExit(['success' => false, 'error' => 'missing required fields'], 'application/json', 400);
		}

		if (!$this->isValidHostname($requestingDomain)) {
			$this->jsonExit(['success' => false, 'error' => 'invalid requesting_domain'], 'application/json', 400);
		}

		if (!preg_match('/^[0-9a-f]{48}$/', $pairingToken)) {
			$this->jsonExit(['success' => false, 'error' => 'invalid pairing_token format'], 'application/json', 400);
		}

		DI::config()->set('udp_pair_request', $requestingDomain, json_encode([
			'domain'        => $requestingDomain,
			'pairing_token' => $pairingToken,
			'context'       => [
				'requester_handle' => substr(trim($context['requester_handle'] ?? ''), 0, 64),
				'target_handle'    => substr(trim($context['target_handle']    ?? ''), 0, 128),
			],
			'received_at'   => time(),
		]));

		$this->jsonExit(['success' => true]);
	}

	private function isValidHostname(string $domain): bool
	{
		return (bool) preg_match('/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain);
	}
}
