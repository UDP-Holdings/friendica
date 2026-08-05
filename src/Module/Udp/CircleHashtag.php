<?php

// UDP Social — hashtag filters for contact circles
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;

class CircleHashtag extends BaseModule
{
	protected function post(array $request = [])
	{
		$uid      = DI::userSession()->getLocalUserId();
		$circleId = (int)($this->parameters['circle'] ?? 0);

		if (!$uid || !$circleId) {
			DI::baseUrl()->redirect('circle');
		}

		if (!DBA::exists('group', ['id' => $circleId, 'uid' => $uid, 'deleted' => false])) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Circle not found.'));
			DI::baseUrl()->redirect('circle');
		}

		BaseModule::checkFormSecurityTokenRedirectOnError('/circle/' . $circleId . '/hashtags', 'circle_hashtag_edit');

		$action = trim($request['action'] ?? '');
		$tag    = strtolower(ltrim(trim($request['tag'] ?? ''), '#'));
		$tag    = preg_replace('/[^a-z0-9_\-]/u', '', $tag);

		if ($action === 'add' && $tag !== '') {
			DBA::insert('udp-circle-hashtag', ['uid' => $uid, 'circle-id' => $circleId, 'tag' => $tag], true);
		} elseif ($action === 'remove' && $tag !== '') {
			DBA::delete('udp-circle-hashtag', ['uid' => $uid, 'circle-id' => $circleId, 'tag' => $tag]);
		}

		DI::baseUrl()->redirect('circle/' . $circleId . '/hashtags');
	}

	protected function content(array $request = []): string
	{
		$uid      = DI::userSession()->getLocalUserId();
		$circleId = (int)($this->parameters['circle'] ?? 0);

		if (!$uid) {
			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}

		$circle = DBA::selectFirst('group', ['id', 'name'], ['id' => $circleId, 'uid' => $uid, 'deleted' => false]);
		if (!DBA::isResult($circle)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Circle not found.'));
			DI::baseUrl()->redirect('circle');
		}

		$tags = DBA::selectToArray('udp-circle-hashtag', ['tag'], ['uid' => $uid, 'circle-id' => $circleId]);
		$tags = array_column($tags, 'tag');
		sort($tags);

		$tpl = Renderer::getMarkupTemplate('udp/circle_hashtags.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'               => DI::l10n()->t('Hashtag filters for %s', $circle['name']),
			'$circle'              => $circle,
			'$tags'                => $tags,
			'$back_url'            => 'circle/' . $circleId,
			'$form_security_token' => BaseModule::getFormSecurityToken('circle_hashtag_edit'),
			'$label_add'           => DI::l10n()->t('Add'),
			'$label_remove'        => DI::l10n()->t('Remove'),
			'$label_tag'           => DI::l10n()->t('Hashtag (without #)'),
			'$label_empty'         => DI::l10n()->t('No hashtag filters yet. Posts matching a filter will appear in this circle\'s feed and be hidden from your main timeline.'),
		]);
	}
}
