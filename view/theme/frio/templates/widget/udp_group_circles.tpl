<nav>
	<span id="udp-groups-sidebar-inflated" class="widget inflated fakelink">
		<button class="fakelink" onclick="openCloseWidget('udp-groups-sidebar', 'udp-groups-sidebar-inflated');" aria-expanded="false">
			<h3>{{$title}}</h3>
		</button>
	</span>
	<div class="widget" id="udp-groups-sidebar">
		<div id="sidebar-udp-groups-header" class="sidebar-widget-header">
			<button class="fakelink" onclick="openCloseWidget('udp-groups-sidebar', 'udp-groups-sidebar-inflated');" aria-expanded="true">
				<h3>{{$title}}</h3>
			</button>
			<a class="widget-action-top pull-right widget-action faded-icon" href="{{$create_url}}" data-toggle="tooltip" title="{{$create_txt}}">
				<i class="fa fa-plus" aria-hidden="true"></i>
			</a>
		</div>
		<div id="sidebar-udp-groups-list" class="sidebar-widget-list">
			<ul id="sidebar-udp-groups-ul">
				{{foreach $circles as $circle}}
					<li class="sidebar-circle-li" style="display:flex;align-items:center;">
						<a id="sidebar-udp-group-{{$circle.id}}" class="sidebar-circle-element" href="{{$circle.href}}" style="flex:1;">{{$circle.name}}</a>
						<a href="{{$circle.members_href}}" class="faded-icon" title="Members &amp; Settings" style="padding:0 4px;"><i class="fa fa-cog" aria-hidden="true"></i></a>
					</li>
				{{foreachelse}}
					<li class="sidebar-circle-li faded-text">{{$empty_txt}}</li>
				{{/foreach}}
			</ul>
			{{if $invitations}}
			<ul id="sidebar-udp-invitations-ul" style="margin-top:0.5rem;border-top:1px solid rgba(128,128,128,0.2);padding-top:0.5rem;">
				{{foreach $invitations as $inv}}
					<li class="sidebar-circle-li">
						<a href="{{$inv.preview_url}}" style="flex:1;font-style:italic;" title="Pending invitation — click to accept or decline">
							<i class="fa fa-envelope" aria-hidden="true" style="margin-right:4px;color:#c0392b;"></i>{{$inv.name}}
						</a>
					</li>
				{{/foreach}}
			</ul>
			{{/if}}
		</div>
	</div>
</nav>
<script>
	initWidget('udp-groups-sidebar', 'udp-groups-sidebar-inflated');
</script>
