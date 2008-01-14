<?php
    /**
     * @file   modules/editor/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  WYSIWYG Editor module's basic language pack
     **/

    $lang->editor = "WYSIWYG Editor";
    $lang->component_name = "Component";
    $lang->component_version = "Version";
    $lang->component_author = "Developer";
    $lang->component_link = "Link";
    $lang->component_date = "Date";
    $lang->component_description = "Description";
    $lang->component_extra_vars = "Option Variable";
    $lang->component_grant = "Permission Setting"; 

    $lang->about_component = "About component";
    $lang->about_component_grant = "Only selected groups are allowed to use. (Everyone can use it when mode is disabled)";

    $lang->msg_component_is_not_founded = 'Cannot find editor component %s';
    $lang->msg_component_is_inserted = 'Selected component is already inserted';
    $lang->msg_component_is_first_order = 'Selected component is located at the first position';
    $lang->msg_component_is_last_order = 'Selected component is located at the last position';
    $lang->msg_load_saved_doc = "There is an automatically saved article. Do you wish to recover it?\nThe auto-saved draft will be discarded after saving current article";
    $lang->msg_auto_saved = "Automatically Saved";

    $lang->cmd_disable = "Inactive";
    $lang->cmd_enable = "Active";

    $lang->editor_skin = '에디터 스킨';
    $lang->upload_file_grant = '파일 첨부 권한'; 
    $lang->enable_default_component_grant = '기본 컴포넌트 사용 권한';
    $lang->enable_component_grant = '컴포넌트 사용 권한';
    $lang->enable_html_grant = 'HTML편집 권한';
    $lang->enable_autosave = '자동저장 사용';
    $lang->height_resizable = '높이 조절 가능';
    $lang->editor_height = '에디터 높이';

    $lang->about_editor_skin = '에디터 스킨을 선택하실 수 있습니다';
    $lang->about_upload_file_grant = '파일을 첨부할 수 있는 권한을 지정하실 수 있습니다 (모두 해제시 아무나 첨부 가능합니다)';
    $lang->about_default_component_grant = '에디터에서 사용되는 기본 컴포넌트를 사용할 수 있는 권한을 지정할 수 있습니다. (모두 해제시 아무나 사용 가능합니다)';
    $lang->about_component_grant = '기본 컴포넌트외의 확장 컴포넌트 기능을 사용할 수 있는 권한을 지정할 수 있습니다. (모두 해제시 아무나 사용 가능합니다)';
    $lang->about_editor_height = '에디터의 기본 높이를 지정하실 수 있습니다';
    $lang->about_editor_height_resizable = '에디터의 높이를 직접 변경할 수 있도록 허용합니다';
    $lang->about_enable_html_grant = 'HTML편집 권한을 부여할 수 있습니다.';
    $lang->about_enable_autosave = '글작성시 자동 저장 기능을 활성화 시킬 수 있습니다';

    $lang->edit->fontname = 'Font';
    $lang->edit->fontsize = 'Size';
    $lang->edit->use_paragraph = 'Paragraph Function';
    $lang->edit->fontlist = array(
    "Gulim",
    "Dodum",
    "Batang",
    "Goongseo",
    "times",
    "Courier",
    "Tahoma",
    "Arial",
    );

    $lang->edit->header = "Style";
    $lang->edit->header_list = array(
    "h1" => "Subject 1",
    "h2" => "Subject 2",
    "h3" => "Subject 3",
    "h4" => "Subject 4",
    "h5" => "Subject 5",
    "h6" => "Subject 6",
    );

    $lang->edit->submit = 'Submit';

    $lang->edit->help_fontcolor = "Select font color";
    $lang->edit->help_fontbgcolor = "Select background color of font";
    $lang->edit->help_bold = "Make font bold";
    $lang->edit->help_italic = "Make italic font";
    $lang->edit->help_underline = "Underline font";
    $lang->edit->help_strike = "Strike font";
    $lang->edit->help_redo = "Redo";
    $lang->edit->help_undo = "Undo";
    $lang->edit->help_align_left = "Align left";
    $lang->edit->help_align_center = "Align center";
    $lang->edit->help_align_right = "Align right";
    $lang->edit->help_add_indent = "Add indent";
    $lang->edit->help_remove_indent = "Remove indent";
    $lang->edit->help_list_number = "Apply number list";
    $lang->edit->help_list_bullet = "Apply bullet list";
    $lang->edit->help_use_paragrapth = "Press Ctrl+Enter to use paragraph. (Press Alt+S to submit)";

    $lang->edit->upload = 'Attachment';
    $lang->edit->upload_file = 'Attach'; 
    $lang->edit->link_file = 'Insert to Content';
    $lang->edit->delete_selected = 'Delete Selected';

    $lang->edit->icon_align_article = 'Occupy a paragraph';
    $lang->edit->icon_align_left = 'Align Left';
    $lang->edit->icon_align_middle = 'Align Center';
    $lang->edit->icon_align_right = 'Align Right';

    $lang->about_dblclick_in_editor = 'You may set detail component configures by double-clicking background, text, images, or quotations';
?>
