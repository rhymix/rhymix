<?php
    /**
     * @file   modules/editor/lang/es.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  Paquete lingual de editor WYSIWYG
     **/

    $lang->editor = "Editor WYSIWYG";
    $lang->component_name = "Componente";
    $lang->component_version = "Versión";
    $lang->component_author = "Autor";
    $lang->component_link = "Enlace";
    $lang->component_date = "Fecha";
    $lang->component_description = "Descripción";
    $lang->component_extra_vars = "Varibles Extras";
    $lang->component_grant = "Autoridad"; 

    $lang->about_component = "Sobre Componente";
    $lang->about_component_grant = "Puede usar el funcion solo grupos seleciónado";

    $lang->msg_component_is_not_founded = 'No puede buscar componente editor %s';
    $lang->msg_component_is_inserted = 'Ya habia insertado el componente';
    $lang->msg_component_is_first_order = 'Componente seleciónado esta ordenado en primero lugar';
    $lang->msg_component_is_last_order = 'Componente seleciónado esta ordenado en ultimo lugar';
    $lang->msg_load_saved_doc = "Hay documento guardado automaticamente. ¿Desea recuperarlo?\nDespues de guardar el documento temporal van a eliminado.";
    $lang->msg_auto_saved = "Documento guardado automaticamente";

    $lang->cmd_disable = "Activado";
    $lang->cmd_enable = "Desactivado";

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
    $lang->edit->help_use_paragrapth = "Press Ctrl+Enter to use paragraph. (Press Alt+S to save)";

    $lang->edit->upload = 'Attachment';
    $lang->edit->upload_file = 'Attach'; 
    $lang->edit->link_file = 'Insert to Content';
    $lang->edit->delete_selected = 'Delete Selected';

    $lang->edit->icon_align_article = 'Occupy a paragraph';
    $lang->edit->icon_align_left = 'Align Left';
    $lang->edit->icon_align_middle = 'Align Center';
    $lang->edit->icon_align_right = 'Align Right';

    $lang->about_dblclick_in_editor = 'You are able to set detail component configure by double-clicking on background, text, images, or quotations';
?>
