{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<style>
.compose-split-pane {
	display: flex;
	gap: 1rem;
	align-items: flex-start;
	margin-bottom: 0.5rem;
}
.compose-editor-pane  { flex: 1 1 50%; min-width: 0; }
.compose-preview-pane { flex: 1 1 50%; min-width: 0; }

.compose-preview-label {
	font-size: 0.72em;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.07em;
	color: #aaa;
	padding-bottom: 5px;
	border-bottom: 1px solid #e8e8e8;
	margin-bottom: 10px;
}
.comment-edit-preview {
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 14px 16px;
	min-height: 220px;
	background: #fafafa;
	overflow-y: auto;
	max-height: 640px;
	transition: opacity 0.15s ease;
}
.comment-edit-preview.is-loading { opacity: 0.45; }
.compose-preview-placeholder {
	color: #bbb;
	font-style: italic;
	text-align: center;
	padding-top: 48px;
	margin: 0;
}

/* Photo chip strip */
#compose-chip-strip-{{$id}} {
	display: none;
	flex-wrap: wrap;
	gap: 6px;
	padding: 6px 0 4px;
}
#compose-chip-strip-{{$id}}.has-chips { display: flex; }
.photo-chip {
	position: relative;
	width: 72px;
	height: 72px;
	border-radius: 6px;
	overflow: hidden;
	background: #eee;
	flex-shrink: 0;
}
.photo-chip img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
}
.photo-chip-remove {
	position: absolute;
	top: 2px;
	right: 2px;
	width: 18px;
	height: 18px;
	border-radius: 50%;
	background: rgba(0,0,0,0.55);
	color: #fff;
	font-size: 11px;
	line-height: 18px;
	text-align: center;
	cursor: pointer;
	border: none;
	padding: 0;
}
.photo-chip-remove:hover { background: rgba(200,0,0,0.8); }

@media (max-width: 767px) {
	.compose-split-pane {
		flex-direction: column;
	}
	.compose-editor-pane,
	.compose-preview-pane { width: 100%; }
	.photo-chip { width: 60px; height: 60px; }
}

/* ── Media Library Drawer ─────────────────────────────────────────────────── */
#udp-media-drawer-{{$id}} {
	display: none;
	flex-direction: column;
	border: 1px solid #ddd;
	border-radius: 8px;
	background: #fff;
	margin-top: 8px;
	overflow: hidden;
	max-height: 420px;
}
#udp-media-drawer-{{$id}}.is-open { display: flex; }

.udp-drawer-toolbar {
	display: flex;
	align-items: center;
	gap: 6px;
	padding: 8px 10px;
	border-bottom: 1px solid #eee;
	flex-shrink: 0;
}
.udp-drawer-search {
	flex: 1;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 4px 8px;
	font-size: 0.85em;
}
.udp-drawer-manage-link {
	font-size: 0.78em;
	white-space: nowrap;
	color: #666;
	text-decoration: none;
}
.udp-drawer-manage-link:hover { color: #333; }

.udp-album-pills {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
	padding: 6px 10px;
	border-bottom: 1px solid #eee;
	flex-shrink: 0;
}
.udp-album-pill {
	padding: 2px 10px;
	border-radius: 12px;
	border: 1px solid #ccc;
	background: #f5f5f5;
	font-size: 0.78em;
	cursor: pointer;
	white-space: nowrap;
}
.udp-album-pill.active {
	background: #555;
	color: #fff;
	border-color: #555;
}

.udp-media-scroll {
	overflow-y: auto;
	flex: 1;
	padding: 8px 10px;
}
.udp-media-group-label {
	font-size: 0.72em;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.06em;
	color: #999;
	margin: 6px 0 4px;
}
.udp-media-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
	gap: 4px;
	margin-bottom: 8px;
}
.udp-media-thumb {
	width: 100%;
	aspect-ratio: 1;
	object-fit: cover;
	border-radius: 4px;
	cursor: pointer;
	background: #eee;
	border: 2px solid transparent;
	transition: border-color 0.1s;
}
.udp-media-thumb:hover { border-color: #555; }
.udp-media-thumb-placeholder {
	width: 100%;
	aspect-ratio: 1;
	border-radius: 4px;
	background: #eee;
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	border: 2px solid transparent;
	font-size: 1.5em;
	color: #aaa;
}
.udp-media-thumb-placeholder:hover { border-color: #555; }
.udp-drawer-empty {
	text-align: center;
	color: #bbb;
	font-style: italic;
	padding: 24px 0;
	font-size: 0.88em;
}

/* Toolbar icon sizing */
.comment-icon-list .fa {
	font-size: 1.33em;
}

@media (max-width: 767px) {
	#udp-media-drawer-{{$id}}.is-open {
		position: fixed;
		left: 0; right: 0; bottom: 56px; /* above UDP bottom nav */
		max-height: 60vh;
		border-radius: 12px 12px 0 0;
		border-bottom: none;
		z-index: 1050;
		box-shadow: 0 -4px 24px rgba(0,0,0,0.18);
	}
}
</style>

<div class="generic-page-wrapper">
	<h2 id="udp-compose-heading">{{$l10n.compose_title}}</h2>
	{{if $l10n.always_open_compose}}
	<p>{{$l10n.always_open_compose nofilter}}</p>
	{{/if}}
	<div id="profile-jot-wrapper">
		<form class="comment-edit-form" data-item-id="{{$id}}" id="comment-edit-form-{{$id}}" action="{{if $form_action}}{{$form_action}}{{else}}compose/{{$type}}{{/if}}" method="post">
			<input type="hidden" name="post_id_random" value="{{$rand_num}}" />
			<input type="hidden" name="post_type" value="{{$posttype}}" />
			<input type="hidden" name="wall" value="{{$wall}}" />
			{{if $post_id}}<input type="hidden" name="post_id" value="{{$post_id}}" />{{/if}}
			<input type="hidden" name="group_circle_id" id="udp-gc-id-{{$id}}" value="{{$group_circle_id|intval}}" />
			<div id="udp-gc-banner-{{$id}}" class="alert alert-info" style="display:{{if $group_circle_id}}flex{{else}}none{{/if}};align-items:center;gap:8px;margin:4px 0 8px;">
				Posting to <strong id="udp-gc-name-{{$id}}">{{$group_circle_name|escape}}</strong>
				<button type="button" id="udp-gc-clear-{{$id}}" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:1.2em;" aria-label="Remove group circle">&times;</button>
			</div>

			<div id="jot-title-wrap">
				<input type="text" name="title" id="jot-title" class="jothidden jotforms form-control" placeholder="{{$l10n.placeholdertitle}}" title="{{$l10n.placeholdertitle}}" value="{{$title}}" tabindex="1" dir="auto" autocapitalize="words" />
			</div>
			{{if $l10n.placeholdercategory}}
				<div id="jot-category-wrap">
					<input name="category" id="jot-category" class="jothidden jotforms form-control" type="text" placeholder="{{$l10n.placeholdercategory}}" title="{{$l10n.placeholdercategory}}" value="{{$category}}" tabindex="2" dir="auto" />
				</div>
			{{/if}}

			<p class="comment-edit-bb-{{$id}} comment-icon-list">
				<span>
					<label class="btn btn-sm template-icon" aria-label="{{$l10n.uploadmedia}}" title="{{$l10n.uploadmedia}}" tabindex="6" style="cursor:pointer;position:relative;overflow:hidden;margin:0;">
						<i class="fa fa-camera"></i>
						<input type="file" id="profile-upload-media-{{$id}}" accept="image/*,video/*,audio/*,application/*" multiple style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
					</label>
					<button type="button" class="btn btn-sm template-icon bb-img" aria-label="{{$l10n.edimg}}" title="{{$l10n.edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="{{$id}}" tabindex="7">
						<i class="fa fa-picture-o"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon" id="udp-media-drawer-btn-{{$id}}" aria-label="Media library" title="Media library" tabindex="8">
						<i class="fa fa-th"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon bb-attach" aria-label="{{$l10n.edattach}}" title="{{$l10n.edattach}}" ondragenter="return commentLinkDrop(event, {{$id}});" ondragover="return commentLinkDrop(event, {{$id}});" ondrop="commentLinkDropper(event);" onclick="commentGetLink({{$id}}, '{{$l10n.prompttext}}');" tabindex="9">
						<i class="fa fa-paperclip"></i>
					</button>
				</span>
				<span>
					<button type="button" class="btn btn-sm template-icon bb-url" aria-label="{{$l10n.edurl}}" title="{{$l10n.edurl}}" onclick="insertFormatting('url',{{$id}});" tabindex="9">
						<i class="fa fa-link"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon underline" aria-label="{{$l10n.eduline}}" title="{{$l10n.eduline}}" onclick="insertFormatting('u',{{$id}});" tabindex="10">
						<i class="fa fa-underline"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon italic" aria-label="{{$l10n.editalic}}" title="{{$l10n.editalic}}" onclick="insertFormatting('i',{{$id}});" tabindex="11">
						<i class="fa fa-italic"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon bold" aria-label="{{$l10n.edbold}}" title="{{$l10n.edbold}}" onclick="insertFormatting('b',{{$id}});" tabindex="12">
						<i class="fa fa-bold"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon quote" aria-label="{{$l10n.edquote}}" title="{{$l10n.edquote}}" onclick="insertFormatting('quote',{{$id}});" tabindex="13">
						<i class="fa fa-quote-left"></i>
					</button>
					<button id="button_emojipicker" type="button" class="btn btn-sm template-icon emojis" aria-label="{{$l10n.edemojis}}" title="{{$l10n.edemojis}}" tabindex="14">
						<i class="fa fa-smile-o"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon bb-url" aria-label="{{$l10n.contentwarn}}" title="{{$l10n.contentwarn}}" onclick="insertFormatting('abstract',{{$id}});" tabindex="15">
						<i class="fa fa-eye"></i>
					</button>
					<button type="button" class="btn btn-sm template-icon code" aria-label="{{$l10n.edcode}}" title="{{$l10n.edcode}}" onclick="insertFormatting('code',{{$id}});" tabindex="4">
						<i class="fa fa-code"></i>
					</button>
				</span>
			</p>

			<div class="udp-keep-original-row" style="font-size:0.8em;color:var(--text-muted,#888);margin:2px 4px 4px;">
				<label style="cursor:pointer;user-select:none;">
					<input type="checkbox" name="keep_original" id="keep-original-{{$id}}" {{if $keep_original_default}}checked{{/if}} style="vertical-align:middle;margin-right:4px;">
					Keep full-resolution original
				</label>
			</div>
			<div class="compose-split-pane">
				<div class="compose-editor-pane">
					<div id="dropzone-{{$id}}" class="dropzone">
						<p>
							<textarea id="comment-edit-text-{{$id}}" class="comment-edit-text form-control text-autosize expandable-textarea" name="body" placeholder="{{$l10n.default}}" rows="18" tabindex="3" dir="auto" onkeydown="sendOnCtrlEnter(event, 'comment-edit-submit-{{$id}}')">{{$body}}</textarea>
						</p>
					</div>
					<div id="compose-chip-strip-{{$id}}"></div>
					<div id="dz-preview-{{$id}}" class="dropzone"></div>
				</div>
				<div class="compose-preview-pane">
					<div class="compose-preview-label">{{$l10n.preview}}</div>
					<div id="comment-edit-preview-{{$id}}" class="comment-edit-preview">
						<p class="compose-preview-placeholder">{{$l10n.preview_placeholder}}</p>
					</div>
				</div>
			</div>

			<p class="comment-edit-submit-wrapper clearfix">
				{{if $type == 'post'}}
					<span class="pull-left">
						<button type="button" name="permissions" class="btn btn-sm template-icon" id="toggle-permissions" title="{{$l10n.toggle_permissions_tooltip}}" onclick="togglePermissions()" style="margin-right: 10px;" tabindex="5">
							<i class="fa fa-ellipsis-h"></i> {{$l10n.toggle_permissions}}
						</button>
						<input type="text" name="location" class="form-control d-inline-block" id="jot-location" value="{{$location}}" placeholder="{{$l10n.location_set}}" tabindex="16" style="width: auto; display: inline-block;" />
						<button type="button" class="btn btn-sm template-icon" id="profile-location"
							data-title-set="{{$l10n.location_set}}"
							data-title-disabled="{{$l10n.location_disabled}}"
							data-title-unavailable="{{$l10n.location_unavailable}}"
							data-title-clear="{{$l10n.location_clear}}"
							title="{{$l10n.location_set}}"
							tabindex="17">
							<i class="fa fa-map-marker" aria-hidden="true"></i>
						</button>
					</span>
				{{/if}}
				<span class="pull-right">
					<span role="presentation" id="profile-rotator-wrapper">
						<img role="presentation" id="profile-rotator" src="images/rotator.gif" alt="{{$l10n.wait}}" title="{{$l10n.wait}}" style="display: none;" />
					</span>
					<span role="presentation" id="character-counter" class="grey text-info"></span>
					<button type="submit" class="btn btn-primary" id="comment-edit-submit-{{$id}}" name="submit" tabindex="18"><i class="fa fa-envelope"></i> {{$l10n.submit}}</button>
				</span>
			</p>

			<div id="permissions-section">
				{{if $type == 'post'}}
				<h3>{{$l10n.visibility_title}}</h3>
				{{$acl_selector nofilter}}

				<div class="jotplugins">
					{{$jotplugins nofilter}}
				</div>

				{{if $scheduled_at}}{{$scheduled_at nofilter}}{{/if}}
				{{if $created_at}}{{$created_at nofilter}}{{/if}}
				{{else}}
				<input type="hidden" name="circle_allow" value="{{$circle_allow}}"/>
				<input type="hidden" name="contact_allow" value="{{$contact_allow}}"/>
				<input type="hidden" name="circle_deny" value="{{$circle_deny}}"/>
				<input type="hidden" name="contact_deny" value="{{$contact_deny}}"/>
				{{/if}}
			</div>
		</form>

		{{* UDP Media Library Drawer — slide-up panel populated via /udp/media/list *}}
		<div id="udp-media-drawer-{{$id}}" role="dialog" aria-label="Media library">
			<div class="udp-drawer-toolbar">
				<input type="search" class="udp-drawer-search" id="udp-media-search-{{$id}}" placeholder="Search media…" autocomplete="off">
				<a class="udp-drawer-manage-link" href="/udp/media/manager">Manage all →</a>
			</div>
			<div class="udp-album-pills" id="udp-album-pills-{{$id}}">
				<span class="udp-album-pill active" data-album="">All</span>
			</div>
			<div class="udp-media-scroll" id="udp-media-scroll-{{$id}}">
				<div class="udp-drawer-empty">Loading…</div>
			</div>
		</div>
	</div>
</div>
<script>
(function() {
	var FORM_ID     = '{{$id}}';
	var STORAGE_KEY = 'photomap-' + FORM_ID;

	// ── PhotoTokenizer ────────────────────────────────────────────────────────
	// Maintains a map of token → {bbcode, thumb, filename} and renders chips.
	window.PhotoTokenizer = (function() {
		var map      = {};   // token → {bbcode, thumb, filename}
		var counter  = 0;

		function saveMap() {
			try { localStorage.setItem(STORAGE_KEY, JSON.stringify(map)); } catch(e) {}
		}

		function loadMap() {
			try {
				var raw = localStorage.getItem(STORAGE_KEY);
				if (raw) { map = JSON.parse(raw) || {}; }
				// restore counter to max existing index
				Object.keys(map).forEach(function(t) {
					var n = parseInt(t.replace('[photo:', '').replace(']', ''), 10);
					if (!isNaN(n) && n >= counter) { counter = n + 1; }
				});
			} catch(e) {}
		}

		function renderChips() {
			var strip = document.getElementById('compose-chip-strip-' + FORM_ID);
			if (!strip) return;
			strip.innerHTML = '';
			var keys = Object.keys(map);
			if (keys.length === 0) {
				strip.classList.remove('has-chips');
				return;
			}
			strip.classList.add('has-chips');
			keys.forEach(function(token) {
				var entry = map[token];
				var chip  = document.createElement('div');
				chip.className = 'photo-chip';
				chip.title     = entry.filename || '';

				if (entry.thumb) {
					var img    = document.createElement('img');
					img.src    = entry.thumb;
					img.alt    = entry.filename || '';
					chip.appendChild(img);
				}

				var btn   = document.createElement('button');
				btn.type  = 'button';
				btn.className   = 'photo-chip-remove';
				btn.textContent = '×';
				btn.setAttribute('aria-label', 'Remove photo');
				btn.addEventListener('click', function() {
					remove(token);
					// also scrub token from textarea
					var ta = document.getElementById('comment-edit-text-' + FORM_ID);
					if (ta) {
						ta.value = ta.value.split(token + '\n').join('').split(token).join('');
						ta.dispatchEvent(new Event('change', { bubbles: true }));
					}
				});
				chip.appendChild(btn);
				strip.appendChild(chip);
			});
		}

		function add(bbcode, thumb, filename) {
			var token = '[photo:' + (counter++) + ']';
			map[token] = { bbcode: bbcode, thumb: thumb || '', filename: filename || '' };
			saveMap();
			renderChips();
			return token;
		}

		function remove(token) {
			delete map[token];
			saveMap();
			renderChips();
		}

		function expand(text) {
			Object.keys(map).forEach(function(token) {
				text = text.split(token).join(map[token].bbcode);
			});
			return text;
		}

		function clear() {
			map     = {};
			counter = 0;
			try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
			renderChips();
		}

		loadMap();
		// render chips for any tokens restored from localStorage
		document.addEventListener('DOMContentLoaded', renderChips);

		return { add: add, remove: remove, expand: expand, clear: clear, renderChips: renderChips };
	}());

	// ── Hook: file-browser photo insertion ───────────────────────────────────
	// Called by main.js fbrowser.photo.comment handler when this function exists.
	window.commentPhotoInsert = function(textarea, bbcode, img, filename) {
		var thumb = img || '';
		var token = window.PhotoTokenizer.add(bbcode, thumb, filename);

		var start  = textarea.selectionStart;
		var prefix = textarea.value.substring(0, start);
		var suffix = textarea.value.substring(textarea.selectionEnd);
		textarea.value = prefix
			+ (prefix.length > 0 && !prefix.endsWith('\n') ? '\n' : '')
			+ token + '\n'
			+ suffix;
		textarea.dispatchEvent(new Event('change', { bubbles: true }));
	};

	// ── Hook: Dropzone upload completion ────────────────────────────────────
	// Called by dropzone-factory.js success handler.
	// Return a token string to replace the default BBCode, or null to pass through.
	window.onDropzoneInsert = function(bbcode, file) {
		// Detect image uploads by BBCode content, not file.type (which may be empty
		// on Android when photos are captured directly from the camera).
		if (typeof bbcode !== 'string' || !bbcode.match(/\[img/)) {
			return null; // attachment upload — insert BBCode as-is
		}
		// Extract the preview thumbnail URL from the BBCode ([img=URL] or [img]URL[/img])
		var thumbMatch = bbcode.match(/\[img[=\]](https?:\/\/[^\]\[]+)/);
		var thumb      = thumbMatch ? thumbMatch[1] : '';
		return window.PhotoTokenizer.add(bbcode, thumb, file.name);
	};

	var dzInstance = dzFactory.setupDropzone('#dropzone-' + FORM_ID, 'comment-edit-text-' + FORM_ID, false, '#dz-preview-' + FORM_ID);
	document.getElementById('profile-upload-media-' + FORM_ID).addEventListener('change', function() {
		var files = this.files;
		for (var i = 0; i < files.length; i++) { dzInstance.addFile(files[i]); }
		this.value = '';
	});

	// Auto-resize textarea
	document.addEventListener('DOMContentLoaded', function() {
		document.querySelectorAll('.expandable-textarea').forEach(function(textarea) {
			textarea.addEventListener('input', function() {
				this.style.height = 'auto';
				this.style.height = this.scrollHeight + 'px';
			});
			textarea.style.height = 'auto';
			textarea.style.height = textarea.scrollHeight + 'px';
		});
	});

	window.togglePermissions = function() {
		var s = document.getElementById('permissions-section');
		s.style.display = (s.style.display === 'none' || !s.style.display) ? 'block' : 'none';
	}

	var formSubmitting = false;
	function setFormSubmitting() {
		formSubmitting = true;
		var ta = document.getElementById('comment-edit-text-' + FORM_ID);
		if (ta && window.PhotoTokenizer) {
			ta.value = window.PhotoTokenizer.expand(ta.value);
			window.PhotoTokenizer.clear();
		}
	}

	window.addEventListener('beforeunload', function(event) {
		if (!formSubmitting && document.getElementById('comment-edit-text-' + FORM_ID).value.trim().length > 0) {
			event.returnValue = 'Are you sure you want to reload the page? All unsaved changes will be lost.';
			return event.returnValue;
		}
	});

	document.getElementById('comment-edit-form-' + FORM_ID).addEventListener('submit', setFormSubmitting);

	// ── Media Library Drawer ────────────────────────────────────────────────
	(function() {
		var drawerEl   = document.getElementById('udp-media-drawer-' + FORM_ID);
		var btnEl      = document.getElementById('udp-media-drawer-btn-' + FORM_ID);
		var scrollEl   = document.getElementById('udp-media-scroll-' + FORM_ID);
		var pillsEl    = document.getElementById('udp-album-pills-' + FORM_ID);
		var searchEl   = document.getElementById('udp-media-search-' + FORM_ID);
		var textarea   = document.getElementById('comment-edit-text-' + FORM_ID);

		var loaded     = false;  // true once first fetch completes
		var allGroups  = [];     // cached from server
		var allAlbums  = [];
		var activeAlbum = '';    // '' = All
		var searchTerm  = '';

		function openDrawer() {
			drawerEl.classList.add('is-open');
			btnEl.classList.add('active');
			if (!loaded) { fetchMedia(); }
		}

		function closeDrawer() {
			drawerEl.classList.remove('is-open');
			btnEl.classList.remove('active');
		}

		btnEl.addEventListener('click', function() {
			drawerEl.classList.contains('is-open') ? closeDrawer() : openDrawer();
		});

		// Close drawer when clicking outside it (use contains so icon children of btnEl don't trigger close)
		document.addEventListener('click', function(e) {
			if (!drawerEl.contains(e.target) && !btnEl.contains(e.target)) {
				closeDrawer();
			}
		});

		function fetchMedia(album) {
			var url = '/udp/media/list';
			if (album !== undefined && album !== '') {
				url += '?album=' + encodeURIComponent(album);
			}
			scrollEl.innerHTML = '<div class="udp-drawer-empty">Loading…</div>';
			fetch(url, { credentials: 'same-origin' })
				.then(function(r) { return r.json(); })
				.then(function(data) {
					if (!data.ok) { scrollEl.innerHTML = '<div class="udp-drawer-empty">Could not load media.</div>'; return; }
					allGroups = data.groups || [];
					allAlbums = data.albums || [];
					loaded    = true;
					renderPills();
					renderGrid();
				})
				.catch(function() {
					scrollEl.innerHTML = '<div class="udp-drawer-empty">Could not load media.</div>';
				});
		}

		function renderPills() {
			pillsEl.innerHTML = '';
			var allPill = document.createElement('span');
			allPill.className = 'udp-album-pill' + (activeAlbum === '' ? ' active' : '');
			allPill.dataset.album = '';
			allPill.textContent = 'All';
			pillsEl.appendChild(allPill);
			allAlbums.forEach(function(name) {
				var p = document.createElement('span');
				p.className = 'udp-album-pill' + (activeAlbum === name ? ' active' : '');
				p.dataset.album = name;
				p.textContent = name;
				pillsEl.appendChild(p);
			});
		}

		pillsEl.addEventListener('click', function(e) {
			var pill = e.target.closest('.udp-album-pill');
			if (!pill) return;
			activeAlbum = pill.dataset.album;
			pillsEl.querySelectorAll('.udp-album-pill').forEach(function(p) {
				p.classList.toggle('active', p.dataset.album === activeAlbum);
			});
			// Re-fetch for album filter (server does the SQL filter)
			fetchMedia(activeAlbum);
		});

		var searchTimer = null;
		searchEl.addEventListener('input', function() {
			searchTerm = this.value.trim().toLowerCase();
			clearTimeout(searchTimer);
			searchTimer = setTimeout(renderGrid, 180);
		});

		function renderGrid() {
			var groups = allGroups;

			// Client-side search filter
			if (searchTerm) {
				groups = groups.map(function(g) {
					return {
						label: g.label,
						items: g.items.filter(function(item) {
							return (item.filename || '').toLowerCase().indexOf(searchTerm) !== -1
								|| (item.album || '').toLowerCase().indexOf(searchTerm) !== -1;
						})
					};
				}).filter(function(g) { return g.items.length > 0; });
			}

			if (!groups.length) {
				scrollEl.innerHTML = '<div class="udp-drawer-empty">No media found.</div>';
				return;
			}

			var html = '';
			groups.forEach(function(group) {
				html += '<div class="udp-media-group-label">' + escHtml(group.label) + '</div>';
				html += '<div class="udp-media-grid">';
				group.items.forEach(function(item) {
					var dataAttr = 'data-item=\'' + escAttr(JSON.stringify(item)) + '\'';
					if (item.thumb_url) {
						html += '<img class="udp-media-thumb" src="' + escHtml(item.thumb_url) + '" alt="' + escHtml(item.filename) + '" loading="lazy" ' + dataAttr + '>';
					} else {
						var icon = item['media-type'] === 'video' ? '▶' : item['media-type'] === 'audio' ? '♫' : '📄';
						html += '<div class="udp-media-thumb-placeholder" ' + dataAttr + '>' + icon + '</div>';
					}
				});
				html += '</div>';
			});
			scrollEl.innerHTML = html;
		}

		scrollEl.addEventListener('click', function(e) {
			var el = e.target.closest('[data-item]');
			if (!el) return;
			var item;
			try { item = JSON.parse(el.dataset.item); } catch(_) { return; }
			insertMediaItem(item);
			closeDrawer();
		});

		function insertMediaItem(item) {
			var insertText;
			if (item['media-type'] === 'photo') {
				// Use PhotoTokenizer so we get a chip + token like camera-button uploads
				var bbcode = '[url=' + item.media_url + '][img]' + item.thumb_url + '[/img][/url]';
				insertText = window.PhotoTokenizer
					? window.PhotoTokenizer.add(bbcode, item.thumb_url, item.filename)
					: bbcode;
			} else if (item['media-type'] === 'video') {
				insertText = '[video]' + item.media_url + '[/video]';
			} else if (item['media-type'] === 'audio') {
				insertText = '[audio]' + item.media_url + '[/audio]';
			} else {
				insertText = '[attachment]' + item.media_url + '[/attachment]';
			}
			var pos = textarea.selectionStart;
			var prefix = textarea.value.substring(0, pos);
			textarea.setRangeText(
				(prefix.length > 0 && !prefix.endsWith('\n') ? '\n' : '') + insertText + '\n',
				pos, pos, 'end'
			);
			textarea.dispatchEvent(new Event('change', { bubbles: true }));
			textarea.focus();
		}

		function escHtml(s) {
			return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
		}
		function escAttr(s) {
			return String(s).replace(/'/g, '&#39;');
		}
	}());

	// Live preview — debounced server-side render
	(function() {
		var DEBOUNCE_MS  = 400;
		var timer        = null;
		var nonce        = 0;
		var textarea     = document.getElementById('comment-edit-text-' + FORM_ID);
		var previewEl    = document.getElementById('comment-edit-preview-' + FORM_ID);
		var placeholder  = '<p class="compose-preview-placeholder">{{$l10n.preview_placeholder}}</p>';

		function fetchPreview() {
			var myNonce = ++nonce;
			previewEl.classList.add('is-loading');

			// Expand photo tokens for preview without mutating the textarea.
			// Also strip !! routing directives — they're not display content.
			var expanded     = window.PhotoTokenizer ? window.PhotoTokenizer.expand(textarea.value) : textarea.value;
			var previewText  = expanded.replace(/[ \t]*!!(\w+)[ \t]*/g, '').trim();
			var originalVal  = textarea.value;
			textarea.value   = previewText;
			var formData     = $('#comment-edit-form-' + FORM_ID).serialize() + '&preview=1';
			textarea.value   = originalVal;

			$.post(
				'item',
				formData,
				function(data) {
					if (myNonce !== nonce) return;
					previewEl.classList.remove('is-loading');
					if (data && data.preview) {
						previewEl.innerHTML = data.preview;
						$('a', previewEl).on('click', function() { return false; });
						document.dispatchEvent(new Event('postprocess_liveupdate'));
					} else {
						previewEl.innerHTML = placeholder;
					}
				},
				'json'
			);
		}

		function schedule() {
			clearTimeout(timer);
			if (!textarea.value.trim()) {
				nonce++;
				previewEl.classList.remove('is-loading');
				previewEl.innerHTML = placeholder;
				return;
			}
			timer = setTimeout(fetchPreview, DEBOUNCE_MS);
		}

		textarea.addEventListener('input',  schedule);
		textarea.addEventListener('change', schedule);

		document.addEventListener('DOMContentLoaded', function() {
			if (textarea.value.trim()) {
				fetchPreview();
			} else {
				previewEl.innerHTML = placeholder;
			}
		});
	}());

	// Wire up @mention and BBcode autocomplete for the compose textarea
	$(function() {
		$('#comment-edit-text-' + FORM_ID).editor_autocomplete(baseurl + '/search/acl');
		$('#comment-edit-text-' + FORM_ID).bbco_autocomplete('bbcode');
	});

	// ── Group Circle banner ──────────────────────────────────────────────────
	// Shows banner and sets group_circle_id when a Group Circle is active.
	// Sources: URL param (PHP-rendered value > 0) or @mention via autocomplete.
	(function () {
		var ACTORS      = {{$group_circle_actors_json nofilter}};
		var gcIdField   = document.getElementById('udp-gc-id-' + FORM_ID);
		var gcNameEl    = document.getElementById('udp-gc-name-' + FORM_ID);
		var gcBannerEl  = document.getElementById('udp-gc-banner-' + FORM_ID);
		var gcClearBtn  = document.getElementById('udp-gc-clear-' + FORM_ID);
		var $section    = $('#permissions-section');
		var mentionAddr = null;

		function actorByAddr(addr) {
			for (var i = 0; i < ACTORS.length; i++) {
				if (ACTORS[i].addr === addr) return ACTORS[i];
			}
			return null;
		}

		function showBanner(name) {
			if (gcNameEl)   gcNameEl.textContent = name;
			if (gcBannerEl) gcBannerEl.style.display = 'flex';
			$section.hide();
		}

		function hideBanner() {
			if (gcBannerEl) gcBannerEl.style.display = 'none';
			$section.show();
		}

		// PHP pre-set from URL: banner already visible, just hide permissions section
		if (gcIdField && parseInt(gcIdField.value, 10) > 0) {
			$section.hide();
		}

		if (gcClearBtn) {
			gcClearBtn.addEventListener('click', function () {
				if (gcIdField) gcIdField.value = '0';
				mentionAddr = null;
				hideBanner();
			});
		}

		$(document).on('udp:group-mention', function (e, item) {
			if (!item.addr) return;
			var actor = actorByAddr(item.addr);
			if (!actor) return;
			mentionAddr = item.addr;
			if (gcIdField) gcIdField.value = actor.circleId;
			showBanner(actor.name);
		});

		$(document).on('input', '#comment-edit-text-' + FORM_ID, function () {
			if (!mentionAddr) return;
			var nick = mentionAddr.split('@')[0];
			var val  = $(this).val();
			// Clear banner if neither @nick nor !!nick is still present
			if (val.indexOf('@' + nick) === -1 && val.indexOf('!!' + nick) === -1) {
				if (gcIdField) gcIdField.value = '0';
				mentionAddr = null;
				hideBanner();
			}
		});
	}());
}());
</script>
