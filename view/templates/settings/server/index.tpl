{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="settings-server" class="generic-page-wrapper">
	<h1>{{$l10n.title}} ({{$count}})</h1>

	{{if $join_qr_svg}}
	<div class="well" style="margin-bottom:1.5em;">
		<h3>{{$l10n.join_header}}</h3>
		<p>{{$l10n.join_desc}}</p>
		<div style="display:flex; gap:2em; flex-wrap:wrap; align-items:flex-start;">
			<div style="background:#fff; padding:12px; display:inline-block; border:1px solid #ccc; border-radius:4px;">
				{{$join_qr_svg nofilter}}
			</div>
			<div style="flex:1; min-width:200px;">
				<label for="udp-join-url"><strong>Or share this link</strong></label>
				<input id="udp-join-url" type="text" class="form-control" readonly value="{{$join_url}}"
					onclick="this.select()" style="font-family:monospace; font-size:12px; margin-top:.5em;">
				<button class="btn btn-default btn-sm" style="margin-top:.5em;" onclick="
					navigator.clipboard.writeText(document.getElementById('udp-join-url').value)
						.then(function(){ this.textContent='Copied!'; }.bind(this));
					return false;">Copy to clipboard</button>
				{{if $join_expires_at}}
				<p class="text-muted" style="margin-top:.75em; font-size:.85em;">
					Expires: <span id="udp-join-expires"></span>
					<script>
					(function(){
						var d = new Date({{$join_expires_at}} * 1000);
						document.getElementById('udp-join-expires').textContent = d.toLocaleDateString(undefined, {month:'short',day:'numeric',year:'numeric'});
					}());
					</script>
				</p>
				{{/if}}
				<form method="post" action="" style="margin-top:.5em;" onsubmit="return confirm('Generate a new QR code? The current one will stop working immediately.');">
					<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
					<input type="hidden" name="udp_regenerate_join_token" value="1">
					<button type="submit" class="btn btn-default btn-xs">Regenerate code</button>
				</form>
			</div>
		</div>
	</div>
	{{/if}}

	<p>{{$l10n.desc1 nofilter}}</p>
	<p>{{$l10n.desc2}}</p>

	{{$paginate nofilter}}

	{{if $count == 0}}
		<em>{{$no_servers}}</em>
	{{else}}
		<form action="" method="POST">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

			<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>

			<table class="table table-striped table-condensed table-bordered">
				<tr>
					<th>{{$l10n.siteName}}</th>
					<th><span title="{{$l10n.ignored_title}}">{{$l10n.ignored}} <i class="fa fa-question-circle icon-question-sign"></i></span></th>
					<th>
						<span title="{{$l10n.delete_title}}">
							<i class="fa fa-trash icon-trash" aria-hidden="true" title="{{$l10n.delete}}"></i>
							<span class="sr-only">{{$l10n.delete}}</span>
							<i class="fa fa-question-circle icon-question-sign"></i>
						</span>
					</th>
				</tr>

	{{foreach $servers as $index => $server}}
				<tr>
					<td>
						<a href="{{$server->gserver->url}}">{{($server->gserver->siteName) ? $server->gserver->siteName : $server->gserver->url}} <i class="fa fa-external-link"></i></a>
					</td>
					<td>
										{{include file="field_checkbox.tpl" field=$ignoredCheckboxes[$index]}}
					</td>
					<td>
										{{include file="field_checkbox.tpl" field=$deleteCheckboxes[$index]}}
					</td>
				</tr>
	{{/foreach}}

			</table>
			<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>
		</form>

		{{$paginate nofilter}}
	{{/if}}
</div>
