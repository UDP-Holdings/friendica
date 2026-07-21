<?php

// UDP Social — Group Circle model
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Notification\ObjectType as NotificationObjectType;
use Friendica\Model\Notification\Type as NotificationType;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\UdpDebug;

/**
 * Manages UDP Group Circles: private invite-only groups backed by AP Group actors.
 *
 * Each Group Circle owns one Friendica user (account-type=community, page-flags=prvgroup)
 * that acts as the AP Group actor. Members are followers of that actor. Delivery uses
 * Announce(Create(Note)) so posts appear attributed to their author, not as boosts.
 *
 * Membership flow:
 *   propose invite → all co-owners vote accept → target receives follow-invite notification
 *   → target follows group actor → Follow is auto-approved → member added here
 *
 * Leave flow:
 *   member unfollows group actor → actor removes from followers → removeMember() called
 */
class UdpGroupCircle
{
	const ROLE_MEMBER   = 0;
	const ROLE_CO_OWNER = 1;

	const INVITE_PENDING  = 0;
	const INVITE_ACCEPTED = 1;
	const INVITE_REJECTED = 2;

	// ── Identity ──────────────────────────────────────────────────────────────

	/**
	 * Returns true if the given local UID is the AP actor for any Group Circle.
	 * Called from Item::tagDeliver and ActivityPub\Delivery to gate Group Circle logic.
	 */
	public static function isGroupCircleActor(int $uid): bool
	{
		return DBA::exists('udp-group-circle', ['actor-uid' => $uid]);
	}

	/** Returns the circle record for a given actor UID, or null. */
	public static function getByActorUid(int $uid): ?array
	{
		$row = DBA::selectFirst('udp-group-circle', [], ['actor-uid' => $uid]);
		return DBA::isResult($row) ? $row : null;
	}

	/** Returns the circle record by circle ID, or null. */
	public static function getById(int $id): ?array
	{
		$row = DBA::selectFirst('udp-group-circle', [], ['id' => $id]);
		return DBA::isResult($row) ? $row : null;
	}

	// ── Creation ──────────────────────────────────────────────────────────────

	/**
	 * Creates a new Group Circle.
	 * Provisions the AP actor user, inserts the circle record, adds the creator as co-owner.
	 *
	 * @param int    $creatorUid Local user UID of the creator
	 * @param string $name       Display name of the group
	 * @param string $description Optional description
	 * @return int  The new circle ID
	 */
	public static function create(int $creatorUid, string $name, string $description = ''): int
	{
		$baseUrl  = (string) DI::baseUrl();
		$domain   = parse_url($baseUrl, PHP_URL_HOST) ?? 'udp.social';
		$slug     = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name));
		$slug     = trim($slug, '_');
		$slug     = $slug ?: 'group';
		$slug     = substr($slug, 0, 28);
		$nickname = $slug;
		$n        = 1;
		while (DBA::exists('user', ['nickname' => $nickname]) || DBA::exists('userd', ['username' => $nickname])) {
			$nickname = $slug . '_' . $n++;
		}
		$password = bin2hex(random_bytes(32));
		$email    = $nickname . '@' . $domain;

		UdpDebug::log('[UdpGC] create() start', ['name' => $name, 'nick' => $nickname, 'email' => $email]);
		// Bypass Friendica's "First Last" full-name requirement for internal actor accounts
		$prevNoRegFullname = DI::config()->get('system', 'no_regfullname');
		UdpDebug::log('[UdpGC] no_regfullname before', ['value' => $prevNoRegFullname]);
		DI::config()->set('system', 'no_regfullname', true);
		try {
			UdpDebug::log('[UdpGC] calling User::create()');
			$result = User::create([
				'username'       => $name,
				'nickname'       => $nickname,
				'email'          => $email,
				'password'       => $password,
				'password1'      => $password,
				'confirm'        => $password,
				'verified'       => true,
				'ignore_invites' => true,
			]);
			UdpDebug::log('[UdpGC] User::create() OK', ['uid' => $result['user']['uid'] ?? null]);
		} catch (\Exception $e) {
			UdpDebug::log('[UdpGC] User::create() threw', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
			throw $e;
		} finally {
			DI::config()->set('system', 'no_regfullname', $prevNoRegFullname);
		}

		$actorUid = $result['user']['uid'] ?? 0;
		if (!$actorUid) {
			throw new \RuntimeException('UdpGroupCircle: failed to create actor user for "' . $name . '"');
		}

		// Upgrade to private AP Group actor
		DBA::update('user', [
			'account-type' => User::ACCOUNT_TYPE_COMMUNITY,
			'page-flags'   => User::PAGE_FLAGS_PRVGROUP,
			'hidewall'     => 1,
		], ['uid' => $actorUid]);

		DBA::update('contact', [
			'contact-type'      => Contact::TYPE_COMMUNITY,
			'manually-approve'  => 1,
		], ['uid' => $actorUid, 'self' => true]);

		DBA::insert('udp-group-circle', [
			'actor-uid'   => $actorUid,
			'creator-uid' => $creatorUid,
			'name'        => $name,
			'description' => $description,
			'created'     => DateTimeFormat::utcNow(),
		]);

		$circleId = DBA::lastInsertId();

		// Creator's self-contact becomes the first co-owner
		$creatorContact = Contact::selectFirst(['id'], ['uid' => $creatorUid, 'self' => true]);
		if (DBA::isResult($creatorContact)) {
			self::addMember($circleId, $creatorContact['id'], $creatorUid, self::ROLE_CO_OWNER);
		}

		// Wire up contact relationships so @mention autocomplete and post rendering work
		self::ensureGroupContactForUser($actorUid, $creatorUid);

		return $circleId;
	}

	// ── Membership ────────────────────────────────────────────────────────────

	/**
	 * Returns all Group Circles the given local user is a member of.
	 */
	public static function getMembershipsForUser(int $uid): array
	{
		$rows = DBA::selectToArray(
			'udp-group-circle-member',
			['circle-id'],
			['uid' => $uid]
		);
		if (empty($rows)) {
			return [];
		}

		$ids = array_column($rows, 'circle-id');
		return DBA::selectToArray('udp-group-circle', [], ['id' => $ids, 'closed' => null]);
	}

	/**
	 * Returns all members of a circle (rows from udp-group-circle-member joined with contact).
	 */
	public static function getMembers(int $circleId): array
	{
		$members = DBA::selectToArray('udp-group-circle-member', [], ['circle-id' => $circleId]);
		foreach ($members as &$m) {
			$contact = Contact::selectFirst(['name', 'nick', 'url', 'photo', 'addr'], ['id' => $m['contact-id']]);
			$m['contact'] = DBA::isResult($contact) ? $contact : [];
		}
		return $members;
	}

	/**
	 * Returns the membership row for a contact in a circle, or null if not a member.
	 */
	public static function getMembership(int $circleId, int $contactId): ?array
	{
		$row = DBA::selectFirst('udp-group-circle-member', [], ['circle-id' => $circleId, 'contact-id' => $contactId]);
		return DBA::isResult($row) ? $row : null;
	}

	/**
	 * Returns true if the given contact is a member of the circle.
	 * Used in tagDeliver to gate forwarding to group members only.
	 */
	public static function isMember(int $circleId, int $contactId): bool
	{
		return DBA::exists('udp-group-circle-member', ['circle-id' => $circleId, 'contact-id' => $contactId]);
	}

	/**
	 * Looks up a contact by AP author URL and returns true if they're a member.
	 */
	public static function isMemberByAuthorLink(int $circleId, string $authorLink): bool
	{
		$contact = Contact::selectFirst(['id'], ['url' => $authorLink]);
		if (!DBA::isResult($contact)) {
			return false;
		}
		return self::isMember($circleId, $contact['id']);
	}

	/** Returns true if the contact holds co-owner role in the circle. */
	public static function isCoOwner(int $circleId, int $contactId): bool
	{
		return DBA::exists('udp-group-circle-member', [
			'circle-id'  => $circleId,
			'contact-id' => $contactId,
			'role'       => self::ROLE_CO_OWNER,
		]);
	}

	/** Returns the number of co-owners in the circle. */
	public static function countCoOwners(int $circleId): int
	{
		return DBA::count('udp-group-circle-member', ['circle-id' => $circleId, 'role' => self::ROLE_CO_OWNER]);
	}

	/**
	 * Adds a member to the circle.
	 * Safe to call on re-add (updates role if already present).
	 */
	public static function addMember(int $circleId, int $contactId, ?int $uid = null, int $role = self::ROLE_MEMBER): void
	{
		$existing = DBA::selectFirst('udp-group-circle-member', ['id'], [
			'circle-id'  => $circleId,
			'contact-id' => $contactId,
		]);

		if (DBA::isResult($existing)) {
			DBA::update('udp-group-circle-member', ['role' => $role], ['id' => $existing['id']]);
			return;
		}

		DBA::insert('udp-group-circle-member', [
			'circle-id'  => $circleId,
			'contact-id' => $contactId,
			'uid'        => $uid,
			'role'       => $role,
			'joined'     => DateTimeFormat::utcNow(),
		]);
	}

	/**
	 * Removes a member from the circle.
	 * Blocks removal if they are the last co-owner.
	 *
	 * @return bool  true = removed, false = blocked (last co-owner)
	 */
	public static function removeMember(int $circleId, int $contactId): bool
	{
		if (self::isCoOwner($circleId, $contactId) && self::countCoOwners($circleId) <= 1) {
			return false;
		}

		DBA::delete('udp-group-circle-member', ['circle-id' => $circleId, 'contact-id' => $contactId]);

		// If the circle now has no members at all, close it
		if (DBA::count('udp-group-circle-member', ['circle-id' => $circleId]) === 0) {
			self::close($circleId);
		}

		return true;
	}

	/**
	 * Promotes a member to co-owner.
	 */
	public static function promoteToCoOwner(int $circleId, int $contactId): void
	{
		DBA::update('udp-group-circle-member', ['role' => self::ROLE_CO_OWNER], [
			'circle-id'  => $circleId,
			'contact-id' => $contactId,
		]);
	}

	// ── Invite flow ───────────────────────────────────────────────────────────

	/**
	 * Proposes adding $targetCid to the circle.
	 * Initialises the votes map with null (pending) for every current co-owner.
	 * The proposer's vote is pre-set to true.
	 *
	 * @return int  Invite ID
	 */
	public static function proposeInvite(int $circleId, int $proposerCid, int $targetCid): int
	{
		// Block if target is already a member
		if (self::isMember($circleId, $targetCid)) {
			throw new \InvalidArgumentException('Contact is already a member of this circle.');
		}

		// Block duplicate pending invite
		if (DBA::exists('udp-group-circle-invite', [
			'circle-id'  => $circleId,
			'target-cid' => $targetCid,
			'status'     => self::INVITE_PENDING,
		])) {
			throw new \InvalidArgumentException('A pending invite for this contact already exists.');
		}

		$coOwners = DBA::selectToArray('udp-group-circle-member', ['contact-id'], [
			'circle-id' => $circleId,
			'role'      => self::ROLE_CO_OWNER,
		]);

		$votes = [];
		foreach ($coOwners as $co) {
			$votes[$co['contact-id']] = ($co['contact-id'] === $proposerCid) ? true : null;
		}

		DBA::insert('udp-group-circle-invite', [
			'circle-id'   => $circleId,
			'proposed-by' => $proposerCid,
			'target-cid'  => $targetCid,
			'votes'       => json_encode($votes),
			'status'      => self::INVITE_PENDING,
			'created'     => DateTimeFormat::utcNow(),
			'expires'     => DateTimeFormat::utc('+7 days'),
		]);

		$inviteId = DBA::lastInsertId();

		// If the proposer is the only co-owner their vote is already true — resolve immediately.
		$allAccepted = !in_array(null, $votes, true) && !in_array(false, $votes, true);
		if ($allAccepted) {
			DBA::update('udp-group-circle-invite', ['status' => self::INVITE_ACCEPTED], ['id' => $inviteId]);
			$targetUidRow = Contact::selectFirst(['uid'], ['id' => $targetCid, 'self' => true]);
			$resolvedUid  = DBA::isResult($targetUidRow) ? (int)$targetUidRow['uid'] : null;
			self::addMember($circleId, $targetCid, $resolvedUid);
			self::notifyInviteTarget($circleId, $targetCid);
		}

		return $inviteId;
	}

	/**
	 * Records a co-owner's vote on an invite.
	 * If unanimous consent is reached, finalises the invite (adds the member).
	 *
	 * @param int  $inviteId     ID of the udp-group-circle-invite row
	 * @param int  $coOwnerCid   contact-id of the voting co-owner
	 * @param bool $accepted     true = accept, false = reject
	 */
	public static function voteOnInvite(int $inviteId, int $coOwnerCid, bool $accepted): void
	{
		$invite = DBA::selectFirst('udp-group-circle-invite', [], ['id' => $inviteId, 'status' => self::INVITE_PENDING]);
		if (!DBA::isResult($invite)) {
			return; // already resolved or not found
		}

		if (!self::isCoOwner($invite['circle-id'], $coOwnerCid)) {
			throw new \InvalidArgumentException('Only co-owners may vote on invites.');
		}

		$votes = json_decode($invite['votes'] ?? '{}', true);
		$votes[$coOwnerCid] = $accepted;
		DBA::update('udp-group-circle-invite', ['votes' => json_encode($votes)], ['id' => $inviteId]);

		// Any rejection immediately kills the invite
		if (!$accepted) {
			DBA::update('udp-group-circle-invite', ['status' => self::INVITE_REJECTED], ['id' => $inviteId]);
			return;
		}

		// Check if all co-owners have now accepted
		$allAccepted = true;
		foreach ($votes as $vote) {
			if ($vote !== true) {
				$allAccepted = false;
				break;
			}
		}

		if ($allAccepted) {
			DBA::update('udp-group-circle-invite', ['status' => self::INVITE_ACCEPTED], ['id' => $inviteId]);
			$invite          = DBA::selectFirst('udp-group-circle-invite', [], ['id' => $inviteId]);
			$targetUidRow    = Contact::selectFirst(['uid'], ['id' => $invite['target-cid'], 'self' => true]);
			$resolvedUid     = DBA::isResult($targetUidRow) ? (int)$targetUidRow['uid'] : null;
			self::addMember($invite['circle-id'], $invite['target-cid'], $resolvedUid);
			self::notifyInviteTarget($invite['circle-id'], $invite['target-cid']);
		}
	}

	/**
	 * Notifies the invite target (if local) that they've been invited to join the group.
	 * Uses a Follow-type notification so it surfaces in the target's notification list.
	 * The target must then follow the group actor to complete AP membership.
	 */
	private static function notifyInviteTarget(int $circleId, int $targetCid): void
	{
		$circle = self::getById($circleId);
		if (!$circle) {
			UdpDebug::log('[UdpGC] notifyInviteTarget: circle not found', ['circleId' => $circleId]);
			return;
		}

		$targetContact = Contact::selectFirst(['url'], ['id' => $targetCid]);
		if (!DBA::isResult($targetContact)) {
			UdpDebug::log('[UdpGC] notifyInviteTarget: target contact not found', ['targetCid' => $targetCid]);
			return;
		}
		$targetUid = User::getIdForURL($targetContact['url']);
		if (!$targetUid) {
			UdpDebug::log('[UdpGC] notifyInviteTarget: remote user, skipping', ['url' => $targetContact['url']]);
			return;
		}

		$actorOwner = User::getOwnerDataById($circle['actor-uid']);
		if (!$actorOwner) {
			UdpDebug::log('[UdpGC] notifyInviteTarget: actor owner not found', ['actorUid' => $circle['actor-uid']]);
			return;
		}
		$actorCid = Contact::getIdForURL($actorOwner['url'], 0);
		if (!$actorCid) {
			UdpDebug::log('[UdpGC] notifyInviteTarget: actor global contact not found', ['url' => $actorOwner['url']]);
			return;
		}

		// Ensure the invited member has a contact row for the group actor
		self::ensureGroupContactForUser($circle['actor-uid'], $targetUid);

		// Look up the contact as seen from the target's account (for notification cid)
		$actorCidForTarget = Contact::getIdForURL($actorOwner['url'], $targetUid, false);
		UdpDebug::log('[UdpGC] notifyInviteTarget: firing', ['actorCidGlobal' => $actorCid, 'actorCidForTarget' => $actorCidForTarget, 'targetUid' => $targetUid]);

		// Write to the notify table so it shows in the nav bell.
		DI::notify()->createFromArray([
			'type'  => NotificationType::INTRO,
			'otype' => NotificationObjectType::INTRO,
			'verb'  => Activity::FOLLOW,
			'uid'   => $targetUid,
			'cid'   => $actorCidForTarget ?: $actorCid,
			'link'  => (string) DI::baseUrl() . '/udp/group/' . $circleId,
		]);
		UdpDebug::log('[UdpGC] notifyInviteTarget: done');
	}

	/** Returns pending invites for a circle (for the members management page). */
	public static function getPendingInvites(int $circleId): array
	{
		$invites = DBA::selectToArray('udp-group-circle-invite', [], [
			'circle-id' => $circleId,
			'status'    => self::INVITE_PENDING,
		]);

		foreach ($invites as &$inv) {
			$target = Contact::selectFirst(['name', 'addr', 'photo', 'url'], ['id' => $inv['target-cid']]);
			$inv['target'] = DBA::isResult($target) ? $target : [];
			$inv['votes']  = json_decode($inv['votes'] ?? '{}', true);
		}

		return $invites;
	}

	// ── Lifecycle ─────────────────────────────────────────────────────────────

	// ── Internal helpers ──────────────────────────────────────────────────────

	/**
	 * Ensures the uid=0 global contact exists for the group actor (needed for
	 * @addr mention rendering in posts) and creates an active per-user contact
	 * for $memberUid (needed for autocomplete and @nick+id mention format).
	 * Inserts directly — no AP Follow activity is sent.
	 */
	private static function ensureGroupContactForUser(int $actorUid, int $memberUid): void
	{
		$actorSelf = Contact::selectFirst(
			['url', 'nurl', 'name', 'nick', 'addr', 'photo', 'thumb', 'micro', 'uri-id'],
			['uid' => $actorUid, 'self' => true]
		);
		if (!DBA::isResult($actorSelf)) {
			UdpDebug::log('[UdpGC] ensureGroupContactForUser: actor self-contact not found', ['actorUid' => $actorUid]);
			return;
		}

		// Ensure uid=0 global contact exists so @addr resolves to display name in post rendering
		Contact::getIdForURL($actorSelf['url'], 0, true);

		// Check if a live (non-archived, non-deleted) per-user contact already exists.
		// The archive check is critical: close() archives old contacts before freeing the slug,
		// so an archived row for a previous group with the same nickname must not block this.
		$nurl = $actorSelf['nurl'] ?: Strings::normaliseLink($actorSelf['url']);
		if (DBA::exists('contact', ['uid' => $memberUid, 'nurl' => $nurl, 'deleted' => false, 'archive' => false])) {
			return;
		}

		DBA::insert('contact', [
			'uid'               => $memberUid,
			'created'           => DateTimeFormat::utcNow(),
			'network'           => Protocol::ACTIVITYPUB,
			'name'              => $actorSelf['name'],
			'nick'              => $actorSelf['nick'],
			'addr'              => $actorSelf['addr'],
			'url'               => $actorSelf['url'],
			'nurl'              => $nurl,
			'uri-id'            => $actorSelf['uri-id'],
			'photo'             => $actorSelf['photo'],
			'thumb'             => $actorSelf['thumb'],
			'micro'             => $actorSelf['micro'],
			'contact-type'      => Contact::TYPE_COMMUNITY,
			'rel'               => Contact::SHARING,
			'notify_new_posts'  => true,
			'pending'           => false,
			'archive'           => false,
			'blocked'           => false,
			'deleted'           => false,
			'manually-approve'  => false,
		]);

		UdpDebug::log('[UdpGC] ensureGroupContactForUser: member→actor contact created', ['actorUid' => $actorUid, 'memberUid' => $memberUid]);

		// Also create the reverse contact: group actor → member with rel=FOLLOWER.
		// Without this, the group actor's Followers circle is empty and the Announce
		// fan-out in tagDeliver delivers to nobody.
		$memberSelf = Contact::selectFirst(
			['url', 'nurl', 'name', 'nick', 'addr', 'photo', 'thumb', 'micro', 'uri-id'],
			['uid' => $memberUid, 'self' => true]
		);
		if (!DBA::isResult($memberSelf)) {
			return;
		}

		$memberNurl = $memberSelf['nurl'] ?: Strings::normaliseLink($memberSelf['url']);
		if (!DBA::exists('contact', ['uid' => $actorUid, 'nurl' => $memberNurl, 'deleted' => false, 'archive' => false])) {
			DBA::insert('contact', [
				'uid'              => $actorUid,
				'created'          => DateTimeFormat::utcNow(),
				'network'          => Protocol::ACTIVITYPUB,
				'name'             => $memberSelf['name'],
				'nick'             => $memberSelf['nick'],
				'addr'             => $memberSelf['addr'],
				'url'              => $memberSelf['url'],
				'nurl'             => $memberNurl,
				'uri-id'           => $memberSelf['uri-id'],
				'photo'            => $memberSelf['photo'],
				'thumb'            => $memberSelf['thumb'],
				'micro'            => $memberSelf['micro'],
				'contact-type'     => Contact::TYPE_PERSON,
				'rel'              => Contact::FOLLOWER,
				'pending'          => false,
				'archive'          => false,
				'blocked'          => false,
				'deleted'          => false,
				'manually-approve' => false,
			]);
			UdpDebug::log('[UdpGC] ensureGroupContactForUser: actor→member (FOLLOWER) contact created', ['actorUid' => $actorUid, 'memberUid' => $memberUid]);
		}
	}

	/**
	 * Closes a circle: stamps the closed datetime and notifies remaining members.
	 * The actor user is left intact so AP tombstone handling works correctly.
	 */
	public static function close(int $circleId): void
	{
		$circle = self::getById($circleId);
		if ($circle) {
			// Archive per-user contacts pointing to this actor's URL before freeing the slug.
			// Without this, a new group that reuses the same nickname would find stale contact
			// rows in ensureGroupContactForUser and skip creating fresh ones.
			$actorSelf = Contact::selectFirst(['url'], ['uid' => $circle['actor-uid'], 'self' => true]);
			if (DBA::isResult($actorSelf)) {
				DBA::update('contact', ['archive' => true], [
					'url'     => $actorSelf['url'],
					'self'    => false,
					'deleted' => false,
				]);
			}
			// Free the slug so it can be reused.
			DBA::update('user', ['nickname' => '_deleted_' . $circleId], ['uid' => $circle['actor-uid']]);
		}

		DBA::update('udp-group-circle', ['closed' => DateTimeFormat::utcNow()], ['id' => $circleId]);

		// TODO: send "this group has been closed" system notification to all members
	}
}
