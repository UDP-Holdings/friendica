<div class="generic-page-wrapper" style="max-width:520px; margin:3em auto;">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Invite a friend</h3>
		</div>
		<div class="panel-body">
			<p>
				Want to connect with someone? Enter their email address to invite them to join
				this community, or their Fediverse handle if they already have an account on
				another node (e.g. <code>@jane@theirnode.com</code>).
			</p>

			<form action="{{$baseurl}}/udp/member-invite" method="post">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

				<div class="form-group">
					<label for="contact">Email or Fediverse handle <span class="text-danger">*</span></label>
					<input type="text" id="contact" name="contact" class="form-control"
						placeholder="jane@example.com or @jane@theirnode.com" required autocomplete="off">
				</div>

				<div class="form-group">
					<label for="note">Note to admin <span class="text-muted">(optional)</span></label>
					<textarea id="note" name="note" class="form-control" rows="3"
						placeholder="How do you know this person?"></textarea>
				</div>

				<button type="submit" class="btn btn-primary">Send request to admin</button>
			</form>
		</div>
	</div>
</div>
