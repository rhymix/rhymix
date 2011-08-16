<?php
    /**
     * @archivo  es.lang.php
     * @autor NHN (developers@xpressengine.com)
     * @sumario Paquete del idioma espanol para la instalación
     **/

    $lang->introduce_title = 'Instalación de XE ';
	$lang->lgpl_agree = 'GNU 약소 일반 공중 사용 허가서(LGPL v2) 동의';
	$lang->enviroment_gather = '설치 환경 수집 동의';
	$lang->install_progress_menu = array(
			'agree'=>'라이선스 동의',
			'condition'=>'설치 조건 확인',
			'ftp'=>'FTP 정보 입력',
			'dbSelect'=>'DB 선택',
			'dbInfo'=>'DB 정보 입력',
			'configInfo'=>'환경 설정',
			'adminInfo'=>'관리자 정보 입력'
		);
    $lang->install_condition_title = "Por favor chequee los requerimientos de la instalación.";

    $lang->install_checklist_title = array(
			'php_version' => 'Versión PHP',
            'permission' => 'Atribución',
            'xml' => 'Librería XML',
            'iconv' => 'Librería ICONV',
            'gd' => 'Librería GD',
            'session' => 'Configuración Session.auto_start',
            'db' => 'DB',
        );

	$lang->install_license_desc = array(
			'lgpl' => 'GNU 약소 일반 공중 사용 허가서(LGPL v2)에 동의해야 합니다.'
		);
    $lang->install_checklist_desc = array(
			'php_version' => '[Requerido] Si la versión de PHP es 5.2.2, XE no sera instalado por errores',
            'permission' => '[Requerido] La ruta de la instalación de XE o el directorio de ./archivos deberia tener la atribución 707',
            'xml' => '[Requerido] Libreria XML es necesario para la comunicación de XML',
            'session' => '[Requerido] Para el uso de la sesion de XE, el archivo php.ini deberia estar configurada  session.auto_start=0',
            'iconv' => 'Para transformar UTF-8 y otra paquete del idioma deberia estar instalado el Iconv.',
            'gd' => 'Libreria GD deberia estar instalado para utilizar la funcion de convertir la imagen',
        );

    $lang->install_checklist_xml = 'Instalar Librería XML ';
    $lang->install_without_xml = 'Librería XML no esta instalado';
    $lang->install_checklist_gd = 'Instalar Librería GD';
    $lang->install_without_gd  = 'Libreria GD no esta instalado para la conversión de la imagen';
    $lang->install_checklist_gd = 'Intalar Librería GD';
    $lang->install_without_iconv = 'Libreria Iconv no esta instalado para procesar las fuentes';
    $lang->install_session_auto_start = 'Puede provocar errores si en la configuración de php esta configurada "session.auto_start=1"';
    $lang->install_permission_denied = 'La atribución de la ruta de instalacion no es igual a 707';

    $lang->cmd_agree_license = 'Yo accepto la licencia';
    $lang->cmd_install_fix_checklist = 'Yo he configurado las condiciones necesarias para la instalación.';
    $lang->cmd_install_next = 'Continuar la instalación';
    $lang->cmd_ignore = 'Ignore';

    $lang->db_desc = array(
        'mysql' => 'Utilizando las funciones mysql*() de PHP usar DB mysql.<br />La transacción es desabilitado ya que DB(Bases de Datos) está creado por myisam.',
        'mysqli' => 'Utilizando las funciones mysqli*() de PHP usar DB mysql.<br />La transacción es desabilitado ya que DB(Bases de Datos) está creado por myisam.',
        'mysql_innodb' => 'Utilizando innodb usar BD mysql.<br />La transacción es hablilitado para innodb',
        'sqlite2' => 'Soporta sqlite2, el cual almacena los datos en archivos <br />En la instalacion, es necesario crear archivo de BD en un lugar inaccesible de la web.<br />(Testeo de la estabilización no realizada)',
        'sqlite3_pdo' => 'A través de PDO de PHP soporta sqlite2 <br />En la instalación, es necesario crear archivo de BD en un lugar inaccesible de la web.',
        'cubrid' => 'Usar BD CUBRID. <a href="http://www.xpressengine.com/?mid=manual&pageid=2880556" onclick="window.open(this.href);return false;" class="manual">manual</a>',
        'mssql' => 'Usar BD MSSQL.',
        'postgresql' => 'Usar BD PostgreSql.',
        'firebird' => 'Usar BD firebird. Cómo crear <br /> PP (crear base de datos "/ path / dbname.fdb" page_size = 8192 el conjunto de caracteres UTF8 por defecto;)',
    );

    $lang->form_title = 'Ingresar  BD &amp; Información del Administrador;';
    $lang->db_title = 'Por favor escribir información de BD';
    $lang->db_type = 'Tipo de BD';
    $lang->select_db_type = 'Seleccione el tipo de BD que desea usar.';
    $lang->db_hostname = 'Hostname de BD';
    $lang->db_port = 'Port de BD';
    $lang->db_userid = 'ID de BD';
    $lang->db_password = 'Contraseña de BD';
    $lang->db_database = 'Base de datos BD';
    $lang->db_database_file = 'Archivo de base de datos BD';
    $lang->db_table_prefix = 'Encabezado de la tabla';
    $lang->admin_title = 'Información del Administrator';
    $lang->env_title = 'Configuración';
    $lang->use_optimizer = 'Habilitar el optimizador';
    $lang->about_optimizer = 'Si optimizador está habilitado, los usuarios pueden acceder rápidamente a este sitio, ya que hay múltiples CSS / JS archivos son comprimidos juntos y antes de la transmisión. <br /> No obstante, esta optimización podría ser problemáticas de acuerdo con CSS o JS. Si inhabilitarlo, que funciona correctamente a pesar de que sería más lento el trabajo.';
    $lang->use_rewrite = 'Usar rewrite mod';
    $lang->use_sso = 'SSO';
    $lang->about_rewrite = "Si el servidor de la web soporte rewrite mod, URL largas como http://bla/?documento_srl=123 puede abreviarse como  http://bla/123";
	$lang->about_sso = '사용자가 한 번만 로그인하면 기본 사이트와 가상 사이트에 동시에 로그인이 되는 기능입니다. 가상 사이트를 사용할 때만 필요합니다.';
    $lang->time_zone = 'La Hora por Zona';
    $lang->about_time_zone = "Si la hora del servidor y la hora de su ubicación es diferente, Usted puede elegir la hora por zona para corregir";
    $lang->qmail_compatibility = 'Compatible con Qmail';
    $lang->about_qmail_compatibility = 'Qmail como MTA no reconoce CRLF como la línea de separación que se enviará por correo.';
    $lang->about_database_file = 'Sqlite guarda el dato en el archivo. Es necesario crear archivo de BD en un lugar inaccesible de la web.<br/><span style="color:red">El archivo de dato debe estar ubicado en un lugar con la atribución 707.</span>';
    $lang->success_installed = 'Instalación finalizada';
    $lang->msg_cannot_proc = 'No puede ser ejecutado lo requerido por no disponer del ambiente de instalación.';
    $lang->msg_already_installed = 'Instalación de XE ya existe.';
    $lang->msg_dbconnect_failed = "Ha ocurrido un error en la conección de BD.\n Por favor chequee la información de BD nuevamente";
    $lang->msg_table_is_exists = "La tabla ya ha sido creado en BD.\n Creado nuevamente el archivo de configuración.";
    $lang->msg_install_completed = "Instalación finalizada.\n Muchas gracias.";
    $lang->msg_install_failed = "Ha ocurrido un error al crear el archivo de instalación.";
    $lang->ftp_get_list = "Get List";
	$lang->read_all = '전문 읽기';
	$lang->license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->install_env_agreement = '설치 환경 수집 동의';
	$lang->install_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>>웹서버, 데이터베이스, PHP버전과 Extension, 사이트에 설치된 모듈과 애드온</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html';
?>
