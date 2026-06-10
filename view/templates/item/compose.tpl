{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
    <h2>{{$l10n.compose_title}}</h2>
    {{if $l10n.always_open_compose}}
    <p>{{$l10n.always_open_compose nofilter}}</p>
    {{/if}}
    <div id="profile-jot-wrapper">
        <form class="comment-edit-form" data-item-id="{{$id}}" id="comment-edit-form-{{$id}}" action="compose/{{$type}}" method="post">
            <input type="hidden" name="post_id_random" value="{{$rand_num}}" />
            <input type="hidden" name="post_type" value="{{$posttype}}" />
            <input type="hidden" name="wall" value="{{$wall}}" />

            <div id="jot-title-wrap">
                <input type="text" name="title" id="jot-title" class="jothidden jotforms form-control" placeholder="{{$l10n.placeholdertitle}}" title="{{$l10n.placeholdertitle}}" value="{{$title}}" tabindex="1" dir="auto" />
            </div>
			{{if $l10n.placeholdersummary}}
			<div id="jot-summary-wrap">
				<input type="text" name="summary" id="jot-summary" class="jothidden jotforms form-control" placeholder="{{$l10n.placeholdersummary}}" title="{{$l10n.placeholdersummary}}" value="{{$summary}}" tabindex="2" dir="auto" />
			</div>
			{{/if}}
            {{if $l10n.placeholdercategory}}
                <div id="jot-category-wrap">
                    <input name="category" id="jot-category" class="jothidden jotforms form-control" type="text" placeholder="{{$l10n.placeholdercategory}}" title="{{$l10n.placeholdercategory}}" value="{{$category}}" tabindex="3" dir="auto" />
                </div>
            {{/if}}

            <div class="comment-edit-bb-{{$id}} btn-toolbar clearfix" role="toolbar">
                <div class="btn-group">
                    <button type="button" class="btn btn-default bb-img" aria-label="{{$l10n.edimg}}" title="{{$l10n.edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="{{$id}}" tabindex="4">
                        <i class="fa fa-picture-o"></i>
                    </button>
                    <button type="button" class="btn btn-default bb-attach" aria-label="{{$l10n.edattach}}" title="{{$l10n.edattach}}" ondragenter="return commentLinkDrop(event, {{$id}});" ondragover="return commentLinkDrop(event, {{$id}});" ondrop="commentLinkDropper(event);" onclick="commentGetLink({{$id}}, '{{$l10n.prompttext}}');" tabindex="5">
                        <i class="fa fa-paperclip"></i>
                    </button>
                    <button type="button" id="button_emojipicker" class="btn btn-default emojis" aria-label="{{$l10n.edemojis}}" title="{{$l10n.edemojis}}" tabindex="6">
                      <i class="fa fa-smile-o"></i>
                    </button>
                </div>

                <div class="pull-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default bb-url" aria-label="{{$l10n.edurl}}" title="{{$l10n.edurl}}" onclick="insertFormatting('url',{{$id}});" tabindex="7">
                            <i class="fa fa-link"></i>
                        </button>
                        <button type="button" class="btn btn-default bb-url" aria-label="{{$l10n.edembed}}" title="{{$l10n.edembed}}" onclick="insertFormatting('embed',{{$id}});" tabindex="8">
                            <i class="fa fa-play"></i>
                        </button>
                        <button type="button" class="btn btn-default underline" aria-label="{{$l10n.eduline}}" title="{{$l10n.eduline}}" onclick="insertFormatting('u',{{$id}});" tabindex="9">
                            <i class="fa fa-underline"></i>
                        </button>
                        <button type="button" class="btn btn-default italic" aria-label="{{$l10n.editalic}}" title="{{$l10n.editalic}}" onclick="insertFormatting('i',{{$id}});" tabindex="10">
                            <i class="fa fa-italic"></i>
                        </button>
                        <button type="button" class="btn btn-default bold" aria-label="{{$l10n.edbold}}" title="{{$l10n.edbold}}" onclick="insertFormatting('b',{{$id}});" tabindex="11">
                            <i class="fa fa-bold"></i>
                        </button>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-default quote" aria-label="{{$l10n.edquote}}" title="{{$l10n.edquote}}" onclick="insertFormatting('quote',{{$id}});" tabindex="12">
                            <i class="fa fa-quote-left"></i>
                        </button>
                        <button type="button" class="btn btn-default bb-url" aria-label="{{$l10n.contentwarn}}" title="{{$l10n.contentwarn}}" onclick="insertFormatting('abstract',{{$id}});" tabindex="13">
                            <i class="fa fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-default code" aria-label="{{$l10n.edcode}}" title="{{$l10n.edcode}}" onclick="insertFormatting('code',{{$id}});" tabindex="14">
                            <i class="fa fa-code"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="dropzone-{{$id}}" class="dropzone">
                <p>
                    <textarea id="comment-edit-text-{{$id}}" class="comment-edit-text form-control text-autosize expandable-textarea" name="body" placeholder="{{$l10n.default}}" rows="18" tabindex="3" dir="auto" onkeydown="sendOnCtrlEnter(event, 'comment-edit-submit-{{$id}}')">{{$body}}</textarea>
                </p>
            </div>
            <div class="comment-edit-submit-wrapper clearfix">
                {{if $type == 'post'}}
                    <div id="compose-additional-settings-location">
                        <button type="button" name="permissions" class="btn btn-default" id="toggle-permissions" title="{{$l10n.toggle_permissions_tooltip}}" onclick="togglePermissions()" tabindex="5">
                            <i class="fa fa-ellipsis-h"></i> {{$l10n.toggle_permissions}}
                        </button>
                        <input type="text" name="location" class="form-control" id="jot-location" value="{{$location}}" placeholder="{{$l10n.location_set}}" tabindex="6" />
                        <button type="button" class="btn btn-default" id="profile-location"
                            data-title-set="{{$l10n.location_set}}"
                            data-title-disabled="{{$l10n.location_disabled}}"
                            data-title-unavailable="{{$l10n.location_unavailable}}"
                            data-title-clear="{{$l10n.location_clear}}"
                            title="{{$l10n.location_set}}"
                            tabindex="7">
                            <i class="fa fa-map-marker" aria-hidden="true"></i>
                        </button>
                    </div>
                {{/if}}
                <div>
                    <span role="presentation" id="profile-rotator-wrapper">
                        <img role="presentation" id="profile-rotator" src="images/rotator.gif" alt="{{$l10n.wait}}" title="{{$l10n.wait}}" style="display: none;" />
                    </span>
                    <span role="presentation" id="character-counter" class="grey text-info"></span>
                    <button type="button" class="btn btn-default" onclick="preview_comment_toggle({{$id}}, '{{$l10n.preview}}');" id="comment-edit-preview-link-{{$id}}" tabindex="8">
                        <i class="fa fa-eye"></i> <span id="preview-btn-text-{{$id}}">{{$l10n.preview}}</span>
                    </button>
                    <button type="submit" class="btn btn-primary" id="comment-edit-submit-{{$id}}" name="submit" tabindex="9"><i class="fa fa-paper-plane"></i> {{$l10n.submit}}</button>
                </div>
            </div>

            <div id="comment-edit-preview-{{$id}}" class="comment-edit-preview" style="display:none;"></div>

            <div id="permissions-section" style="display: none;">
                {{if $type == 'post'}}
                    <h3>{{$l10n.visibility_title}}</h3>
                    {{$acl_selector nofilter}}

                    <div class="jotplugins">
                        {{$jotplugins nofilter}}
                    </div>

        			{{include file="field_checkbox.tpl" field=$sensitive}}
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
    </div>
</div>
<script>
    dzFactory.setupDropzone('#dropzone-{{$id}}', 'comment-edit-text-{{$id}}');

    function preview_comment_toggle(id, originalText) {
        var previewPane = document.getElementById('comment-edit-preview-' + id);
        var btnTextSpan = document.getElementById('preview-btn-text-' + id);
        if (previewPane.style.display === 'block') {
            previewPane.style.display = 'none';
            btnTextSpan.textContent = originalText;
        } else {
            preview_comment(id);
            btnTextSpan.textContent = "Close preview";
            previewPane.style.display = 'block';
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        var textareas = document.querySelectorAll(".expandable-textarea");

        textareas.forEach(function(textarea) {
            textarea.addEventListener("input", function() {
                this.style.height = "auto";
                this.style.height = (this.scrollHeight) + "px";
            });

            // Set initial height
            textarea.style.height = "auto";
            textarea.style.height = (textarea.scrollHeight) + "px";
        });
    });

    function togglePermissions() {
        var permissionsSection = document.getElementById('permissions-section');
        if (permissionsSection.style.display === 'none' || permissionsSection.style.display === '') {
            permissionsSection.style.display = 'block';
        } else {
            permissionsSection.style.display = 'none';
        }
    }

    var formSubmitting = false;

    function setFormSubmitting() {
        formSubmitting = true;
    }

    document.addEventListener("DOMContentLoaded", function() {
        var textareas = document.querySelectorAll(".expandable-textarea");

        textareas.forEach(function(textarea) {
            textarea.style.height = "auto";
            textarea.style.height = (textarea.scrollHeight) + "px";

            const savedContent = localStorage.getItem(`comment-edit-text-${textarea.id}`);
            const lastSaved = localStorage.getItem(`last-saved-${textarea.id}`);

            if (savedContent && lastSaved) {
                const currentTime = new Date().getTime();
                const timeElapsed = currentTime - parseInt(lastSaved, 10);

                if (timeElapsed <= 600000) {
                    textarea.value = savedContent;
                    textarea.style.height = "auto";
                    textarea.style.height = (textarea.scrollHeight) + "px";
                } else {
                    localStorage.removeItem(`comment-edit-text-${textarea.id}`);
                    localStorage.removeItem(`last-saved-${textarea.id}`);
                }
            }
        });
    });

    setInterval(() => {
        var textareas = document.querySelectorAll(".expandable-textarea");
        textareas.forEach(function(textarea) {
            if (textarea.value.trim() !== "") {
                localStorage.setItem(`comment-edit-text-${textarea.id}`, textarea.value);
                const currentTime = new Date().getTime();
                localStorage.setItem(`last-saved-${textarea.id}`, currentTime.toString());
            }
        });
    }, 5000);

    function setFormSubmitting() {
        formSubmitting = true;
        var textareas = document.querySelectorAll(".expandable-textarea");
        textareas.forEach(function(textarea) {
            localStorage.removeItem(`comment-edit-text-${textarea.id}`);
            localStorage.removeItem(`last-saved-${textarea.id}`);
        });
    }

    window.addEventListener("beforeunload", function (event) {
        if (!formSubmitting) {
            var textField = document.getElementById('comment-edit-text-{{$id}}').value.trim();
            if (textField.length > 0) {
                var confirmationMessage = 'Are you sure you want to reload the page? All unsaved changes will be lost.';
                event.returnValue = confirmationMessage;
                return confirmationMessage;
            }
        }
    });

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

	// Draft persistence (localStorage, 10-minute TTL)
	document.addEventListener('DOMContentLoaded', function() {
		document.querySelectorAll('.expandable-textarea').forEach(function(textarea) {
			textarea.style.height = 'auto';
			textarea.style.height = textarea.scrollHeight + 'px';

			var saved    = localStorage.getItem('comment-edit-text-' + textarea.id);
			var lastSave = localStorage.getItem('last-saved-' + textarea.id);
			if (saved && lastSave && (new Date().getTime() - parseInt(lastSave, 10)) <= 600000) {
				textarea.value = saved;
				textarea.style.height = 'auto';
				textarea.style.height = textarea.scrollHeight + 'px';
			} else {
				localStorage.removeItem('comment-edit-text-' + textarea.id);
				localStorage.removeItem('last-saved-' + textarea.id);
			}
		});
	});

	setInterval(function() {
		document.querySelectorAll('.expandable-textarea').forEach(function(textarea) {
			if (textarea.value.trim()) {
				localStorage.setItem('comment-edit-text-' + textarea.id, textarea.value);
				localStorage.setItem('last-saved-' + textarea.id, new Date().getTime().toString());
			}
		});
	}, 5000);

	function togglePermissions() {
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
		document.querySelectorAll('.expandable-textarea').forEach(function(textarea) {
			localStorage.removeItem('comment-edit-text-' + textarea.id);
			localStorage.removeItem('last-saved-' + textarea.id);
		});
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

			// Expand photo tokens for preview without mutating the textarea
			var expanded     = window.PhotoTokenizer ? window.PhotoTokenizer.expand(textarea.value) : textarea.value;
			var originalVal  = textarea.value;
			textarea.value   = expanded;
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
}());
</script>
