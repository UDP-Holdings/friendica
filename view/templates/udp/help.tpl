<div class="generic-page-wrapper">
<h2>Help</h2>

{{if $is_admin}}

<p>Need help with your UDP node? Reach out to UDP support and we'll get back to you.</p>

<p><a class="btn btn-primary" href="mailto:support@udp.social">Email UDP Support</a></p>

{{else}}

<p>Need help? Contact your node admin{{if $admin_name != 'your admin'}}, <strong>{{$admin_name}}</strong>{{/if}}.</p>

<p>
	{{if $admin_email}}
	<a class="btn btn-default" href="mailto:{{$admin_email}}">Email {{$admin_name}}</a>
	{{/if}}
	{{if $admin_profile}}
	<a class="btn btn-default" href="{{$admin_profile}}">Message {{$admin_name}}</a>
	{{/if}}
</p>

{{/if}}

</div>
