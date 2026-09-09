{{*
  * Admin — Takedown Requests
  * Three views: list, new (intake form), review (post preview + action)
  *}}

<div id="adminpage">
<h1>{{$title}} - {{$page}}</h1>

{{* ── LIST VIEW ──────────────────────────────────────────────────────────── *}}
{{if $view == "list"}}

<p>
	<a href="{{$baseurl}}/admin/takedown/new" class="btn btn-primary">+ New takedown request</a>
</p>

<h3>Open requests</h3>
{{if $open}}
<table class="table table-striped">
	<thead>
		<tr>
			<th>#</th>
			<th>Received</th>
			<th>Complainant</th>
			<th>Claimed work</th>
			<th>Content URL</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	{{foreach $open as $req}}
	<tr>
		<td>{{$req.id}}</td>
		<td><small>{{$req.received_at}}</small></td>
		<td>{{$req.complainant_name}}</td>
		<td>{{$req.claimed_work}}</td>
		<td><small><a href="{{$req.claimed_url}}" target="_blank" rel="noopener noreferrer">{{$req.claimed_url}}</a></small></td>
		<td><a href="{{$baseurl}}/admin/takedown/review/{{$req.id}}" class="btn btn-warning btn-xs">Review</a></td>
	</tr>
	{{/foreach}}
	</tbody>
</table>
{{else}}
<p class="text-muted">No open takedown requests.</p>
{{/if}}

<h3>Recently resolved <small class="text-muted">(last 50)</small></h3>
{{if $closed}}
<table class="table table-striped">
	<thead>
		<tr>
			<th>#</th>
			<th>Received</th>
			<th>Resolved</th>
			<th>Complainant</th>
			<th>Claimed work</th>
			<th>Outcome</th>
		</tr>
	</thead>
	<tbody>
	{{foreach $closed as $req}}
	<tr>
		<td><a href="{{$baseurl}}/admin/takedown/review/{{$req.id}}">{{$req.id}}</a></td>
		<td><small>{{$req.received_at}}</small></td>
		<td><small>{{$req.actioned_at}}</small></td>
		<td>{{$req.complainant_name}}</td>
		<td>{{$req.claimed_work}}</td>
		<td>
			{{if $req.status == "actioned"}}
			<span class="label label-success">{{$req.action_taken}}</span>
			{{else}}
			<span class="label label-default">dismissed</span>
			{{/if}}
		</td>
	</tr>
	{{/foreach}}
	</tbody>
</table>
{{else}}
<p class="text-muted">No resolved requests yet.</p>
{{/if}}

{{/if}}
{{* ── END LIST ──────────────────────────────────────────────────────────── *}}


{{* ── NEW REQUEST FORM ───────────────────────────────────────────────────── *}}
{{if $view == "new"}}

<p>Record a new takedown request received from a copyright holder or their representative.</p>

<form action="{{$baseurl}}/admin/takedown/new" method="post">
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

	<div class="form-group">
		<label for="complainant_name">Complainant name <span class="text-danger">*</span></label>
		<input type="text" class="form-control" id="complainant_name" name="complainant_name"
		       placeholder="Full legal name of copyright holder or authorised agent" required>
	</div>

	<div class="form-group">
		<label for="complainant_email">Complainant email</label>
		<input type="email" class="form-control" id="complainant_email" name="complainant_email"
		       placeholder="Contact email for correspondence">
	</div>

	<div class="form-group">
		<label for="claimed_work">Claimed copyrighted work <span class="text-danger">*</span></label>
		<input type="text" class="form-control" id="claimed_work" name="claimed_work"
		       placeholder='e.g. "Photograph titled Example, © 2024 Jane Smith"'>
	</div>

	<div class="form-group">
		<label for="claimed_url">URL of allegedly infringing content <span class="text-danger">*</span></label>
		<input type="url" class="form-control" id="claimed_url" name="claimed_url"
		       placeholder="https://this.node/display/...">
		<p class="help-block">Paste the full URL as provided by the complainant. If this is a post on this node, the system will attempt to locate it for inline review.</p>
	</div>

	<div class="form-group">
		<label for="notes">Notes</label>
		<textarea class="form-control" id="notes" name="notes" rows="4"
		          placeholder="Any additional context — how the notice was received, attachments, correspondence summary, etc."></textarea>
	</div>

	<button type="submit" class="btn btn-primary">Record request</button>
	<a href="{{$baseurl}}/admin/takedown" class="btn btn-default">Cancel</a>
</form>

{{/if}}
{{* ── END NEW ────────────────────────────────────────────────────────────── *}}


{{* ── REVIEW VIEW ────────────────────────────────────────────────────────── *}}
{{if $view == "review"}}

<div class="row">
<div class="col-md-6">

<h3>Request details</h3>
<table class="table">
	<tr><th>Status</th><td>
		{{if $row.status == "open"}}<span class="label label-warning">Open</span>
		{{elseif $row.status == "actioned"}}<span class="label label-success">Actioned</span>
		{{else}}<span class="label label-default">Dismissed</span>{{/if}}
	</td></tr>
	<tr><th>Received</th><td>{{$row.received_at}}</td></tr>
	<tr><th>Complainant</th><td>{{$row.complainant_name}}</td></tr>
	{{if $row.complainant_email}}
	<tr><th>Contact</th><td><a href="mailto:{{$row.complainant_email}}">{{$row.complainant_email}}</a></td></tr>
	{{/if}}
	<tr><th>Claimed work</th><td>{{$row.claimed_work}}</td></tr>
	<tr><th>Claimed URL</th><td><a href="{{$row.claimed_url}}" target="_blank" rel="noopener noreferrer">{{$row.claimed_url}}</a></td></tr>
	{{if $row.notes}}
	<tr><th>Notes</th><td style="white-space:pre-wrap">{{$row.notes}}</td></tr>
	{{/if}}
	{{if $row.action_taken}}
	<tr><th>Action taken</th><td>{{$row.action_taken}}</td></tr>
	<tr><th>Resolved</th><td>{{$row.actioned_at}}</td></tr>
	{{/if}}
</table>

</div>
<div class="col-md-6">

<h3>Content on this node</h3>
{{if $post_preview}}
	{{if $post_preview.found}}
	<div class="panel panel-default">
		<div class="panel-heading">
			<strong>{{$post_preview.author_name}}</strong>
			<small class="text-muted"> — {{$post_preview.created}}</small>
			<a href="{{$post_preview.plink}}" target="_blank" rel="noopener noreferrer" class="pull-right">
				<small>Open post ↗</small>
			</a>
		</div>
		<div class="panel-body" style="white-space:pre-wrap; font-size:.9em; max-height:300px; overflow-y:auto;">{{$post_preview.body}}</div>
	</div>
	<p class="text-muted"><small>Review the post above. Does it reproduce the complainant's claimed work?</small></p>
	{{elseif $post_preview.deleted}}
	<div class="alert alert-info">This post has already been deleted from this node.</div>
	{{else}}
	<div class="alert alert-warning">Post not found on this node. It may be on a remote node, or the URL is incorrect.</div>
	{{/if}}
{{else}}
	<div class="alert alert-warning">No post URI could be resolved from the claimed URL. Locate the content manually before taking action.</div>
{{/if}}

</div>
</div>{{* /row *}}

{{if $row.status == "open"}}
<hr>
<h3>Take action</h3>
<p>Document your decision below. This record will be retained for safe harbour purposes.</p>

<form action="{{$baseurl}}/admin/takedown/review/{{$row.id}}" method="post">
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

	<div class="form-group">
		<label>Decision <span class="text-danger">*</span></label>
		<div class="radio">
			<label>
				<input type="radio" name="decision" value="delete_post" required>
				Delete the post — content infringes the claimed work
			</label>
		</div>
		<div class="radio">
			<label>
				<input type="radio" name="decision" value="delete_post_block_account">
				Delete the post <strong>and</strong> block the account
			</label>
		</div>
		<div class="radio">
			<label>
				<input type="radio" name="decision" value="dismiss">
				Dismiss — no infringement found (or notice is defective)
			</label>
		</div>
	</div>

	<div class="form-group">
		<label for="notes_review">Reviewer notes</label>
		<textarea class="form-control" id="notes_review" name="notes" rows="4"
		          placeholder="Record your reasoning — what you reviewed, why you reached this decision."
		          >{{$row.notes}}</textarea>
	</div>

	<button type="submit" class="btn btn-danger"
	        onclick="return confirm('This action will be logged. Proceed?');">
		Confirm decision
	</button>
	<a href="{{$baseurl}}/admin/takedown" class="btn btn-default">Back to list</a>
</form>
{{else}}
<p><a href="{{$baseurl}}/admin/takedown" class="btn btn-default">← Back to list</a></p>
{{/if}}

{{/if}}
{{* ── END REVIEW ─────────────────────────────────────────────────────────── *}}

</div>{{* /adminpage *}}
