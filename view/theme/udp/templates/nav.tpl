{{*
  * UDP Mobile Theme — nav.tpl
  * Desktop: full frio navbar (hidden on mobile via CSS).
  * Mobile: slim fixed top bar + 5-tab fixed bottom nav (hidden on desktop via CSS).
  * Notification badge IDs match frio's so existing JS updates them.
  *}}

{{if $userinfo}}

{{* ================================================================== *}}
{{* DESKTOP NAV — full frio navbar structure                            *}}
{{* Hidden on mobile via: @media (max-width:767px) { display:none }    *}}
{{* ================================================================== *}}

<header>
	<div id="site-location" aria-hidden="true">{{$sitelocation}}</div>
	<div id="banner" class="hidden-sm hidden-xs">
		<a href="{{$baseurl}}" aria-hidden="true" class="udp-banner-brand">
			<div id="logo-img" aria-label="{{$home}}"></div>
			<span class="udp-version">UDP v1.1</span>
		</a>
	</div>
</header>

<nav id="topbar-first" class="topbar" role="menubar">
	<div class="container">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 no-padding">
			<div class="topbar-nav">

				<button type="button" class="navbar-toggle offcanvas-right-toggle pull-right"
					aria-controls="offcanvasUsermenu" aria-haspopup="true">
					<span class="sr-only">Toggle navigation</span>
					<i class="fa fa-ellipsis-v fa-fw fa-lg" aria-hidden="true"></i>
				</button>
				<button type="button" class="navbar-toggle collapsed pull-right" data-toggle="collapse"
					data-target="#search-mobile" aria-expanded="false" aria-controls="search-mobile">
					<span class="sr-only">Toggle Search</span>
					<i class="fa fa-search fa-fw fa-lg" aria-hidden="true"></i>
				</button>
				<button type="button" id="mobile-left-menu" class="navbar-toggle collapsed pull-left visible-sm visible-xs"
					data-toggle="offcanvas" data-target="aside" aria-haspopup="true">
					<span class="sr-only">Toggle navigation</span>
					<i class="fa fa-angle-double-right fa-fw fa-lg" aria-hidden="true"></i>
				</button>

				<ul class="nav navbar-left">
					<li class="sr-only">
						<button class="sr-only" onclick="document.getElementById('content').scrollIntoView(); document.getElementById('content').focus();">{{$skip}}</button>
					</li>
					<li class="sr-only">
						<a class="sr-only" href="{{$baseurl}}">{{$home}}</a>
					</li>
					{{if $nav.network}}
					<li class="nav-segment">
						<a accesskey="n" class="nav-menu {{$sel.network}}" href="{{$nav.network.0}}"
							data-toggle="tooltip" data-viewport="#topbar-first" aria-label="{{$nav.network.3}}" title="{{$nav.network.3}}">
							<i class="fa fa-lg fa-th fa-fw" aria-hidden="true"></i>
							<span id="net-update" class="nav-network-badge badge nav-notification"></span>
						</a>
					</li>
					{{/if}}
					{{if $nav.channel}}
					<li class="nav-segment">
						<a accesskey="l" class="nav-menu {{$sel.channel}}" href="{{$nav.channel.0}}"
							data-toggle="tooltip" data-viewport="#topbar-first" aria-label="{{$nav.channel.3}}" title="{{$nav.channel.3}}">
							<i class="fa fa-lg fa-newspaper-o fa-fw" aria-hidden="true"></i>
						</a>
					</li>
					{{/if}}
					{{if $nav.home}}
					<li class="nav-segment">
						<a accesskey="p" class="nav-menu {{$sel.home}}" href="{{$nav.home.0}}"
							data-toggle="tooltip" data-viewport="#topbar-first" aria-label="{{$nav.home.3}}" title="{{$nav.home.3}}">
							<i class="fa fa-lg fa-home fa-fw" aria-hidden="true"></i>
							<span id="home-update" class="nav-home-badge badge nav-notification"></span>
						</a>
					</li>
					{{/if}}
					{{if $nav.community}}
					<li class="nav-segment">
						<a accesskey="c" class="nav-menu {{$sel.community}}" href="{{$nav.community.0}}"
							data-toggle="tooltip" data-viewport="#topbar-first" aria-label="{{$nav.community.3}}" title="{{$nav.community.3}}">
							<i class="fa fa-lg fa-bullseye fa-fw" aria-hidden="true"></i>
						</a>
					</li>
					{{/if}}
					{{if $nav.messages}}
					<li class="nav-segment hidden-xs">
						<a accesskey="m" id="nav-messages-link" href="{{$nav.messages.0}}"
							data-toggle="tooltip" data-viewport="#topbar-first"
							aria-label="{{$nav.messages.1}}" title="{{$nav.messages.1}}"
							class="nav-menu {{$sel.messages}}">
							<i class="fa fa-envelope fa-lg fa-fw" aria-hidden="true"></i>
							<span id="mail-update" class="nav-mail-badge badge nav-notification"></span>
						</a>
					</li>
					{{/if}}
					{{if $nav.calendar}}
					<li class="nav-segment hidden-xs">
						<a accesskey="e" id="nav-calendar-link" href="{{$nav.calendar.0}}"
							data-toggle="tooltip" data-viewport="#topbar-first"
							aria-label="{{$nav.calendar.1}}" title="{{$nav.calendar.1}}" class="nav-menu">
							<i class="fa fa-lg fa-calendar fa-fw"></i>
						</a>
					</li>
					{{/if}}
					{{if $nav.contacts}}
					<li class="nav-segment hidden-xs">
						<a accesskey="k" id="nav-contacts-link" href="{{$nav.contacts.0}}"
							data-toggle="tooltip" data-viewport="#topbar-first"
							aria-label="{{$nav.contacts.1}}" title="{{$nav.contacts.1}}"
							class="nav-menu {{$sel.contacts}} {{$nav.contacts.2}}">
							<i class="fa fa-users fa-lg fa-fw"></i>
						</a>
					</li>
					{{/if}}
					{{if $nav.notifications}}
					<li id="nav-notification" class="nav-segment dropdown">
						<button id="nav-notifications-menu-btn" class="btn-link dropdown-toggle" data-toggle="dropdown"
							type="button" aria-haspopup="true" aria-expanded="false" aria-controls="nav-notifications-menu">
							<span id="notification-update" class="nav-notification-badge badge nav-notification"></span>
							<i class="fa fa-bell fa-lg" aria-label="{{$nav.notifications.1}}"></i>
						</button>
						<ul id="nav-notifications-menu" class="dropdown-menu menu-popup" role="menu"
							aria-labelledby="nav-notifications-menu-btn">
							<li id="nav-notifications-mark-all" class="dropdown-header">
								<div class="arrow"></div>
								<header id="notifications-header">
									<p id="notifications-title">{{$nav.notifications.1}}</p>
									<header id="notifications-subheader">
										<a href="{{$nav.notifications.all.0}}">{{$nav.notifications.all.1}}</a>
										<button role="menuitem" type="button" id="notifications-mark-as-read" class="btn-link"
											onclick="notificationMarkAll();" data-toggle="tooltip">{{$nav.notifications.mark.1}}
										</button>
									</header>
								</header>
							</li>
							<li id="nav-notifications-loading" class="loading" style="font-weight: bold; color: #555; padding-left: 10px;">
								<i class="fa fa-spinner fa-spin" aria-hidden="true" style="vertical-align: middle;"></i> {{$loadingnotifications}}
							</li>
							<li id="nav-notifications-empty" class="empty" style="display: none;">
								<p role="menuitem" class="text-muted text-center"><i>{{$emptynotifications}}</i></p>
							</li>
						</ul>
					</li>
					{{/if}}
				</ul>
			</div>

			<div class="topbar-actions pull-right">
				<ul class="nav">
					{{if $nav.search}}
					<li id="search-box" class="hidden-xs">
						<form class="navbar-form" role="search" method="get" action="{{$nav.search.0}}">
							<div class="form-group form-group-search">
								<input accesskey="s" id="nav-search-input-field" class="form-control form-search"
									type="search" name="q" placeholder="{{$search_placeholder}}">
								<button class="btn btn-primary btn-md form-button-search" type="submit">
									<i class="fa fa-search" aria-hidden="true"></i>
									<span class="sr-only">{{$nav.search.1}}</span>
								</button>
							</div>
						</form>
					</li>
					{{/if}}

					<li id="nav-user-linkmenu" class="dropdown account nav-menu hidden-xs">
						<button accesskey="u" id="main-menu" class="btn-link dropdown-toggle nav-avatar"
							data-toggle="dropdown" type="button" aria-haspopup="true" aria-expanded="false"
							aria-controls="nav-user-menu">
							<div aria-hidden="true" class="user-title pull-left hidden-xs hidden-sm hidden-md">
								<strong>{{$userinfo.name}}</strong><br>
								{{if $nav.remote}}<span class="truncate">{{$nav.remote}}</span>{{/if}}
							</div>
							<img id="avatar" src="{{$userinfo.icon}}" alt="{{$userinfo.name}}">
							<span class="caret"></span>
						</button>
						<ul id="nav-user-menu" class="dropdown-menu pull-right menu-popup" role="menu"
							aria-labelledby="main-menu">
							{{if $nav.remote}}
								{{if $nav.sitename}}
									<li id="nav-sitename" role="menuitem">{{$nav.sitename}}</li>
									<li class="divider"><hr></li>
								{{/if}}
							{{/if}}
							{{foreach $nav.usermenu as $usermenu}}
							<li>
								<a role="menuitem" class="{{$usermenu.2}}" href="{{$usermenu.0}}" title="{{$usermenu.3}}">
									<i class="fa {{$usermenu.4}}"></i> {{$usermenu.1}}
								</a>
							</li>
							{{/foreach}}
							<li class="divider"><hr></li>
							{{if $nav.notifications}}
							<li>
								<a role="menuitem" href="{{$nav.notifications.all.0}}" title="{{$nav.notifications.1}}">
									<i class="fa fa-bell fa-fw" aria-hidden="true"></i> {{$nav.notifications.1}}
								</a>
							</li>
							{{/if}}
							{{if $nav.messages}}
							<li>
								<a role="menuitem" class="nav-commlink {{$nav.messages.2}} {{$sel.messages}}"
									href="{{$nav.messages.0}}" title="{{$nav.messages.3}}">
									<i class="fa fa-envelope fa-fw" aria-hidden="true"></i> {{$nav.messages.1}}
									<span id="mail-update-li" class="nav-mail-badge badge nav-notification"></span>
								</a>
							</li>
							{{/if}}
							<li class="divider"><hr></li>
							{{if $nav.contacts}}
							<li>
								<a role="menuitem" id="nav-menu-contacts-link" class="nav-link {{$nav.contacts.2}}"
									href="{{$nav.contacts.0}}" title="{{$nav.contacts.3}}">
									<i class="fa fa-users fa-fw" aria-hidden="true"></i> {{$nav.contacts.1}}
								</a>
							</li>
							{{/if}}
							{{if $nav.delegation}}
							<li>
								<a role="menuitem" id="nav-delegation-link"
									class="nav-commlink {{$nav.delegation.2}} {{$sel.delegation}}"
									href="{{$nav.delegation.0}}" title="{{$nav.delegation.3}}">
									<i class="fa fa-flag fa-fw" aria-hidden="true"></i> {{$nav.delegation.1}}
								</a>
							</li>
							{{/if}}
							<li>
								<a role="menuitem" id="nav-directory-link" class="nav-link {{$nav.directory.2}}"
									href="{{$nav.directory.0}}" title="{{$nav.directory.3}}">
									<i class="fa fa-sitemap fa-fw" aria-hidden="true"></i>{{$nav.directory.1}}
								</a>
							</li>
							<li class="divider"><hr></li>
							{{if $nav.apps}}
							<li>
								<a role="menuitem" id="nav-apps-link" class="nav-link {{$nav.apps.2}}"
									href="{{$nav.apps.0}}" title="{{$nav.apps.3}}">
									<i class="fa fa-puzzle-piece fa-fw" aria-hidden="true"></i> {{$nav.apps.1}}
								</a>
							</li>
							<li class="divider"><hr></li>
							{{/if}}
							{{if $nav.help}}
							<li>
								<a role="menuitem" id="nav-help-link" class="nav-link {{$nav.help.2}}"
									href="{{$nav.help.0}}" title="{{$nav.help.3}}">
									<i class="fa fa-question-circle fa-fw" aria-hidden="true"></i> {{$nav.help.1}}
								</a>
							</li>
							{{/if}}
							{{if $nav.settings}}
							<li>
								<a role="menuitem" id="nav-settings-link" class="nav-link {{$nav.settings.2}}"
									href="{{$nav.settings.0}}" title="{{$nav.settings.3}}">
									<i class="fa fa-cog fa-fw" aria-hidden="true"></i> {{$nav.settings.1}}
								</a>
							</li>
							{{/if}}
							{{if $nav.admin}}
							<li>
								<a accesskey="a" role="menuitem" id="nav-admin-link" class="nav-link {{$nav.admin.2}}"
									href="{{$nav.admin.0}}" title="{{$nav.admin.3}}">
									<i class="fa fa-user-secret fa-fw" aria-hidden="true"></i> {{$nav.admin.1}}
								</a>
							</li>
							{{/if}}
							{{if $nav.moderation}}
							<li>
								<a accesskey="m" role="menuitem" id="nav-moderation-link" class="nav-link {{$nav.moderation.2}}"
									href="{{$nav.moderation.0}}" title="{{$nav.moderation.3}}">
									<i class="fa fa-gavel fa-fw" aria-hidden="true"></i> {{$nav.moderation.1}}
								</a>
							</li>
							{{/if}}
							<li class="divider"><hr></li>
							<li>
								<a role="menuitem" id="nav-about-link" class="nav-link {{$nav.about.2}}"
									href="{{$nav.about.0}}" title="{{$nav.about.3}}">
									<i class="fa fa-info fa-fw" aria-hidden="true"></i> {{$nav.about.1}}
								</a>
							</li>
							{{if $nav.tos}}
							<li>
								<a role="menuitem" id="nav-tos-link" class="nav-link {{$nav.tos.2}}"
									href="{{$nav.tos.0}}" title="{{$nav.tos.3}}">
									<i class="fa fa-file-text" aria-hidden="true"></i> {{$nav.tos.1}}
								</a>
							</li>
							{{/if}}
							<li class="divider"><hr></li>
							{{if $nav.logout}}
							<li>
								<a role="menuitem" id="nav-logout-link" class="nav-link {{$nav.logout.2}}"
									href="{{$nav.logout.0}}" title="{{$nav.logout.3}}">
									<i class="fa fa fa-sign-out fa-fw" aria-hidden="true"></i> {{$nav.logout.1}}
								</a>
							</li>
							{{else}}
							<li>
								<a role="menuitem" id="nav-login-link" class="nav-login-link {{$nav.login.2}}"
									href="{{$nav.login.0}}" title="{{$nav.login.3}}">
									<i class="fa fa-power-off fa-fw" aria-hidden="true"></i> {{$nav.login.1}}
								</a>
							</li>
							{{/if}}
						</ul>
					</li>
				</ul>
			</div>

			{{* frio's offcanvas right user menu — used by frio's own mobile toggle buttons above *}}
			<div class="offcanvas-right-overlay visible-xs-block"></div>
			<div id="offcanvasUsermenu" class="offcanvas-right visible-xs-block">
				<div class="nav-container">
					<ul role="menu" class="list-group">
						{{if $nav.remote}}
							{{if $nav.sitename}}
								<li role="menuitem" class="nav-sitename list-group-item">{{$nav.sitename}}</li>
							{{/if}}
						{{/if}}
						<li class="list-group-item">
							<img src="{{$userinfo.icon}}" alt="{{$userinfo.name}}"
								style="max-width:15px; max-height:15px; min-width:15px; min-height:15px; width:15px; height:15px;">&nbsp;
							{{$userinfo.name}}{{if $nav.remote}} ({{$nav.remote}}){{/if}}
						</li>
						{{foreach $nav.usermenu as $usermenu}}
						<li class="list-group-item">
							<a role="menuitem" class="{{$usermenu.2}}" href="{{$usermenu.0}}" title="{{$usermenu.3}}">
								<i class="fa {{$usermenu.4}}"></i>&nbsp;{{$usermenu.1}}
							</a>
						</li>
						{{/foreach}}
						{{if $nav.notifications || $nav.contacts || $nav.messages || $nav.delegation}}
						<li class="divider"><hr></li>
						{{/if}}
						{{if $nav.notifications}}
						<li class="list-group-item">
							<a role="menuitem" href="{{$nav.notifications.all.0}}" title="{{$nav.notifications.1}}">
								<i class="fa fa-bell fa-fw" aria-hidden="true"></i> {{$nav.notifications.1}}
							</a>
						</li>
						{{/if}}
						{{if $nav.contacts}}
						<li class="list-group-item">
							<a role="menuitem" class="nav-link {{$nav.contacts.2}}" href="{{$nav.contacts.0}}"
								title="{{$nav.contacts.3}}">
								<i class="fa fa-users fa-fw" aria-hidden="true"></i> {{$nav.contacts.1}}
							</a>
						</li>
						{{/if}}
						{{if $nav.messages}}
						<li class="list-group-item">
							<a role="menuitem" class="nav-link {{$nav.messages.2}} {{$sel.messages}}"
								href="{{$nav.messages.0}}" title="{{$nav.messages.3}}">
								<i class="fa fa-envelope fa-fw" aria-hidden="true"></i> {{$nav.messages.1}}
							</a>
						</li>
						{{/if}}
						{{if $nav.delegation}}
						<li class="list-group-item">
							<a role="menuitem" class="nav-commlink {{$nav.delegation.2}} {{$sel.delegation}}"
								href="{{$nav.delegation.0}}" title="{{$nav.delegation.3}}">
								<i class="fa fa-flag fa-fw" aria-hidden="true"></i> {{$nav.delegation.1}}
							</a>
						</li>
						{{/if}}
						{{if $nav.settings || $nav.admin || $nav.logout}}
						<li class="divider"><hr></li>
						{{/if}}
						{{if $nav.settings}}
						<li class="list-group-item">
							<a role="menuitem" class="nav-link {{$nav.settings.2}}" href="{{$nav.settings.0}}"
								title="{{$nav.settings.3}}">
								<i class="fa fa-cog fa-fw" aria-hidden="true"></i> {{$nav.settings.1}}
							</a>
						</li>
						{{/if}}
						{{if $nav.admin}}
						<li class="list-group-item">
							<a role="menuitem" class="nav-link {{$nav.admin.2}}" href="{{$nav.admin.0}}"
								title="{{$nav.admin.3}}">
								<i class="fa fa-user-secret fa-fw" aria-hidden="true"></i> {{$nav.admin.1}}
							</a>
						</li>
						{{/if}}
						{{if $nav.moderation}}
						<li class="list-group-item">
							<a role="menuitem" class="nav-link {{$nav.moderation.2}}" href="{{$nav.moderation.0}}"
								title="{{$nav.moderation.3}}">
								<i class="fa fa-gavel fa-fw" aria-hidden="true"></i> {{$nav.moderation.1}}
							</a>
						</li>
						{{/if}}
						<li class="divider"><hr></li>
						<li class="list-group-item">
							<a role="menuitem" class="nav-link {{$nav.about.2}}" href="{{$nav.about.0}}"
								title="{{$nav.about.3}}">
								<i class="fa fa-info fa-fw" aria-hidden="true"></i> {{$nav.about.1}}
							</a>
						</li>
						<li class="divider"><hr></li>
						{{if $nav.logout}}
						<li class="list-group-item">
							<a role="menuitem" class="nav-link {{$nav.logout.2}}" href="{{$nav.logout.0}}"
								title="{{$nav.logout.3}}">
								<i class="fa fa fa-sign-out fa-fw" aria-hidden="true"></i> {{$nav.logout.1}}
							</a>
						</li>
						{{else}}
						<li class="list-group-item">
							<a role="menuitem" class="nav-login-link {{$nav.login.2}}" href="{{$nav.login.0}}"
								title="{{$nav.login.3}}">
								<i class="fa fa-power-off fa-fw" aria-hidden="true"></i> {{$nav.login.1}}
							</a>
						</li>
						{{/if}}
					</ul>
				</div>
			</div>
		</div>
	</div>
</nav>

{{* frio mobile search — Bootstrap hides at sm+; CSS hides on mobile (UDP has its own) *}}
<div id="search-mobile" class="hidden-lg hidden-md hidden-sm collapse row well">
	{{if $nav.search}}
	<div class="col-xs-12">
		<form class="navbar-form" role="search" method="get" action="{{$nav.search.0}}">
			<div class="form-group form-group-search">
				<input id="nav-search-input-field-mobile" class="form-control form-search" type="search" name="q"
					placeholder="{{$search_placeholder}}">
				<button class="btn btn-primary btn-sm form-button-search" type="submit">
					<i class="fa fa-search fa-fw fa-lg" aria-hidden="true"></i>
					<span class="sr-only">{{$nav.search.1}}</span>
				</button>
			</div>
		</form>
	</div>
	{{/if}}
</div>

<div id="topbar-second" class="topbar">
	<div class="container">
		<div class="col-lg-3 col-md-3 hidden-sm hidden-xs" id="nav-short-info"></div>
		<div class="col-lg-7 col-md-7 col-sm-11 col-xs-10" id="tabmenu"></div>
		<div class="col-lg-2 col-md-2 col-sm-1 col-xs-2" id="navbar-button"></div>
	</div>
</div>

{{* ================================================================== *}}
{{* MOBILE CHROME — UDP top bar + bottom nav                            *}}
{{* Hidden on desktop via: @media (min-width:768px) { display:none }   *}}
{{* ================================================================== *}}
<header id="udp-topbar" role="banner">
	<button type="button" id="udp-sidebar-toggle" class="udp-topbar-btn"
		aria-label="Sidebar" data-toggle="offcanvas" data-target="aside">
		<i class="fa fa-angle-double-right" aria-hidden="true"></i>
	</button>
	<a href="{{$baseurl}}" class="udp-site-name" aria-label="{{$home}}">~config.sitename~</a>
	<div style="display:flex;align-items:center;gap:4px;">
		{{if $nav.search}}
		<button type="button" id="udp-search-toggle" class="udp-topbar-btn"
			aria-label="{{$nav.search.1}}" aria-expanded="false" aria-controls="udp-search-bar">
			<i class="fa fa-search" aria-hidden="true"></i>
		</button>
		{{/if}}
		<button type="button" id="udp-menu-toggle" class="udp-topbar-btn"
			aria-label="Menu" aria-haspopup="dialog" aria-controls="udp-user-menu">
			<i class="fa fa-ellipsis-v" aria-hidden="true"></i>
		</button>
	</div>
</header>

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

<nav id="udp-bottom-nav" role="navigation" aria-label="Main navigation">

	<a href="{{$baseurl}}/timeline" class="udp-bottom-nav-item {{$sel.network}}"
		aria-label="Home">
		<i class="fa fa-home" aria-hidden="true"></i>
		<span id="net-update" class="nav-network-badge badge nav-notification"></span>
		<span class="udp-nav-label">Home</span>
	</a>

	{{if $nav.notifications}}
	<a href="{{$nav.notifications.all.0}}" class="udp-bottom-nav-item"
		aria-label="{{$nav.notifications.1}}">
		<i class="fa fa-bell" aria-hidden="true"></i>
		<span id="notification-update" class="nav-notification-badge badge nav-notification"></span>
		<span class="udp-nav-label">Alerts</span>
	</a>
	{{/if}}

	<a href="{{$baseurl}}/compose" class="udp-bottom-nav-item udp-bottom-nav-compose"
		aria-label="New post">
		<i class="fa fa-plus-circle" aria-hidden="true"></i>
		<span class="udp-nav-label">Post</span>
	</a>

	{{if $nav.messages}}
	<a href="{{$nav.messages.0}}" class="udp-bottom-nav-item {{$sel.messages}}"
		aria-label="{{$nav.messages.1}}">
		<i class="fa fa-envelope" aria-hidden="true"></i>
		<span id="mail-update" class="nav-mail-badge badge nav-notification"></span>
		<span class="udp-nav-label">Messages</span>
	</a>
	{{/if}}

	<button type="button" id="udp-me-btn" class="udp-bottom-nav-item"
		aria-label="Profile and settings" aria-haspopup="dialog" aria-controls="udp-user-menu">
		<img src="{{$userinfo.icon}}" alt="" class="udp-nav-avatar" aria-hidden="true">
		<span class="udp-nav-label">Me</span>
	</button>

</nav>

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

<script>
(function () {
	var menu   = document.getElementById('udp-user-menu');
	var search = document.getElementById('udp-search-bar');

	function openMenu() {
		menu.classList.add('open');
		menu.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
	}
	function closeMenu() {
		menu.classList.remove('open');
		menu.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
	}

	['udp-me-btn', 'udp-menu-toggle'].forEach(function (id) {
		var el = document.getElementById(id);
		if (el) el.addEventListener('click', openMenu);
	});

	var backdrop = menu && menu.querySelector('.udp-user-menu-backdrop');
	if (backdrop) backdrop.addEventListener('click', closeMenu);
	var closeBtn = menu && menu.querySelector('.udp-menu-close');
	if (closeBtn) closeBtn.addEventListener('click', closeMenu);

	var searchToggle = document.getElementById('udp-search-toggle');
	if (searchToggle && search) {
		searchToggle.addEventListener('click', function () {
			var open = search.classList.toggle('open');
			searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) {
				var inp = search.querySelector('input');
				if (inp) inp.focus();
			}
		});
	}
}());
</script>

{{* Notification template used by frio's notification JS *}}
{{include file="notifications/nav/notify.tpl"}}

{{else}}
{{* ================================================================== *}}
{{* LOGGED-OUT                                                          *}}
{{* ================================================================== *}}

{{* Desktop: frio's standard logged-out navbar (hidden on mobile via CSS) *}}
<nav id="frio-nav-public" class="navbar navbar-fixed-top">
	<div class="container">
		<div class="navbar-header pull-left">
			<a class="navbar-brand" href="{{$baseurl}}">
				<div id="navbrand-container">
					<div id="logo-img"></div>
					<div id="navbar-brand-text">~config.sitename~</div>
					<span class="udp-version">UDP v1.1</span>
				</div>
			</a>
		</div>
		<div class="pull-right">
			<ul class="nav navbar-nav navbar-right">
				<li>
					<a href="login?mode=none" id="nav-login">
						<i class="fa fa-sign-in fa-fw" aria-hidden="true"></i>
						{{$nav.login.3}}
					</a>
				</li>
				<li>
					<a href="{{$nav.about.0}}" id="nav-about" data-toggle="tooltip"
						aria-label="{{$nav.about.3}}" title="{{$nav.about.3}}">
						<i class="fa fa-info fa-fw" aria-hidden="true"></i>
					</a>
				</li>
			</ul>
		</div>
	</div>
</nav>

{{* Mobile: UDP slim top bar (hidden on desktop via CSS) *}}
<nav id="udp-topbar" role="navigation" aria-label="Site navigation"
	style="justify-content:space-between;">
	<a href="{{$baseurl}}" class="udp-site-name">~config.sitename~</a>
	<a href="login?mode=none" class="udp-topbar-btn" aria-label="{{$nav.login.3}}">
		<i class="fa fa-sign-in" aria-hidden="true"></i>
	</a>
</nav>

{{* Placeholders for frio JS compatibility *}}
<div id="topbar-first" aria-hidden="true" style="display:none;"></div>
<div id="topbar-second" aria-hidden="true" style="display:none;">
	<div id="tabmenu"></div>
</div>

{{/if}}
