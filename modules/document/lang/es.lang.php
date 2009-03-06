<?php
    /**
     * @archivo   modules/document/lang/es.lang.php
     * @autor zero <zero@nzeo.com>
     * @sumario Paquete del idioma español para el módulo de documentos.
     **/

    $lang->document_list = 'Document list';
    $lang->thumbnail_type = 'Thumbnail Type';
    $lang->thumbnail_crop = 'Crop';
    $lang->thumbnail_ratio = 'Ratio';
    $lang->cmd_delete_all_thumbnail = 'Delete all thumbnails';
    $lang->move_target_module = "Módulo para cambiar de posición";
    $lang->title_bold = 'Bold';
    $lang->title_color = 'Color';
    $lang->new_document_count = '새글';

    $lang->parent_category_title = 'Categoría Superior';
    $lang->category_title = 'Nombre de la categoría';
    $lang->category_color = '분류 폰트색깔';
    $lang->expand = 'Expandir';
    $lang->category_group_srls = 'Limitar el grupo';
    $lang->cmd_make_child = 'Agregar sub categoría';
    $lang->cmd_enable_move_category = "Cambiar la posición de la categoría. (arrastrar y soltar luego de haber selecionado)";
    $lang->about_category_title = 'Ingresar el nombre de la categoría.';
    $lang->about_expand = 'Si seleccionas esta opción, siempre estará expandido.';
    $lang->about_category_group_srls = '선택하신 그룹만 현재 카테고리를 지정할 수 있도록 합니다';
    $lang->about_category_color = '분류 폰트색깔을 지정합니다.';

    $lang->cmd_search_next = 'Buscar siguiente';

    $lang->cmd_temp_save = 'Guardar Temporales';

	$lang->cmd_toggle_checked_document = 'Invertir los elementos seleccionados';
    $lang->cmd_delete_checked_document = 'Eliminar lo seleccionado';
    $lang->cmd_document_do = 'Usted ..';

    $lang->msg_cart_is_null = 'Selecciona el documento que desea eliminar';
    $lang->msg_category_not_moved = 'No puede se movido';
    $lang->msg_is_secret = 'Es un documento secreto';
    $lang->msg_checked_document_is_deleted = 'Total de %d documentos han sido eliminados';

    // Objetivo de búsqueda en la página del administrador
    $lang->search_target_list = array(
        'title' => 'Título',
        'content' => 'Contenido',
        'user_id' => 'ID',
        'member_srl' => 'Número del usuario',
        'user_name' => 'Nombre del usuario',
        'nick_name' => 'Apodo',
        'email_address' => 'Correo Electrónico',
        'homepage' => 'Página web',
        'is_notice' => 'Aviso',
        'is_secret' => 'Secreto',
        'tags' => 'Etiqueta',
        'readed_count' => 'Número de leídos (sobre)',
        'voted_count' => 'Número de recomnedados (sobre)',
        'comment_count ' => 'Número de comentarios (sobre)',
        'trackback_count ' => 'Número de Trackback (sobre)',
        'uploaded_count ' => 'Número de archivos adjuntos (sobre)',
        'regdate' => 'Día del registro',
        'last_update' => 'Día de la última actualización',
        'ipaddress' => 'Dirección IP',
    );
    $lang->alias = "Alias";
    $lang->history = "히스토리";
    $lang->about_use_history = "히스토리 기능의 사용여부를 지정합니다. 히스토리 기능을 사용할 경우 문서 수정시 이전 리비전을 기록하고 복원할 수 있습니다.";
    $lang->trace_only = "흔적만 남김";
?>
