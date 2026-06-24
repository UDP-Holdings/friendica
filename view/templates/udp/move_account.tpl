{{* UDP Social — Move Account settings page *}}

<div class="generic-page-wrapper">
	<h2>{{$title}}</h2>

	<div class="well" style="max-width:600px;">
		<p>Moving your account transfers your <strong>follower graph</strong> to a new server via ActivityPub. People who follow you will automatically follow your new account.</p>

		<div class="alert alert-warning">
			<strong>Before you move:</strong>
			<ul style="margin:.5em 0 0; padding-left:1.25em;">
				<li>Create your new account on the destination server first.</li>
				<li>Your posts and media <strong>stay on this server</strong> — they do not transfer.</li>
				<li>This action cannot be undone from this page. Contact UDP Support if you need to reverse it.</li>
			</ul>
		</div>

		<p style="color:#888; font-size:.9em;">Your current address: <strong>{{$addr}}</strong></p>

		<form action="{{$baseurl}}/udp/move-account" method="post">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<div class="form-group">
				<label for="udp-move-target"><strong>Destination account</strong></label>
				<input type="text" id="udp-move-target" name="target" class="form-control"
					placeholder="you@newserver.example" style="max-width:360px; margin-top:.4em;" required autofocus>
				<p class="help-block" style="font-size:.85em;">Enter the handle (<code>user@server.tld</code>) or profile URL of your new account.</p>
			</div>
			<button type="submit" class="btn btn-danger"
				onclick="return confirm('Move your account to the address you entered? This will notify all your followers.');">
				Move my account
			</button>
		</form>
	</div>
</div>
