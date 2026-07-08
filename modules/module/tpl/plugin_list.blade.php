<div class="x_page-header">
	<h1>{{ $lang->installed_plugins }}</h1>
</div>

<p>{{ $lang->about_installed_plugins }}</p>

<form action="./" method="post">
	<input type="hidden" name="module" value="module" />
	<input type="hidden" name="act" value="procModuleAdminSaveEnabledPlugins" />
	<input type="hidden" name="xe_validator_id" value="modules/module/tpl/plugin_list/1" />

	@if (!empty($XE_VALIDATOR_MESSAGE) && $XE_VALIDATOR_ID == 'modules/module/tpl/plugin_list/1')
		<div class="message {{ $XE_VALIDATOR_MESSAGE_TYPE }}">
			<p>{{ $XE_VALIDATOR_MESSAGE }}</p>
		</div>
	@endif

	<table class="x_table x_table-striped x_table-hover">
		<caption>
			<strong>{{ $lang->all }} ({{ count($plugin_list) }})</strong>
		</caption>
		<thead>
			<tr>
				<th class="title">{{ $lang->plugin_name }}</th>
				<th class="nowr">{{ $lang->version }}</th>
				<th class="nowr rx_detail_marks">{{ $lang->author }}</th>
				<th class="nowr rx_detail_marks">{{ $lang->installed_path }}</th>
				<th class="nowr">{{ $lang->cmd_setup }}</th>
				<th class="nowr">{{ $lang->plugin_activate }}</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($plugin_list as $key => $plugin_info)
				<tr>
					<td class="title">
						<p>
							<a href="@url(['module' => 'admin', 'act' => 'dispModuleAdminPluginConfig', 'plugin' => $plugin_info->name])">
								{{ $plugin_info->title }}
							</a>
						</p>
						@if ($plugin_info->description)
							<p>{{ $plugin_info->description }}</p>
						@endif
						@if (Context::isBlacklistedPlugin($plugin_info->name, 'plugin'))
							<p class="x_alert x_alert-error">
								{{ $lang->msg_blacklisted_plugin }}
							</p>
						@endif
					</td>
					<td>
						@if ($plugin_info->version === 'RX_VERSION' && Context::isDefaultPlugin($plugin_info->name, 'plugin'))
							<img src="{{ \RX_BASEURL }}common/img/icon.png" class="core_symbol" alt="Rhymix Core" title="Rhymix Core" />
						@else
							<span @style(['color:#aaa' => Context::isBlacklistedPlugin($plugin_info->name, 'plugin')])>
								{{ $plugin_info->version }}
							</span>
						@endif
					</td>
					<td class="nowr rx_detail_marks">
						@foreach($plugin_info->author as $author)
							@if($author->homepage)
								<a href="{{ $author->homepage }}" target="_blank">{{ $author->name }}</a>
							@else
								{{ $author->name }}
							@endif
						@endforeach
					</td>
					<td class="nowr rx_detail_marks">{{ $plugin_info->path }}</td>
					<td class="nowr">
						<a href="@url(['module' => 'admin', 'act' => 'dispModuleAdminPluginConfig', 'plugin' => $plugin_info->name])">
							{{ $lang->cmd_setup }}
						</a>
					</td>
					<td class="nowr">
						<input type="checkbox" name="enabled_plugins[]" value="{{ $plugin_info->name }}" @checked($plugin_info->is_enabled) />
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
	<div class="x_clearfix">
		<div class="x_pull-right">
			<button type="submit" class="x_btn x_btn-primary">{{ $lang->cmd_save }}</button>
		</div>
	</div>
</form>
