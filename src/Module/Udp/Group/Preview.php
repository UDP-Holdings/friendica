<?php

// UDP Social — Group invitation preview (invitee consent)
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\UdpGroupCircle;
use Friendica\Network\HTTPException;

/**
 * GET  /udp/group/{id}/preview — invitation preview page for the invitee.
 * POST /udp/group/{id}/preview — accept or decline the invitation.
 *
 * Shown when a user with an INVITE_AWAITING_INVITEE row visits the group link.
 * Displays the circle name, description, and current member roster so the
 * invitee can make an informed decision before their first post commits them.
 */
class Preview extends BaseModule
{
	protected function post(array $request = []): void
	{
		$uid = DI::userSession()->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\UnauthorizedException();
		}

		$circleId = intval($this->parameters['id'] ?? 0);
		$circle   = UdpGroupCircle::getById($circleId);
		if (!$circle) {
			throw new HTTPException\NotFoundException();
		}

		self::checkFormSecurityTokenRedirectOnError('/udp/group/' . $circleId . '/preview', 'udp_group_preview_' . $circleId);

		$action = trim($request['action'] ?? '');

		if ($action === 'accept') {
			if (UdpGroupCircle::acceptInvite($circleId, $uid)) {
				DI::sysmsg()->addInfo(DI::l10n()->t('Welcome! You are now a member of the group.'));
				DI::baseUrl()->redirect('network/group/' . $circleId);
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('No pending invitation found.'));
				DI::baseUrl()->redirect('udp/group');
			}
		} elseif ($action === 'decline') {
			if (UdpGroupCircle::declineInvite($circleId, $uid)) {
				DI::sysmsg()->addInfo(DI::l10n()->t('Invitation declined.'));
			}
			DI::baseUrl()->redirect('udp/group');
		}

		DI::baseUrl()->redirect('udp/group/' . $circleId . '/preview');
	}

	protected function content(array $request = []): string
	{
		$uid = DI::userSession()->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\UnauthorizedException();
		}

		$circleId = intval($this->parameters['id'] ?? 0);
		$circle   = UdpGroupCircle::getById($circleId);
		if (!$circle) {
			throw new HTTPException\NotFoundException();
		}

		// Only reachable if there is a pending invite for this user.
		$invite = UdpGroupCircle::getPendingInviteForUser($circleId, $uid);
		if (!$invite) {
			// Already a member? Redirect to the group timeline.
			$selfContact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
			if ($selfContact && UdpGroupCircle::isMember($circleId, $selfContact['id'])) {
				DI::baseUrl()->redirect('network/group/' . $circleId);
			}
			throw new HTTPException\ForbiddenException();
		}

		$members = UdpGroupCircle::getMembers($circleId);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/group/preview.tpl'), [
			'$circle'              => $circle,
			'$members'             => $members,
			'$form_security_token' => self::getFormSecurityToken('udp_group_preview_' . $circleId),
			'$action_url'          => DI::baseUrl() . '/udp/group/' . $circleId . '/preview',
		]);
	}
}
