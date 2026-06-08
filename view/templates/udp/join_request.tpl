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
				is a member of <strong>{{$node}}</strong> and would like to join your UDP Social node.
			</p>
			<p>To invite them, send a registration link to:</p>
			<p>
				<a href="mailto:{{$email}}" class="btn btn-primary">{{$email}}</a>
			</p>
			<hr>
			<p class="text-muted" style="font-size:.9em;">
				You are seeing this page because you scanned or opened a QR code shared by this person.
				No action has been taken automatically. Only send an invitation if you recognise this person
				and want them on your node.
			</p>
		</div>
	</div>
</div>
