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
use Friendica\Util\Strings;

/**
 * GET /udp/group/{id} — redirects to the group's conversation timeline.
 *
 * /contact/{contactId}/conversations is the correct scoped view: it shows only
 * posts involving this contact (the group actor) with full Friendica rendering.
 * Falls back to the members page if the viewer's per-user contact row is missing.
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

		$selfContact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
		if (!$selfContact || !UdpGroupCircle::isMember($circleId, $selfContact['id'])) {
			throw new HTTPException\ForbiddenException();
		}

		$actorOwner   = \Friendica\Model\User::getOwnerDataById($circle['actor-uid']);
		$actorContact = $actorOwner
			? Contact::selectFirst(
				['id'],
				['uid' => $uid, 'nurl' => Strings::normaliseLink($actorOwner['url']), 'archive' => false, 'deleted' => false]
			)
			: null;

		if ($actorContact) {
			DI::baseUrl()->redirect('contact/' . $actorContact['id'] . '/conversations');
		}

		DI::baseUrl()->redirect('udp/group/' . $circleId . '/members');
		return '';
	}
}
