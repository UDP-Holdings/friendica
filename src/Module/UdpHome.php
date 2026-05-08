<?php

// UDP Social customization — redirect logged-in users to the unified feed instead of /network
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\DI;

/**
 * UDP override of the home landing page.
 * Sends logged-in users to /feed (unified feed) rather than /network.
 * New file = zero upstream merge conflict risk.
 */
class UdpHome extends Home
{
	protected function content(array $request = []): string
	{
		if (DI::userSession()->getLocalUserId() && DI::userSession()->getLocalUserNickname()) {
			DI::baseUrl()->redirect('timeline');
		}

		return parent::content($request);
	}
}
