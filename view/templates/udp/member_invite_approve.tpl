{{*
  * UDP Social — admin approval page for a user-submitted member invite request
  *}}
<div class="generic-page-wrapper" style="max-width:520px; margin:3em auto;">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Approve member invite request</h3>
		</div>
		<div class="panel-body">
			<p>
				<strong>{{$requester_name}}</strong>
				{{if $requester_nick}}<span class="text-muted">(@{{$requester_nick}})</span>{{/if}}
				would like to invite the following person to join this community:
			</p>

			<dl class="dl-horizontal" style="margin: 1em 0;">
				<dt>Name</dt>
				<dd>{{if $friend_name}}{{$friend_name}}{{else}}<em class="text-muted">not provided</em>{{/if}}</dd>
				<dt>Email</dt>
				<dd><strong>{{$friend_email}}</strong></dd>
				{{if $note}}
				<dt>Note</dt>
				<dd>{{$note}}</dd>
				{{/if}}
			</dl>

			<p>Approving will generate a single-use registration link and email it to <strong>{{$friend_email}}</strong>.</p>

			<form action="{{$baseurl}}/udp/member-invite-approve/{{$token}}" method="post">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<button type="submit" class="btn btn-success">Approve &amp; send invite</button>
				<a href="{{$baseurl}}/admin" class="btn btn-default" style="margin-left:.5em;">Dismiss</a>
			</form>

			<p class="text-muted" style="font-size:.9em; margin-top:1.5em;">
				This request expires <strong>{{$expires_str}}</strong>. Dismissing does not send any notification.
			</p>
		</div>
	</div>
</div>
