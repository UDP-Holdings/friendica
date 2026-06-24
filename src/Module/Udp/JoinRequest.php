<?php

// UDP Social customization — public landing page for non-admin node join requests
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Register;
use Friendica\Network\HTTPException;

/**
 * Handles the public landing page when an admin scans a user's join-request QR code.
 *
 * GET  /udp/join-request/{token}  — show the request details + "Send invitation" form
 * POST /udp/join-request/{token}  — send the invite email, consume the token
 *
 * No login required — the scanning admin won't be authenticated on this node.
 */
class JoinRequest extends BaseModule
{
	protected function post(array $request = []): void
	{
		$token = $this->parameters['token'] ?? '';
		if (!$token) {
			throw new HTTPException\NotFoundException();
		}

		self::checkFormSecurityTokenRedirectOnError('/udp/join-request/' . $token, 'udp_join_invite');

		$stored = DI::config()->get('udp_join_req', $token);
		if (!$stored) {
			DI::sysmsg()->addNotice(DI::l10n()->t('This join request link has expired or is invalid.'));
			DI::baseUrl()->redirect('/');
		}

		$data = json_decode($stored, true);

		if (empty($data['expires_at']) || $data['expires_at'] < time()) {
			DI::config()->delete('udp_join_req', $token);
			DI::sysmsg()->addNotice(DI::l10n()->t('This join request link has expired.'));
			DI::baseUrl()->redirect('/');
		}

		$recipientEmail = $data['email'] ?? '';
		$recipientName  = $data['name']  ?? $recipientEmail;

		// Create a single-use invite code so registration works even on closed nodes.
		$inviteCode = Register::createForInvitation();
		$registerUrl = (string) DI::baseUrl() . '/register?invite=' . $inviteCode;

		$sitename = DI::config()->get('config', 'sitename');
		$subject  = DI::l10n()->t("You've been invited to join %s", $sitename);
		$body     = DI::l10n()->t(
			"Hi %s,\n\nYou've been invited to join %s.\n\nClick the link below to create your account:\n\n%s\n\nThis invitation expires in 3 days.\n\n— The %s team",
			$recipientName,
			$sitename,
			$registerUrl,
			$sitename
		);

		$email = DI::emailer()
			->newSystemMail()
			->withMessage($subject, $body)
			->withRecipient($recipientEmail)
			->build();

		$sent = DI::emailer()->send($email);

		// Consume the token regardless — admin can re-scan if something went wrong.
		DI::config()->delete('udp_join_req', $token);

		if ($sent) {
			DI::sysmsg()->addInfo(DI::l10n()->t('Invitation sent to %s.', $recipientEmail));
		} else {
			DI::sysmsg()->addNotice(DI::l10n()->t('Could not send the invitation email. Please contact UDP support.'));
		}

		DI::baseUrl()->redirect('/admin/node-pair');
	}

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

		$expiresAt  = (int) ($data['expires_at'] ?? 0);
		$expiresStr = $expiresAt ? date('M j, Y', $expiresAt) : '';

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/join_request.tpl'), [
			'$name'                 => $data['name']  ?? '',
			'$nick'                 => $data['nick']  ?? '',
			'$email'                => $data['email'] ?? '',
			'$node'                 => $data['node']  ?? '',
			'$node_url'             => 'https://' . ($data['node'] ?? ''),
			'$expires_str'          => $expiresStr,
			'$baseurl'              => (string) DI::baseUrl(),
			'$token'                => $token,
			'$form_security_token'  => self::getFormSecurityToken('udp_join_invite'),
		]);
	}
}
