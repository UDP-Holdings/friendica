<?php

// UDP Social — Group Circle hub page
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\UdpGroupCircle;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * GET /udp/group/{id} — group hub page.
 *
 * Shows group name, description, member count, and links to the full timeline
 * (/network?cid=X) and the membership management page.
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

		$isClosed    = !empty($circle['closed']);
		$isCoOwner   = UdpGroupCircle::isCoOwner($circleId, $selfContact['id']);
		$memberCount = count(UdpGroupCircle::getMembers($circleId));

		$actorOwner  = \Friendica\Model\User::getOwnerDataById($circle['actor-uid']);
		$groupHandle = $actorOwner
			? ('@' . $actorOwner['nickname'] . '@' . parse_url((string) DI::baseUrl(), PHP_URL_HOST))
			: '';

		// /contact/{id}/conversations shows exactly this contact's posts with full
		// Friendica rendering. We need the viewer's per-user contact row for the group actor.
		$actorContact = $actorOwner
			? Contact::selectFirst(
				['id'],
				['uid' => $uid, 'nurl' => Strings::normaliseLink($actorOwner['url']), 'archive' => false, 'deleted' => false]
			)
			: null;
		$timelineUrl = $actorContact
			? ((string) DI::baseUrl() . '/contact/' . $actorContact['id'] . '/conversations')
			: '';

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/group/view.tpl'), [
			'$circle'              => $circle,
			'$group_handle'        => $groupHandle,
			'$is_closed'           => $isClosed,
			'$is_co_owner'         => $isCoOwner,
			'$member_count'        => $memberCount,
			'$timeline_url'        => $timelineUrl,
			'$members_url'         => DI::baseUrl() . '/udp/group/' . $circleId . '/members',
			'$how_to_post'         => DI::l10n()->t('To post in this group, mention %s in a post.', $groupHandle),
		]);
	}
}
