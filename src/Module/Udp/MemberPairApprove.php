<?php

// UDP Social customization — admin confirmation page for Flow B node pairing requests
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Udp;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Network\HTTPException;

/**
 * Admin confirmation page for a user-initiated Flow B (Fediverse handle) pairing request.
 *
 * GET  /udp/member-pair-approve/{token}
 *   — Show context (who wants to connect with whom), discover the target node's
 *     contact admin via /api/v1/instance, then offer a single "Send pairing request" button.
 *
 * POST /udp/member-pair-approve/{token}
 *   — Generate a udp_pair token on this node, POST it to the target node's
 *     /udp/pair-request endpoint with context attached, then consume this token.
 *     On failure, leaves the member-pair token intact so the admin can retry.
 */
class MemberPairApprove extends BaseAdmin
{
	protected function post(array $request = []): void
	{
		parent::post();

		$token = $this->parameters['token'] ?? '';
		if (!$token) {
			throw new HTTPException\NotFoundException();
		}

		self::checkFormSecurityTokenRedirectOnError('/udp/member-pair-approve/' . $token, 'udp_member_pair_approve');

		$stored = DI::config()->get('udp_member_pair', $token);
		if (!$stored) {
			DI::sysmsg()->addNotice(DI::l10n()->t('This pairing request has already been handled or has expired.'));
			DI::baseUrl()->redirect('admin/node-pair');
		}

		$data = json_decode($stored, true);

		if (empty($data['expires_at']) || $data['expires_at'] < time()) {
			DI::config()->delete('udp_member_pair', $token);
			DI::sysmsg()->addNotice(DI::l10n()->t('This pairing request has expired.'));
			DI::baseUrl()->redirect('admin/node-pair');
		}

		$targetDomain = $data['target_domain'] ?? '';
		if (!$targetDomain) {
			throw new HTTPException\BadRequestException();
		}

		// Generate a pairing token on this node for the target to redeem
		$pairingToken = bin2hex(random_bytes(24));
		DI::config()->set('udp_pair', $pairingToken, json_encode([
			'domain'     => DI::baseUrl()->getHost(),
			'expires_at' => time() + 86400,
		]));

		$body = json_encode([
			'requesting_domain' => DI::baseUrl()->getHost(),
			'pairing_token'     => $pairingToken,
			'context'           => [
				'requester_handle' => $data['requester_nick'] ?? '',
				'target_handle'    => $data['target_handle']  ?? '',
			],
		]);

		$result = DI::httpClient()->post(
			'https://' . $targetDomain . '/udp/pair-request',
			$body,
			['Content-Type' => 'application/json'],
			30,
			HttpClientRequest::ACTIVITYPUB
		);

		if (!$result->isSuccess()) {
			DI::config()->delete('udp_pair', $pairingToken);
			DI::sysmsg()->addNotice(DI::l10n()->t(
				'Could not reach %s. The pairing request was not sent — please try again or use the manual node-pairing flow.',
				$targetDomain
			));
			DI::baseUrl()->redirect('udp/member-pair-approve/' . $token);
		}

		$response = json_decode($result->getBodyString(), true);
		if (empty($response['success'])) {
			DI::config()->delete('udp_pair', $pairingToken);
			DI::sysmsg()->addNotice(DI::l10n()->t(
				'%s rejected the pairing request: %s',
				$targetDomain,
				$response['error'] ?? 'unknown error'
			));
			DI::baseUrl()->redirect('udp/member-pair-approve/' . $token);
		}

		DI::config()->delete('udp_member_pair', $token);

		DI::sysmsg()->addInfo(DI::l10n()->t(
			'Pairing request sent to %s. Their admin will see it when they next visit the Node Pairing page.',
			$targetDomain
		));
		DI::baseUrl()->redirect('admin/node-pair');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$token = $this->parameters['token'] ?? '';
		if (!$token) {
			throw new HTTPException\NotFoundException();
		}

		$stored = DI::config()->get('udp_member_pair', $token);
		if (!$stored) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('This pairing request has already been handled or has expired.'));
		}

		$data = json_decode($stored, true);

		if (empty($data['expires_at']) || $data['expires_at'] < time()) {
			DI::config()->delete('udp_member_pair', $token);
			throw new HTTPException\NotFoundException(DI::l10n()->t('This pairing request has expired.'));
		}

		$targetDomain  = $data['target_domain'] ?? '';
		$contactHandle = '';

		if ($targetDomain) {
			try {
				$result = DI::httpClient()->get('https://' . $targetDomain . '/api/v1/instance');
				if ($result->isSuccess()) {
					$instance = json_decode($result->getBodyString(), true);
					$acct = $instance['contact_account']['acct'] ?? '';
					if ($acct) {
						$contactHandle = '@' . $acct . '@' . $targetDomain;
					}
				}
			} catch (\Throwable $e) {
				// Non-fatal — we show a generic fallback message in the template
			}
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('udp/member_pair_approve.tpl'), [
			'$requester_nick'      => $data['requester_nick'] ?? '',
			'$target_handle'       => $data['target_handle']  ?? '',
			'$target_domain'       => $targetDomain,
			'$contact_handle'      => $contactHandle,
			'$note'                => $data['note']           ?? '',
			'$token'               => $token,
			'$baseurl'             => (string) DI::baseUrl(),
			'$form_security_token' => self::getFormSecurityToken('udp_member_pair_approve'),
		]);
	}
}
