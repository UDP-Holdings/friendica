{{*
  * UDP Social — Join request landing page (scanned by admin of another node)
  *}}
<div class="generic-page-wrapper" style="max-width:520px; margin:3em auto;">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Node join request</h3>
		</div>
		<div class="panel-body">
			<p>
				<strong>{{$name}}</strong>
				{{if $nick}} <span class="text-muted">(@{{$nick}})</span>{{/if}}
				is a member of <strong><a href="{{$node_url}}" target="_blank" rel="noopener">{{$node}}</a></strong>
				and would like to join your UDP Social node.
			</p>
			<p>Clicking the button below will send a registration invitation to:</p>
			<p><strong>{{$email}}</strong></p>

			<form action="{{$baseurl}}/udp/join-request/{{$token}}" method="post">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<button type="submit" class="btn btn-primary">Send invitation to {{$email}}</button>
			</form>

			{{if $expires_str}}
			<p class="text-muted" style="font-size:.9em; margin-top:1.5em;">
				This request link is valid until <strong>{{$expires_str}}</strong>.
			</p>
			{{/if}}
			<hr>
			<p class="text-muted" style="font-size:.9em;">
				You are seeing this page because you scanned or opened a QR code shared by this person.
				Only send an invitation if you recognise this person and want them on your node.
			</p>
		</div>
	</div>
</div>
