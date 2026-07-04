@load('css/theme_admin.scss')

<div class="x_page-header">
	<h1>{{ $theme_info->title }}</h1>
</div>

<table class="x_table">
	<tbody>
		<tr>
			<th class="nowr">{{ $lang->version }}</th>
			<td>
				@if($theme_info->version === 'RX_VERSION' && Context::isDefaultPlugin($theme_info->name, 'theme'))
					<img src="{{ \RX_BASEURL }}common/img/icon.png" class="core_symbol" alt="Rhymix Core" title="Rhymix Core" />
					{{ $lang->is_core_theme }}
				@else
					{{ $theme_info->version }}
					@if($theme_info->date)
						({{ zdate($theme_info->date, 'Y-m-d') }})
					@endif
				@endif
			</td>
		</tr>
		<tr>
			<th class="nowr">{{ $lang->author }}</th>
			<td>
				@foreach ($theme_info->author as $author)
					{{ $author->name }}&nbsp;
					@if ($author->homepage)
						<a href="{{ $author->homepage }}" target="_blank">{{ $author->homepage }}</a>
					@endif
					@if ($author->email_address)
						<a href="mailto:{{ $author->email_address }}">{{ $author->email_address }}</a>
					@endif
					<br />
				@endforeach
			</td>
		</tr>
		@if ($theme_info->homepage)
			<tr>
				<th class="nowr">{{ $lang->homepage }}</th>
				<td><a href="{{ $theme_info->homepage }}" target="_blank">{{ $theme_info->homepage }}</a></td>
			</tr>
		@endif
		@if ($theme_info->description)
			<tr>
				<th class="nowr">{{ $lang->description }}</th>
				<td>{{ $theme_info->description }}</td>
			</tr>
		@endif
	</tbody>
</table>

<form action="./" method="post" class="x_form-horizontal rx_ajax">
	<input type="hidden" name="module" value="layout" />
	<input type="hidden" name="act" value="procLayoutAdminSaveThemeConfig" />
	<input type="hidden" name="theme" value="{{ $theme_info->name }}" />
	<input type="hidden" name="sub_name" value="theme" />
	<input type="hidden" name="xe_validator_id" value="modules/layout/tpl/theme_config/1" />

	@if (!empty($XE_VALIDATOR_MESSAGE) && $XE_VALIDATOR_ID == 'modules/layout/tpl/theme_config/1')
		<div class="message {{ $XE_VALIDATOR_MESSAGE_TYPE }}">
			<p>{{ $XE_VALIDATOR_MESSAGE }}</p>
		</div>
	@endif

	@if (Context::isBlacklistedPlugin($theme_info->name, 'theme'))
		<div class="message error">
			<p><em class="x_label x_label-important">{{ $lang->msg_warning }}</em> {{ $lang->msg_blacklisted_theme }}</p>
		</div>
	@endif

	@if (count(get_object_vars($theme_info->config)))
		@foreach ($theme_info->config_groups as $group_name)
			<section class="section theme_config">
				<h2>{{ $group_name }}</h2>
				@foreach ($theme_info->config as $key => $var)
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
			@foreach ($theme_info->config as $key => $var)
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
	@else
		<div class="message info">
			<p>{{ $lang->theme_has_no_config }}</p>
		</div>
	@endif

	<div class="x_clearfix">
		<div class="x_pull-right">
			<button type="submit" class="x_btn x_btn-primary">{{ $lang->cmd_save }}</button>
		</div>
	</div>

</form>
