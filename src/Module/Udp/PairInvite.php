<?php

// UDP Social customization — public landing page for emailed node-pairing invitations
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * Public landing page for node-pairing invitations sent by email.
 *
 * GET /udp/pair-invite/{token}
 *
 * No login required. Shows the pairing QR + copyable payload so Admin A can
 * complete the handshake at their own node's Admin → Node Pairing → Accept page.
 */
class PairInvite extends BaseModule
{
	protected function content(array $request = []): string
	{
		$token = $this->parameters['token'] ?? '';
		if (!$token) {
			throw new HTTPException\NotFoundException();
		}

		$stored = DI::config()->get('udp_pair', $token);
		if (!$stored) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('This pairing link has expired or has already been used.'));
		}

		$data = json_decode($stored, true);
		if (empty($data['expires_at']) || $data['expires_at'] < time()) {
			DI::config()->delete('udp_pair', $token);
			throw new HTTPException\NotFoundException(DI::l10n()->t('This pairing link has expired.'));
		}

		$payload    = ['d' => $data['domain'], 't' => $token];
		$payloadStr = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

		$renderer = new ImageRenderer(new RendererStyle(256), new SvgImageBackEnd());
		$qr_svg   = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', (new Writer($renderer))->writeString($payloadStr));

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/pair_invite.tpl'), [
			'$domain'   => $data['domain'],
			'$payload'  => $payloadStr,
			'$qr_svg'   => $qr_svg,
			'$expires'  => date('M j, Y g:i A T', (int) $data['expires_at']),
		]);
	}
}
