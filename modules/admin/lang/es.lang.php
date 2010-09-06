<?php
    /**
     * @archivo   es.lang.php
     * @autor NHN (developers@xpressengine.com)
     * @sumario  Paquete del idioma español (sólo los básicos)
     **/

    $lang->admin_info = 'Administrador de Información';
    $lang->admin_index = 'Índice de la página admin';
    $lang->control_panel = 'Control panel';
    $lang->start_module = 'Módulo de inicio';
    $lang->about_start_module = 'Puede especificar el módulo de inicio por defecto.';

    $lang->module_category_title = array(
        'service' => 'Service Setting',
        'member' => 'Member Setting',
        'content' => 'Content Setting',
        'statistics' => 'Statistics',
        'construction' => 'Construction',
        'utility' => 'Utility Setting',
        'interlock' => 'Interlock Setting',
        'accessory' => 'Accessories',
        'migration' => 'Data Migration',
        'system' => 'System Setting',
    );


    $lang->newest_news = "Noticias recientes";
    
    $lang->env_setup = "Configuración";
    $lang->default_url = "기본 URL";
    $lang->about_default_url = "XE sitio virtual (cafeXE, etc) tiene que introducir la URL base, al utilizar las capacidades de trabajo virtual y el tema de autenticación sayiteugan / módulos y las conexiones se realizan correctamente. (Ej: http:// dominio / ruta de instalación)";

    $lang->env_information = "Información Ambiental";
    $lang->current_version = "Versión actual";
    $lang->current_path = "Instalado Sendero";
    $lang->released_version = "Versión más reciente";
    $lang->about_download_link = "La versión más reciente Zerboard XE está disponible.\nPara descargar la versión más reciente, haga clic en enlace de descarga.";
	
    $lang->item_module = "Lista de Módulos";
    $lang->item_addon  = "Lista de Addons";
    $lang->item_widget = "Lista de Widgets";
    $lang->item_layout = "Liasta de Diseños";

    $lang->module_name = "Nombre del Módulo";
    $lang->addon_name = "Nombre de Addon";
    $lang->version = "Versión";
    $lang->author = "Autor";
    $lang->table_count = "Número de los tableros";
    $lang->installed_path = "Ruta de instalación";

    $lang->cmd_shortcut_management = "Editar el Menú";

    $lang->msg_is_not_administrator = 'Sólo se permite el ingreso del administrador.';
    $lang->msg_manage_module_cannot_delete = 'No se puede eliminar acceso directo del Módulo, Addon, Diseño y Widget.';
    $lang->msg_default_act_is_null = 'No se puede registrar acceso directo por no estar determinada la acción del administrador predefinido.';
	
    $lang->welcome_to_xe = 'Esta es la página del Administrador de XE';
    $lang->about_admin_page = "La página del Administrador aún está en desarrollo.";
    $lang->about_lang_env = "Para aplicar idioma seleccionado conjunto de los usuarios, como por defecto, haga clic en el botón [Guardar] el cambio.";


    $lang->xe_license = 'XE está bajo la Licencia de GPL';
    $lang->about_shortcut = 'Puede Eliminar los accesos directos de módulos, los cuales fueron registrados en la lista de módulos usados frecuentemente';

    $lang->yesterday = "Yesterday";
    $lang->today = "Today";

    $lang->cmd_lang_select = "Selección de Idioma";
    $lang->about_cmd_lang_select = "Seleccione el idioma es sólo el servicio";
    $lang->about_recompile_cache = "Inválido inútil archivo de caché puede organizar jyeotgeona";
    $lang->use_ssl = "Usar SSL";
    $lang->ssl_options = array(
        'none' => "Desactivar",
        'optional' => "Opcionalmente el",
        'always' => "Utilice siempre el"
    );
    $lang->about_use_ssl = "Opcionalmente, la composición de suscripción / editar la información y el uso de SSL especificada en la acción es siempre el uso de SSL para todos los servicios que se utilizarán";
    $lang->server_ports = "Especifique el puerto del servidor";
    $lang->about_server_ports = "80 de HTTP, HTTPS al puerto 443 si se utiliza otro que se especifique lo contrario, el puerto va a necesitar.";
    $lang->use_db_session = '인증 세션 DB 사용';
    $lang->about_db_session = '인증시 사용되는 PHP 세션을 DB로 사용하는 기능입니다.<br/>웹서버의 사용율이 낮은 사이트에서는 비활성화시 사이트 응답 속도가 향상될 수 있습니다<br/>단 현재 접속자를 구할 수 없어 관련된 기능을 사용할 수 없게 됩니다.';
    $lang->sftp = "Use SFTP";
    $lang->ftp_get_list = "Get List";
    $lang->ftp_remove_info = 'Remove FTP Info.';
	$lang->msg_ftp_invalid_path = 'Failed to read the specified FTP Path.';
	$lang->msg_self_restart_cache_engine = 'Please restart Memcached or cache daemon.';
	$lang->mobile_view = 'Use Mobile View';
	$lang->about_mobile_view = 'If accessing with a smartphone, display content with mobile layout.';
    $lang->autoinstall = 'EasyInstall';

    $lang->last_week = 'Last week';
    $lang->this_week = 'This week';
?>
