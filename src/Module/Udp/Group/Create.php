<?php

// UDP Social — Group creation
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\UdpGroupCircle;
use Friendica\Network\HTTPException;
use Friendica\Util\UdpDebug;

/**
 * GET  /udp/group/create — show the creation form
 * POST /udp/group/create — create the group
 */
class Create extends BaseModule
{
	protected function post(array $request = []): void
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException();
		}

		self::checkFormSecurityTokenRedirectOnError('/udp/group/create', 'udp_group_create');

		$name        = trim($request['name'] ?? '');
		$description = trim($request['description'] ?? '');

		if (!$name) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter a name for the Group.'));
			DI::baseUrl()->redirect('udp/group/create');
		}

		if (mb_strlen($name) > 255) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Name is too long (max 255 characters).'));
			DI::baseUrl()->redirect('udp/group/create');
		}

		$uid = DI::userSession()->getLocalUserId();

		UdpDebug::log('[Create] post() reached', ['uid' => $uid, 'name' => $name]);
		try {
			$circleId = UdpGroupCircle::create($uid, $name, $description);
			UdpDebug::log('[Create] success', ['circleId' => $circleId]);
		} catch (\Exception $e) {
			UdpDebug::log('[Create] caught exception', ['msg' => $e->getMessage()]);
			DI::sysmsg()->addNotice(DI::l10n()->t('Could not create the Group. Please try again.'));
			DI::baseUrl()->redirect('udp/group/create');
		}

		DI::sysmsg()->addInfo(DI::l10n()->t('Group "%s" created.', $name));
		DI::baseUrl()->redirect('udp/group/' . $circleId);
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException();
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/group/create.tpl'), [
			'$title'               => DI::l10n()->t('Create a Group'),
			'$form_security_token' => self::getFormSecurityToken('udp_group_create'),
			'$name_label'          => DI::l10n()->t('Name'),
			'$desc_label'          => DI::l10n()->t('Description (optional)'),
			'$submit_label'        => DI::l10n()->t('Create'),
			'$cancel_url'          => DI::baseUrl() . '/udp/group',
		]);
	}
}
