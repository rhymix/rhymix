<?php
    /**
     * @file   modules/editor/lang/en.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  WYSIWYG Editor module's basic language pack
     **/

    $lang->editor = 'WYSIWYG Editor';
    $lang->component_name = 'Component';
    $lang->component_version = 'Version';
    $lang->component_author = 'Developer';
    $lang->component_link = 'Link';
    $lang->component_date = 'Date';
    $lang->component_license = 'License';
    $lang->component_history = 'Updates';
    $lang->component_description = 'Description';
    $lang->component_extra_vars = 'Option Variable';
    $lang->component_grant = 'Permission Setting';
    $lang->content_style = 'Content Style';
    $lang->content_font = 'Content Font';
    $lang->content_font_size = 'Content Font Size';

    $lang->about_component = 'About component';
    $lang->about_component_grant = 'Selected group(s) will be able to use expanded components of editor.<br />(Leave them blank if you want all groups to have permission)';
    $lang->about_component_mid = 'Editor components can select targets.<br />(All targets will be selected when nothing is selected)';

    $lang->msg_component_is_not_founded = 'Cannot find editor component %s';
    $lang->msg_component_is_inserted = 'Selected component is already inserted';
    $lang->msg_component_is_first_order = 'Selected component is located at the first position';
    $lang->msg_component_is_last_order = 'Selected component is located at the last position';
    $lang->msg_load_saved_doc = "There is an automatically saved article. Do you wish to recover it?\nThe auto-saved draft will be discarded after saving current article";
    $lang->msg_auto_saved = 'Automatically Saved';

    $lang->cmd_disable = 'Inactive';
    $lang->cmd_enable = 'Active';

    $lang->editor_skin = 'Editor Skin';
    $lang->upload_file_grant = 'Permission for Uploading';
    $lang->enable_default_component_grant = 'Permission for Default Components';
    $lang->enable_component_grant = 'Permission for Components';
    $lang->enable_html_grant = 'Permission for HTML';
    $lang->enable_autosave = 'Auto-Save';
    $lang->height_resizable = 'Height Resizable';
    $lang->editor_height = 'Height of Editor';

    $lang->about_editor_skin = 'You may select the skin of editor.';
    $lang->about_content_style = 'You may select style for editting article or displaying content';
    $lang->about_content_font = 'You may select font for editting article or displaying content.<br/>Default font is your own font<br/> Please use comma(,) for multiple input.';
	$lang->about_content_font_size = 'You may select font size for editting article or displaying content.<br/>Please input units such as px or em.';
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

    $lang->edit->header = 'Style';
    $lang->edit->header_list = array(
    'h1' => 'Subject 1',
    'h2' => 'Subject 2',
    'h3' => 'Subject 3',
    'h4' => 'Subject 4',
    'h5' => 'Subject 5',
    'h6' => 'Subject 6',
    );

    $lang->edit->submit = 'Submit';

    $lang->edit->fontcolor = 'Text Color';
    $lang->edit->fontbgcolor = 'Background Color';
    $lang->edit->bold = 'Bold';
    $lang->edit->italic = 'Italic';
    $lang->edit->underline = 'Underline';
    $lang->edit->strike = 'Strike';
    $lang->edit->sup = 'Sup';
    $lang->edit->sub = 'Sub';
    $lang->edit->redo = 'Re Do';
    $lang->edit->undo = 'Un Do';
    $lang->edit->align_left = 'Align Left';
    $lang->edit->align_center = 'Align Center';
    $lang->edit->align_right = 'Align Right';
    $lang->edit->align_justify = 'Align Justify';
    $lang->edit->add_indent = 'Indent';
    $lang->edit->remove_indent = 'Outdent';
    $lang->edit->list_number = 'Orderd List';
    $lang->edit->list_bullet = 'Unordered List';
    $lang->edit->remove_format = 'Style Remover';

    $lang->edit->help_remove_format = 'Tags in selected area will be removed';
    $lang->edit->help_strike_through = 'Strike will be on the words';
    $lang->edit->help_align_full = 'Align left and right';

    $lang->edit->help_fontcolor = 'Select font color';
    $lang->edit->help_fontbgcolor = 'Select background color of font';
    $lang->edit->help_bold = 'Make font bold';
    $lang->edit->help_italic = 'Make italic font';
    $lang->edit->help_underline = 'Underline font';
    $lang->edit->help_strike = 'Strike font';
    $lang->edit->help_sup = 'Superscript';
    $lang->edit->help_sub = 'Subscript';
    $lang->edit->help_redo = 'Redo';
    $lang->edit->help_undo = 'Undo';
    $lang->edit->help_align_left = 'Align left';
    $lang->edit->help_align_center = 'Align center';
    $lang->edit->help_align_right = 'Align right';
	$lang->edit->help_align_justify = 'Align justity';
    $lang->edit->help_add_indent = 'Add indent';
    $lang->edit->help_remove_indent = 'Remove indent';
    $lang->edit->help_list_number = 'Apply number list';
    $lang->edit->help_list_bullet = 'Apply bullet list';
    $lang->edit->help_use_paragraph = 'Press Ctrl+Enter to use paragraph. (Press Alt+S to submit)';

    $lang->edit->url = 'URL';
    $lang->edit->blockquote = 'Blockquote';
    $lang->edit->table = 'Table';
    $lang->edit->image = 'Image';
    $lang->edit->multimedia = 'Movie';
    $lang->edit->emoticon = 'Emoticon';

    $lang->edit->upload = 'Attachment';
    $lang->edit->upload_file = 'Attach';
    $lang->edit->link_file = 'Insert to Content';
    $lang->edit->delete_selected = 'Delete Selected';

    $lang->edit->icon_align_article = 'Occupy a paragraph';
    $lang->edit->icon_align_left = 'Align Left';
    $lang->edit->icon_align_middle = 'Align Center';
    $lang->edit->icon_align_right = 'Align Right';

    $lang->about_dblclick_in_editor = 'You may set detail component configures by double-clicking background, text, images, or quotations';


    $lang->edit->rich_editor = 'Rich Text Editor';
    $lang->edit->html_editor = 'HTML Editor';
    $lang->edit->extension ='Extension Components';
    $lang->edit->help = 'Help';
    $lang->edit->help_command = 'Help Hotkeys';
    
    $lang->edit->lineheight = 'Line Height';
	$lang->edit->fontbgsampletext = 'ABC';
	
	$lang->edit->hyperlink = 'Hyperlink';
	$lang->edit->target_blank = 'New Window';
	
	$lang->edit->quotestyle1 = 'Left Solid';
	$lang->edit->quotestyle2 = 'Quote';
	$lang->edit->quotestyle3 = 'Solid';
	$lang->edit->quotestyle4 = 'Solid + Background';
	$lang->edit->quotestyle5 = 'Bold Solid';
	$lang->edit->quotestyle6 = 'Dotted';
	$lang->edit->quotestyle7 = 'Dotted + Background';
	$lang->edit->quotestyle8 = 'Cancel';


    $lang->edit->jumptoedit = 'Skip Edit Toolbox';
    $lang->edit->set_sel = 'Set Cell Count';
    $lang->edit->row = 'Row';
    $lang->edit->col = 'Column';
    $lang->edit->add_one_row = 'Add 1 Row';
    $lang->edit->del_one_row = 'Remove 1 Row';
    $lang->edit->add_one_col = 'Add 1 Column';
    $lang->edit->del_one_col = 'Remove 1 Column';

    $lang->edit->table_config = 'Table Config';
    $lang->edit->border_width = 'Border Width';
    $lang->edit->border_color = 'Border Color';
    $lang->edit->add = 'Add';
    $lang->edit->del = 'Sub';
    $lang->edit->search_color = 'Search Colors';
    $lang->edit->table_backgroundcolor = 'Table Background Color';
    $lang->edit->special_character = 'Special Characters';
    $lang->edit->insert_special_character = 'Insert Special Characters';
    $lang->edit->close_special_character = 'Close Special Characters Layer';
    $lang->edit->symbol = 'Symbols';
    $lang->edit->number_unit = 'Numbers and Units';
    $lang->edit->circle_bracket = 'Circles, Brackets';
    $lang->edit->korean = 'Korean';
    $lang->edit->greece = 'Greek';
    $lang->edit->Latin  = 'Latin';
    $lang->edit->japan  = 'Japanese';
    $lang->edit->selected_symbol  = 'Selected Symbols';

    $lang->edit->search_replace  = 'Find/Replace';
    $lang->edit->close_search_replace  = 'Close Find/Replace Layer';
    $lang->edit->replace_all  = 'Replace All';
    $lang->edit->search_words  = 'Words to Find';
    $lang->edit->replace_words  = 'Words to Replace';
    $lang->edit->next_search_words  = 'Find Next';
    $lang->edit->edit_height_control  = 'Set Edit Form Size';

    $lang->edit->merge_cells = 'Merge Table Cells';
    $lang->edit->split_row = 'Split Row';
    $lang->edit->split_col = 'Split Column';
    
    $lang->edit->toggle_list   = 'Fold/Unfold';
    $lang->edit->minimize_list = 'Minimize';
    
    $lang->edit->move = 'Move';
	$lang->edit->refresh = 'Refresh';
    $lang->edit->materials = 'Materials';
    $lang->edit->temporary_savings = 'Temporary Saved List';

	$lang->edit->paging_prev = 'Prev';
	$lang->edit->paging_next = 'Next';
	$lang->edit->paging_prev_help = 'Move to previous page.';
	$lang->edit->paging_next_help = 'Move to next page.';

	$lang->edit->toc = 'Table of Contents';
	$lang->edit->close_help = 'Close Help';
	
	$lang->edit->confirm_submit_without_saving = 'There is paragraphs that were not saved.\\nProceed anyway?';

	$lang->edit->image_align = '이미지 정렬';
	$lang->edit->attached_files = '첨부 파일';
?>