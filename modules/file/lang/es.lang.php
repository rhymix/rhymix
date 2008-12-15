<?php
    /**
     * @archivo   modules/file/lang/es.lang.php
     * @autor zero <zero@nzeo.com>
     * @sumario Paquete del idioma español para los archivos adjuntos
     **/

    $lang->file = 'Adjuntar archivos';
    $lang->file_name = 'Nombre del archivo';
    $lang->file_size = 'Tamaño del archivo';
    $lang->download_count = 'Cantidad Bajado';
    $lang->status = 'Estado';
    $lang->is_valid = 'Válido';
    $lang->is_stand_by = 'En espera';
    $lang->file_list = 'Lista de archivos adjuntos';
    $lang->allowed_filesize = 'Límite del tamaño del archivo adjunto';
    $lang->allowed_attach_size = 'Límite del tamaño total de los archivos adjuntos por documento';
    $lang->allowed_filetypes = 'Tipos de archivos permitidos';
    $lang->enable_download_group = 'Descargar permitió grupos';

    $lang->about_allowed_filesize = 'Puede definir el límite del tamaño del archivo adjunto. (exceptuando el administrador)';
    $lang->about_allowed_attach_size = 'Puede definir el límite del tamaño total de los archivos adjuntos por documento. (exceptuando el administrador)';
    $lang->about_allowed_filetypes = 'Puede definir las extensiones de los archivos permitidos. Para permitir una extensión use "*.extensión". Para permitir más de una extensión use ";".<br />ej) *.* o *.jpg;*.gif;etc.<br />(exceptuando el administrador)';

    $lang->cmd_delete_checked_file = 'Eliminar el archivo seleccionado';
    $lang->cmd_move_to_document = 'Mover hacia el doncumento';
    $lang->cmd_download = 'Descargar';

    $lang->msg_not_permitted_download = 'Usted no tiene ningún permiso para descargar';
    $lang->msg_cart_is_null = 'Seleccione el archivo a eliminar';
    $lang->msg_checked_file_is_deleted = 'Total de %d archivos eliminados';
    $lang->msg_exceeds_limit_size = 'Ha excedido el límite del tamaño total de los archivos adjuntos';

    $lang->file_search_target_list = array(
        'filename' => 'Nombre del archivo',
        'filesize' => 'Tamaño del archivo (Byte, sobre)',
        'filesize_mega' => '파일크기 (Mb, 이상)',
        'download_count' => 'Descargados (Sobre)',
        'user_id' => '아이디',
        'user_name' => '이름',
        'nick_name' => '닉네임',
        'regdate' => 'La fecha registrada',
        'ipaddress' => 'Dirección IP',
    );
?>
