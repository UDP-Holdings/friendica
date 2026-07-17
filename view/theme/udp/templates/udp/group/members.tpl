{{* UDP Group Circle — membership management *}}
<div class="generic-page-wrapper">
	<h2>{{$circle.name}} — Members</h2>
	<p><a href="{{$back_url}}" class="btn btn-sm btn-default">&larr; Back to group</a></p>

	<h3>Members</h3>
	<div class="list-group">
	{{foreach $members as $m}}
		<div class="list-group-item" style="display:flex;align-items:center;gap:0.75rem;">
			{{if $m.contact.photo}}
				<img src="{{$m.contact.photo}}" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
			{{/if}}
			<div style="flex:1;">
				<strong>{{$m.contact.name}}</strong>
				{{if $m.contact.addr}}<span class="text-muted"> @{{$m.contact.addr}}</span>{{/if}}
				{{if $m.role == 1}}<span class="label label-primary" style="margin-left:6px;">co-owner</span>{{/if}}
			</div>
			{{if $is_co_owner && $m.contact_id != $self_contact_id}}
			<form method="post" action="/udp/group/{{$circle.id}}/members" style="display:inline;">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<input type="hidden" name="contact_id" value="{{$m.contact-id}}">
				{{if $m.role == 0}}
					<button type="submit" name="action" value="promote" class="btn btn-xs btn-default">Make co-owner</button>
				{{/if}}
				<button type="submit" name="action" value="remove" class="btn btn-xs btn-danger"
					onclick="return confirm('Remove this member?')">Remove</button>
			</form>
			{{/if}}
		</div>
	{{/foreach}}
	</div>

	{{if $is_co_owner}}
	<h3 style="margin-top:2rem;">Invite someone</h3>
	<form method="post" action="{{$invite_url}}">
		<input type="hidden" name="form_security_token" value="{{$invite_token}}">
		<div class="input-group" style="max-width:400px;">
			<input type="text" name="handle" class="form-control" placeholder="@user@server.tld or profile URL">
			<span class="input-group-btn">
				<button type="submit" class="btn btn-primary">Propose invite</button>
			</span>
		</div>
		<p class="help-block">All co-owners must accept before the person is added.</p>
	</form>

	{{if $pending_invites}}
	<h3 style="margin-top:2rem;">Pending invites</h3>
	<div class="list-group">
	{{foreach $pending_invites as $inv}}
		<div class="list-group-item" style="display:flex;align-items:center;gap:0.75rem;">
			<div style="flex:1;">
				<strong>{{$inv.target.name}}</strong>
				{{if $inv.target.addr}}<span class="text-muted"> @{{$inv.target.addr}}</span>{{/if}}
				<div class="text-muted" style="font-size:0.85em;margin-top:2px;">
					{{foreach $inv.votes as $cid => $vote}}
						<span class="label {{if $vote === true}}label-success{{elseif $vote === false}}label-danger{{else}}label-default{{/if}}">
							{{if $vote === true}}✓{{elseif $vote === false}}✗{{else}}?{{/if}}
						</span>
					{{/foreach}}
				</div>
			</div>
			<form method="post" action="/udp/group/{{$circle.id}}/invite/{{$inv.id}}/vote" style="display:inline;">
				<input type="hidden" name="form_security_token" value="{{self::getFormSecurityToken('udp_group_vote_' ~ $inv.id)}}">
				<button type="submit" name="vote" value="accept" class="btn btn-xs btn-success">Accept</button>
				<button type="submit" name="vote" value="reject" class="btn btn-xs btn-danger">Reject</button>
			</form>
		</div>
	{{/foreach}}
	</div>
	{{/if}}
	{{/if}}
</div>
