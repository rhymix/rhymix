<?php
    /**
     * @file   modules/editor/lang/zh-CN.lang.php
     * @author zero <zero@nzeo.com>
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
    $lang->content_style = 'Content Style';
    $lang->content_font = 'Content Font';
	$lang->content_font_size = '문서 폰트 크기';

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
    $lang->about_content_style = '문서 편집 및 내용 출력시 원하는 서식을 지정할 수 있습니다';
    $lang->about_content_font = '문서 편집 및 내용 출력시 원하는 폰트를 지정할 수 있습니다.<br/>지정하지 않으면 사용자 설정에 따르게 됩니다<br/> ,(콤마)로 여러 폰트를 지정할 수 있습니다.';
	$lang->about_content_font_size = '문서 편집 및 내용 출력시 원하는 폰트의 크기를 지정할 수 있습니다.<br/>12px, 1em등 단위까지 포함해서 입력해주세요.';
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
    '仿宋_GB2312'=>'仿宋',
    '隶书'=>'隶书',
    '幼圆'=>'幼圆',
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
    $lang->edit->fontbgcolor = '背景颜色';
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

    $lang->edit->help_fontcolor = '文本颜色';
    $lang->edit->help_fontbgcolor = '背景颜色';
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
    $lang->edit->help_add_indent = '增加缩进';
    $lang->edit->help_remove_indent = '减少缩进';
    $lang->edit->help_list_number = '有序列表';
    $lang->edit->help_list_bullet = '无序列表';
    $lang->edit->help_use_paragraph = '分段请按 ctrl＋回车． (发表主题快捷键：alt＋S)';

    $lang->edit->url = '插入链接';
    $lang->edit->blockquote = '插入注释框';
    $lang->edit->table = '表格';
    $lang->edit->image = '图片';
    $lang->edit->multimedia = '视频';
    $lang->edit->emoticon = '表情图标';

    $lang->edit->upload = '上传';
    $lang->edit->upload_file = '上传附件';
    $lang->edit->link_file = '插入附件';
    $lang->edit->delete_selected = '删除所选';

    $lang->edit->icon_align_article = '占一个段落';
    $lang->edit->icon_align_left = '文本左侧';
    $lang->edit->icon_align_middle = '居中对齐';
    $lang->edit->icon_align_right = '文本右侧';

    $lang->about_dblclick_in_editor = '双击背景, 文本, 图片, 引用即可对其相关组件进行详细设置。';


    $lang->edit->rich_editor = '所见即所得编辑器';
    $lang->edit->html_editor = 'HTML 编辑器';
    $lang->edit->extension ='扩展组建';
    $lang->edit->help = '帮助';
    $lang->edit->help_command = '快捷键说明';
    
    $lang->edit->lineheight = '줄간격';
	$lang->edit->fontbgsampletext = '가나다';
	
	$lang->edit->hyperlink = '하이퍼링크';
	$lang->edit->target_blank = '새창으로';
	
	$lang->edit->quotestyle1 = '왼쪽 실선';
	$lang->edit->quotestyle2 = '인용 부호';
	$lang->edit->quotestyle3 = '실선';
	$lang->edit->quotestyle4 = '실선 + 배경';
	$lang->edit->quotestyle5 = '굵은 실선';
	$lang->edit->quotestyle6 = '점선';
	$lang->edit->quotestyle7 = '점선 + 배경';
	$lang->edit->quotestyle8 = '적용 취소';


    $lang->edit->jumptoedit = '편집 도구모음 건너뛰기';
    $lang->edit->set_sel = '칸 수 지정';
    $lang->edit->row = '행';
    $lang->edit->col = '열';
    $lang->edit->add_one_row = '1행추가';
    $lang->edit->del_one_row = '1행삭제';
    $lang->edit->add_one_col = '1열추가';
    $lang->edit->del_one_col = '1열삭제';

    $lang->edit->table_config = '표 속성 지정';
    $lang->edit->border_width = '테두리 굵기';
    $lang->edit->border_color = '테두리 색';
    $lang->edit->add = '더하기';
    $lang->edit->del = '빼기';
    $lang->edit->search_color = '색상찾기';
    $lang->edit->table_backgroundcolor = '표 배경색';
    $lang->edit->special_character = '특수문자';
    $lang->edit->insert_special_character = '특수문자 삽입';
    $lang->edit->close_special_character = '특수문자 레이어 닫기';
    $lang->edit->symbol = '일반기호';
    $lang->edit->number_unit = '숫자와 단위';
    $lang->edit->circle_bracket = '원,괄호';
    $lang->edit->korean = '한글';
    $lang->edit->greece = '그리스';
    $lang->edit->Latin  = '라틴어';
    $lang->edit->japan  = '일본어';
    $lang->edit->selected_symbol  = '선택한 기호';

    $lang->edit->search_replace  = '찾기/바꾸기';
    $lang->edit->close_search_replace  = '찾기/바꾸기 레이어 닫기';
    $lang->edit->replace_all  = '모두바꾸기';
    $lang->edit->search_words  = '찾을단어';
    $lang->edit->replace_words  = '바꿀단어';
    $lang->edit->next_search_words  = '다음찾기';
    $lang->edit->edit_height_control  = '입력창 크기 조절';

	$lang->edit->merge_cells = '셀 병합';
    $lang->edit->split_row = '행 분할';
    $lang->edit->split_col = '열 분할';
    
    $lang->edit->toggle_list   = '목록 접기/펼치기';
    $lang->edit->minimize_list = '최소화';
    
    $lang->edit->move = '이동';
    $lang->edit->materials = '글감보관함';
    $lang->edit->temporary_savings = '임시저장목록';
    
    $lang->edit->drag_here = '아래의 단락추가 툴바에서 원하는 유형의 단락을 추가해 글 쓰기를 시작하세요.<br />글감 보관함에 글이 있으면 이곳으로 끌어 넣기 할 수 있습니다.';

	$lang->edit->paging_prev = '이전';
	$lang->edit->paging_next = '다음';
	$lang->edit->paging_prev_help = '이전 페이지로 이동합니다.';
	$lang->edit->paging_next_help = '다음 페이지로 이동합니다.';

	$lang->edit->toc = '목차';
	$lang->edit->close_help = '도움말 닫기';

	$lang->edit->confirm_submit_without_saving = '저장하지 않은 단락이 있습니다.\\n그냥 전송하시겠습니까?';
?>
