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

	// Recent-colors palette on the Display Settings page
	if (str_ends_with(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/settings/display')) {
		DI::page()['htmlhead'] .= <<<'HTML'
<style>
.udp-palette-row{display:flex;flex-wrap:wrap;align-items:center;gap:5px;margin-top:6px}
.udp-palette-label{font-size:11px;color:#999;width:100%;margin-bottom:1px}
.udp-swatch{width:22px;height:22px;border-radius:4px;border:2px solid rgba(0,0,0,.18);cursor:pointer;display:inline-block;flex-shrink:0;transition:transform .1s,border-color .1s}
.udp-swatch:hover{transform:scale(1.2);border-color:rgba(0,0,0,.45)}
</style>
<script>
(function(){
var KEY='udp_recent_colors',MAX=8;
function load(){try{return JSON.parse(localStorage.getItem(KEY)||'[]')}catch(e){return[]}}
function save(colors){localStorage.setItem(KEY,JSON.stringify(colors.slice(0,MAX)))}
function push(hex){if(!/^#[0-9a-fA-F]{6}$/.test(hex))return;var c=load().filter(function(x){return x.toLowerCase()!==hex.toLowerCase()});c.unshift(hex);save(c)}

function renderPalette(input,group){
  var old=group.querySelector('.udp-palette-row');if(old)old.remove();
  var colors=load();if(!colors.length)return;
  var row=document.createElement('div');row.className='udp-palette-row';
  var lbl=document.createElement('span');lbl.className='udp-palette-label';lbl.textContent='Recent';row.appendChild(lbl);
  colors.forEach(function(hex){
    var s=document.createElement('span');s.className='udp-swatch';s.style.backgroundColor=hex;s.title=hex;
    s.addEventListener('click',function(){
      input.value=hex;
      var icon=group.querySelector('.input-group-addon i');if(icon)icon.style.backgroundColor=hex;
      input.dispatchEvent(new Event('input',{bubbles:true}));
      input.dispatchEvent(new Event('change',{bubbles:true}));
    });
    row.appendChild(s);
  });
  group.appendChild(row);
}

function init(){
  document.querySelectorAll('.form-group.field.input.color').forEach(function(group){
    var input=group.querySelector('input.form-control.color');if(!input)return;
    renderPalette(input,group);
    input.addEventListener('change',function(){push(this.value);renderPalette(this,group)});
  });
  var form=document.getElementById('settings-form');
  if(form)form.addEventListener('submit',function(){
    document.querySelectorAll('input.form-control.color').forEach(function(i){push(i.value)});
  });
}
setTimeout(init,400);
})();
</script>
HTML;
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
