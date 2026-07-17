<?php

// UDP Social — Group Circle index (list my groups)
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp\Group;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\UdpGroupCircle;
use Friendica\Network\HTTPException;

/**
 * GET /udp/group — list Group Circles the logged-in user belongs to.
 */
class Index extends BaseModule
{
	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException();
		}

		$uid    = DI::userSession()->getLocalUserId();
		$circles = UdpGroupCircle::getMembershipsForUser($uid);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/group/index.tpl'), [
			'$title'        => DI::l10n()->t('Group Circles'),
			'$create_label' => DI::l10n()->t('Create a Group Circle'),
			'$create_url'   => DI::baseUrl() . '/udp/group/create',
			'$circles'      => $circles,
			'$empty'        => DI::l10n()->t('You are not a member of any Group Circles yet.'),
		]);
	}
}
