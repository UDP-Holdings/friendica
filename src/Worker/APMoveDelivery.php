<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;

/**
 * Delivers a single AP Move activity to one inbox. Spawned by MoveAccount.
 */
class APMoveDelivery
{
	public static function execute(int $uid, string $inbox, string $target_url): void
	{
		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			DI::logger()->warning('APMoveDelivery: user not found', ['uid' => $uid]);
			return;
		}

		$success = ActivityPub\Transmitter::sendMove($owner, $inbox, $target_url);

		if (!$success) {
			DI::logger()->notice('APMoveDelivery: delivery failed, will retry', ['uid' => $uid, 'inbox' => $inbox]);
			if (!Worker::defer()) {
				DI::logger()->warning('APMoveDelivery: giving up after retries', ['uid' => $uid, 'inbox' => $inbox]);
			}
		}
	}
}
