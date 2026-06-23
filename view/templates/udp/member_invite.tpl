{{*
  * UDP Social — user-initiated friend invite request form
  *}}
<div class="generic-page-wrapper" style="max-width:520px; margin:3em auto;">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Invite a friend</h3>
		</div>
		<div class="panel-body">
			<p>
				Know someone who'd be a good fit for this community? Fill out this form and
				the admin will review your request. If approved, your friend will receive a
				registration link by email.
			</p>

			<form action="{{$baseurl}}/udp/member-invite" method="post">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

				<div class="form-group">
					<label for="friend_name">Friend's name</label>
					<input type="text" id="friend_name" name="friend_name" class="form-control"
						placeholder="Jane Smith" autocomplete="off">
				</div>

				<div class="form-group">
					<label for="friend_email">Friend's email <span class="text-danger">*</span></label>
					<input type="email" id="friend_email" name="friend_email" class="form-control"
						placeholder="jane@example.com" required autocomplete="off">
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
