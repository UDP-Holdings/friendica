<?php

// UDP Social — unified media index model (Cat2 — new file)

namespace Friendica\Model;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * CRUD and query layer for the udp-media table.
 *
 * udp-media is a thin index that sits above the upstream `photo` and `attach`
 * tables. It adds album assignment and video-thumbnail references without
 * touching either upstream table.
 */
class UdpMedia
{
	const TYPE_PHOTO = 'photo';
	const TYPE_VIDEO = 'video';
	const TYPE_AUDIO = 'audio';

	const REF_PHOTO  = 'photo';
	const REF_ATTACH = 'attach';

	/**
	 * Insert a new udp-media record. Called immediately after a successful
	 * photo or attach store so every upload is indexed from the start.
	 *
	 * @param int    $uid        Owner user id
	 * @param string $mediaType  One of the TYPE_* constants
	 * @param string $refTable   One of the REF_* constants
	 * @param int    $refId      id in the source table
	 * @param string $resourceId      photo resource-id (for photo rows), otherwise ''
	 * @param string $album           Initial album name; empty = unorganized
	 * @param string $thumbResourceId photo resource-id of the video thumbnail; '' if none
	 * @return int|false  Insert id or false on failure
	 */
	public static function create(
		int $uid,
		string $mediaType,
		string $refTable,
		int $refId,
		string $resourceId = '',
		string $album = '',
		string $thumbResourceId = ''
	) {
		$fields = [
			'uid'              => $uid,
			'media-type'       => $mediaType,
			'ref-table'        => $refTable,
			'ref-id'           => $refId,
			'resource-id'      => $resourceId,
			'album'            => $album,
			'thumb-resource-id'=> $thumbResourceId,
			'created'          => DateTimeFormat::utcNow(),
		];

		if (!DBA::insert('udp-media', $fields)) {
			return false;
		}
		return DBA::lastInsertId();
	}

	/**
	 * Attach a video thumbnail to an existing udp-media row.
	 *
	 * @param int    $id              udp-media row id
	 * @param int    $uid             Must match the row's uid (ownership check)
	 * @param string $thumbResourceId photo resource-id of the thumbnail image
	 * @return bool
	 */
	public static function setThumb(int $id, int $uid, string $thumbResourceId): bool
	{
		return DBA::update(
			'udp-media',
			['thumb-resource-id' => $thumbResourceId],
			['id' => $id, 'uid' => $uid]
		);
	}

	/**
	 * Move one or more media items to an album.
	 *
	 * @param array  $ids  udp-media row ids
	 * @param int    $uid  Owner (used as a safety condition)
	 * @param string $album Target album name ('' = unorganized)
	 * @return bool
	 */
	public static function setAlbum(array $ids, int $uid, string $album): bool
	{
		if (empty($ids)) {
			return true;
		}
		return DBA::update(
			'udp-media',
			['album' => $album],
			['id' => $ids, 'uid' => $uid]
		);
	}

	/**
	 * Delete a udp-media row (does NOT touch the underlying photo/attach record).
	 */
	public static function delete(int $id, int $uid): bool
	{
		return DBA::delete('udp-media', ['id' => $id, 'uid' => $uid]);
	}

	/**
	 * Return a flat list of media items for a user, newest first.
	 * Optionally filtered to a specific album.
	 *
	 * Each row is enriched with display fields sourced from the underlying
	 * photo or attach table:
	 *   - thumb_url  : URL for a 150×150-ish thumbnail
	 *   - media_url  : URL of the full-size file
	 *   - filename   : human-readable name
	 *
	 * @param int         $uid
	 * @param string|null $album  null = all albums; '' = unorganized only
	 * @param int         $limit
	 * @param int         $offset
	 * @return array
	 */
	public static function listForUser(int $uid, ?string $album = null, int $limit = 200, int $offset = 0): array
	{
		$conditions = ['uid' => $uid];
		if ($album !== null) {
			$conditions['album'] = $album;
		}

		$rows = DBA::toArray(DBA::select(
			'udp-media',
			[],
			$conditions,
			['order' => ['created' => true], 'limit' => [$offset, $limit]]
		));

		if (empty($rows)) {
			return [];
		}

		$baseUrl  = (string) DI::baseUrl();
		$nickname = DI::userSession()->getLocalUserNickname() ?? '';

		foreach ($rows as &$row) {
			if ($row['ref-table'] === self::REF_PHOTO) {
				$rid = $row['resource-id'];
				// Scale 2 is the 320px preview; scale 0 is full-size
				$row['thumb_url'] = $baseUrl . '/photo/' . $rid . '-2';
				$row['media_url'] = $baseUrl . '/photo/' . $rid . '-0';
				$photo = DBA::selectFirst('photo', ['filename'], ['resource-id' => $rid, 'uid' => $uid]);
				$row['filename'] = $photo['filename'] ?? $rid;
			} else {
				// attach row — thumb from thumb-resource-id if set, else a generic icon placeholder
				if (!empty($row['thumb-resource-id'])) {
					$row['thumb_url'] = $baseUrl . '/photo/' . $row['thumb-resource-id'] . '-2';
				} else {
					$row['thumb_url'] = '';
				}
				$row['media_url'] = $baseUrl . '/attach/' . $row['ref-id'];
				$attach = DBA::selectFirst('attach', ['filename', 'filetype'], ['id' => $row['ref-id'], 'uid' => $uid]);
				$row['filename'] = $attach['filename'] ?? ('attach-' . $row['ref-id']);
				$row['filetype'] = $attach['filetype'] ?? '';
			}
		}
		unset($row);

		return $rows;
	}

	/**
	 * Return distinct album names for a user, sorted alphabetically.
	 * An empty-string album is excluded — it represents the "unorganized" pool.
	 *
	 * @param int $uid
	 * @return string[]
	 */
	public static function getAlbums(int $uid): array
	{
		$stmt = DBA::p(
			"SELECT DISTINCT `album` FROM `udp-media` WHERE `uid` = ? AND `album` != '' ORDER BY `album`",
			$uid
		);
		$rows = DBA::toArray($stmt);
		return array_column($rows, 'album');
	}

	/**
	 * Group a flat listForUser() result into date buckets.
	 * Returns [ ['label' => 'May 2026', 'items' => [...]], ... ]
	 *
	 * @param array $rows  Output from listForUser()
	 * @return array
	 */
	public static function groupByDate(array $rows): array
	{
		$groups = [];
		foreach ($rows as $row) {
			$label = date('F Y', strtotime($row['created']));
			if (!isset($groups[$label])) {
				$groups[$label] = ['label' => $label, 'items' => []];
			}
			$groups[$label]['items'][] = $row;
		}
		return array_values($groups);
	}
}
