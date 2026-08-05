<?php

// UDP Social — Group invite proposal + co-owner voting
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\UdpGroupCircle;
use Friendica\Model\User;
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

		// Normalise: strip leading @ so both "@user@host" and "user@host" work.
		// If no domain is present (bare "@user" or "user"), append the local domain.
		if (!str_starts_with($handle, 'http')) {
			$handle = ltrim($handle, '@');
			if (!str_contains($handle, '@')) {
				$handle .= '@' . DI::baseUrl()->getHost();
			}
		}

		// Resolve the handle to a contact
		$targetContact = Contact::getByURL($handle, true); // fetch from network if needed
		if (!$targetContact) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Could not find that person. Please check the handle and try again.'));
			DI::baseUrl()->redirect('udp/group/' . $circleId . '/members');
		}

		// For local users use their self-contact so membership checks in View/Members match.
		// For remote users fall back to the global (uid=0) contact.
		$targetLocalUid = User::getIdForURL($targetContact['url']);
		if ($targetLocalUid) {
			$selfRow = Contact::selectFirst(['id'], ['uid' => $targetLocalUid, 'self' => true]);
			$localTargetCid = DBA::isResult($selfRow) ? $selfRow['id'] : 0;
		} else {
			$localTargetCid = Contact::getIdForURL($targetContact['url'], 0, true);
		}
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
