@php
	$groups = array_column((array) $extra_vars ?: [], 'group');
	$groups = array_unique($groups) ?: [''];

	$group_descriptions = array_column((array) $extra_vars ?: [], 'group_description');
	$group_descriptions = array_unique($group_descriptions) ?: [''];
@endphp

@foreach ($groups as $key => $group)
	<section class="extra_vars section">
		<h1>{{ $group }}</h1>
		<p>{!! $group_descriptions[$key] !!}</p>

		@foreach ($extra_vars as $id => $var)
			@if ($group !== $var->group)
				@continue
			@endif

			<div class="x_control-group @if ($var->type == 'mid' || $var->type == 'module_srl_list') moduleSearch moduleSearch1 modulefinder @endif ">
				<label class="x_control-label" for="{{ $id }}"|cond="$var->type != 'radio' && $var->type != 'checkbox'">{{ $var->title }}</label>

				<div class="x_controls">
					@if ($var->type == 'checkbox')
						{{-- checkbox --}}
						@foreach($var->options as $key => $val)
						<label>
							<input type="checkbox" name="{{ $id }}" id="{{ $id }}_{{ $key }}" value="{{ $key }}" @checked(in_array($key, $var->default))> {{ $val->title }}
						</label>
						@endforeach
					@elseif ($var->type == 'color' || $var->type == 'colorpicker')
						{{-- color --}}
						<input type="text" name="{{ $id }}" value="{{ $var->default }}" id="{{ $id }}" class="rx-spectrum" style="width:178px" />
					@elseif ($var->type == 'filebox')
						{{-- filebox --}}
						@php
							$use_filebox = true;
						@endphp
						<input type="hidden" name="{{ $id }}" />
						<a class="x_btn modalAnchor filebox" href="#modalFilebox">@lang('cmd_select')</a>
					@elseif ($var->type == 'member_group')
						{{-- member_group --}}
						@foreach ($group_list as $key => $val)
							<label class="x_inline"><input type="checkbox" value="{{ $key }}" name="{{ $id }}" id="chk_member_gruop_{{ $id }}_{{ $key }}" /> {{ $val->title }}</label>
						@endforeach
					@elseif ($var->type == 'menu')
						{{-- menu --}}
						<select name="{{ $id }}">
							<option value="">-</option>
							@foreach ($menu_list as $key => $val)
								<option value="{{ $val->menu_srl }}">{{ $val->title }}</option>
							@endforeach
						</select>
					@elseif ($var->type == 'mid')
						{{-- mid --}}
						<input type="hidden" name="{{ $id }}" value="" />
						<input type="text" readonly="readonly" />
						<a href="#" class="x_btn moduleTrigger">@lang('cmd_select')</a>
						<button type="button" class="x_btn delete">@lang('cmd_delete')</button>
						<script>
							xe.registerApp(new xe.MidManager('{{ $id }}'));
						</script>
					@elseif ($var->type == 'mid_list')
						{{-- mid_list --}}
						@foreach ($mid_list as $module_category_srl => $modules)
							<fieldset>
								@if (count($mid_list) > 1)
									<legend cond="$modules->title">{{ $modules->title }}</legend>
									<legend cond="!$modules->title">@lang('none_category')</legend>
								@endif
								@foreach ($modules->list as $key => $val)
									<div>
										<label class="x_inline"><input type="checkbox" value="{{ $key }}" name="{{ $id }}" /> {{ $key }} ({{ $val->browser_title }})</label>
									</div>
								@endforeach
							</fieldset>
						@endforeach
					@elseif ($var->type == 'module_srl_list')
						{{-- module_srl_list --}}
						<input type="hidden" name="{{ $id }}" value="" />
						<select class="modulelist_selected" size="8" multiple="multiple" style="vertical-align:top;margin-bottom:5px"></select>
						<p class="x_help-inline">{{ $var->description }}</p>
						<br>
						<a href="#" id="__module_srl_list_{{ $id }}" class="x_btn moduleTrigger" data-multiple="true" style="margin:0 -5px 0 0;border-radius:2px 0 0 2px">@lang('cmd_add')</a>
						<button type="button" class="x_btn modulelist_up" style="margin:0 -5px 0 0;border-radius:0">@lang('cmd_move_up')</button>
						<button type="button" class="x_btn modulelist_down" style="margin:0 -5px 0 0;border-radius:0">@lang('cmd_move_down')</button>
						<button type="button" class="x_btn modulelist_del" style="border-radius:0 2px 2px 0">@lang('cmd_delete')</button>
						<script>
							xe.registerApp(new xe.ModuleListManager('{{ $id }}'));
						</script>
					@elseif ($var->type == 'radio')
						{{-- radio --}}
						@foreach ($var->options as $key => $val)
							<label>
								<input type="radio" name="{{ $id }}" id="{{ $id }}_{{ $key }}" value="{{ $key }}" @checked($key === $var->default)> {{ $val->title }}
							</label>
						@endforeach
					@elseif ($var->type == 'select')
						{{-- select --}}
						<select name="{{ $id }}" id="{{ $id }}">
							@foreach ($var->options as $key => $val)
								<option value="{{ $key }}" @selected($key === $var->default)>{{ $val->title }}</option>
							@endforeach
						</select>
					@elseif ($var->type == 'select-multi-order')
						{{-- select-multi-order --}}
						@if ($var->init_options && is_array($var->init_options))
							@php
								$inits = array_keys($var->init_options);
							@endphp
							<input type="hidden" name="{{ $id }}" value="{implode(',', $inits)}" />
						@else
							<input type="hidden" name="{{ $id }}" value="" />
						@endif
						<div style="display:inline-block;padding-top:3px">
							<label>@lang('display_no')</label>
							<select class="multiorder_show" size="8" multiple="multiple" style="vertical-align:top;margin-bottom:5px">
								@foreach ($var->options as $key => $val)
									@if (!$var->init_options[$key])
										<option value="{{ $key }}" default="true"|cond="$var->default_options[$key]">{{ $val->title }}</option>
									@endif
								@endforeach
							</select>
							<br>
							<button type="button" class="x_btn multiorder_add" style="vertical-align:top">@lang('cmd_insert')</button>
						</div>
						<div style="display:inline-block;padding-top:3px">
							<label>@lang('display_yes')</label>
							<select class="multiorder_selected" size="8" multiple="multiple" style="vertical-align:top;margin-bottom:5px">
								@foreach ($var->options as $key => $val)
									@if ($var->init_options[$key])
										<option value="{{ $key }}" default="true"|cond="$var->default_options[$key]">{{ $val->title }}</option>
									@endif
								@endforeach
							</select>
							<br>
							<button type="button" class="x_btn multiorder_up" style="margin:0 -5px 0 0;border-radius:2px 0 0 2px">@lang('cmd_move_up')</button>
							<button type="button" class="x_btn multiorder_down" style="margin:0 -5px 0 0;border-radius:0">@lang('cmd_move_down')</button>
							<button type="button" class="x_btn multiorder_del" style="border-radius:0 2px 2px 0">@lang('cmd_delete')</button>
						</div>
						<script>
							xe.registerApp(new xe.MultiOrderManager('{{ $id }}'));
						</script>
					@elseif ($var->type == 'text')
						{{-- text --}}
						<input type="text" value="{{ $var->default }}" name="{{ $id }}" />
					@elseif ($var->type == 'textarea')
						{{-- textarea --}}
						<textarea name="{{ $id }}" id="{{ $id }}" rows="8" cols="42">{{ $var->default }}</textarea>
					@endif

					@if ($var->description)
						<p class="x_help">{!! $var->description !!}</p>
					@endif
				</div>
			</div>
		@endforeach
	</section>
@endforeach
