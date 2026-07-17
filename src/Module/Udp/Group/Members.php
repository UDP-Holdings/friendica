<?php

// UDP Social — Group Circle membership management
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
 * GET  /udp/group/{id}/members — membership roster + pending invites
 * POST /udp/group/{id}/members — promote/remove a member (co-owner only)
 */
class Members extends BaseModule
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

		self::checkFormSecurityTokenRedirectOnError('/udp/group/' . $circleId . '/members', 'udp_group_members_' . $circleId);

		$selfContact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
		if (!$selfContact || !UdpGroupCircle::isCoOwner($circleId, $selfContact['id'])) {
			throw new HTTPException\ForbiddenException();
		}

		$action    = $request['action']     ?? '';
		$contactId = intval($request['contact_id'] ?? 0);

		switch ($action) {
			case 'promote':
				UdpGroupCircle::promoteToCoOwner($circleId, $contactId);
				DI::sysmsg()->addInfo(DI::l10n()->t('Member promoted to co-owner.'));
				break;

			case 'remove':
				if (!UdpGroupCircle::removeMember($circleId, $contactId)) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Cannot remove the last co-owner. Promote someone else first.'));
				} else {
					DI::sysmsg()->addInfo(DI::l10n()->t('Member removed.'));
				}
				break;

			default:
				DI::sysmsg()->addNotice(DI::l10n()->t('Unknown action.'));
		}

		DI::baseUrl()->redirect('udp/group/' . $circleId . '/members');
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

		$selfContact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
		if (!$selfContact || !UdpGroupCircle::isMember($circleId, $selfContact['id'])) {
			throw new HTTPException\ForbiddenException();
		}

		$isCoOwner      = UdpGroupCircle::isCoOwner($circleId, $selfContact['id']);
		$members        = UdpGroupCircle::getMembers($circleId);
		$pendingInvites = $isCoOwner ? UdpGroupCircle::getPendingInvites($circleId) : [];

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/group/members.tpl'), [
			'$circle'              => $circle,
			'$members'             => $members,
			'$pending_invites'     => $pendingInvites,
			'$is_co_owner'         => $isCoOwner,
			'$self_contact_id'     => $selfContact['id'],
			'$form_security_token' => self::getFormSecurityToken('udp_group_members_' . $circleId),
			'$invite_url'          => DI::baseUrl() . '/udp/group/' . $circleId . '/invite',
			'$invite_token'        => self::getFormSecurityToken('udp_group_invite_' . $circleId),
			'$back_url'            => DI::baseUrl() . '/udp/group/' . $circleId,
		]);
	}
}
