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
									title="Mark list as read"
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
<script>
	initWidget('circle-sidebar', 'circle-sidebar-inflated');

	document.querySelectorAll('.udp-markread').forEach(function(el) {
		el.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var badge = this;
			if (!badge.textContent.trim()) return;
			var circleId = badge.dataset.circleId;
			var token    = badge.dataset.token;
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
	});
</script>
