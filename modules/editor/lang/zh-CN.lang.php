<?php
    /**
     * @file   modules/editor/lang/zh-CN.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  网页编辑器(editor) 模块语言包
     **/

    $lang->editor = '网页编辑器';
    $lang->component_name = '组件';
    $lang->component_version = '版本';
    $lang->component_author = '作者';
    $lang->component_link = '链接';
    $lang->component_date = '编写日期';
    $lang->component_license = '版权';
    $lang->component_history = '更新日志';
    $lang->component_description = '说明';
    $lang->component_extra_vars = '变数设置';
    $lang->component_grant = '权限设置';
    $lang->content_style = '文档样式';
    $lang->content_font = '文档字体';
    $lang->content_font_size = '字体大小';

    $lang->about_component = '组件简介';
    $lang->about_component_grant = '可以设置除默认组件外的扩展组件使用权限<br />(全部解除时任何用户都可以使用)。';
    $lang->about_component_mid = '可以指定使用编辑器组件的对象。<br />(全部解除时任何用户都可以使用)。';

    $lang->msg_component_is_not_founded = '找不到%s 组件说明！';
    $lang->msg_component_is_inserted = '您选择的组件已插入！';
    $lang->msg_component_is_first_order = '您选择的组件已到最上端位置！';
    $lang->msg_component_is_last_order = '您选择的组件已到最下端位置！';
    $lang->msg_load_saved_doc = "有自动保存的内容， 确定要恢复吗？\n发布主题后，自动保存的文本将会被删除。";
    $lang->msg_auto_saved = '已自动保存！';

    $lang->cmd_disable = '非激活';
    $lang->cmd_enable = '激活';

    $lang->editor_skin = '编辑器皮肤';
    $lang->upload_file_grant = '文件上传权限';
    $lang->enable_default_component_grant = '默认组件使用权限';
    $lang->enable_component_grant = '组件使用权限';
    $lang->enable_html_grant = 'HTML编辑权限';
    $lang->enable_autosave = '内容自动保存';
    $lang->height_resizable = '高度调整';
    $lang->editor_height = '编辑器高度';

    $lang->about_editor_skin = '可以选择编辑器皮肤。';
    $lang->about_content_style = '可以指定编辑或打印文档时的文档样式。';
    $lang->about_content_font = '可以指定编辑或打印文档时的文档字体，不指定随用户设置。<br/>多个字体可以用逗号(，)区分输入。';
    $lang->about_content_font_size = '可以指定编辑或打印文档时的文档字体大小。<br/>指定时请包含字体单位(如12px, 1em)。';
    $lang->about_upload_file_grant = '可以设置上传文件的权限(全部解除为无限制)。';
    $lang->about_default_component_grant = '可以设置编辑器默认组件的使用权限(全部解除为无限制)。';
    $lang->about_editor_height = '可以指定编辑器的默认高度。';
    $lang->about_editor_height_resizable = '允许用户拖动编辑器高度。';
    $lang->about_enable_html_grant = 'HTML代码编辑权限设置。';
    $lang->about_enable_autosave = '发表主题时激活内容自动保存功能。';

    $lang->edit->fontname = '字体';
    $lang->edit->fontsize = '大小';
    $lang->edit->use_paragraph = '段落功能';
    $lang->edit->fontlist = array(
    '宋体'=>'宋体',
    '黑体'=>'黑体',
    '楷体_GB2312'=>'楷体',
    'Arial'=>'Arial',
    'Arial Black'=>'Arial Black',
    'Tahoma'=>'Tahoma',
    'Verdana'=>'Verdana',
    'Sans-serif'=>'Sans-serif',
    'Serif'=>'Serif',
    'Monospace'=>'Monospace',
    'Cursive'=>'Cursive',
    'Fantasy'=>'Fantasy',
    );

    $lang->edit->header = '样式';
    $lang->edit->header_list = array(
    'h1' => '标题 1',
    'h2' => '标题 2',
    'h3' => '标题 3',
    'h4' => '标题 4',
    'h5' => '标题 5',
    'h6' => '标题 6',
    );

    $lang->edit->submit = '确认';

    $lang->edit->fontcolor = '文本颜色';
	$lang->edit->fontcolor_apply = '글자 색 적용';
	$lang->edit->fontcolor_more = '글자 색 더보기';
    $lang->edit->fontbgcolor = '背景颜色';
	$lang->edit->fontbgcolor_apply = '글자 배경색 적용';
	$lang->edit->fontbgcolor_more = '글자 배경색 더보기';
    $lang->edit->bold = '粗体';
    $lang->edit->italic = '斜体';
    $lang->edit->underline = '下划线';
    $lang->edit->strike = '取消线';
    $lang->edit->sup = '上标';
    $lang->edit->sub = '下标';
    $lang->edit->redo = '恢复';
    $lang->edit->undo = '撤销';
    $lang->edit->align_left = '左对齐';
    $lang->edit->align_center = '居中对齐';
    $lang->edit->align_right = '右对齐';
    $lang->edit->align_justify = '两端对齐';
    $lang->edit->add_indent = '增加缩进';
    $lang->edit->remove_indent = '减少缩进';
    $lang->edit->list_number = '有序列表';
    $lang->edit->list_bullet = '无序列表';
    $lang->edit->remove_format = '删除文字格式';

    $lang->edit->help_remove_format = '清除格式。';
    $lang->edit->help_strike_through = '取消线。';
    $lang->edit->help_align_full = '两端对齐。';

    $lang->edit->help_fontcolor = '文本颜色。';
    $lang->edit->help_fontbgcolor = '背景颜色。';
    $lang->edit->help_bold = '粗体';
    $lang->edit->help_italic = '斜体';
    $lang->edit->help_underline = '下划线';
    $lang->edit->help_strike = '取消线';
    $lang->edit->help_sup = '上标';
    $lang->edit->help_sub = '下标';
    $lang->edit->help_redo = '恢复';
    $lang->edit->help_undo = '撤销';
    $lang->edit->help_align_left = '左对齐';
    $lang->edit->help_align_center = '居中对齐';
    $lang->edit->help_align_right = '右对齐';
    $lang->edit->help_align_justify = '两端对齐';
    $lang->edit->help_add_indent = '增加缩进';
    $lang->edit->help_remove_indent = '减少缩进';
    $lang->edit->help_list_number = '有序列表';
    $lang->edit->help_list_bullet = '无序列表';
    $lang->edit->help_use_paragraph = '分段请按 Ctrl＋回车． (发表主题快捷键：Alt＋S)';

    $lang->edit->url = '链接';
    $lang->edit->blockquote = '注释框';
    $lang->edit->table = '表格';
    $lang->edit->image = '图片';
    $lang->edit->multimedia = '视频';
    $lang->edit->emoticon = '表情图标';

	$lang->edit->file = '파일';
    $lang->edit->upload = '上传';
    $lang->edit->upload_file = '上传附件';
	$lang->edit->upload_list = '첨부 목록';
    $lang->edit->link_file = '插入附件';
    $lang->edit->delete_selected = '删除所选';

    $lang->edit->icon_align_article = '占一个段落';
    $lang->edit->icon_align_left = '文本左侧';
    $lang->edit->icon_align_middle = '居中对齐';
    $lang->edit->icon_align_right = '文本右侧';

    $lang->about_dblclick_in_editor = '双击背景, 文本, 图片, 引用即可对其相关组件进行详细设置。';

    $lang->edit->rich_editor = '常规';
    $lang->edit->html_editor = 'HTML';
    $lang->edit->extension ='扩展组件';
    $lang->edit->help = '帮助';
    $lang->edit->help_command = '快捷键说明';
    
    $lang->edit->lineheight = '行间距';
    $lang->edit->fontbgsampletext = '我爱中华！';
	
    $lang->edit->hyperlink = '链接';
    $lang->edit->target_blank = '新窗口';
	
    $lang->edit->quotestyle1 = '左侧实线';
    $lang->edit->quotestyle2 = '引用符号';
    $lang->edit->quotestyle3 = '实线';
    $lang->edit->quotestyle4 = '实线 + 背景';
    $lang->edit->quotestyle5 = '粗实线';
    $lang->edit->quotestyle6 = '虚线';
    $lang->edit->quotestyle7 = '虚线 + 背景';
    $lang->edit->quotestyle8 = '取消应用';


    $lang->edit->jumptoedit = '跳转';
    $lang->edit->set_sel = '表格大小';
    $lang->edit->row = '行';
    $lang->edit->col = '列';
    $lang->edit->add_one_row = '添加1行';
    $lang->edit->del_one_row = '删除1行';
    $lang->edit->add_one_col = '添加1列';
    $lang->edit->del_one_col = '删除1列';

    $lang->edit->table_config = '表格属性';
    $lang->edit->border_width = '表格边框';
    $lang->edit->border_color = '边框颜色';
    $lang->edit->add = '加';
    $lang->edit->del = '减';
    $lang->edit->search_color = '修改颜色';
    $lang->edit->table_backgroundcolor = '背景颜色';
    $lang->edit->special_character = '特殊字符';
    $lang->edit->insert_special_character = '插入特殊字符';
    $lang->edit->close_special_character = '关闭';
    $lang->edit->symbol = '基本符号';
    $lang->edit->number_unit = '数字/单位';
    $lang->edit->circle_bracket = '数字序号';
    $lang->edit->korean = '韩文';
    $lang->edit->greece = '希腊';
    $lang->edit->Latin  = '拉丁';
    $lang->edit->japan  = '日文';
    $lang->edit->selected_symbol  = '被选字符';

    $lang->edit->search_replace  = '查找/替换';
    $lang->edit->close_search_replace  = '关闭';
    $lang->edit->replace_all  = '全部替换';
    $lang->edit->search_words  = '查找内容';
    $lang->edit->replace_words  = '替换为';
    $lang->edit->next_search_words  = '查找下一个';
    $lang->edit->edit_height_control  = '调整输入框大小';

    $lang->edit->merge_cells = '合并单元格';
    $lang->edit->split_row = '跨行';
    $lang->edit->split_col = '跨列';
    
    $lang->edit->toggle_list   = '展开/折叠列表';
    $lang->edit->minimize_list = '最小化';
    
    $lang->edit->move = '移动';
    $lang->edit->refresh = '刷新';
    $lang->edit->materials = '素材库';
    $lang->edit->temporary_savings = '临时保存列表';

    $lang->edit->paging_prev = '上一个';
    $lang->edit->paging_next = '下一个';
    $lang->edit->paging_prev_help = '上一页。';
    $lang->edit->paging_next_help = '下一页。';

    $lang->edit->toc = '列表';
    $lang->edit->close_help = '关闭帮助';

    $lang->edit->confirm_submit_without_saving = '尚有未保存的段落。\\n确定要提交吗？';

    $lang->edit->image_align = '图片对齐';
    $lang->edit->attached_files = '附件';

	$lang->edit->fontcolor_input = '폰트색 직접입력';
	$lang->edit->fontbgcolor_input = '배경색 직접입력';
	$lang->edit->pangram = '무궁화 꽃이 피었습니다';

	$lang->edit->table_caption_position = '표 제목(caption) 및 배치';
	$lang->edit->table_caption = '표 제목(caption)';
	$lang->edit->table_header = '머리글 셀(th)';
	$lang->edit->table_header_none = '없음';
	$lang->edit->table_header_left = '왼쪽';
	$lang->edit->table_header_top = '위쪽';
	$lang->edit->table_header_both = '모두';
	$lang->edit->table_size = '표 크기';
	$lang->edit->table_width = '표 폭';

	$lang->edit->upper_left = '상단좌측';
	$lang->edit->upper_center = '상단중앙';
	$lang->edit->upper_right = '상단우측';
	$lang->edit->bottom_left = '하단좌측';
	$lang->edit->bottom_center = '하단중앙';
	$lang->edit->bottom_right = '하단우측';

	$lang->edit->no_image = '첨부된 이미지가 없습니다.';
	$lang->edit->no_multimedia = '첨부된 동영상이 없습니다.';
	$lang->edit->no_attachment = '첨부된 파일이 없습니다.';
	$lang->edit->insert_selected = '선택 넣기';
	$lang->edit->delete_selected = '선택 삭제';

	$lang->edit->fieldset = '글상자';
	$lang->edit->paragraph = '문단';
	
	$lang->edit->autosave_format = '글을 쓰기 시작한지 <strong>%s</strong>이 지났습니다. 마지막 저장 시간은 <strong>%s</strong> 입니다.';
	$lang->edit->autosave_hour = '%d시간';
	$lang->edit->autosave_hours = '%d시간';
	$lang->edit->autosave_min = '%d분';
	$lang->edit->autosave_mins = '%d분';
	$lang->edit->autosave_hour_ago = '%d시간 전';
	$lang->edit->autosave_hours_ago = '%d시간 전';
	$lang->edit->autosave_min_ago = '%d분 전';
	$lang->edit->autosave_mins_ago = '%d분 전';
	
	$lang->edit->upload_not_enough_quota   = '허용된 용량이 부족하여 파일을 첨부할 수 없습니다.';
?>
