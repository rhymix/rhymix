<div class="x_page-header">
	<h1>{{ $lang->extra_vars }}</h1>
</div>
<ul class="x_nav x_nav-tabs">
	<li @class(['x_active' => $act === 'dispExtravarAdminConfig'])>
		<a href="{{ getUrl(['module' => 'admin', 'act' => 'dispExtravarAdminConfig']) }}">{{ $lang->skin }}</a>
	</li>
</ul>
