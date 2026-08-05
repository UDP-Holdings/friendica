<?php

// UDP Social — Group invite voting (co-owner accept / reject)
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\UdpGroupCircle;
use Friendica\Network\HTTPException;

/**
 * POST /udp/group/{id}/invite/{iid}/vote — co-owner casts accept or reject.
 */
class Vote extends BaseModule
{
	protected function post(array $request = []): void
	{
		$uid = DI::userSession()->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\UnauthorizedException();
		}

		$circleId = intval($this->parameters['id']  ?? 0);
		$inviteId = intval($this->parameters['iid'] ?? 0);

		$circle = UdpGroupCircle::getById($circleId);
		if (!$circle) {
			throw new HTTPException\NotFoundException();
		}

		self::checkFormSecurityTokenRedirectOnError('/udp/group/' . $circleId . '/members', 'udp_group_vote_' . $inviteId);

		$selfContact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
		if (!$selfContact || !UdpGroupCircle::isCoOwner($circleId, $selfContact['id'])) {
			throw new HTTPException\ForbiddenException();
		}

		$accepted = ($request['vote'] ?? '') === 'accept';

		try {
			UdpGroupCircle::voteOnInvite($inviteId, $selfContact['id'], $accepted);
			DI::sysmsg()->addInfo($accepted
				? DI::l10n()->t('You accepted the invite.')
				: DI::l10n()->t('You rejected the invite.')
			);
		} catch (\InvalidArgumentException $e) {
			DI::sysmsg()->addNotice(DI::l10n()->t($e->getMessage()));
		}

		DI::baseUrl()->redirect('udp/group/' . $circleId . '/members');
	}
}
