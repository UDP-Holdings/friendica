<?php
/**
 * UDP mobile-first page layout.
 * No sidebar. Content is always full-width. Fixed top bar + bottom nav via nav.tpl.
 */

use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\AppHelper;

require_once 'view/theme/frio/theme.php';
require_once 'view/theme/frio/php/frio_boot.php';
require_once 'view/theme/frio/php/scheme.php';

if (!isset($minimal)) {
	$minimal = false;
}

$frio            = 'view/theme/frio';
$basepath        = DI::baseUrl()->getPath() ? '/' . DI::baseUrl()->getPath() . '/' : '/';
$view_mode_class = (DI::mode()->isMobile()) ? 'mobile-view' : 'desktop-view';

// Read nav_bg from user or site config for theme-color meta (no scheme file needed)
$uid    = Profile::getThemeUid($a);
$nav_bg = DI::pConfig()->get($uid, 'frio', 'nav_bg') ?: DI::config()->get('frio', 'nav_bg') ?: '#708fa0';

?>
<!DOCTYPE html>
<html lang="<?php echo DI::l10n()->getCurrentLang(); ?>">
<head>
	<title><?php if (!empty($page['title'])) echo $page['title']; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta request="<?php echo htmlspecialchars($_REQUEST['pagename'] ?? '') ?>">
	<meta name="theme-color" content="<?php echo htmlspecialchars($nav_bg, ENT_QUOTES, 'UTF-8') ?>">
	<script type="text/javascript">var baseurl = "<?php echo (string)DI::baseUrl(); ?>";</script>
	<script type="text/javascript">var frio = "<?php echo $frio; ?>";</script>
<?php
	if (!$minimal && !empty($page['htmlhead'])) {
		echo $page['htmlhead'];
	}
?>
</head>

<body id="top" class="mod-<?php echo $page['module'] . ' ' . $view_mode_class; ?>">
<?php
if (!empty($page['nav']) && !$minimal) {
	echo str_replace(
		['~config.sitename~', '~system.banner~'],
		[DI::config()->get('config', 'sitename'), DI::config()->get('system', 'banner')],
		$page['nav']
	);
}

if ($minimal) {
?>
	<section class="minimal">
		<?php if (!empty($page['content'])) echo $page['content']; ?>
		<div id="page-footer"></div>
	</section>
<?php
} else {
?>
	<main>
		<div class="container">
			<div class="row">
<?php
			if ((empty($_REQUEST['pagename']) || $_REQUEST['pagename'] != 'lostpass') && ($_SERVER['REQUEST_URI'] != $basepath)) {
				echo '<aside class="col-lg-3 col-md-3 offcanvas-sm offcanvas-xs">';
				if (!empty($page['aside']))       echo $page['aside'];
				if (!empty($page['right_aside'])) echo $page['right_aside'];
				echo '</aside>';
				echo '<div class="col-lg-7 col-md-7 col-sm-12 col-xs-12" id="content" tabindex="0">';
				echo '<section class="sectiontop ' . ($page['section'] ?? '') . '-content-wrapper">';
				if (!empty($page['content'])) echo $page['content'];
				echo '<div id="pause"></div></section></div>';
			} else {
				echo '<section class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="content" style="margin-top:50px;">';
				if (!empty($page['content'])) echo $page['content'];
				echo '</section>';
			}
?>
			</div>
		</div>

		<div id="back-to-top" title="<?php echo DI::l10n()->t('Back to top') ?>">⇧</div>
	</main>

	<footer>
		<?php echo $page['footer'] ?? ''; ?>
	</footer>
<?php } ?>
</body>
</html>
