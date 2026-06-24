{{*
  * UDP Social override of admin/summary.tpl
  * Strips sysadmin-facing content (PHP/DB settings, raw warning text).
  * Normie-friendly: one contact link if anything looks wrong.
  *}}

<div id="adminpage">
	<h1>Community Settings — Overview</h1>

	{{* Warnings: hide raw Friendica technical text; show a single friendly prompt.
	    TODO: replace mailto stub with a POST to /api/support-request on the orchestrator.
	    The button should POST {node, issues[], timestamp} and orchestrator emails UDP ops.
	    See project_admin_ui_normie_audit.md for full design.
	    FIXME: this block is rendered once at page load and requires a hard refresh to clear.
	    Add HTMX polling (hx-get + hx-trigger="every 60s") on a dedicated endpoint that
	    returns just this fragment so warnings auto-dismiss when the underlying issue resolves. *}}
	{{if $warningtext|count}}
	<div id="admin-warning-message-wrapper">
		<p class="warning-message">
			UDP has detected a problem with your community — you may be experiencing service issues.
			<a href="mailto:support@udp.social?subject=Issue+detected+on+{{$baseurl|escape:'url'}}">Click here</a>
			to notify UDP and we'll fix it.
		</p>
	</div>
	{{/if}}

	{{* Version — useful for confirming the UDP fork is current *}}
	<dl>
		<dt>{{$version_label}}</dt>
		<dd>{{$platform}} {{$codename}} {{$VERSION}}</dd>
	</dl>

	{{* Active addons *}}
	<dl>
		<dt>{{$addons.0}}</dt>
		{{foreach $addons.1 as $p}}
			<dd><a href="{{$baseurl}}/admin/addons/{{$p}}/">{{$p}}</a></dd>
		{{/foreach}}
	</dl>

</div>
