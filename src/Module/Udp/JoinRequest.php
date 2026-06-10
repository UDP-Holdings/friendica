<?php

// UDP Social customization — public landing page for non-admin node join requests
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * Handles the public landing page when an admin scans a user's join-request QR code.
 *
 * GET /udp/join-request/{token}
 *
 * No login required — the scanning admin won't be authenticated on this node.
 * Displays the requesting user's name, node, and email so the admin knows who to invite.
 *
 * New file = zero upstream merge conflict risk.
 */
class JoinRequest extends BaseModule
{
	protected function content(array $request = []): string
	{
		$token = $this->parameters['token'] ?? '';

		if (!$token) {
			throw new HTTPException\NotFoundException();
		}

		$stored = DI::config()->get('udp_join_req', $token);
		if (!$stored) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('This join request link has expired or is invalid.'));
		}

		$data = json_decode($stored, true);

		if (empty($data['expires_at']) || $data['expires_at'] < time()) {
			DI::config()->delete('udp_join_req', $token);
			throw new HTTPException\NotFoundException(DI::l10n()->t('This join request link has expired.'));
		}

		$expiresAt = (int) ($data['expires_at'] ?? 0);
		$expiresStr = $expiresAt ? date('M j, Y', $expiresAt) : '';

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/join_request.tpl'), [
			'$name'       => $data['name']  ?? '',
			'$nick'       => $data['nick']  ?? '',
			'$email'      => $data['email'] ?? '',
			'$node'       => $data['node']  ?? '',
			'$node_url'   => 'https://' . ($data['node'] ?? ''),
			'$expires_str'=> $expiresStr,
			'$baseurl'    => (string)DI::baseUrl(),
		]);
	}
}
