<?php

// UDP Social — Group Circle hub page
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\UdpGroupCircle;
use Friendica\Network\HTTPException;

/**
 * GET /udp/group/{id} — auth-gate redirect to /network/group/{id}.
 *
 * Validates session and membership before handing off to the network timeline.
 * If the user has a pending (awaiting_invitee) invitation, redirects to the
 * preview page instead so they can accept or decline.
 */
class View extends BaseModule
{
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

		// Pending invitee: send to the consent preview page.
		if (UdpGroupCircle::getPendingInviteForUser($circleId, $uid)) {
			DI::baseUrl()->redirect('udp/group/' . $circleId . '/preview');
		}

		$selfContact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
		if (!$selfContact || !UdpGroupCircle::isMember($circleId, $selfContact['id'])) {
			throw new HTTPException\ForbiddenException();
		}

		DI::baseUrl()->redirect('network/group/' . $circleId);
		return '';
	}
}
