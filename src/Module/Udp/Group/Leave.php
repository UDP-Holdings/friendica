<?php

// UDP Social — Group Circle leave
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\UdpGroupCircle;
use Friendica\Network\HTTPException;

/**
 * POST /udp/group/{id}/leave — member leaves the group.
 * Blocks if the member is the last co-owner (must promote someone first).
 */
class Leave extends BaseModule
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

		self::checkFormSecurityTokenRedirectOnError('/udp/group/' . $circleId, 'udp_group_action_' . $circleId);

		$selfContact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
		if (!$selfContact || !UdpGroupCircle::isMember($circleId, $selfContact['id'])) {
			DI::baseUrl()->redirect('udp/group');
		}

		// Only the creator can delete the group
		if (($request['action'] ?? '') === 'close' && $circle['creator-uid'] === $uid) {
			UdpGroupCircle::close($circleId);
			DI::sysmsg()->addInfo(DI::l10n()->t('The group has been deleted.'));
			DI::baseUrl()->redirect('udp/group');
		}

		if (!UdpGroupCircle::removeMember($circleId, $selfContact['id'])) {
			DI::sysmsg()->addNotice(DI::l10n()->t(
				'You are the only co-owner. Promote another member to co-owner before leaving, or delete the group.'
			));
			DI::baseUrl()->redirect('udp/group/' . $circleId . '/members');
		}

		DI::sysmsg()->addInfo(DI::l10n()->t('You have left the group.'));
		DI::baseUrl()->redirect('udp/group');
	}
}
