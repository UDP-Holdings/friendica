{{* UDP Group — create form *}}
<div class="generic-page-wrapper">
	<h2>{{$title}}</h2>

	<form method="post" action="/udp/group/create">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<div class="form-group">
			<label for="gc-name">{{$name_label}}</label>
			<input type="text" id="gc-name" name="name" class="form-control" maxlength="255" required autofocus>
		</div>

		<div class="form-group">
			<label for="gc-desc">{{$desc_label}}</label>
			<textarea id="gc-desc" name="description" class="form-control" rows="3"></textarea>
		</div>

		<button type="submit" class="btn btn-primary">{{$submit_label}}</button>
		<a href="{{$cancel_url}}" class="btn btn-default">Cancel</a>
	</form>
</div>
