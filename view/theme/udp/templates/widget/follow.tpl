{{*
  * UDP Social override of widget/follow.tpl
  * Adds "Invite by email" below the standard handle/URL connect form.
  *}}

<nav id="follow-sidebar" class="widget">
	<h3>{{$connect}}</h3>

	<form action="contact/follow" method="post">
		<div class="form-group form-group-search">
			<input id="side-follow-url" class="search-input form-control form-search" type="text" name="follow-url" value="{{$value}}" placeholder="{{$hint}}" data-toggle="tooltip" />
			<button id="side-follow-submit" class="btn btn-default btn-sm form-button-search" type="submit">{{$follow}}</button>
		</div>
	</form>

	<hr style="margin:1em 0;">

	<h4 style="font-size:1em; margin-bottom:.4em;">Invite by email</h4>
	<p style="font-size:.85em; color:#888; margin-bottom:.6em;">Don't have their handle? Send a connection invite to their email address.</p>
	<form action="contact/invite" method="post">
		<div class="form-group form-group-search">
			<input class="search-input form-control form-search" type="email" name="invite_email" placeholder="their@email.example" required />
			<button class="btn btn-default btn-sm form-button-search" type="submit">Send invite</button>
		</div>
	</form>
</nav>
