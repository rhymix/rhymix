<?php
    /**
     * @file   modules/file/lang/es.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  Paquete lingual de archivo ajuntado
     **/

    $lang->file = 'Ajuntar Archivo';
    $lang->file_name = 'Nombre de Archivo';
    $lang->file_size = 'Tamaño';
    $lang->download_count = 'Bajado';
    $lang->status = 'Estado';
    $lang->is_valid = 'Valido';
    $lang->is_stand_by = 'Preparado';
    $lang->file_list = 'Lista de archivo';
    $lang->allowed_filesize = 'Tamaño maximo de archivo';
    $lang->allowed_attach_size = 'Tamaño maximo en total por documento';
    $lang->allowed_filetypes = 'Tipo de Archivos';

    $lang->about_allowed_filesize = 'Puede especificar el limite de archivos incluidos. (excepto administrador)';
    $lang->about_allowed_attach_size = 'Pueude especificar el limite de archivos incluidos en documento. (excepto administrador)';
    $lang->about_allowed_filetypes = 'Puede subir archivo solo extenciónes definido. Puede definir en la forma como: "*.extensión", y puede usar ";" para muliples.<br />ej) *.* o *.jpg;*.gif;<br />(excepto administrador)';

    $lang->cmd_delete_checked_file = 'eliminar la selección';
    $lang->cmd_move_to_document = 'mover a documento';
    $lang->cmd_download = 'bajar';

    $lang->msg_cart_is_null = 'Por favor seleccióne archivo';
    $lang->msg_checked_file_is_deleted = 'Ha eliminado %d archivos';
    $lang->msg_exceeds_limit_size = 'Ha excedido del limite de incluidos';

    $lang->search_target_list = array(
        'filename' => 'Nombre',
        'filesize' => 'Tamaño (byte)',
        'download_count' => 'Bajado (más)',
        'regdate' => 'Registrado',
        'ipaddress' => 'Dirección IP',
    );
?>
