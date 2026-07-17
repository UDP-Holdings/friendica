<?php

// UDP Social — Group Circle invite proposal + co-owner voting
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\UdpGroupCircle;
use Friendica\Network\HTTPException;

/**
 * POST /udp/group/{id}/invite — any member proposes adding someone.
 * The contact is identified by handle (@user@server) or profile URL.
 */
class Invite extends BaseModule
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

		self::checkFormSecurityTokenRedirectOnError('/udp/group/' . $circleId . '/members', 'udp_group_invite_' . $circleId);

		$selfContact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
		if (!$selfContact || !UdpGroupCircle::isMember($circleId, $selfContact['id'])) {
			throw new HTTPException\ForbiddenException();
		}

		$handle = trim($request['handle'] ?? '');
		if (!$handle) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter a handle or profile URL.'));
			DI::baseUrl()->redirect('udp/group/' . $circleId . '/members');
		}

		// Resolve the handle to a contact
		$targetContact = Contact::getByURL($handle, true); // fetch from network if needed
		if (!$targetContact) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Could not find that person. Please check the handle and try again.'));
			DI::baseUrl()->redirect('udp/group/' . $circleId . '/members');
		}

		// Ensure we have a contact row for this person on this node
		$localTargetCid = Contact::getIdForURL($targetContact['url'], 0, true);
		if (!$localTargetCid) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Could not resolve contact. Please try again.'));
			DI::baseUrl()->redirect('udp/group/' . $circleId . '/members');
		}

		try {
			UdpGroupCircle::proposeInvite($circleId, $selfContact['id'], $localTargetCid);
			DI::sysmsg()->addInfo(DI::l10n()->t('Invite proposed. All co-owners must accept before the person is added.'));
		} catch (\InvalidArgumentException $e) {
			DI::sysmsg()->addNotice(DI::l10n()->t($e->getMessage()));
		} catch (\Exception $e) {
			DI::logger()->error('Group Circle invite failed', ['circle' => $circleId, 'error' => $e->getMessage()]);
			DI::sysmsg()->addNotice(DI::l10n()->t('Something went wrong. Please try again.'));
		}

		DI::baseUrl()->redirect('udp/group/' . $circleId . '/members');
	}
}
