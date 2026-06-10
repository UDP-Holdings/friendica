<?php

// UDP Social customization — adds the Unified Feed / Following Only toggle to /network
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Conversation;

use Friendica\DI;

/**
 * UDP wrapper for the standard /network feed.
 * Prepends the same toggle strip shown on /timeline so users can switch views.
 * New file = zero upstream merge conflict risk.
 */
class UdpNetwork extends Network
{
	protected function parseRequest(array $request): void
	{
		if (!DI::userSession()->getLocalUserId()) {
			DI::baseUrl()->redirect('login');
		}

		parent::parseRequest($request);
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			$this->baseUrl->redirect('login');
		}

		$o = parent::content($request);

		$toggle = '<nav class="widget"><ul>'
			. '<li><a href="/timeline">Unified Feed</a></li>'
			. '<li class="selected"><a href="/network">Following Only</a></li>'
			. '</ul></nav>';

		$this->page['aside'] = $toggle . $this->page['aside'];

		return $o;
	}
}
