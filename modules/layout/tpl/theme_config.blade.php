@load('css/theme_admin.scss')
@load('js/theme_admin.js')

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

	<ul class="x_nav x_nav-tabs">
		<li class="theme-config-tab x_active">
			<a href="#" data-target="theme">{{ $lang->theme_common_config }}</a>
		</li>
		@foreach ($sub_infos as $sub_name => $sub_info)
			<li class="theme-config-tab">
				<a href="#" data-target="{{ $sub_name }}">{{ $sub_info->title }}</a>
			</li>
		@endforeach
	</ul>

	<div class="theme-config-content theme-config-content-theme">
		@if (count(get_object_vars($theme_info->config)))
			@include('theme_config_include', ['info' => $theme_info, 'type' => 'theme'])
		@else
			<div class="message info">
				<p>{{ $lang->theme_has_no_config }}</p>
			</div>
		@endif
	</div>

	@foreach ($sub_infos as $sub_name => $sub_info)
		<div class="theme-config-content theme-config-content-{{ $sub_name }}" style="display:none">
			@if (count(get_object_vars($sub_info->config)))
				@include ('theme_config_include', ['info' => $sub_info, 'type' => $sub_info->type])
			@else
				<div class="message info">
					<p>{{ $lang->theme_has_no_config }}</p>
				</div>
			@endif
			@if ($sub_info->type === 'layout')
				<section class="section theme_config">
					<h2>{{ $lang->theme_select_menu }}</h2>
					@foreach ($sub_info->menus as $menu)
						<div class="x_control-group">
							<label class="x_control-label">{{ $menu->title }}</label>
							<div class="x_controls">
								<select name="{{ $sub_name }}__menus__{{ $menu->name }}">
									<option value=""></option>
									@foreach ($menu_list as $menu_item)
										<option value="{{ $menu_item->menu_srl }}" @selected($menu_item->menu_srl == ($sub_menus[$sub_name][$menu->name] ?? 0))>
											{{ Context::replaceUserLang($menu_item->title) }}
										</option>
									@endforeach
								</select>
							</div>
						</div>
					@endforeach
				</section>
			@endif
		</div>
	@endforeach

	<div class="x_clearfix btnArea">
		<div class="x_pull-right">
			<button type="submit" class="x_btn x_btn-primary">{{ $lang->cmd_save }}</button>
		</div>
	</div>

</form>
