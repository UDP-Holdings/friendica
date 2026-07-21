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
		</div>
	</div>
</nav>
<script>
	initWidget('udp-groups-sidebar', 'udp-groups-sidebar-inflated');
</script>
