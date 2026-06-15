<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Asika\SimpleConsole\Console;
use Friendica\App\Mode;
use Friendica\Core\Worker;
use Friendica\Model\User;

/**
 * Initiates an ActivityPub Move activity for a local user account.
 *
 * Broadcasts a Move activity to all followers, notifying them that the account
 * has relocated to a new server. Followers' servers will automatically re-follow
 * the new account upon receipt.
 *
 * Usage: bin/console move-account <uid> <target_url>
 *
 * Example:
 *   bin/console move-account 2 https://newserver.example.com/profile/alice
 */
class MoveAccount extends Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/** @var Mode */
	private $appMode;

	public function __construct(Mode $appMode, array $argv = null)
	{
		parent::__construct($argv);
		$this->appMode = $appMode;
	}

	protected function getHelp(): string
	{
		return <<<HELP
console move-account - Broadcast an AP Move activity to all followers of a local user

Usage
    bin/console move-account <uid> <target_url> [-h|--help|-?] [-v]

Arguments
    uid         Local user ID (integer)
    target_url  Full AP actor URL of the destination account
                e.g. https://newserver.example.com/profile/alice

Options
    -h|--help|-? Show help information
    -v           Show debug information

Description
    Sends an ActivityPub Move activity from the source account to all its
    followers. Receiving servers will automatically re-follow the destination
    account on behalf of their users.

    This command only handles the outbound notification. The destination account
    should already exist before running this command.
HELP;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
		}

		if (count($this->args) < 2) {
			$this->out($this->getHelp());
			return 0;
		}

		if ($this->appMode->isInstall()) {
			throw new \RuntimeException('Database is not ready');
		}

		$uid        = (int) $this->getArgument(0);
		$target_url = $this->getArgument(1);

		if ($uid <= 0) {
			$this->out('Error: uid must be a positive integer');
			return 1;
		}

		if (!filter_var($target_url, FILTER_VALIDATE_URL)) {
			$this->out('Error: target_url must be a valid URL');
			return 1;
		}

		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			$this->out('Error: no user found with uid ' . $uid);
			return 1;
		}

		$this->out('Queueing Move activity for ' . $owner['addr'] . ' → ' . $target_url);

		Worker::add(Worker::PRIORITY_HIGH, 'MoveAccount', $uid, $target_url);

		$this->out('Done. Workers will deliver to all followers shortly.');
		return 0;
	}
}
