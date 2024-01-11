@load('../../admin/tpl/css/admin.bootstrap.css')
@load('../../admin/tpl/css/admin.css')
@load('../../admin/tpl/js/admin.js')
@load('js/generate_code.js')
@load('../../admin/tpl/js/jquery.tmpl.js')
@load('../../admin/tpl/js/jquery.jstree.js')

<script>
    xe.cmd_find = "@lang('cmd_find')";
    xe.cmd_cancel = "@lang('cmd_cancel')";
    xe.cmd_confirm = "@lang('cmd_confirm')";
    xe.msg_select_menu = "@lang('msg_select_menu')";
    xe.lang.cmd_delete = '@lang('cmd_delete')';

    jQuery(document).ready(function() {
        doFillWidgetVars();
    });
</script>

<div class="x">
    <div class="x_modal-header">
        <h1>{{ $widget_info->title }} @lang('cmd_generate_code')</h1>
    </div>
    <div id="content" class="x_modal-body">
        <p>{{ $widget_info->description }} @lang('about_widget_code_in_page')</p>
        <form cond="$type=='faceoff'" class="x_form-horizontal">
            <input type="hidden" name="module" value="widget" />
            <input type="hidden" name="type" value="faceoff" />
            <input type="hidden" name="act" value="dispWidgetGenerateCodeInPage" />
            <input type="hidden" name="error_return_url" value="" />
            <div class="x_control-group">
                <label for="selected_widget" class="x_control-label">
                    @lang('widget')
                </label>
                <div class="x_controls">
                    <select name="selected_widget" id="selected_widget" style="margin:0">
                        @foreach ($widget_list as $list_widget_info)
                            <option value="{{ $list_widget_info->widget }}" @selected($list_widget_info->widget === $selected_widget)>
								{{ $list_widget_info->title }}
							</option>
                        @endforeach
                    </select>
                    <input type="submit" value="@lang('cmd_select')" class="x_btn" />
                </div>
            </div>
        </form>
        <form class="x_form-horizontal" action="./" method="post" id="fo_widget">
            <input type="hidden" name="module" value="widget" />
            <input type="hidden" name="module_srl" value="{$module_srl}" />
            <input type="hidden" name="widget_sequence" value="" />
            <input type="hidden" name="style" value="float:left;width:100%;margin:none;padding:none;" />
            <input type="hidden" name="widget_padding_left" value="" />
            <input type="hidden" name="widget_padding_right" value="" />
            <input type="hidden" name="widget_padding_top" value="" />
            <input type="hidden" name="widget_padding_bottom" value="" />
            <input type="hidden" name="selected_widget" value="{$widget_info->widget}" />

            @include('widget_generate_code.include')

            <div class="btnArea">
                <input type="submit" class="x_btn x_btn-primary" value="@lang('cmd_generate_code')" />
            </div>
        </form>
    </div>

    @include('../../module/tpl/include.filebox.blade.php')
</div>
