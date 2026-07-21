{{* UDP Group Circle — hub page *}}
<div class="generic-page-wrapper">
	<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
		<h2 style="margin:0;flex:1;">{{$circle.name}}{{if $is_closed}} <span class="label label-default" style="font-size:0.6em;vertical-align:middle;">Closed</span>{{/if}}</h2>
	</div>

	{{if $circle.description}}<p class="text-muted" style="margin-top:0;margin-bottom:1rem;">{{$circle.description}}</p>{{/if}}

	<p class="text-muted" style="margin-bottom:1.25rem;">
		{{$member_count}} {{if $member_count == 1}}member{{else}}members{{/if}}
		&nbsp;·&nbsp;
		<span class="text-muted" style="font-family:monospace;font-size:0.9em;">{{$group_handle}}</span>
	</p>

	<div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1.5rem;">
		{{if $timeline_url}}
		<a href="{{$timeline_url}}" class="btn btn-primary">View Posts</a>
		{{/if}}
		<a href="{{$members_url}}" class="btn btn-default">Members &amp; Settings</a>
	</div>

	<p class="text-muted" style="font-size:0.9em;">{{$how_to_post}}</p>
</div>
