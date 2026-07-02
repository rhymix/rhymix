@load('css/plugin_admin.scss')

<div class="x_page-header">
	<h1>{{ $lang->installed_plugins }}</h1>
</div>

<table class="x_table">
	<tbody>
		<tr>
			<th class="nowr">{{ $lang->version }}</th>
			<td>
				@if($plugin_info->version === 'RX_VERSION' && Context::isDefaultPlugin($plugin_info->name, 'plugin'))
					CORE
				@else
					{{ $plugin_info->version }}
					@if($plugin_info->date)
						({{ zdate($plugin_info->date, 'Y-m-d') }})
					@endif
				@endif
			</td>
		</tr>
		<tr>
			<th class="nowr">{{ $lang->author }}</th>
			<td>
				@foreach ($plugin_info->author as $author)
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
		@if ($plugin_info->homepage)
			<tr>
				<th class="nowr">{{ $lang->homepage }}</th>
				<td><a href="{{ $plugin_info->homepage }}" target="_blank">{{ $plugin_info->homepage }}</a></td>
			</tr>
		@endif
		@if ($plugin_info->description)
			<tr>
				<th class="nowr">{{ $lang->description }}</th>
				<td>{{ $plugin_info->description }}</td>
			</tr>
		@endif
	</tbody>
</table>

<form action="./" method="post" class="x_form-horizontal rx_ajax">
	<input type="hidden" name="module" value="module" />
	<input type="hidden" name="act" value="procModuleAdminSavePluginConfig" />
	<input type="hidden" name="plugin" value="{{ $plugin_info->name }}" />
	<input type="hidden" name="xe_validator_id" value="modules/module/tpl/plugin_config/1" />

	@if (!empty($XE_VALIDATOR_MESSAGE) && $XE_VALIDATOR_ID == 'modules/module/tpl/plugin_config/1')
		<div class="message {{ $XE_VALIDATOR_MESSAGE_TYPE }}">
			<p>{{ $XE_VALIDATOR_MESSAGE }}</p>
		</div>
	@endif

	@if (Context::isBlacklistedPlugin($plugin_info->name, 'plugin'))
		<div class="message error">
			<p><em class="x_label x_label-important">{{ $lang->msg_warning }}</em> {{ $lang->msg_blacklisted_plugin }}</p>
		</div>
	@endif

	@if (!count(get_object_vars($plugin_info->config)))
		<div class="message info">
			<p>{{ $lang->plugin_has_no_config }}</p>
		</div>
	@endif

	<section class="section plugin_config">
		@foreach ($plugin_info->config as $key => $var)
			<div class="x_control-group">
				<label class="x_control-label">{{ $var->title }}</label>
				<div class="x_controls">
					{!! $var->input !!}
					<span class="x_help-block">{{ nl2br($var->description) }}</span>
				</div>
			</div>
		@endforeach
	</section>

	<div class="x_clearfix">
		<div class="x_pull-right">
			<button type="submit" class="x_btn x_btn-primary">{{ $lang->cmd_save }}</button>
		</div>
	</div>

</form>
