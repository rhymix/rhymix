@load('../../module/tpl/js/multi_order.js')
@load('../../module/tpl/js/module_list.js')
@load('../../module/tpl/js/mid.js')

<!--%load_js_plugin("spectrum")-->

<div class="x_control-group">
    <label class="x_control-label" for="skin">@lang('skin')</label>
    <div class="x_controls">
        <select name="skin" id="skin">
            <option value="">@lang('select')</option>
            @foreach ($skin_list as $skin_name => $skin)
                <option value="{{ $skin_name }}">{{ $skin->title }}({{ $skin_name }})</option>
            @endforeach
        </select>
        <input type="button" class="x_btn" value="@lang('cmd_select')" />
    </div>
</div>

<div class="x_control-group">
    <label class="x_control-label" for="colorset">@lang('colorset')</label>
    <div class="x_controls">
        <select name="colorset" id="widget_colorset">
        </select>
    </div>
</div>

<div class="x_control-group">
    <label class="x_control-label" for="widget_cache">@lang('widget_cache')</label>
    <div class="x_controls">
        <input type="number" name="widget_cache" id="widget_cache" value="0" size="5" />
        <select name="widget_cache_unit" id="widget_cache_unit" style="width:60px;min-width:60px">
            <option value="s">@lang('unit_sec')</option>
            <option value="m" selected="selected">@lang('unit_min')</option>
            <option value="h">@lang('unit_hour')</option>
            <option value="d">@lang('unit_day')</option>
        </select>
        <br />
        <p class="x_help-inline">@lang('about_widget_cache')</p>
    </div>
</div>

@include('^/common/tpl/extra_vars_fields', [ 'extra_vars' => $widget_info->extra_vars ])

<script>
    xe.current_lang = "{{ $lang_type }}";
</script>
