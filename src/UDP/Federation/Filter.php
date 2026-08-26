<?php

namespace Friendica\UDP\Federation;

use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Object\Search\ContactResult;
use Friendica\Object\Search\ResultList;
use Friendica\Util\HTTPSignature;

/**
 * Applies the UDP allowlist to higher-level objects before any outbound probe
 * or connection is attempted. All domain checks delegate to Gateway; this class
 * only handles parsing and collection filtering.
 */
class Filter
{
	public function __construct(private Gateway $gateway)
	{
	}

	/**
	 * True if the host of a fully-qualified URL is on the allowlist.
	 */
	public function allowsUrl(string $url): bool
	{
		$domain = parse_url($url, PHP_URL_HOST) ?? '';
		return $this->gateway->isAllowedOutbound($domain);
	}

	/**
	 * True if the domain half of a handle (user@domain or @user@domain) is on the allowlist.
	 * Falls back to allowsUrl() when the input looks like an HTTP URL.
	 */
	public function allowsHandle(string $handle): bool
	{
		$handle = ltrim($handle, '@');

		if (str_starts_with($handle, 'http://') || str_starts_with($handle, 'https://')) {
			return $this->allowsUrl($handle);
		}

		// user@domain.tld
		$parts = explode('@', $handle, 2);
		if (count($parts) === 2 && !empty($parts[1])) {
			return $this->gateway->isAllowedOutbound($parts[1]);
		}

		// No domain extractable — treat as local/safe.
		return true;
	}

	/**
	 * Remove ContactResult entries whose URL domain is not on the allowlist.
	 * Other result types are passed through unchanged.
	 */
	public function filterContactResults(ResultList $results): ResultList
	{
		$filtered = [];
		foreach ($results->getResults() as $result) {
			if (!($result instanceof ContactResult)) {
				$filtered[] = $result;
				continue;
			}
			if ($this->allowsUrl((string) $result->getUrl())) {
				$filtered[] = $result;
			}
		}

		$count = count($filtered);
		return new ResultList($results->getStart(), $count, $count, $filtered);
	}

	/**
	 * Remove URLs from a bulk list whose domain is not on the allowlist.
	 * Used by the contact-import path before handing off to AddContact.
	 */
	public function filterUrls(array $urls): array
	{
		return array_values(array_filter($urls, fn(string $url) => $this->allowsUrl($url)));
	}

	/**
	 * Require a valid HTTP signature from an allowlisted domain on AP fetch endpoints.
	 * Throws ForbiddenException for unsigned requests or non-allowlisted signers.
	 * Call this at the top of rawContent() on any AP GET endpoint.
	 */
	public function checkInboundFetch(array $server): void
	{
		$signer = HTTPSignature::getSigner('', $server);
		$domain = $signer ? (parse_url($signer, PHP_URL_HOST) ?? '') : '';
		$this->gateway->checkInbound($domain);
	}

	/**
	 * Gate WebFinger /.well-known/webfinger by allowlist.
	 * Unsigned or non-allowlisted requests get NotFoundException (404) so the
	 * user's existence is not confirmed to outside crawlers.
	 * Safe to call even when gateway is disabled — Gateway::checkInbound() no-ops in that case.
	 */
	public function checkInboundWebFinger(array $server): void
	{
		$signer = HTTPSignature::getSigner('', $server);
		$domain = $signer ? (parse_url($signer, PHP_URL_HOST) ?? '') : '';

		try {
			$this->gateway->checkInbound($domain);
		} catch (\Friendica\Network\HTTPException\ForbiddenException $e) {
			throw new NotFoundException();
		}
	}
}
