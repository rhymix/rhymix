<?php
    /**
     * @archivo  modules/editor/lang/es.lang.php
     * @autor NHN (developers@xpressengine.com)
     * @sumario  Paquete del idioma español para el editor de WYSIWYG
     **/

    $lang->editor = 'Editor WYSIWYG';
    $lang->component_name = 'Componente';
    $lang->component_version = 'Versión';
    $lang->component_author = 'Autor';
    $lang->component_link = 'Enlace';
    $lang->component_date = 'Fecha';
    $lang->component_license = 'License';
    $lang->component_history = 'History';
    $lang->component_description = 'Descripción';
    $lang->component_extra_vars = 'Varibles Extras';
    $lang->component_grant = 'Ajuste de las atribuciones';
    $lang->content_style = 'Content Style';
    $lang->content_font = 'Content Font';
	$lang->content_font_size = '문서 폰트 크기';

    $lang->about_component = 'Presentación del componente';
    $lang->about_component_grant = 'Usted puede configurar el permiso de utilizar la ampliación de los componentes de editor.<br /> (Todo el mundo tendría permiso si no comprobado)';
    $lang->about_component_mid = '에디터 컴포넌트가 사용될 대상을 지정할 수 있습니다.<br />(모두 해제시 모든 대상에서 사용 가능합니다)';

    $lang->msg_component_is_not_founded = 'No se puede encontrar el componente del editor %s';
    $lang->msg_component_is_inserted = 'El componente seleccionado ya esta insertado';
    $lang->msg_component_is_first_order = 'El componente seleccionado se localiza en la primera posición';
    $lang->msg_component_is_last_order = 'El componente seleccionado se localiza en la última posición';
    $lang->msg_load_saved_doc = "Existe un documento guardado automáticamente ¿desea recuperarlo ?\nDespués de guardar el documento escrito, el documento autoguardado sera eliminado.";
    $lang->msg_auto_saved = 'Documento guardado automáticamente';

    $lang->cmd_disable = 'Desactivado';
    $lang->cmd_enable = 'activado';

    $lang->editor_skin = 'Editor de Cuidado de la Piel';
    $lang->upload_file_grant = 'La autorización para cargar';
    $lang->enable_default_component_grant = 'La autorización del uso de los componentes por defecto';
    $lang->enable_component_grant = 'La autorización de la utilización de componentes';
    $lang->enable_html_grant = 'La autorización de uso de HTML';
    $lang->enable_autosave = 'Utilice función de guardado automático,';
    $lang->height_resizable = 'Altura cambiar de tamaño';
    $lang->editor_height = 'Altura de Editor';

    $lang->about_editor_skin = 'Usted puede seleccionar la piel del editor.';
    $lang->about_content_style = '문서 편집 및 내용 출력시 원하는 서식을 지정할 수 있습니다';
    $lang->about_content_font = '문서 편집 및 내용 출력시 원하는 폰트를 지정할 수 있습니다.<br/>지정하지 않으면 사용자 설정에 따르게 됩니다<br/> ,(콤마)로 여러 폰트를 지정할 수 있습니다.';
	$lang->about_content_font_size = '문서 편집 및 내용 출력시 원하는 폰트의 크기를 지정할 수 있습니다.<br/>12px, 1em등 단위까지 포함해서 입력해주세요.';
    $lang->about_upload_file_grant = 'Usted puede configurar el permiso de archivo adjunto. (Todo el mundo tendría permiso si no comprobado)';
    $lang->about_default_component_grant = 'Usted puede configurar el permiso de uso de los componentes de editor por defecto. (Todo el mundo tendría permiso si no comprobado)';
    $lang->about_editor_height = 'Usted puede configurar la altura del editor.';
    $lang->about_editor_height_resizable = 'Permiso para cambiar el tamaño de la altura del editor.';
    $lang->about_enable_html_grant = 'Usted puede dar el permiso de uso de HTML';
    $lang->about_enable_autosave = 'Usted puede permitir que la función de guardado automático, en tanto que función de la redacción de artículos';

    $lang->edit->fontname = 'Fuente';
    $lang->edit->fontsize = 'Tamaño';
    $lang->edit->use_paragraph = 'Párrafo';
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

    $lang->edit->header = 'Estilo';
    $lang->edit->header_list = array(
    'h1' => 'Título 1',
    'h2' => 'Título 2',
    'h3' => 'Título 3',
    'h4' => 'Título 4',
    'h5' => 'Título 5',
    'h6' => 'Título 6',
    );

    $lang->edit->submit = 'Confirmar';

    $lang->edit->fontcolor = 'Text Color';
	$lang->edit->fontcolor_apply = '글자 색 적용';
	$lang->edit->fontcolor_more = '글자 색 더보기';
    $lang->edit->fontbgcolor = 'Background Color';
	$lang->edit->fontbgcolor_apply = '글자 배경색 적용';
	$lang->edit->fontbgcolor_more = '글자 배경색 더보기';
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

    $lang->edit->help_fontcolor = 'Selecciona el color de las letras';
    $lang->edit->help_fontbgcolor = 'Selecciona el color del fondo de la letras';
    $lang->edit->help_bold = 'Letra gruesa';
    $lang->edit->help_italic = 'Letra cursiva';
    $lang->edit->help_underline = 'Letra subrayada';
    $lang->edit->help_strike = 'Letra con linea';
    $lang->edit->help_sup = 'Sup';
    $lang->edit->help_sub = 'Sub';
    $lang->edit->help_redo = 'Rehacer';
    $lang->edit->help_undo = 'Deshacer';
    $lang->edit->help_align_left = 'Margen izquierdo';
    $lang->edit->help_align_center = 'Margen central';
    $lang->edit->help_align_right = 'Margen derecho';
	$lang->edit->help_align_justify = 'Align justity';
    $lang->edit->help_add_indent = 'Anadir tabulación';
    $lang->edit->help_remove_indent = 'Quitar tabulación';
    $lang->edit->help_list_number = 'Aplicar la lista con números';
    $lang->edit->help_list_bullet = 'Aplicar la lista con símbolos';
    $lang->edit->help_use_paragraph = 'Presiona Ctrl+Enter para usar el párrafo (Presiona Alt+S para guardar)';

    $lang->edit->url = 'URL';
    $lang->edit->blockquote = 'Blockquote';
    $lang->edit->table = 'Table';
    $lang->edit->image = 'Image';
    $lang->edit->multimedia = 'Movie';
    $lang->edit->emoticon = 'Emoticon';

	$lang->edit->file = '파일';
    $lang->edit->upload = 'Adjuntar';
    $lang->edit->upload_file = 'Archivo adjunto';
	$lang->edit->upload_list = '첨부 목록';
    $lang->edit->link_file = 'Insertar en el contenido del documento';
    $lang->edit->delete_selected = 'Eliminar lo seleccionado';

    $lang->edit->icon_align_article = 'Ocupar un párrafo';
    $lang->edit->icon_align_left = 'Margen izquierdo';
    $lang->edit->icon_align_middle = 'Margen central';
    $lang->edit->icon_align_right = 'Margen derecho';

    $lang->about_dblclick_in_editor = 'Para la configuracion más detallada debera hacer dobleclick sobre el texto, imagen, fondo, etc.';


    $lang->edit->rich_editor = '스타일 편집기';
    $lang->edit->html_editor = 'HTML 편집기';
    $lang->edit->extension ='확장 컴포넌트';
    $lang->edit->help = '도움말';
    $lang->edit->help_command = '단축키 안내';
    
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
	$lang->edit->refresh = 'Refresh';
    $lang->edit->materials = '글감보관함';
    $lang->edit->temporary_savings = '임시저장목록';

	$lang->edit->paging_prev = '이전';
	$lang->edit->paging_next = '다음';
	$lang->edit->paging_prev_help = '이전 페이지로 이동합니다.';
	$lang->edit->paging_next_help = '다음 페이지로 이동합니다.';

	$lang->edit->toc = '목차';
	$lang->edit->close_help = '도움말 닫기';

	$lang->edit->confirm_submit_without_saving = '저장하지 않은 단락이 있습니다.\\n그냥 전송하시겠습니까?';

	$lang->edit->image_align = '이미지 정렬';
	$lang->edit->attached_files = '첨부 파일';

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