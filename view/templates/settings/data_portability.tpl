{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h2>{{$title}}</h2>

{{* ── Export ─────────────────────────────────────────────────────────────── *}}

<h3>{{$export_title}}</h3>
<p>{{$export_intro}}</p>

<dl>
	<dt><a href="{{$export_data_url}}" class="btn btn-default">{{$export_data_label}}</a></dt>
	<dd>{{$export_data_desc}}</dd>
</dl>

<dl>
	<dt><a href="{{$export_media_url}}" class="btn btn-default">{{$export_media_label}}</a></dt>
	<dd>{{$export_media_desc}}</dd>
</dl>

<dl>
	<dt><a href="{{$export_full_url}}" class="btn btn-default">{{$export_full_label}}</a></dt>
	<dd>{{$export_full_desc}}</dd>
</dl>

{{* ── Restore / Import ───────────────────────────────────────────────────── *}}

<h3>{{$import_title}}</h3>
<p>{{$import_intro}}</p>
<p><strong>{{$import_warn}}</strong></p>

<form action="settings/data-portability" method="post" enctype="multipart/form-data" id="data-portability-import-form">
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

	<div class="form-group">
		<label for="id_import_media">{{$import_field.1}}</label>
		<input type="file" id="id_import_media" name="import_media" accept=".zip" class="form-control">
		<span class="help-block">{{$import_field.3}}</span>
	</div>

	<div class="form-group">
		<button type="submit" class="btn btn-primary" id="data-portability-submit">{{$submit}}</button>
	</div>
</form>

{{* ── Account Migration links ────────────────────────────────────────────── *}}

<h3>{{$account_links_title}}</h3>
<dl>
	<dt><a href="{{$account_export_url}}">{{$account_export_label}}</a></dt>
	<dd></dd>
</dl>
<dl>
	<dt><a href="{{$account_import_url}}">{{$account_import_label}}</a></dt>
	<dd></dd>
</dl>
