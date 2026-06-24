<?php

// UDP Social customization — user-accessible node-pairing invite via email
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * Lets any logged-in user send a node-pairing invitation by email.
 *
 * POST /contact/invite
 *
 * Generates a udp_pair token (same as Admin → Node Pairing → Generate) and
 * emails the recipient a link to the public /udp/pair-invite/{token} page.
 * The actual trust (allowed_sites) is only granted when the recipient's admin
 * completes the handshake via their own node's accept flow.
 */
class Invite extends BaseModule
{
	protected function post(array $request = []): void
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException();
		}

		// FIXME: This endpoint generates an admin-level QR/token and must be
		// restricted to site admins. Currently any logged-in user can trigger it,
		// which exposes the node-pairing credential to non-admin accounts.
		// Fix: add isSiteAdmin() check and return 403 otherwise.

		$recipientEmail = trim($request['invite_email'] ?? '');
		if (!$recipientEmail || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter a valid email address.'));
			DI::baseUrl()->redirect('contact');
		}

		$token = bin2hex(random_bytes(24));
		DI::config()->set('udp_pair', $token, json_encode([
			'domain'     => DI::baseUrl()->getHost(),
			'expires_at' => time() + 86400,
		]));

		$payload    = ['d' => DI::baseUrl()->getHost(), 't' => $token];
		$payloadStr = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
		$inviteUrl  = (string) DI::baseUrl() . '/udp/pair-invite/' . $token;
		$sitename   = DI::config()->get('config', 'sitename');

		$subject  = DI::l10n()->t('%s wants to connect with you', $sitename);
		$preamble = DI::l10n()->t(
			"Someone on %s would like to connect their community with yours.\n\nView the pairing QR code here (valid 24 hours):\n%s\n\nOr paste this token at your node's Admin → Node Pairing → Scan / paste token:\n\n%s",
			$sitename,
			$inviteUrl,
			$payloadStr
		);

		$mail = DI::emailer()
			->newSystemMail()
			->withMessage($subject, $preamble)
			->withRecipient($recipientEmail)
			->build();

		if (DI::emailer()->send($mail)) {
			DI::sysmsg()->addInfo(DI::l10n()->t('Invitation sent to %s.', $recipientEmail));
		} else {
			DI::sysmsg()->addNotice(DI::l10n()->t('Could not send the invitation email. Please try again or share the pairing token manually.'));
		}

		DI::baseUrl()->redirect('contact');
	}
}
