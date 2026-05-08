{{*
  * UDP Mobile Theme — nav.tpl
  * Slim fixed top bar + 5-tab fixed bottom navigation.
  * Notification badge spans keep their frio IDs so the existing JS updates them.
  *}}

{{if $userinfo}}

{{* ------------------------------------------------------------------ *}}
{{* Hidden legacy nav container — #topbar-first must exist in DOM for   *}}
{{* the nav-update event binding in standard.php (document title count). *}}
{{* ------------------------------------------------------------------ *}}
<div id="topbar-first" aria-hidden="true" style="position:absolute;visibility:hidden;pointer-events:none;height:0;overflow:hidden;"></div>

{{* topbar-second holds #tabmenu which frio JS populates with page tabs. *}}
{{* Keep it in DOM but hidden; tabs won't show on mobile (acceptable).   *}}
<div id="topbar-second" aria-hidden="true" style="display:none;">
	<div class="container">
		<div id="nav-short-info"></div>
		<div id="tabmenu"></div>
		<div id="navbar-button"></div>
	</div>
</div>

{{* ------------------------------------------------------------------ *}}
{{* Slim top bar                                                         *}}
{{* ------------------------------------------------------------------ *}}
<header id="udp-topbar" role="banner">
	<a href="{{$baseurl}}" class="udp-site-name" aria-label="{{$home}}">~config.sitename~</a>
	<div style="display:flex;align-items:center;gap:4px;">
		{{if $nav.search}}
		<button type="button" id="udp-search-toggle" class="udp-topbar-btn"
			aria-label="{{$nav.search.1}}" aria-expanded="false" aria-controls="udp-search-bar">
			<i class="fa fa-search" aria-hidden="true"></i>
		</button>
		{{/if}}
	</div>
</header>

{{* Collapsible search bar below top bar *}}
{{if $nav.search}}
<div id="udp-search-bar" role="search">
	<form method="get" action="{{$nav.search.0}}">
		<input type="search" name="q" placeholder="{{$search_placeholder}}" autocomplete="off"
			aria-label="{{$nav.search.1}}">
		<button type="submit" aria-label="{{$nav.search.1}}">
			<i class="fa fa-search" aria-hidden="true"></i>
		</button>
	</form>
</div>
{{/if}}

{{* ------------------------------------------------------------------ *}}
{{* Bottom navigation bar                                                *}}
{{* ------------------------------------------------------------------ *}}
<nav id="udp-bottom-nav" role="navigation" aria-label="Main navigation">

	{{* Home — links to unified timeline *}}
	<a href="{{$baseurl}}/timeline" class="udp-bottom-nav-item {{$sel.network}}"
		aria-label="Home">
		<i class="fa fa-home" aria-hidden="true"></i>
		<span id="net-update" class="nav-network-badge badge nav-notification"></span>
		<span class="udp-nav-label">Home</span>
	</a>

	{{* Notifications *}}
	{{if $nav.notifications}}
	<a href="{{$nav.notifications.all.0}}" class="udp-bottom-nav-item"
		aria-label="{{$nav.notifications.1}}">
		<i class="fa fa-bell" aria-hidden="true"></i>
		<span id="notification-update" class="nav-notification-badge badge nav-notification"></span>
		<span class="udp-nav-label">Alerts</span>
	</a>
	{{/if}}

	{{* Compose — opens full compose page *}}
	<a href="{{$baseurl}}/compose" class="udp-bottom-nav-item udp-bottom-nav-compose"
		aria-label="New post">
		<i class="fa fa-plus-circle" aria-hidden="true"></i>
		<span class="udp-nav-label">Post</span>
	</a>

	{{* Messages *}}
	{{if $nav.messages}}
	<a href="{{$nav.messages.0}}" class="udp-bottom-nav-item {{$sel.messages}}"
		aria-label="{{$nav.messages.1}}">
		<i class="fa fa-envelope" aria-hidden="true"></i>
		<span id="mail-update" class="nav-mail-badge badge nav-notification"></span>
		<span class="udp-nav-label">Messages</span>
	</a>
	{{/if}}

	{{* Me — triggers slide-up user menu panel *}}
	<button type="button" id="udp-me-btn" class="udp-bottom-nav-item"
		aria-label="Profile and settings" aria-haspopup="dialog" aria-controls="udp-user-menu">
		<img src="{{$userinfo.icon}}" alt="" class="udp-nav-avatar" aria-hidden="true">
		<span class="udp-nav-label">Me</span>
	</button>

</nav>

{{* ------------------------------------------------------------------ *}}
{{* User menu panel (slides up from bottom on Me tap)                   *}}
{{* ------------------------------------------------------------------ *}}
<div id="udp-user-menu" aria-hidden="true" aria-modal="true" role="dialog"
	aria-label="Profile and settings">
	<div class="udp-user-menu-backdrop"></div>
	<div class="udp-user-menu-panel">

		<div class="udp-user-menu-header">
			<img src="{{$userinfo.icon}}" alt="{{$userinfo.name}}" class="udp-menu-avatar">
			<div>
				<strong>{{$userinfo.name}}</strong>
				{{if $nav.remote}}<div class="udp-menu-remote">{{$nav.remote}}</div>{{/if}}
			</div>
			<button type="button" class="udp-menu-close" aria-label="Close menu">&times;</button>
		</div>

		<ul class="udp-menu-list">
			{{* Profile links from usermenu *}}
			{{foreach $nav.usermenu as $usermenu}}
			<li>
				<a href="{{$usermenu.0}}" title="{{$usermenu.3}}">
					<i class="fa {{$usermenu.4}} fa-fw" aria-hidden="true"></i>
					{{$usermenu.1}}
				</a>
			</li>
			{{/foreach}}

			<li class="divider"></li>

			{{if $nav.contacts}}
			<li>
				<a href="{{$nav.contacts.0}}" title="{{$nav.contacts.3}}">
					<i class="fa fa-users fa-fw" aria-hidden="true"></i> {{$nav.contacts.1}}
				</a>
			</li>
			{{/if}}

			{{if $nav.messages}}
			<li>
				<a href="{{$nav.messages.0}}" title="{{$nav.messages.3}}">
					<i class="fa fa-envelope fa-fw" aria-hidden="true"></i> {{$nav.messages.1}}
				</a>
			</li>
			{{/if}}

			{{if $nav.calendar}}
			<li>
				<a href="{{$nav.calendar.0}}" title="{{$nav.calendar.1}}">
					<i class="fa fa-calendar fa-fw" aria-hidden="true"></i> {{$nav.calendar.1}}
				</a>
			</li>
			{{/if}}

			<li class="divider"></li>

			{{if $nav.directory}}
			<li>
				<a href="{{$nav.directory.0}}" title="{{$nav.directory.3}}">
					<i class="fa fa-sitemap fa-fw" aria-hidden="true"></i> {{$nav.directory.1}}
				</a>
			</li>
			{{/if}}

			{{if $nav.settings}}
			<li>
				<a href="{{$nav.settings.0}}" title="{{$nav.settings.3}}">
					<i class="fa fa-cog fa-fw" aria-hidden="true"></i> {{$nav.settings.1}}
				</a>
			</li>
			{{/if}}

			{{if $nav.admin}}
			<li>
				<a href="{{$nav.admin.0}}" title="{{$nav.admin.3}}">
					<i class="fa fa-user-secret fa-fw" aria-hidden="true"></i> {{$nav.admin.1}}
				</a>
			</li>
			{{/if}}

			{{if $nav.moderation}}
			<li>
				<a href="{{$nav.moderation.0}}" title="{{$nav.moderation.3}}">
					<i class="fa fa-gavel fa-fw" aria-hidden="true"></i> {{$nav.moderation.1}}
				</a>
			</li>
			{{/if}}

			{{if $nav.help}}
			<li>
				<a href="{{$nav.help.0}}" title="{{$nav.help.3}}">
					<i class="fa fa-question-circle fa-fw" aria-hidden="true"></i> {{$nav.help.1}}
				</a>
			</li>
			{{/if}}

			<li class="divider"></li>

			{{if $nav.logout}}
			<li>
				<a href="{{$nav.logout.0}}" title="{{$nav.logout.3}}">
					<i class="fa fa-sign-out fa-fw" aria-hidden="true"></i> {{$nav.logout.1}}
				</a>
			</li>
			{{else}}
			<li>
				<a href="{{$nav.login.0}}" title="{{$nav.login.3}}">
					<i class="fa fa-power-off fa-fw" aria-hidden="true"></i> {{$nav.login.1}}
				</a>
			</li>
			{{/if}}
		</ul>

	</div>
</div>

{{* Notification template used by frio's notification JS *}}
{{include file="notifications/nav/notify.tpl"}}

{{else}}
{{* ------------------------------------------------------------------ *}}
{{* Logged-out top bar                                                   *}}
{{* ------------------------------------------------------------------ *}}
<nav id="udp-topbar" role="navigation" aria-label="Site navigation"
	style="justify-content:space-between;">
	<a href="{{$baseurl}}" class="udp-site-name">~config.sitename~</a>
	<a href="login?mode=none" class="udp-topbar-btn" aria-label="{{$nav.login.3}}">
		<i class="fa fa-sign-in" aria-hidden="true"></i>
	</a>
</nav>

{{* Keep for JS compatibility *}}
<div id="topbar-first" aria-hidden="true" style="display:none;"></div>
<div id="topbar-second" aria-hidden="true" style="display:none;">
	<div id="tabmenu"></div>
</div>

{{/if}}
