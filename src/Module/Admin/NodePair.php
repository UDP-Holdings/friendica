<?php

// UDP Social customization — node pairing UI for private family/friends federation
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;

/**
 * Admin UI for pairing two UDP Social nodes.
 *
 * Flow:
 *   Admin B (sharer) → GET /admin/node-pair/generate → POST to create token → shows QR + copy text
 *   Admin A (joiner) → GET /admin/node-pair/accept   → scans/pastes payload → POST to confirm
 *
 * New file = zero upstream merge conflict risk.
 */
class NodePair extends BaseAdmin
{
	protected function post(array $request = []): void
	{
		parent::post();

		$action = $this->parameters['action'] ?? '';

		if ($action === 'generate') {
			self::checkFormSecurityTokenRedirectOnError('/admin/node-pair/generate', 'admin_node_pair_generate');

			$token = bin2hex(random_bytes(24)); // 48 hex chars — fits config.k (varbinary(50))
			DI::config()->set('udp_pair', $token, json_encode([
				'domain'     => DI::baseUrl()->getHost(),
				'expires_at' => time() + 86400,
			]));

			$recipientEmail = trim($request['email'] ?? '');
			if ($recipientEmail && filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
				$payload    = ['d' => DI::baseUrl()->getHost(), 't' => $token];
				$payloadStr = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
				$inviteUrl  = (string) DI::baseUrl() . '/udp/pair-invite/' . $token;
				$sitename   = DI::config()->get('config', 'sitename');

				$subject  = DI::l10n()->t('%s wants to pair nodes with you', $sitename);
				$preamble = DI::l10n()->t(
					"%s would like to pair their node with yours.\n\nView the pairing QR code here (valid 24 hours):\n%s\n\nOr paste this token at your node's Admin → Node Pairing → Scan / paste token:\n\n%s",
					$sitename,
					$inviteUrl,
					$payloadStr
				);

				$mail = DI::emailer()
					->newSystemMail()
					->withMessage($subject, $preamble)
					->withRecipient($recipientEmail)
					->build();

				if (DI::emailer()->send($mail)) {
					DI::sysmsg()->addInfo(DI::l10n()->t('Pairing invitation emailed to %s.', $recipientEmail));
				} else {
					DI::sysmsg()->addNotice(DI::l10n()->t('Token generated but the email to %s could not be sent. Share the token manually.', $recipientEmail));
				}
			}

			DI::baseUrl()->redirect('admin/node-pair/generate?token=' . $token);
		}

		if ($action === 'accept') {
			self::checkFormSecurityTokenRedirectOnError('/admin/node-pair/accept', 'admin_node_pair_accept');

			$payload_raw = trim($request['payload'] ?? '');
			$payload     = json_decode(base64_decode(strtr($payload_raw, '-_', '+/')), true);

			if (empty($payload['d']) || empty($payload['t'])) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Invalid pairing payload — check that you scanned or pasted the full token.'));
				DI::baseUrl()->redirect('admin/node-pair/accept');
			}

			$remote_domain = $payload['d'];
			$token         = $payload['t'];

			$url    = 'https://' . $remote_domain . '/udp/pair';
			$result = DI::httpClient()->post(
				$url,
				json_encode(['token' => $token, 'requesting_domain' => DI::baseUrl()->getHost()]),
				['Content-Type' => 'application/json'],
				30,
				HttpClientRequest::ACTIVITYPUB
			);

			if (!$result->isSuccess()) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Could not reach %s or the token was rejected. Make sure the token has not expired (24 h limit).', $remote_domain));
				DI::baseUrl()->redirect('admin/node-pair/accept');
			}

			$response = json_decode($result->getBodyString(), true);
			if (empty($response['success'])) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Pairing rejected by %s: %s', $remote_domain, $response['error'] ?? 'unknown error'));
				DI::baseUrl()->redirect('admin/node-pair/accept');
			}

			$this->addToAllowedSites($remote_domain);
			DI::federationGateway()->allow($remote_domain, 'peer');

			// Consume the pending pair request now that the handshake is complete
			DI::config()->delete('udp_pair_request', $remote_domain);

			// Store the shared secret returned by the remote node for HMAC-signed directory requests
			$secret = $response['secret'] ?? '';
			if ($secret && preg_match('/^[0-9a-f]{64}$/', $secret)) {
				DI::config()->set('udp_shared_secret', $remote_domain, $secret);
			}

			DI::sysmsg()->addInfo(DI::l10n()->t('Successfully paired with %s! Posts from that node will now appear in your feeds.', $remote_domain));
			DI::baseUrl()->redirect('admin/node-pair');
		}

		if ($action === 'reject') {
			self::checkFormSecurityTokenRedirectOnError('/admin/node-pair', 'admin_node_pair_reject');

			$domain = trim($request['domain'] ?? '');
			if ($domain) {
				DI::config()->delete('udp_pair_request', $domain);
				DI::sysmsg()->addInfo(DI::l10n()->t('Pairing request from %s has been declined.', $domain));
			}
			DI::baseUrl()->redirect('admin/node-pair');
		}

		if ($action === 'remove') {
			self::checkFormSecurityTokenRedirectOnError('/admin/node-pair', 'admin_node_pair_remove');

			$domain = trim($request['domain'] ?? '');
			if ($domain) {
				DI::federationGateway()->deny($domain);
				$this->removeFromAllowedSites($domain);
				DI::sysmsg()->addInfo(DI::l10n()->t('%s has been removed from your network.', $domain));
			}
			DI::baseUrl()->redirect('admin/node-pair');
		}

		if ($action === 'add') {
			self::checkFormSecurityTokenRedirectOnError('/admin/node-pair', 'admin_node_pair_add');

			$domain = strtolower(trim($request['domain'] ?? ''));
			if ($domain && $this->isValidHostname($domain)) {
				DI::federationGateway()->allow($domain, 'peer');
				$this->addToAllowedSites($domain);
				DI::sysmsg()->addInfo(DI::l10n()->t('%s has been added to your network.', $domain));
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Invalid domain name.'));
			}
			DI::baseUrl()->redirect('admin/node-pair');
		}
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$action     = $this->parameters['action'] ?? '';
		$qr_payload = '';
		$qr_svg     = '';

		if ($action === 'generate') {
			$token  = $request['token'] ?? '';
			$stored = $token ? DI::config()->get('udp_pair', $token) : null;

			if ($stored) {
				$data = json_decode($stored, true);
				if (!empty($data['expires_at']) && $data['expires_at'] > time()) {
					$payload    = ['d' => $data['domain'], 't' => $token];
					$qr_payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

					$renderer = new ImageRenderer(new RendererStyle(256), new SvgImageBackEnd());
					$qr_svg   = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', (new Writer($renderer))->writeString($qr_payload));
				}
			}

			if (!$qr_payload) {
				notice(DI::l10n()->t('Token not found or expired.'));
				DI::baseUrl()->redirect('admin/node-pair');
			}
		}

		$local_host   = DI::baseUrl()->getHost();
		$paired_nodes = DI::dba()->selectToArray(
			'udp_allowlist',
			['allowed_domain', 'source', 'created_at'],
			['slot_domain' => $local_host],
			['order' => ['source', 'allowed_domain']]
		);

		// Load pending inbound pair requests from other UDP nodes
		$pending_requests = [];
		$raw_requests = DI::dba()->selectToArray('config', ['k', 'v'], ['cat' => 'udp_pair_request']);
		foreach ($raw_requests as $row) {
			// DI::config()->set() PHP-serializes values; unserialize before JSON-decoding.
			$json = @unserialize($row['v']);
			$d    = is_string($json) ? json_decode($json, true) : null;
			if (!$d) {
				continue;
			}
			$payload         = ['d' => $d['domain'], 't' => $d['pairing_token']];
			$encoded_payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
			$pending_requests[] = [
				'domain'           => $d['domain'],
				'requester_handle' => $d['context']['requester_handle'] ?? '',
				'target_handle'    => $d['context']['target_handle']    ?? '',
				'received_at'      => $d['received_at'] ? date('M j, Y g:i a', $d['received_at']) : '',
				'accept_payload'   => $encoded_payload,
			];
		}

		// Pre-fill the accept textarea when arriving from a pending-request link
		$prefill_payload = $action === 'accept' ? ($request['payload'] ?? '') : '';

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/node_pair.tpl'), [
			'$title'                      => DI::l10n()->t('Administration'),
			'$page'                       => DI::l10n()->t('Node Pairing'),
			'$action'                     => $action,
			'$qr_payload'                 => $qr_payload,
			'$qr_svg'                     => $qr_svg,
			'$paired_nodes'               => $paired_nodes,
			'$pending_requests'           => $pending_requests,
			'$prefill_payload'            => $prefill_payload,
			'$form_security_token_gen'    => self::getFormSecurityToken('admin_node_pair_generate'),
			'$form_security_token_accept' => self::getFormSecurityToken('admin_node_pair_accept'),
			'$form_security_token_reject' => self::getFormSecurityToken('admin_node_pair_reject'),
			'$form_security_token_remove' => self::getFormSecurityToken('admin_node_pair_remove'),
			'$form_security_token_add'    => self::getFormSecurityToken('admin_node_pair_add'),
			'$baseurl'                    => (string) DI::baseUrl(),
		]);
	}

	private function addToAllowedSites(string $domain): void
	{
		if (!$this->isValidHostname($domain)) {
			return;
		}
		$current = DI::config()->get('system', 'allowed_sites') ?? '';
		$domains = array_filter(array_map('trim', explode(',', $current)));
		if (!in_array($domain, $domains, true)) {
			$domains[] = $domain;
			DI::config()->set('system', 'allowed_sites', implode(',', $domains));
		}
	}

	private function removeFromAllowedSites(string $domain): void
	{
		$current = DI::config()->get('system', 'allowed_sites') ?? '';
		$domains = array_filter(array_map('trim', explode(',', $current)), fn($d) => $d !== $domain);
		DI::config()->set('system', 'allowed_sites', implode(',', $domains));
	}

	private function isValidHostname(string $domain): bool
	{
		return (bool) preg_match('/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain);
	}
}
