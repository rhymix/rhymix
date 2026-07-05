<div class="x_page-header">
	<h1>{{ $lang->installed_themes }}</h1>
</div>

<p>{{ $lang->about_installed_themes }}</p>

<form action="./" method="post">
	<input type="hidden" name="module" value="layout" />
	<input type="hidden" name="act" value="procLayoutAdminApplyTheme" />
	<input type="hidden" name="xe_validator_id" value="modules/layout/tpl/theme_list/1" />

	@if (!empty($XE_VALIDATOR_MESSAGE) && $XE_VALIDATOR_ID == 'modules/layout/tpl/theme_list/1')
		<div class="message {{ $XE_VALIDATOR_MESSAGE_TYPE }}">
			<p>{{ $XE_VALIDATOR_MESSAGE }}</p>
		</div>
	@endif

	<table class="x_table x_table-striped x_table-hover">
		<caption>
			<strong>{{ $lang->all }} ({{ count($theme_list) }})</strong>
		</caption>
		<thead>
			<tr>
				<th class="title">{{ $lang->theme_name }}</th>
				<th class="nowr">{{ $lang->version }}</th>
				<th class="nowr rx_detail_marks">{{ $lang->author }}</th>
				<th class="nowr rx_detail_marks">{{ $lang->installed_path }}</th>
				<th class="nowr">{{ $lang->cmd_setup }}</th>
				<th class="nowr">{{ $lang->theme_activate }}</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($theme_list as $key => $theme_info)
				<tr>
					<td class="title">
						<p>
							<a href="@url(['module' => 'admin', 'act' => 'dispLayoutAdminThemeConfig', 'theme' => $theme_info->name])">
								{{ $theme_info->title }}
							</a>
						</p>
						@if ($theme_info->description)
							<p>{{ $theme_info->description }}</p>
						@endif
						@if (Context::isBlacklistedPlugin($theme_info->name, 'theme'))
							<p class="x_alert x_alert-error">
								{{ $lang->msg_blacklisted_theme }}
							</p>
						@endif
					</td>
					<td>
						@if ($theme_info->version === 'RX_VERSION' && Context::isDefaultPlugin($theme_info->name, 'theme'))
							<img src="{{ \RX_BASEURL }}common/img/icon.png" class="core_symbol" alt="Rhymix Core" title="Rhymix Core" />
						@else
							<span @style(['color:#aaa' => Context::isBlacklistedPlugin($theme_info->name, 'theme')])>
								{{ $theme_info->version }}
							</span>
						@endif
					</td>
					<td class="nowr rx_detail_marks">
						@foreach($theme_info->author as $author)
							@if($author->homepage)
								<a href="{{ $author->homepage }}" target="_blank">{{ $author->name }}</a>
							@else
								{{ $author->name }}
							@endif
						@endforeach
					</td>
					<td class="nowr rx_detail_marks">{{ $theme_info->path }}</td>
					<td class="nowr">
						<a href="@url(['module' => 'admin', 'act' => 'dispLayoutAdminThemeConfig', 'theme' => $theme_info->name])">
							{{ $lang->cmd_setup }}
						</a>
					</td>
					<td class="nowr">
						<input type="radio" name="active_theme" value="{{ $theme_info->name }}" @checked($theme_info->name === $active_theme) />
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
	<div class="x_clearfix">
		<div class="x_pull-right">
			<label class="x_inline">
				<input type="radio" name="active_theme" value="" @checked(empty($active_theme)) />
				{{ $lang->theme_deactivate_all }}
			</label>
			<button type="submit" class="x_btn x_btn-primary">{{ $lang->cmd_save }}</button>
		</div>
	</div>
</form>
