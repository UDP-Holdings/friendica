<?php

namespace Friendica\UDP\Federation;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\ForbiddenException;
use Psr\Log\LoggerInterface;

/**
 * Allowlist-based federation gate for all ActivityPub surfaces.
 *
 * Source of truth: udp_allowlist table in the slot's MariaDB.
 * Performance cache: Redis Set keyed udp:allowlist:{slotDomain}.
 * global_worker.py warms the Redis Set at boot and sets the ready flag;
 * PHP falls back to DB when Redis is cold or unavailable.
 */
class Gateway
{
	private string $slotDomain;
	private ?\Redis $redis     = null;
	private bool $redisReady   = false;
	private bool $redisChecked = false;

	public function __construct(
		private Database $dba,
		private LoggerInterface $logger,
		private IManageConfigValues $config,
		BaseURL $baseUrl,
	) {
		$this->slotDomain = $baseUrl->getHost();
	}

	/**
	 * Assert an inbound actor domain is on the allowlist.
	 * Throws ForbiddenException (→ HTTP 403) if not.
	 */
	public function checkInbound(string $domain): void
	{
		if (!$this->config->get('udp', 'gateway_enabled', true)) {
			return;
		}
		if (!$this->isAllowed($domain)) {
			$this->logger->notice('UDP gateway blocked inbound', ['domain' => $domain, 'slot' => $this->slotDomain]);
			throw new ForbiddenException();
		}
	}

	/**
	 * Return false if an outbound target domain is not on the allowlist.
	 * Callers in worker context should drop the delivery silently.
	 */
	public function isAllowedOutbound(string $domain): bool
	{
		if (!$this->config->get('udp', 'gateway_enabled', true)) {
			return true;
		}
		$allowed = $this->isAllowed($domain);
		if (!$allowed) {
			$this->logger->notice('UDP gateway blocked outbound', ['domain' => $domain, 'slot' => $this->slotDomain]);
		}
		return $allowed;
	}

	/**
	 * Add a domain to the allowlist (DB + Redis).
	 * Called by PHP on QR flow acceptance.
	 */
	public function allow(string $domain, string $source = 'peer'): void
	{
		$this->dba->insert('udp_allowlist', [
			'slot_domain'    => $this->slotDomain,
			'allowed_domain' => $domain,
			'source'         => $source,
			'created_at'     => gmdate('Y-m-d H:i:s'),
		], Database::INSERT_IGNORE);

		if ($this->redisWarmedUp()) {
			try {
				$this->redis->sAdd('udp:allowlist:' . $this->slotDomain, $domain);
			} catch (\RedisException $e) {
				$this->logger->warning('UDP gateway SADD failed', ['error' => $e->getMessage()]);
			}
		}
	}

	/**
	 * Remove a domain from the allowlist (DB + Redis).
	 */
	public function deny(string $domain): void
	{
		$this->dba->delete('udp_allowlist', [
			'slot_domain'    => $this->slotDomain,
			'allowed_domain' => $domain,
		]);

		if ($this->redisWarmedUp()) {
			try {
				$this->redis->sRem('udp:allowlist:' . $this->slotDomain, $domain);
			} catch (\RedisException $e) {
				$this->logger->warning('UDP gateway SREM failed', ['error' => $e->getMessage()]);
			}
		}
	}

	private function isAllowed(string $domain): bool
	{
		if (empty($domain)) {
			return false;
		}

		// Same-slot traffic is always allowed.
		if ($domain === $this->slotDomain) {
			return true;
		}

		if ($this->redisWarmedUp()) {
			try {
				return (bool) $this->redis->sIsMember('udp:allowlist:' . $this->slotDomain, $domain);
			} catch (\RedisException $e) {
				$this->logger->warning('UDP gateway Redis read failed, falling back to DB', ['error' => $e->getMessage()]);
			}
		}

		return $this->dba->exists('udp_allowlist', [
			'slot_domain'    => $this->slotDomain,
			'allowed_domain' => $domain,
		]);
	}

	/**
	 * Returns true when Redis is connected and global_worker has finished
	 * warming the allowlist Set for this slot.
	 */
	private function redisWarmedUp(): bool
	{
		if ($this->redisChecked) {
			return $this->redisReady;
		}
		$this->redisChecked = true;

		$redis = $this->connectRedis();
		if ($redis === null) {
			return false;
		}

		try {
			$this->redisReady = (bool) $redis->get('udp:allowlist:ready:' . $this->slotDomain);
		} catch (\RedisException $e) {
			$this->redisReady = false;
		}

		return $this->redisReady;
	}

	private function connectRedis(): ?\Redis
	{
		if ($this->redis !== null) {
			return $this->redis;
		}

		$host = $this->config->get('system', 'redis_host');
		if (empty($host) || !class_exists('Redis', false)) {
			return null;
		}

		$port     = $this->config->get('system', 'redis_port');
		$password = $this->config->get('system', 'redis_password');
		$db       = (int) $this->config->get('system', 'redis_db', 0);

		try {
			$redis = new \Redis();
			if (is_numeric($port) && (int) $port > -1) {
				$redis->connect($host, (int) $port);
			} else {
				$redis->connect($host);
			}
			if (!empty($password)) {
				$redis->auth($password);
			}
			if ($db !== 0) {
				$redis->select($db);
			}
			$this->redis = $redis;
		} catch (\RedisException $e) {
			$this->logger->warning('UDP gateway Redis connection failed', ['error' => $e->getMessage()]);
		}

		return $this->redis;
	}
}
