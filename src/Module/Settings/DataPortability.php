<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Object\Image;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Data portability page: export and recover user data including all media.
 *
 * Export actions (GET, via rawContent):
 *   /settings/data-portability/data   – JSON dump of profile, posts, contacts, etc.
 *   /settings/data-portability/media  – ZIP of all photos/albums
 *   /settings/data-portability/full   – ZIP containing both data.json and all media
 *
 * Import (POST):
 *   Accepts a media ZIP previously exported from this page and restores the
 *   photos into the logged-in user's account, preserving album structure.
 */
class DataPortability extends BaseSettings
{
	private DbaDefinition $dbaDefinition;
	private SystemMessages $systemMessages;

	public function __construct(
		DbaDefinition $dbaDefinition,
		SystemMessages $systemMessages,
		IHandleUserSessions $session,
		App\Page $page,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = []
	) {
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dbaDefinition = $dbaDefinition;
		$this->systemMessages = $systemMessages;
	}

	// -------------------------------------------------------------------------
	// POST – handle media ZIP import
	// -------------------------------------------------------------------------

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\ForbiddenException($this->l10n->t('Permission denied.'));
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/data-portability', 'data_portability');

		if (!empty($_FILES['import_media']) && $_FILES['import_media']['error'] === UPLOAD_ERR_OK) {
			$this->importMediaZip($_FILES['import_media']);
		} elseif (!empty($_FILES['import_media'])) {
			$this->systemMessages->addNotice($this->l10n->t('Upload error. Please try again.'));
		}

		$this->baseUrl->redirect('settings/data-portability');
	}

	// -------------------------------------------------------------------------
	// rawContent – stream export files directly to the browser
	// -------------------------------------------------------------------------

	protected function rawContent(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\ForbiddenException($this->l10n->t('Permission denied.'));
		}

		if (empty($this->parameters['action'])) {
			return;
		}

		self::checkFormSecurityTokenForbiddenOnError('data_portability', 't');

		$uid      = $this->session->getLocalUserId();
		$nickname = $this->session->getLocalUserNickname();

		switch ($this->parameters['action']) {
			case 'data':
				header('Content-type: application/json');
				header('Content-Disposition: attachment; filename="' . $nickname . '-data.json"');
				$this->streamDataJson($uid);
				System::exit();
				break;

			case 'media':
				$this->streamZip($uid, $nickname . '-media.zip', false);
				System::exit();
				break;

			case 'full':
				$this->streamZip($uid, $nickname . '-full-export.zip', true);
				System::exit();
				break;
		}
	}

	// -------------------------------------------------------------------------
	// content – render the portability settings page
	// -------------------------------------------------------------------------

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\ForbiddenException($this->l10n->t('Permission denied.'));
		}

		parent::content();

		$uid   = $this->session->getLocalUserId();
		$t     = self::getFormSecurityToken('data_portability');

		// Stats for the UI
		$albums     = Photo::getBrowsableAlbumsForUser($uid);
		$photoCount = DBA::count('photo', ['uid' => $uid, 'scale' => 0]);
		$postCount  = Post::count(['uid' => $uid]);

		$tpl = Renderer::getMarkupTemplate('settings/data_portability.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'             => $this->l10n->t('Data Portability'),

			// Export section
			'$export_title'      => $this->l10n->t('Export Your Data'),
			'$export_intro'      => $this->l10n->t('Download copies of your Friendica data for backup or migration purposes.'),
			'$export_data_label' => $this->l10n->t('Export data (JSON)'),
			'$export_data_desc'  => $this->l10n->t('Profile, posts, contacts, circles, and settings as a JSON file. Does not include media files.'),
			'$export_data_url'   => 'settings/data-portability/data?t=' . $t,
			'$export_media_label'=> $this->l10n->t('Export media (ZIP)'),
			'$export_media_desc' => $this->l10n->t('All your photos and photo albums as a ZIP archive (%d photos across %d album(s)).', $photoCount, count($albums)),
			'$export_media_url'  => 'settings/data-portability/media?t=' . $t,
			'$export_full_label' => $this->l10n->t('Export everything (ZIP)'),
			'$export_full_desc'  => $this->l10n->t('Profile, posts, contacts, and all media files packaged together. May be very large.'),
			'$export_full_url'   => 'settings/data-portability/full?t=' . $t,

			// Import/restore section
			'$import_title'      => $this->l10n->t('Restore Media'),
			'$import_intro'      => $this->l10n->t('Restore photos from a previously exported media or full ZIP archive. Albums and filenames are preserved.'),
			'$import_warn'       => $this->l10n->t('This restores only photos. To migrate your entire account to a new server use the Account Export/Import instead.'),
			'$import_field'      => ['import_media', $this->l10n->t('Media ZIP file'), '', $this->l10n->t('Select a .zip file exported using "Export media" or "Export everything" above.')],
			'$submit'            => $this->l10n->t('Restore Photos'),
			'$form_security_token' => self::getFormSecurityToken('data_portability'),

			// Links to existing account-level export/import
			'$account_links_title'   => $this->l10n->t('Account Migration'),
			'$account_export_label'  => $this->l10n->t('Export account (for server migration)'),
			'$account_export_url'    => 'settings/userexport',
			'$account_import_label'  => $this->l10n->t('Import account (move from another server)'),
			'$account_import_url'    => 'user/import',
		]);
	}

	// -------------------------------------------------------------------------
	// Export helpers
	// -------------------------------------------------------------------------

	/**
	 * Stream a JSON export of all user data (profile, contacts, posts, etc.)
	 * to the current output buffer.  Format is newline-delimited JSON:
	 *   line 1 – account/profile/contact/circle/pconfig record
	 *   line 2+ – batches of 500 posts
	 */
	private function streamDataJson(int $uid): void
	{
		// Account-level data
		$user = $this->exportRow(
			sprintf("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1", $uid)
		);

		$contact = $this->exportMultiRow(
			sprintf("SELECT * FROM `contact` WHERE `uid` = %d", $uid)
		);

		$profile = $this->exportMultiRow(
			sprintf("SELECT *, 'default' AS `profile_name`, 1 AS `is-default` FROM `profile` WHERE `uid` = %d", $uid)
		);

		$profile_fields = $this->exportMultiRow(
			sprintf("SELECT * FROM `profile_field` WHERE `uid` = %d", $uid)
		);

		$photo = $this->exportMultiRow(
			sprintf("SELECT * FROM `photo` WHERE `uid` = %d AND `profile` = 1", $uid)
		);
		foreach ($photo as &$p) {
			$p['data'] = bin2hex($p['data'] ?? '');
		}
		unset($p);

		$pconfig = $this->exportMultiRow(
			sprintf("SELECT * FROM `pconfig` WHERE `uid` = %d", $uid)
		);

		$circle = $this->exportMultiRow(
			sprintf("SELECT * FROM `group` WHERE `uid` = %d", $uid)
		);

		$circle_member = $this->exportMultiRow(
			sprintf(
				"SELECT `cm`.`gid`, `cm`.`contact-id`
				 FROM `group_member` AS `cm`
				 INNER JOIN `group` AS `g` ON `g`.`id` = `cm`.`gid`
				 WHERE `g`.`uid` = %d",
				$uid
			)
		);

		$header = [
			'version'        => App::VERSION,
			'schema'         => DB_UPDATE_VERSION,
			'baseurl'        => (string)$this->baseUrl,
			'user'           => $user,
			'contact'        => $contact,
			'profile'        => $profile,
			'profile_fields' => $profile_fields,
			'photo'          => $photo,
			'pconfig'        => $pconfig,
			'circle'         => $circle,
			'circle_member'  => $circle_member,
		];

		echo json_encode($header, JSON_PARTIAL_OUTPUT_ON_ERROR);
		echo "\n";

		// Posts in chunks to avoid memory exhaustion
		$total = Post::count(['uid' => $uid]);
		for ($offset = 0; $offset < $total; $offset += 500) {
			$items  = Post::selectToArray(Item::ITEM_FIELDLIST, ['uid' => $uid], ['limit' => [$offset, 500]]);
			$output = ['item' => $items];
			echo json_encode($output, JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n";
		}
	}

	/**
	 * Build and stream a ZIP archive.
	 *
	 * @param int    $uid         User ID
	 * @param string $filename    Suggested download filename
	 * @param bool   $includeData Whether to include a data.json inside the ZIP
	 */
	private function streamZip(int $uid, string $filename, bool $includeData): void
	{
		if (!class_exists('\ZipArchive')) {
			throw new HTTPException\InternalServerErrorException(
				$this->l10n->t('ZIP support is not available on this server (ZipArchive extension missing).')
			);
		}

		$tmpFile = tempnam(sys_get_temp_dir(), 'friendica_export_');

		try {
			$zip = new \ZipArchive();
			if ($zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
				throw new HTTPException\InternalServerErrorException(
					$this->l10n->t('Could not create export archive.')
				);
			}

			// Optionally embed the data JSON
			if ($includeData) {
				ob_start();
				$this->streamDataJson($uid);
				$jsonContent = ob_get_clean();
				$zip->addFromString('data.json', $jsonContent);
			}

			// Add a README so the ZIP is self-documenting
			$zip->addFromString('README.txt', $this->buildReadme($includeData));

			// Export photos: only scale 0 (original) to keep file sizes reasonable
			$photos = DBA::select(
				'photo',
				['id', 'resource-id', 'album', 'filename', 'type', 'scale', 'backend-class', 'backend-ref', 'data', 'created'],
				['uid' => $uid, 'scale' => 0]
			);

			while ($photo = DBA::fetch($photos)) {
				$data = Photo::getImageDataForPhoto($photo);
				if ($data === null) {
					$this->logger->warning('DataPortability: could not retrieve photo data', ['resource-id' => $photo['resource-id']]);
					continue;
				}

				$album = $this->safePathSegment($photo['album'] ?: 'Uncategorized');
				$ext   = Images::getExtensionByMimeType($photo['type'] ?: 'image/jpeg');
				// Use resource-id as filename for unambiguous round-trip restore.
				// A sidecar .txt stores the original human-readable filename.
				$resourceId = $photo['resource-id'];
				$entryName  = 'media/photos/' . $album . '/' . $resourceId . '.' . $ext;

				$zip->addFromString($entryName, $data);

				// Embed original filename as a sidecar so users can inspect the archive
				if (!empty($photo['filename'])) {
					$zip->addFromString(
						'media/photos/' . $album . '/' . $resourceId . '.name.txt',
						$photo['filename']
					);
				}
			}
			DBA::close($photos);

			$zip->close();

			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . filesize($tmpFile));
			header('Cache-Control: no-cache, must-revalidate');

			readfile($tmpFile);
		} finally {
			if (file_exists($tmpFile)) {
				unlink($tmpFile);
			}
		}
	}

	// -------------------------------------------------------------------------
	// Import helpers
	// -------------------------------------------------------------------------

	/**
	 * Import photos from a ZIP archive previously created by streamZip().
	 * Photos are stored under media/photos/{album}/{filename} inside the ZIP.
	 * Existing photos with the same resource-id are skipped.
	 */
	private function importMediaZip(array $file): void
	{
		if (!class_exists('\ZipArchive')) {
			$this->systemMessages->addNotice($this->l10n->t('ZIP support is not available on this server (ZipArchive extension missing).'));
			return;
		}

		$uid = $this->session->getLocalUserId();

		$zip = new \ZipArchive();
		$result = $zip->open($file['tmp_name']);
		if ($result !== true) {
			$this->systemMessages->addNotice($this->l10n->t('Could not open the uploaded ZIP file (error %d).', $result));
			return;
		}

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entryName = $zip->getNameIndex($i);

			// Only process photo entries
			if (!str_starts_with($entryName, 'media/photos/')) {
				continue;
			}

			// Parse album and filename from path: media/photos/{album}/{filename.ext}
			$relative = substr($entryName, strlen('media/photos/'));
			$parts    = explode('/', $relative, 2);
			if (count($parts) !== 2 || $parts[1] === '') {
				continue; // directory entry or malformed path
			}

			[$album, $basename] = $parts;
			$album = trim($album);

			// Skip sidecar .name.txt files — they are metadata, not images
			if (str_ends_with($basename, '.name.txt')) {
				continue;
			}

			$data = $zip->getFromIndex($i);
			if ($data === false) {
				$this->logger->warning('DataPortability import: could not read ZIP entry', ['entry' => $entryName]);
				$errors++;
				continue;
			}

			$mimeType = Images::getMimeTypeByData($data);
			if (empty($mimeType)) {
				$skipped++;
				continue; // not a recognised image
			}

			// The filename stem is the original resource-id (set during export)
			$resourceId = pathinfo($basename, PATHINFO_FILENAME);

			// Try to read the original human-readable filename from the sidecar
			$sidecar = 'media/photos/' . $album . '/' . $resourceId . '.name.txt';
			$origFilename = $zip->getFromName($sidecar) ?: $basename;

			// Skip if a photo with this resource-id already exists for this user
			if (Photo::exists(['uid' => $uid, 'resource-id' => $resourceId])) {
				$skipped++;
				continue;
			}

			$image = new Image($data, $mimeType, $origFilename);

			$stored = Photo::store(
				$image,
				$uid,
				0,              // contact-id (0 = self/owner)
				$resourceId,
				$origFilename,
				$album,
				0,              // scale 0 = original
				Photo::DEFAULT  // not a profile/banner photo
			);

			if ($stored === false) {
				$this->logger->warning('DataPortability import: failed to store photo', ['resource-id' => $resourceId, 'album' => $album]);
				$errors++;
			} else {
				$imported++;
			}
		}

		$zip->close();

		if ($imported > 0) {
			$this->systemMessages->addInfo(
				$this->l10n->tt('%d photo restored.', '%d photos restored.', $imported)
			);
		}
		if ($skipped > 0) {
			$this->systemMessages->addInfo(
				$this->l10n->tt('%d photo skipped (already exists).', '%d photos skipped (already exist).', $skipped)
			);
		}
		if ($errors > 0) {
			$this->systemMessages->addNotice(
				$this->l10n->tt('%d photo could not be restored.', '%d photos could not be restored.', $errors)
			);
		}
		if ($imported === 0 && $skipped === 0 && $errors === 0) {
			$this->systemMessages->addNotice($this->l10n->t('No photos found in the uploaded archive.'));
		}
	}

	// -------------------------------------------------------------------------
	// DB export utilities (mirrors UserExport private methods)
	// -------------------------------------------------------------------------

	private function exportMultiRow(string $query): array
	{
		$dbStructure = $this->dbaDefinition->getAll();

		preg_match('/\s+from\s+`?([a-z\d_]+)`?/i', $query, $match);
		$table = $match[1];

		$result = [];
		$rows   = DBA::p($query);
		while ($row = DBA::fetch($rows)) {
			$p = [];
			foreach ($dbStructure[$table]['fields'] as $column => $field) {
				if (!isset($row[$column])) {
					continue;
				}
				$p[$column] = $row[$column];
			}
			$result[] = $p;
		}
		DBA::close($rows);

		return $result;
	}

	private function exportRow(string $query): array
	{
		$dbStructure = $this->dbaDefinition->getAll();

		preg_match('/\s+from\s+`?([a-z\d_]+)`?/i', $query, $match);
		$table = $match[1];

		$result = [];
		$rows   = DBA::p($query);
		while ($row = DBA::fetch($rows)) {
			foreach ($row as $k => $v) {
				if (empty($dbStructure[$table]['fields'][$k])) {
					continue;
				}
				$result[$k] = ($dbStructure[$table]['fields'][$k]['type'] === 'datetime')
					? ($v ?? DBA::NULL_DATETIME)
					: $v;
			}
		}
		DBA::close($rows);

		return $result;
	}

	// -------------------------------------------------------------------------
	// Misc helpers
	// -------------------------------------------------------------------------

	/** Strip characters that would be unsafe as a ZIP path segment. */
	private function safePathSegment(string $name): string
	{
		// Replace directory separators and control characters
		$name = str_replace(['/', '\\', "\0"], '_', $name);
		// Collapse runs of non-printable/reserved chars
		$name = preg_replace('/[^\x20-\x7E]/', '_', $name);
		return trim($name) ?: 'unnamed';
	}

	private function buildReadme(bool $includeData): string
	{
		$lines = [
			'Friendica Data Export',
			'=====================',
			'',
			'Generated: ' . date('Y-m-d H:i:s') . ' UTC',
			'Friendica version: ' . App::VERSION,
			'',
			'Contents',
			'--------',
		];

		if ($includeData) {
			$lines[] = '  data.json            – Profile, contacts, posts, circles, and settings';
			$lines[] = '                         Compatible with the Friendica account import at /user/import';
		}

		$lines[] = '  media/photos/{album}/ – Original photos organised by album';
		$lines[] = '';
		$lines[] = 'Restore photos via Settings → Data Portability → Restore Media.';

		if ($includeData) {
			$lines[] = 'Restore full account via /user/import (new server) or Settings → Data Portability.';
		}

		return implode("\n", $lines) . "\n";
	}
}
