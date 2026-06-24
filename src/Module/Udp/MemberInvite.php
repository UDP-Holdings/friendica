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
 * Flow A (plain email): admin approves → friend gets a registration link.
 * Flow B (@handle):     if node already paired → tell user to search; otherwise email
 *                       this node's admin to initiate node pairing.
 */
class MemberInvite extends BaseModule
{
	protected function post(array $request = []): void
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException();
		}

		self::checkFormSecurityTokenRedirectOnError('/udp/member-invite', 'udp_member_invite');

		$contact = trim($request['contact'] ?? '');
		$note    = trim($request['note']    ?? '');

		if (!$contact) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter an email address or Fediverse handle.'));
			DI::baseUrl()->redirect('udp/member-invite');
		}

		if (str_starts_with($contact, '@')) {
			$this->handleFlowB($contact, $note);
			return;
		}

		$this->handleFlowA($contact, $note);
	}

	/**
	 * Flow B: user typed a Fediverse handle (@user@other-node.tld).
	 * If the remote node is already paired, tell the user to search directly.
	 * Otherwise email this node's admin to initiate node pairing.
	 */
	private function handleFlowB(string $contact, string $note): void
	{
		$handle = ltrim($contact, '@');
		$parts  = explode('@', $handle, 2);

		if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter a full Fediverse handle, e.g. @username@server.tld'));
			DI::baseUrl()->redirect('udp/member-invite');
		}

		$remoteDomain = strtolower(trim($parts[1]));

		$allowedRaw    = DI::config()->get('system', 'allowed_sites') ?? '';
		$pairedDomains = array_filter(array_map('trim', explode(',', $allowedRaw)));

		if (in_array($remoteDomain, $pairedDomains, true)) {
			DI::sysmsg()->addInfo(DI::l10n()->t(
				'Good news — this community is already connected to %s. Search for %s from Contacts to find your friend.',
				$remoteDomain,
				$contact
			));
			DI::baseUrl()->redirect('udp/member-invite');
		}

		$uid        = DI::userSession()->getLocalUserId();
		$user       = \Friendica\Model\User::getById($uid, ['username', 'nickname', 'email']);
		$sitename   = DI::config()->get('config', 'sitename');
		$adminEmail = DI::config()->get('config', 'admin_email');

		if (!$adminEmail) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Unable to send request — no admin email is configured. Please contact your community admin directly.'));
			DI::baseUrl()->redirect('udp/member-invite');
		}

		$nodePairUrl = (string) DI::baseUrl() . '/admin/node-pair';
		$subject     = DI::l10n()->t('%s wants to connect with someone on %s', $user['username'] ?? 'A member', $remoteDomain);
		$body        = DI::l10n()->t(
			"Hi,\n\n%s (%s) wants to connect with %s on %s, but that node isn't paired with %s yet.%s\n\nTo allow this, pair with that node first:\n%s\n\n— UDP Social",
			$user['username'] ?? '',
			$user['email']    ?? '',
			$contact,
			$remoteDomain,
			$sitename,
			$note ? "\n\nNote from user: " . $note : '',
			$nodePairUrl
		);

		$mail = DI::emailer()
			->newSystemMail()
			->withMessage($subject, $body)
			->withRecipient($adminEmail)
			->build();

		DI::emailer()->send($mail);

		DI::sysmsg()->addInfo(DI::l10n()->t('Your request has been sent to the community admin. They\'ll reach out to that node so you can connect.'));
		DI::baseUrl()->redirect('udp/member-invite');
	}

	/**
	 * Flow A: user typed a plain email address.
	 * Store an approval token and email the admin to approve and forward an invite.
	 */
	private function handleFlowA(string $friendEmail, string $note): void
	{
		if (!filter_var($friendEmail, FILTER_VALIDATE_EMAIL)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter a valid email address or a Fediverse handle (e.g. @username@server.tld).'));
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
		$expires = time() + 86400 * 3;

		DI::config()->set('udp_member_invite', $token, json_encode([
			'requester_uid'   => $uid,
			'requester_name'  => $user['username']  ?? '',
			'requester_nick'  => $user['nickname']  ?? '',
			'requester_email' => $user['email']     ?? '',
			'friend_email'    => $friendEmail,
			'note'            => $note,
			'expires_at'      => $expires,
		]));

		$approveUrl = (string) DI::baseUrl() . '/udp/member-invite-approve/' . $token;
		$subject    = DI::l10n()->t('%s wants to invite a friend to %s', $user['username'] ?? 'A member', $sitename);
		$body       = DI::l10n()->t(
			"Hi,\n\n%s (%s) would like to invite the following person to join %s:\n\nEmail: %s%s\n\nTo approve and send them an invitation, visit:\n%s\n\nThis request expires in 3 days.\n\n— UDP Social",
			$user['username'] ?? '',
			$user['email']    ?? '',
			$sitename,
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
