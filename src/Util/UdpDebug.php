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

	// Wraps PHP's exception and error handlers so uncaught throwables land in
	// our log file before Friendica's Monolog handler runs (and potentially
	// fails silently if its log file is unwritable). Safe to call multiple times.
	private static bool $handlersRegistered = false;

	public static function registerHandlers(): void
	{
		if (!self::ENABLED || self::$handlersRegistered) {
			return;
		}
		self::$handlersRegistered = true;

		$prevException = set_exception_handler(function (\Throwable $e) use (&$prevException): void {
			self::log('UNCAUGHT EXCEPTION', [
				'type'    => get_class($e),
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
				'trace'   => $e->getTraceAsString(),
			]);
			if ($prevException) {
				($prevException)($e);
			}
		});

		$prevError = null;
		$prevError = set_error_handler(function (int $code, string $msg, string $file, int $line) use (&$prevError): bool {
			self::log('PHP ERROR', ['code' => $code, 'message' => $msg, 'file' => $file, 'line' => $line]);
			if ($prevError) {
				return (bool)($prevError)($code, $msg, $file, $line);
			}
			return false;
		});
	}
}
