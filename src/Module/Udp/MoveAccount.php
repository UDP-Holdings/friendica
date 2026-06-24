<?php

// UDP Social — self-service account move UI
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Network\HTTPException;

/**
 * Lets a logged-in user initiate an ActivityPub Move of their account to another server.
 *
 * POST flow:
 *   1. User submits a destination handle (user@domain) or URL.
 *   2. We probe the target via Contact::getByURL to resolve the AP actor URL.
 *   3. Worker::add('MoveAccount', uid, target_url) queues the broadcast.
 *
 * What moves: the follower graph (AP Move activity).
 * What stays: posts, media, and history remain on the origin server.
 */
class MoveAccount extends BaseModule
{
	protected function post(array $request = []): void
	{
		if (!DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		self::checkFormSecurityTokenRedirectOnError('/udp/move-account', 'udp_move_account');

		$uid    = DI::userSession()->getLocalUserId();
		$target = trim($request['target'] ?? '');

		if (empty($target)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter a destination address.'));
			DI::baseUrl()->redirect('udp/move-account');
		}

		// Probe the target — resolves handle or URL to a full AP actor URL.
		$contact = Contact::getByURL($target, true);
		if (empty($contact['url'])) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Could not find an account at "%s". Check the address and try again.', $target));
			DI::baseUrl()->redirect('udp/move-account');
		}

		$target_url = $contact['url'];

		// Prevent moving to the same server.
		if (parse_url($target_url, PHP_URL_HOST) === DI::baseUrl()->getHost()) {
			DI::sysmsg()->addNotice(DI::l10n()->t('The destination account must be on a different server.'));
			DI::baseUrl()->redirect('udp/move-account');
		}

		Worker::add(Worker::PRIORITY_HIGH, 'MoveAccount', $uid, $target_url);

		DI::sysmsg()->addInfo(DI::l10n()->t(
			'Your account is being moved to %s. Your followers will be notified over the next few minutes.',
			$target_url
		));
		DI::baseUrl()->redirect('udp/move-account');
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		$uid   = DI::userSession()->getLocalUserId();
		$owner = User::getOwnerDataById($uid);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/move_account.tpl'), [
			'$title'               => DI::l10n()->t('Move Account'),
			'$addr'                => $owner['addr'] ?? '',
			'$form_security_token' => self::getFormSecurityToken('udp_move_account'),
		]);
	}
}
