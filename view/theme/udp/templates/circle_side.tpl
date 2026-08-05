{{*
  * UDP Social — circle sidebar with per-list "mark all as read" on badge click.
  * Extends frio/circle_side.tpl; badge becomes a clickable clear button.
  *}}
<nav>
	<span id="circle-sidebar-inflated" class="widget inflated fakelink">
		<button class="fakelink" onclick="openCloseWidget('circle-sidebar', 'circle-sidebar-inflated');" aria-expanded="false">
			<h3>{{$title}}</h3>
		</button>
	</span>
	<div class="widget" id="circle-sidebar">
		<div id="sidebar-circle-header" class="sidebar-widget-header">
			<button class="fakelink" onclick="openCloseWidget('circle-sidebar', 'circle-sidebar-inflated');" aria-expanded="true">
				<h3>{{$title}}</h3>
			</button>
			{{if ! $new_circle}}
				<a class="widget-action-top pull-right widget-action faded-icon" id="sidebar-edit-circle" href="{{$circle_page}}" data-toggle="tooltip" title="{{$edit_circles_text}}">
					<i class="fa fa-pencil" aria-hidden="true"></i>
				</a>
			{{else}}
				<a class="widget-action-top pull-right widget-action faded-icon" id="sidebar-new-circle"
					onclick="javascript:$('#circle-new-form').fadeIn('fast');" data-toggle="tooltip" title="{{$createtext}}">
					<i class="fa fa-plus" aria-hidden="true"></i>
				</a>
				<form id="circle-new-form" action="circle/new" method="post" style="display:none;">
					<div class="form-group">
						<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
						<input name="circle_name" id="id_circle_name" class="form-control input-sm" placeholder="{{$create_circle}}">
					</div>
				</form>
			{{/if}}
		</div>
		<div id="sidebar-circle-list" class="sidebar-widget-list">
			<ul id="sidebar-circle-ul">
				{{foreach $circles as $circle}}
					<li class="sidebar-circle-li circle-{{$circle.id}} {{if $circle.selected}}selected{{/if}}">
						{{if ! $new_circle}}
							{{if $circle.id}}
								<span class="notify badge pull-right udp-markread"
									data-circle-id="{{$circle.id}}"
									data-token="{{$form_security_token_markread}}"
									data-href="{{$circle.href}}"
									title="Circle options"
									style="cursor:pointer"></span>
							{{else}}
								<span class="notify badge pull-right"></span>
							{{/if}}
						{{/if}}
						{{if $circle.cid}}
							<div class="checkbox pull-right circle-checkbox ">
								<input type="checkbox" id="sidebar-circle-checkbox-{{$circle.id}}" class="{{if $circle.selected}}ticked{{else}}unticked {{/if}} action" onclick="return contactCircleChangeMember(this, '{{$circle.id}}','{{$circle.cid}}');" {{if $circle.ismember}}checked="checked" {{/if}} aria-checked="{{if $circle.ismember}}true{{else}}false{{/if}}" />
								<label for="sidebar-circle-checkbox-{{$circle.id}}"></label>
								<div class="clearfix"></div>
							</div>
						{{/if}}
						{{if $circle.edit}}
							<a id="edit-sidebar-circle-element-{{$circle.id}}" class="circle-edit-tool pull-right faded-icon" href="{{$circle.edit.href}}" data-toggle="tooltip" title="{{$edittext}}">
								<i class="fa fa-pencil" aria-hidden="true"></i>
							</a>
						{{/if}}
						<a id="sidebar-circle-element-{{$circle.id}}" class="sidebar-circle-element" href="{{$circle.href}}">{{$circle.text}}</a>
					</li>
				{{/foreach}}

				{{if $uncircled}}<li class="{{if $uncircled_selected}}selected{{/if}} sidebar-circle-li" id="sidebar-uncircled"><a href="nocircle">{{$uncircled}}</a></li>{{/if}}
			</ul>
		</div>
	</div>
</nav>
<div id="udp-markread-popover" role="menu" style="display:none; position:fixed; z-index:9999; border-radius:4px; box-shadow:0 2px 8px rgba(0,0,0,.25); min-width:140px; padding:4px 0; background:#fff; border:1px solid #ccc;">
	<a id="udp-markread-view" href="#" style="display:block; padding:6px 14px; cursor:pointer; text-decoration:none; color:#333; white-space:nowrap;">View list</a>
	<a id="udp-markread-clear" href="#" style="display:block; padding:6px 14px; cursor:pointer; text-decoration:none; color:#333; white-space:nowrap;">Mark as read</a>
</div>
<style>
	#udp-markread-popover a:hover { background:#f5f5f5; }
	@media (prefers-color-scheme: dark) {
		#udp-markread-popover { background:#2a2a2a !important; border-color:#444 !important; }
		#udp-markread-popover a { color:#ddd !important; }
		#udp-markread-popover a:hover { background:#3a3a3a !important; }
	}
</style>
<script>
	initWidget('circle-sidebar', 'circle-sidebar-inflated');

	(function() {
		var popover      = document.getElementById('udp-markread-popover');
		var viewLink     = document.getElementById('udp-markread-view');
		var clearLink    = document.getElementById('udp-markread-clear');
		var activeBadge  = null;

		document.body.appendChild(popover);

		function closePopover() {
			popover.style.display = 'none';
			activeBadge = null;
		}

		function openPopover(badge) {
			activeBadge = badge;
			viewLink.href = badge.dataset.href;

			var rect = badge.getBoundingClientRect();
			var popW = 144;
			popover.style.display = 'block';
			popover.style.top  = (rect.bottom + 4) + 'px';
			popover.style.left = Math.max(4, rect.right - popW) + 'px';
		}

		document.querySelectorAll('.udp-markread').forEach(function(el) {
			el.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				if (!this.textContent.trim()) return;
				if (activeBadge === this && popover.style.display !== 'none') {
					closePopover();
				} else {
					openPopover(this);
				}
			});
		});

		clearLink.addEventListener('click', function(e) {
			e.preventDefault();
			if (!activeBadge) return;
			var badge    = activeBadge;
			var circleId = badge.dataset.circleId;
			var token    = badge.dataset.token;
			closePopover();
			fetch('/circle/markread/' + circleId + '?t=' + encodeURIComponent(token), {
				credentials: 'same-origin',
				redirect:    'follow'
			}).then(function(r) {
				if (r.ok) {
					badge.textContent = '';
					badge.classList.remove('show');
				}
			});
		});

		document.addEventListener('click', function(e) {
			if (popover.style.display !== 'none' && !popover.contains(e.target)) {
				closePopover();
			}
		});
	})();
</script>
