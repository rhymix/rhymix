<div class="x_page-header">
	<h1>{{ $lang->installed_themes }}</h1>
</div>

<p>{{ $lang->about_installed_themes }}</p>

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
			</tr>
		@endforeach
	</tbody>
</table>
