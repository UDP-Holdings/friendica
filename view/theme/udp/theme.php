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
