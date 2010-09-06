<?php
    /**
     * @archivo   modules/file/lang/es.lang.php
     * @autor NHN (developers@xpressengine.com)
     * @sumario Paquete del idioma espaA±ol para los archivos adjuntos
     **/

    $lang->file = 'Adjuntar archivos';
    $lang->file_name = 'Nombre del archivo';
    $lang->file_size = 'Tamaño del archivo';
    $lang->download_count = 'Cantidad Bajado';
    $lang->status = 'Estado';
    $lang->is_valid = 'Válido';
    $lang->is_stand_by = 'En espera';
    $lang->file_list = 'Lista de archivos adjuntos';
    $lang->allow_outlink = 'Archivo Enlaces externos';
    $lang->allow_outlink_site = 'Fuera del archivo de sitios permitidos';
    $lang->allowed_filesize = 'Límite del tamaño del archivo adjunto';
    $lang->allowed_attach_size = 'Límite del tamaño total de los archivos adjuntos por documento';
    $lang->allowed_filetypes = 'Tipos de archivos permitidos';
    $lang->enable_download_group = 'Descargar permitió grupos';

    $lang->about_allow_outlink = 'Enlaces externos a Rusia Ripper puede bloquear el archivo. (*. WMV, *. mp3, etc, excepto los archivos multimedia)';
    $lang->about_allow_outlink_site = 'Archivos, independientemente de la configuración para permitir a los enlaces externos es la dirección del sitio. Entrada múltiples gubunhaeju un cambio en la línea, por favor. <br /> Ej.) http://xpressengine.com/';
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
    $lang->msg_file_not_found = '요청하신 파일을 찾을 수 없습니다.';

    $lang->file_search_target_list = array(
        'filename' => 'Nombre del archivo',
        'filesize_more' => 'Tamaño del archivo (Byte, sobre)',
        'filesize_mega_more' => 'Tamaño del archivo (Mb, o mb)',
		'filesize_less' => '파일크기 (byte, 이하)',
		'filesize_mega_less' => '파일크기 (Mb, 이하)',
        'download_count' => 'Descargados (Sobre)',
        'user_id' => 'ID',
        'user_name' => 'Nombre',
        'nick_name' => 'Apodo',
        'regdate' => 'La fecha registrada',
        'ipaddress' => 'Dirección IP',
    );
	$lang->msg_not_allowed_outlink = 'It is not allowed to download files not from this site.'; 
    $lang->msg_not_permitted_create = '파일 또는 디렉토리를 생성할 수 없습니다.';
	$lang->msg_file_upload_error = '파일 업로드 중 에러가 발생하였습니다.';

?>
