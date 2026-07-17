{{* UDP Group Circle — group feed *}}
<div class="generic-page-wrapper">
	<div class="page-header" style="display:flex;align-items:baseline;gap:1rem;">
		<h2 style="margin:0;">{{$circle.name}}</h2>
		{{if $is_closed}}<span class="label label-default">Closed</span>{{/if}}
		<span class="text-muted" style="font-size:0.9em;">{{$member_count}} members</span>
		<span style="margin-left:auto;">
			<a href="{{$members_url}}" class="btn btn-sm btn-default">Members</a>
		</span>
	</div>

	{{if $circle.description}}
		<p class="text-muted">{{$circle.description}}</p>
	{{/if}}

	<div class="alert alert-info" style="margin:1rem 0; font-size:0.9em;">
		{{$how_to_post}}
	</div>

	<div class="udp-group-feed" style="margin-top:1rem;">
		{{foreach $items as $item}}
		<div class="panel panel-default" style="margin-bottom:0.75rem;">
			<div class="panel-body">
				<div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:0.5rem;">
					<img src="{{$item.author-avatar}}" style="width:32px;height:32px;border-radius:50%;flex-shrink:0;" alt="">
					<div>
						<strong><a href="{{$item.author-link}}">{{$item.author-name}}</a></strong>
						<small class="text-muted" style="display:block;">{{$item.created}}</small>
					</div>
				</div>
				<div class="item-body">{{$item.body_html nofilter}}</div>
			</div>
		</div>
		{{foreachelse}}
		<p class="text-muted" style="font-style:italic;">No posts yet. Mention {{$group_handle}} in a post to share it with this group.</p>
		{{/foreach}}
	</div>

	{{if !$is_closed}}
	<div style="margin-top:2rem;padding-top:1rem;border-top:1px solid #eee;display:flex;gap:0.5rem;">
		<form method="post" action="{{$leave_url}}">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<button type="submit" class="btn btn-sm btn-default"
				onclick="return confirm('Leave this group?')">Leave group</button>
		</form>
		{{if $is_co_owner}}
		<form method="post" action="{{$leave_url}}">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<input type="hidden" name="action" value="close">
			<button type="submit" class="btn btn-sm btn-danger"
				onclick="return confirm('Delete this group? This cannot be undone.')">Delete group</button>
		</form>
		{{/if}}
	</div>
	{{/if}}
</div>
