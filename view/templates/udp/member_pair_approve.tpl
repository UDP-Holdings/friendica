<div class="generic-page-wrapper" style="max-width:560px; margin:3em auto;">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Send node pairing request</h3>
		</div>
		<div class="panel-body">
			<p>
				<strong>{{$requester_nick}}</strong> wants to connect with
				<strong>{{$target_handle}}</strong> on
				<a href="https://{{$target_domain}}" target="_blank" rel="noopener">{{$target_domain}}</a>,
				but that node isn't paired with this one yet.
			</p>

			{{if $note}}
			<blockquote style="font-size:.9em; color:#555; border-left:3px solid #ccc; margin:1em 0; padding:.5em 1em;">
				{{$note}}
			</blockquote>
			{{/if}}

			{{if $contact_handle}}
			<p class="text-muted" style="font-size:.9em;">
				The designated contact admin for <strong>{{$target_domain}}</strong> is
				<strong>{{$contact_handle}}</strong>.
				They'll see the pairing request the next time they visit their Node Pairing page.
			</p>
			{{else}}
			<p class="text-muted" style="font-size:.9em;">
				Could not reach <strong>{{$target_domain}}</strong> to identify its admin.
				The pairing request will still be delivered if the node is reachable.
			</p>
			{{/if}}

			<form action="{{$baseurl}}/udp/member-pair-approve/{{$token}}" method="post" style="margin-top:1.5em;">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<button type="submit" class="btn btn-primary">Send pairing request to {{$target_domain}}</button>
				<a href="{{$baseurl}}/admin/node-pair" class="btn btn-default" style="margin-left:.5em;">Cancel</a>
			</form>
		</div>
	</div>
</div>
