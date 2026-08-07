<?php
// UDP Social theme configuration — delegates entirely to frio's color scheme system.
// Without this file, Friendica's getConfigFile() theme-extends fallback fails silently
// and the color pickers are absent from Display Settings.

// The Display Settings form submits with button name "<theme>-settings-submit".
// Frio's theme_post() only checks for "frio-settings-submit", so normalize here.
if (!isset($_POST['frio-settings-submit']) && isset($_POST['udp-settings-submit'])) {
	$_POST['frio-settings-submit'] = $_POST['udp-settings-submit'];
}

if (!function_exists('theme_content')) {
	require_once 'view/theme/frio/config.php';
}
