<?php

// UDP Social — lightweight debug logger
// Toggle ENABLED to true/false; no framework dependencies.

namespace Friendica\Util;

class UdpDebug
{
	const ENABLED  = false;
	const LOG_FILE = '/var/log/friendica/udp_debug.log';

	public static function log(string $msg, array $context = []): void
	{
		if (!self::ENABLED) {
			return;
		}

		$line = date('Y-m-d H:i:s') . ' ' . $msg;
		if ($context) {
			$line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		file_put_contents(self::LOG_FILE, $line . "\n", FILE_APPEND | LOCK_EX);
	}
}
