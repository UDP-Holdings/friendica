{{* UDP Group Circle — invitation preview (invitee consent) *}}
<div class="generic-page-wrapper">
	<h2>You've been invited to join <strong>{{$circle.name}}</strong></h2>

	{{if $circle.description}}
	<p class="text-muted">{{$circle.description}}</p>
	{{/if}}

	<p>Review who is in this group before deciding. Once you accept, you can post to the group and see everyone's posts.</p>

	<h3>Current members</h3>
	<div class="list-group" style="max-width:480px;">
	{{foreach $members as $m}}
		<div class="list-group-item" style="display:flex;align-items:center;gap:0.75rem;">
			{{if $m.contact.photo}}
				<img src="{{$m.contact.photo}}" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
			{{/if}}
			<div>
				<strong>{{$m.contact.name}}</strong>
				{{if $m.contact.addr}}<span class="text-muted"> @{{$m.contact.addr}}</span>{{/if}}
				{{if $m.role == 1}}<span class="label label-primary" style="margin-left:6px;">co-owner</span>{{/if}}
			</div>
		</div>
	{{/foreach}}
	</div>

	<div style="margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap;">
		<form method="post" action="{{$action_url}}">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<input type="hidden" name="action" value="accept">
			<button type="submit" class="btn btn-primary">Join group</button>
		</form>
		<form method="post" action="{{$action_url}}">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<input type="hidden" name="action" value="decline">
			<button type="submit" class="btn btn-default"
				onclick="return confirm('Decline this invitation?')">Decline</button>
		</form>
	</div>

	<p class="text-muted" style="margin-top:1rem;font-size:0.9em;">
		If you join, your posts to this group will be visible to all members.
		You can leave at any time from the group members page.
	</p>
</div>
