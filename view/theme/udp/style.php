<?php
// Output frio's complete CSS (colors, schemes, all frio rules) first.
// Capture it so we can catch the 304 NotModifiedException cleanly.
ob_start();
try {
	require_once 'view/theme/frio/style.php';
} catch (\Friendica\Network\HTTPException\NotModifiedException $e) {
	ob_end_clean();
	throw $e;
}
echo ob_get_clean();

// After require_once frio/style.php, its local vars ($nav_bg, $nav_icon_color, etc.)
// are in our scope. Provide safe fallbacks in case a scheme sets them empty.
$udp_nav_bg         = !empty($nav_bg)         ? $nav_bg         : '#1565c0';
$udp_nav_icon_color = !empty($nav_icon_color) ? $nav_icon_color : '#ffffff';
?>

/* ====================================================================
   UDP Mobile Theme — responsive overrides
   Mobile PWA layout at ≤767px; frio desktop layout at ≥768px.
   ==================================================================== */

/* ---- Mobile only: suppress frio's desktop top bars + body offset ---- */
@media (max-width: 767px) {
	#topbar-first,
	#topbar-second,
	#site-location,
	#banner,
	#search-mobile,
	#frio-nav-public {
		display: none !important;
	}

	body {
		padding-top: 48px !important;
		padding-bottom: 70px !important;
	}
}

/* ---- UDP chrome styling (display controlled by breakpoint below) ---- */
#udp-topbar {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	height: 48px;
	background-color: <?= $udp_nav_bg ?>;
	color: <?= $udp_nav_icon_color ?>;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 12px;
	z-index: 1020;
	box-shadow: 0 1px 3px rgba(0,0,0,0.25);
}

#udp-topbar .udp-site-name {
	color: <?= $udp_nav_icon_color ?>;
	font-size: 18px;
	font-weight: 600;
	text-decoration: none;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	max-width: calc(100% - 80px);
}

.udp-topbar-btn {
	background: none;
	border: none;
	color: <?= $udp_nav_icon_color ?>;
	font-size: 20px;
	padding: 4px 10px;
	cursor: pointer;
	opacity: 0.85;
	line-height: 1;
}
.udp-topbar-btn:hover, .udp-topbar-btn:active { opacity: 1; }

/* ---- Fixed bottom navigation bar ---- */
#udp-bottom-nav {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	height: 56px;
	background-color: <?= $udp_nav_bg ?>;
	display: flex;
	justify-content: space-around;
	align-items: stretch;
	z-index: 1020;
	border-top: 1px solid rgba(0,0,0,0.15);
	padding-bottom: env(safe-area-inset-bottom, 0);
}

.udp-bottom-nav-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	flex: 1;
	color: rgba(255,255,255,0.6);
	text-decoration: none;
	font-size: 10px;
	position: relative;
	background: none;
	border: none;
	padding: 0;
	-webkit-tap-highlight-color: transparent;
	cursor: pointer;
	transition: color 0.12s;
}

.udp-bottom-nav-item:hover,
.udp-bottom-nav-item.active,
.udp-bottom-nav-item:focus {
	color: #ffffff;
	text-decoration: none;
	outline: none;
}

.udp-bottom-nav-item i {
	font-size: 22px;
	margin-bottom: 2px;
	line-height: 1;
}

.udp-nav-label {
	font-size: 10px;
	line-height: 1;
}

/* Badges on bottom nav items */
.udp-bottom-nav-item .badge {
	position: absolute;
	top: 5px;
	left: calc(50% + 5px);
	min-width: 16px;
	height: 16px;
	border-radius: 8px;
	background-color: #e53935;
	color: #fff !important;
	font-size: 9px;
	line-height: 16px;
	padding: 0 3px;
	pointer-events: none;
	font-weight: 700;
}

.udp-bottom-nav-item .badge:empty { display: none; }

/* Compose button — visually prominent center anchor */
.udp-bottom-nav-compose { color: rgba(255,255,255,0.9) !important; }
.udp-bottom-nav-compose i { font-size: 30px !important; }

/* Avatar in Me tab */
.udp-nav-avatar {
	width: 26px;
	height: 26px;
	border-radius: 50%;
	margin-bottom: 2px;
	object-fit: cover;
}

/* ---- Desktop only: hide UDP chrome, let frio nav bars render naturally ---- */
@media (min-width: 768px) {
	#udp-topbar,
	#udp-bottom-nav,
	#udp-search-bar,
	#udp-user-menu {
		display: none !important;
	}
}

/* ---- Mobile only: compact container ---- */
@media (max-width: 767px) {
	.container { padding-left: 8px; padding-right: 8px; }
}

/* ---- Search bar (toggleable below top bar) ---- */
#udp-search-bar {
	position: fixed;
	top: 48px;
	left: 0;
	right: 0;
	background-color: <?= $udp_nav_bg ?>;
	padding: 6px 12px 8px;
	z-index: 1015;
	display: none;
	box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

#udp-search-bar.open { display: block; }

#udp-search-bar form { display: flex; gap: 6px; align-items: center; }

#udp-search-bar input[type="search"] {
	flex: 1;
	height: 36px;
	border-radius: 18px;
	border: none;
	padding: 0 14px;
	font-size: 15px;
	background: rgba(255,255,255,0.18);
	color: <?= $udp_nav_icon_color ?>;
}

#udp-search-bar input[type="search"]::placeholder { color: rgba(255,255,255,0.55); }

#udp-search-bar button {
	background: none;
	border: none;
	color: <?= $udp_nav_icon_color ?>;
	font-size: 18px;
	padding: 0 6px;
	cursor: pointer;
	opacity: 0.85;
}

/* ---- User menu panel (slides up from bottom) ---- */
#udp-user-menu {
	position: fixed;
	inset: 0;
	z-index: 2000;
	display: none;
}

#udp-user-menu.open { display: block; }

.udp-user-menu-backdrop {
	position: absolute;
	inset: 0;
	background: rgba(0,0,0,0.5);
}

.udp-user-menu-panel {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	background: #fff;
	border-radius: 16px 16px 0 0;
	max-height: 80vh;
	overflow-y: auto;
	-webkit-overflow-scrolling: touch;
	padding-bottom: env(safe-area-inset-bottom, 0);
	transform: translateY(100%);
	transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

#udp-user-menu.open .udp-user-menu-panel { transform: translateY(0); }

.udp-user-menu-header {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 16px;
	border-bottom: 1px solid #eee;
}

.udp-menu-avatar {
	width: 48px;
	height: 48px;
	border-radius: 50%;
	object-fit: cover;
	flex-shrink: 0;
}

.udp-user-menu-header > div { flex: 1; min-width: 0; }

.udp-user-menu-header strong { display: block; font-size: 15px; font-weight: 600; }

.udp-menu-remote {
	font-size: 12px;
	color: #888;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.udp-menu-close {
	background: none;
	border: none;
	font-size: 28px;
	color: #999;
	padding: 0 4px;
	cursor: pointer;
	line-height: 1;
	flex-shrink: 0;
}

.udp-menu-list {
	list-style: none;
	padding: 8px 0 16px;
	margin: 0;
}

.udp-menu-list li a {
	display: flex;
	align-items: center;
	gap: 14px;
	padding: 12px 20px;
	color: #222;
	text-decoration: none;
	font-size: 15px;
	min-height: 48px;
}

.udp-menu-list li a i {
	width: 20px;
	text-align: center;
	color: #666;
	font-size: 17px;
}

.udp-menu-list li a:active { background: #f0f0f0; }

.udp-menu-list li.divider {
	border-top: 1px solid #eee;
	margin: 4px 0;
}

/* ---- Fix Friendica logo mask: frio CSS uses a relative path that breaks when
   served from view/theme/udp/style.php. Pin to the absolute URL. ---- */
header #banner #logo-img,
.navbar-brand #logo-img {
	-webkit-mask-image: url("<?= (string) DI::baseUrl() ?>/view/theme/frio/img/friendica-25.png");
}

/* ---- Login page — UDP background image override ---- */
.mod-login {
	background-image: linear-gradient(to right, rgba(0,0,0,0.6), rgba(0,0,0,0.35)), url("<?= (string) DI::baseUrl() ?>/view/theme/udp/img/login_bg.jpg");
}

/* ---- UDP version badge ---- */
.udp-version {
	display: none; /* hidden everywhere by default; enabled below on desktop only */
	font-size: 9px;
	font-weight: 700;
	letter-spacing: 0.05em;
	text-transform: uppercase;
	white-space: nowrap;
	opacity: 0.6;
}

/* In #banner (logged-in): flex the <a> so version sits to the right of the logo */
a.udp-banner-brand {
	display: flex !important;
	align-items: flex-end;
	gap: 5px;
	text-decoration: none;
}
#banner .udp-version {
	display: block;
	color: <?= $udp_nav_icon_color ?>;
	padding-bottom: 3px; /* baseline-align with bottom of 25px logo */
}

/* In navbar-brand (logged-out): #navbrand-container is already flex */
#navbrand-container .udp-version {
	display: block;
	color: <?= $udp_nav_icon_color ?>;
	align-self: flex-end;
	padding-bottom: 1px;
}

/* ---- Login attribution footer ---- */
#udp-login-attribution {
	position: fixed;
	bottom: 6px;
	right: 12px;
	font-size: 10px;
	color: rgba(255,255,255,0.45);
	margin: 0;
	pointer-events: none;
}

/* ---- Mobile only: touch-friendliness + misc ---- */
@media (max-width: 767px) {
	/* Prevent iOS zoom on input focus */
	.jot-text, .profile-jot-text-full, #profile-jot-text,
	textarea, input[type="text"], input[type="email"],
	input[type="password"], input[type="search"], select {
		font-size: 16px !important;
	}

	/* Back to top button — clear of bottom nav */
	#back-to-top { bottom: 70px; }

	/* Post items — edge-to-edge feel */
	.wall-item-container { border-radius: 0; border-left: none; border-right: none; }
	.panel { border-radius: 0; }
	.panel + .panel { margin-top: 4px; }
}
