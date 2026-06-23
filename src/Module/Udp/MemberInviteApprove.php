<?php

// UDP Social customization — admin approval for user-initiated friend invites
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\Model\Register;
use Friendica\Module\BaseAdmin;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * Admin-only endpoint to approve a user-submitted member invite request.
 *
 * GET  /udp/member-invite-approve/{token} — show the pending request + approve button
 * POST /udp/member-invite-approve/{token} — generate invite code, email the friend, consume token
 */
class MemberInviteApprove extends BaseAdmin
{
	protected function post(array $request = []): void
	{
		parent::post();

		$token = $this->parameters['token'] ?? '';
		if (!$token) {
			throw new HTTPException\NotFoundException();
		}

		self::checkFormSecurityTokenRedirectOnError('/udp/member-invite-approve/' . $token, 'udp_member_invite_approve');

		$stored = DI::config()->get('udp_member_invite', $token);
		if (!$stored) {
			DI::sysmsg()->addNotice(DI::l10n()->t('This invite request has already been handled or has expired.'));
			DI::baseUrl()->redirect('admin');
		}

		$data = json_decode($stored, true);

		if (empty($data['expires_at']) || $data['expires_at'] < time()) {
			DI::config()->delete('udp_member_invite', $token);
			DI::sysmsg()->addNotice(DI::l10n()->t('This invite request has expired.'));
			DI::baseUrl()->redirect('admin');
		}

		$friendEmail = $data['friend_email'] ?? '';
		$friendName  = $data['friend_name']  ?? $friendEmail;
		$sitename    = DI::config()->get('config', 'sitename');

		$inviteCode  = Register::createForInvitation();
		$registerUrl = (string) DI::baseUrl() . '/register?invite=' . $inviteCode;

		$subject = DI::l10n()->t("You've been invited to join %s", $sitename);
		$body    = DI::l10n()->t(
			"Hi %s,\n\n%s has invited you to join their community on %s.\n\nClick the link below to create your account:\n\n%s\n\nThis invitation link expires in 7 days.\n\n— The %s team",
			$friendName,
			$data['requester_name'] ?? 'A community member',
			$sitename,
			$registerUrl,
			$sitename
		);

		$mail = DI::emailer()
			->newSystemMail()
			->withMessage($subject, $body)
			->withRecipient($friendEmail)
			->build();

		$sent = DI::emailer()->send($mail);

		DI::config()->delete('udp_member_invite', $token);

		if ($sent) {
			DI::sysmsg()->addInfo(DI::l10n()->t('Invitation sent to %s.', $friendEmail));
		} else {
			DI::sysmsg()->addNotice(DI::l10n()->t('Invite code generated but the email to %s could not be sent. Registration link: %s', $friendEmail, $registerUrl));
		}

		DI::baseUrl()->redirect('admin');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$token = $this->parameters['token'] ?? '';
		if (!$token) {
			throw new HTTPException\NotFoundException();
		}

		$stored = DI::config()->get('udp_member_invite', $token);
		if (!$stored) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('This invite request has already been handled or has expired.'));
		}

		$data = json_decode($stored, true);

		if (empty($data['expires_at']) || $data['expires_at'] < time()) {
			DI::config()->delete('udp_member_invite', $token);
			throw new HTTPException\NotFoundException(DI::l10n()->t('This invite request has expired.'));
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/member_invite_approve.tpl'), [
			'$requester_name'  => $data['requester_name']  ?? '',
			'$requester_nick'  => $data['requester_nick']  ?? '',
			'$friend_name'     => $data['friend_name']     ?? '',
			'$friend_email'    => $data['friend_email']    ?? '',
			'$note'            => $data['note']            ?? '',
			'$expires_str'     => date('M j, Y', (int) ($data['expires_at'] ?? 0)),
			'$token'           => $token,
			'$baseurl'         => (string) DI::baseUrl(),
			'$form_security_token' => self::getFormSecurityToken('udp_member_invite_approve'),
		]);
	}
}
