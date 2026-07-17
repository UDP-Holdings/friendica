<?php

// UDP Social — Group Circle feed view
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\UdpGroupCircle;
use Friendica\Network\HTTPException;

/**
 * GET /udp/group/{id} — show the group feed.
 *
 * The feed is the timeline of the group actor user, which contains all posts
 * forwarded to it via tagDeliver.  We render the standard network thread view
 * scoped to the actor UID so existing Friendica timeline code does the heavy lifting.
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

		// Only members may view the feed
		$selfContact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
		if (!$selfContact || !UdpGroupCircle::isMember($circleId, $selfContact['id'])) {
			throw new HTTPException\ForbiddenException();
		}

		$isClosed   = !empty($circle['closed']);
		$isCoOwner  = UdpGroupCircle::isCoOwner($circleId, $selfContact['id']);
		$memberCount = count(UdpGroupCircle::getMembers($circleId));

		// Build the group actor profile URL so members can @-mention it in posts
		$actorOwner = \Friendica\Model\User::getOwnerDataById($circle['actor-uid']);
		$groupHandle = $actorOwner ? ('@' . $actorOwner['nickname'] . '@' . parse_url((string) DI::baseUrl(), PHP_URL_HOST)) : '';

		// Fetch the 25 most recent top-level posts on the group actor's timeline
		$stmt = Post::selectForUser(
			$circle['actor-uid'],
			['id', 'uri-id', 'author-name', 'author-link', 'author-avatar', 'body', 'created'],
			['uid' => $circle['actor-uid'], 'gravity' => Item::GRAVITY_PARENT],
			['order' => ['created' => true], 'limit' => 25]
		);
		$items = Post::toArray($stmt);
		foreach ($items as &$item) {
			$item['body_html'] = BBCode::convertForUriId($item['uri-id'], $item['body']);
		}
		unset($item);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/group/view.tpl'), [
			'$circle'       => $circle,
			'$group_handle' => $groupHandle,
			'$is_closed'    => $isClosed,
			'$is_co_owner'  => $isCoOwner,
			'$member_count' => $memberCount,
			'$items'        => $items,
			'$members_url'  => DI::baseUrl() . '/udp/group/' . $circleId . '/members',
			'$leave_url'    => DI::baseUrl() . '/udp/group/' . $circleId . '/leave',
			'$form_security_token' => self::getFormSecurityToken('udp_group_action_' . $circleId),
			'$how_to_post'  => DI::l10n()->t('To post in this group, mention %s in a post.', $groupHandle),
		]);
	}
}
