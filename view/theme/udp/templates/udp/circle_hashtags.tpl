{{* UDP — hashtag filters for a contact circle *}}
<div class="generic-page-wrapper">
	<p><a href="{{$back_url}}" class="btn btn-sm btn-default">&larr; Back to circle</a></p>
	<h2>{{$title}}</h2>

	{{if $tags}}
		<ul class="list-group" style="max-width:480px;margin-bottom:1.5rem;">
		{{foreach $tags as $tag}}
			<li class="list-group-item" style="display:flex;align-items:center;justify-content:space-between;">
				<span><strong>#{{$tag|escape}}</strong></span>
				<form method="post" action="/circle/{{$circle.id}}/hashtags" style="margin:0;">
					<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
					<input type="hidden" name="action" value="remove">
					<input type="hidden" name="tag" value="{{$tag|escape}}">
					<button type="submit" class="btn btn-xs btn-danger">{{$label_remove}}</button>
				</form>
			</li>
		{{/foreach}}
		</ul>
	{{else}}
		<p class="text-muted" style="margin-bottom:1.5rem;">{{$label_empty}}</p>
	{{/if}}

	<form method="post" action="/circle/{{$circle.id}}/hashtags" class="form-inline" style="max-width:480px;">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<input type="hidden" name="action" value="add">
		<div class="input-group" style="width:100%;">
			<span class="input-group-addon">#</span>
			<input type="text" name="tag" class="form-control" placeholder="{{$label_tag}}"
				pattern="[a-zA-Z0-9_\-]+" autocomplete="off" autocapitalize="none" spellcheck="false">
			<span class="input-group-btn">
				<button type="submit" class="btn btn-primary">{{$label_add}}</button>
			</span>
		</div>
	</form>
</div>
