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
    $lang->component_license = 'License';
    $lang->component_history = 'Updates';
    $lang->component_description = "Description";
    $lang->component_extra_vars = "Option Variable";
    $lang->component_grant = "Permission Setting"; 

    $lang->about_component = "About component";
    $lang->about_component_grant = 'Selected group(s) will be able to use expanded components of editor.<br />(Leave them blank if you want all groups to have permission)';
    $lang->about_component_mid = "Editor components can select targets.<br />(All targets will be selected when nothing is selected)";

    $lang->msg_component_is_not_founded = 'Cannot find editor component %s';
    $lang->msg_component_is_inserted = 'Selected component is already inserted';
    $lang->msg_component_is_first_order = 'Selected component is located at the first position';
    $lang->msg_component_is_last_order = 'Selected component is located at the last position';
    $lang->msg_load_saved_doc = "There is an automatically saved article. Do you wish to recover it?\nThe auto-saved draft will be discarded after saving current article";
    $lang->msg_auto_saved = "Automatically Saved";

    $lang->cmd_disable = "Inactive";
    $lang->cmd_enable = "Active";

    $lang->editor_skin = 'Editor Skin';
    $lang->upload_file_grant = 'Permission for Uploading'; 
    $lang->enable_default_component_grant = 'Permission for Default Components';
    $lang->enable_component_grant = 'Permission for Components';
    $lang->enable_html_grant = 'Permission for HTML';
    $lang->enable_autosave = 'Auto-Save';
    $lang->height_resizable = 'Height Resizable';
    $lang->editor_height = 'Height of Editor';

    $lang->about_editor_skin = 'You may select the skin of editor.';
    $lang->about_upload_file_grant = 'Selected group(s) will be able to upload files. (Leave them blank if you want all groups to have permission)';
    $lang->about_default_component_grant = 'Selected group(s) will be able to use default components of editor. (Leave them blank if you want all groups to have permission)';
    $lang->about_editor_height = 'You may set the height of editor.';
    $lang->about_editor_height_resizable = 'You may decide whether height of editor can be resized.';
    $lang->about_enable_html_grant = 'Selected group(s) will be able to use HTML';
    $lang->about_enable_autosave = 'You may decide whether auto-save function will be used.';

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

	$lang->edit->help_remove_format = "Tags in selected area will be removed";
    $lang->edit->help_strike_through = "Strike will be on the words";
    $lang->edit->help_align_full = "Align left and right";

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
