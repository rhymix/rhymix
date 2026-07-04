@foreach ($info->config_groups as $group_name)
	<section class="section theme_config">
		<h2>{{ $group_name }}</h2>
		@foreach ($info->config as $key => $var)
			@if ($var->group === $group_name)
				<div class="x_control-group">
					<label class="x_control-label">{{ $var->title }}</label>
					<div class="x_controls">
						{!! $var->input !!}
						<span class="x_help-block">{{ nl2br($var->description) }}</span>
					</div>
				</div>
			@endif
		@endforeach
	</section>
@endforeach

<section class="section theme_config">
	@if (!count($info->config_groups))
		@if ($type === 'theme')
			<h2>{{ lang('layout.theme_common_config') }}</h2>
		@elseif ($type === 'layout')
			<h2>{{ lang('layout.theme_layout_config') }}</h2>
		@else
			<h2>{{ lang('layout.theme_skin_config') }}</h2>
		@endif
	@endif
	@foreach ($info->config as $key => $var)
		@if ($var->group === null)
			<div class="x_control-group">
				<label class="x_control-label">{{ $var->title }}</label>
				<div class="x_controls">
					{!! $var->input !!}
					<span class="x_help-block">{{ nl2br($var->description) }}</span>
				</div>
			</div>
		@endif
	@endforeach
</section>
