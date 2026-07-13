<?php
/**
 * Name: UDP
 * Description: Mobile-first PWA theme for UDP Social, extending frio.
 * Version: 1.1
 * Author: UDP Holdings
 * extends: frio
 */

use Friendica\AppHelper;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

require_once 'view/theme/frio/theme.php';

function udp_init(AppHelper $appHelper)
{
	frio_init($appHelper);

	// Override viewport for mobile-first + iOS safe-area support
	DI::page()['htmlhead'] .= '<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">';

	// Force a cache-bust for the UDP stylesheet on every theme version bump.
	// Friendica uses App::VERSION (e.g. "2026.01") as the ?v= param for all assets,
	// which never changes between our deploys. Adding ?udp=1 gives us a distinct URL
	// that Page::registerStylesheet() won't overwrite (it only merges the 'v' key),
	// so browsers re-fetch when we increment this string.
	Renderer::$theme['stylesheet'] = 'view/theme/udp/style.pcss?udp=5';

	// UDP always routes "New post" directly to /compose — no jot modal
	$uid = DI::userSession()->getLocalUserId();
	if ($uid) {
		DI::pConfig()->set($uid, 'frio', 'always_open_compose', true);
	}

	// Pass compose mode to client so the template can adapt without a PHP fork
	$udp_mode = htmlspecialchars($_REQUEST['udp_mode'] ?? '', ENT_QUOTES, 'UTF-8');
	DI::page()['htmlhead'] .= '<script>window.UDP_MODE=' . json_encode($udp_mode) . ';</script>';

	// Version build suffix: 6-char git hash + 'rc' if worktree is dirty.
	// Falls back to view/theme/udp/.build-hash (write this file in your docker build step).
	$_udp_build = '';
	$_git_root  = realpath(__DIR__ . '/../../..');
	if ($_git_root && is_dir($_git_root . '/.git') && function_exists('shell_exec')) {
		$_udp_hash = trim((string)shell_exec('git -C ' . escapeshellarg($_git_root) . ' rev-parse --short=6 HEAD 2>/dev/null'));
		if ($_udp_hash !== '') {
			$_udp_dirty = trim((string)shell_exec('git -C ' . escapeshellarg($_git_root) . ' status --porcelain 2>/dev/null')) !== '';
			$_udp_build = $_udp_hash . ($_udp_dirty ? 'rc' : '');
		}
	}
	if (!$_udp_build && is_readable(__DIR__ . '/.build-hash')) {
		$_udp_build = trim((string)file_get_contents(__DIR__ . '/.build-hash'));
	}
	DI::page()['htmlhead'] .= '<script>window.UDP_BUILD=' . json_encode($_udp_build) . ';</script>';
	if ($_udp_build) {
		$_udp_build_esc = htmlspecialchars($_udp_build, ENT_QUOTES, 'UTF-8');
		DI::page()['htmlhead'] .= '<style>'
			. '#udp-ver-banner::after,#udp-ver-public::after{content:" ' . $_udp_build_esc . '"}'
			. '#udp-ver-admin::after{content:"+UDPv1.1-' . $_udp_build_esc . '"}'
			. '</style>';
	}

	// Default compose visibility based on current URL context.
	// /network/circle/{id}  → restrict to that circle; all other pages → no default (Public)
	$_udp_uri  = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
	$_udp_compose_defaults = null;
	if (preg_match('#/network/circle/(\d+)$#', (string)$_udp_uri, $_m)) {
		$_udp_compose_defaults = ['circle_allow' => $_m[1]];
	}
	if ($_udp_compose_defaults !== null) {
		DI::page()['htmlhead'] .= '<script>window.UDP_COMPOSE_DEFAULTS=' . json_encode($_udp_compose_defaults) . ';</script>';
	}
}

function udp_install()
{
	Hook::register('prepare_body_final', 'view/theme/udp/theme.php', 'udp_item_photo_links');
	Hook::register('item_photo_menu',    'view/theme/udp/theme.php', 'udp_item_photo_menu');
	Hook::register('contact_photo_menu', 'view/theme/udp/theme.php', 'udp_contact_photo_menu');
	Hook::register('nav_info',           'view/theme/udp/theme.php', 'udp_remote_nav');
	Hook::register('display_item',       'view/theme/udp/theme.php', 'udp_display_item');

	DI::logger()->info('installed theme udp');
}

function udp_uninstall()
{
	Hook::unregister('prepare_body_final', 'view/theme/udp/theme.php', 'udp_item_photo_links');
	Hook::unregister('item_photo_menu',    'view/theme/udp/theme.php', 'udp_item_photo_menu');
	Hook::unregister('contact_photo_menu', 'view/theme/udp/theme.php', 'udp_contact_photo_menu');
	Hook::unregister('nav_info',           'view/theme/udp/theme.php', 'udp_remote_nav');
	Hook::unregister('display_item',       'view/theme/udp/theme.php', 'udp_display_item');
}

// Delegate all frio hooks to their frio implementations
function udp_item_photo_links(&$body_info) { frio_item_photo_links($body_info); }
function udp_item_photo_menu(&$arr)        { frio_item_photo_menu($arr); }
function udp_contact_photo_menu(&$args)    { frio_contact_photo_menu($args); }
function udp_remote_nav(&$nav_info)        { frio_remote_nav($nav_info); }
function udp_display_item(&$arr)           { frio_display_item($arr); }
