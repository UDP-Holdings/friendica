<?php

// UDP Social customization — user-initiated friend invite with admin approval
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * Lets any logged-in user request that a friend be invited to this node.
 *
 * GET  /udp/member-invite         — show the request form
 * POST /udp/member-invite         — store request, notify admin
 *
 * The admin receives an email with a link to /udp/member-invite-approve/{token}.
 * No invite code is generated until the admin approves.
 */
class MemberInvite extends BaseModule
{
	protected function post(array $request = []): void
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException();
		}

		self::checkFormSecurityTokenRedirectOnError('/udp/member-invite', 'udp_member_invite');

		$friendName  = trim($request['friend_name']  ?? '');
		$friendEmail = trim($request['friend_email'] ?? '');
		$note        = trim($request['note']         ?? '');

		if (!$friendEmail || !filter_var($friendEmail, FILTER_VALIDATE_EMAIL)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter a valid email address for your friend.'));
			DI::baseUrl()->redirect('udp/member-invite');
		}

		$uid      = DI::userSession()->getLocalUserId();
		$user     = \Friendica\Model\User::getById($uid, ['username', 'nickname', 'email']);
		$sitename = DI::config()->get('config', 'sitename');
		$adminEmail = DI::config()->get('config', 'admin_email');

		if (!$adminEmail) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Unable to send request — no admin email is configured. Please contact your community admin directly.'));
			DI::baseUrl()->redirect('udp/member-invite');
		}

		$token   = bin2hex(random_bytes(24));
		$expires = time() + 86400 * 3; // 3-day window for the admin to act

		DI::config()->set('udp_member_invite', $token, json_encode([
			'requester_uid'   => $uid,
			'requester_name'  => $user['username']  ?? '',
			'requester_nick'  => $user['nickname']  ?? '',
			'requester_email' => $user['email']     ?? '',
			'friend_name'     => $friendName,
			'friend_email'    => $friendEmail,
			'note'            => $note,
			'expires_at'      => $expires,
		]));

		$approveUrl = (string) DI::baseUrl() . '/udp/member-invite-approve/' . $token;
		$subject    = DI::l10n()->t('%s wants to invite a friend to %s', $user['username'] ?? 'A member', $sitename);
		$body       = DI::l10n()->t(
			"Hi,\n\n%s (%s) would like to invite the following person to join %s:\n\nName: %s\nEmail: %s%s\n\nTo approve and send them an invitation, visit:\n%s\n\nThis request expires in 3 days.\n\n— UDP Social",
			$user['username'] ?? '',
			$user['email']    ?? '',
			$sitename,
			$friendName ?: '(name not provided)',
			$friendEmail,
			$note ? "\nNote: " . $note : '',
			$approveUrl
		);

		$mail = DI::emailer()
			->newSystemMail()
			->withMessage($subject, $body)
			->withRecipient($adminEmail)
			->build();

		DI::emailer()->send($mail);

		DI::sysmsg()->addInfo(DI::l10n()->t('Your request has been sent to the community admin. They\'ll send %s an invitation if approved.', $friendEmail));
		DI::baseUrl()->redirect('udp/member-invite');
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException();
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/member_invite.tpl'), [
			'$baseurl'              => (string) DI::baseUrl(),
			'$form_security_token'  => self::getFormSecurityToken('udp_member_invite'),
		]);
	}
}
