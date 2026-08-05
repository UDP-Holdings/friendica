{{* UDP Group index *}}
<div class="generic-page-wrapper">
	<h2>{{$title}}</h2>

	<p><a href="{{$create_url}}" class="btn btn-primary">{{$create_label}}</a></p>

	{{if $circles}}
		<div class="list-group">
		{{foreach $circles as $circle}}
			<a href="/udp/group/{{$circle.id}}" class="list-group-item">
				<strong>{{$circle.name}}</strong>
				{{if $circle.description}}
					<span class="text-muted"> — {{$circle.description}}</span>
				{{/if}}
				{{if $circle.closed}}
					<span class="label label-default pull-right">Closed</span>
				{{/if}}
			</a>
		{{/foreach}}
		</div>
	{{else}}
		<p class="text-muted">{{$empty}}</p>
	{{/if}}
</div>
