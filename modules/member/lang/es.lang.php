<?php
    /**
     * @file   es.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Spanish Language Pack (Only Basic Things)
     **/

    $lang->member = 'Usuario';
    $lang->member_default_info = 'Informacion Basica';
    $lang->member_extend_info = 'Informacion adicional';
    $lang->default_group_1 = "Usuario registrado";
    $lang->default_group_2 = "Usuario regular";
    $lang->admin_group = "Manejo del grupo";
    $lang->keep_signed = 'Keep me signed in';
    $lang->remember_user_id = 'Guardar ID';
    $lang->already_logged = "Ya esta conectado";
    $lang->denied_user_id = 'Este ID esta prohibido.';
    $lang->null_user_id = 'IngresarID';
    $lang->null_password = 'Ingresar la contrasena';
    $lang->invalid_authorization = 'No esta certificado';
    $lang->invalid_user_id= "Este ID no existe";
    $lang->invalid_password = 'Contrasena incorrecta';
    $lang->allow_mailing = 'Registro del envio de mail';
    $lang->denied = 'Prohibido';
    $lang->is_admin = 'Atribucion del administrador superior';
    $lang->group = 'Grupo asignado';
    $lang->group_title = 'Nombre del grupo';
    $lang->group_srl = 'Numero del grupo';
    $lang->signature = 'Firma';
    $lang->profile_image = 'Perfil de Imagen';
    $lang->profile_image_max_width = 'Max Anchura';
    $lang->profile_image_max_height = 'Max Altura';
    $lang->image_name = 'Nombre de la imagen';
    $lang->image_name_max_width = 'Ancho Maximo';
    $lang->image_name_max_height = 'Altura Maxima';
    $lang->image_mark = 'Marca de la imagen';
    $lang->image_mark_max_width = 'Ancho Maximo';
    $lang->image_mark_max_height = 'Altura Maxima';
    $lang->enable_openid = 'Activar OpenID';
    $lang->enable_join = 'Permitir el registro del usuario';
    $lang->enable_confirm = '메일 인증 사용';
    $lang->enable_ssl = 'Activar SSL';
    $lang->security_sign_in = 'Sign in using enhanced security';
    $lang->limit_day = 'Limite de la fecha temporal';
    $lang->limit_date = 'Limite de fecha';
    $lang->after_login_url = 'URL despues del inicio de sesion';
    $lang->after_logout_url = 'URL despues de cerrar sesion';
    $lang->redirect_url = 'URL luego del registro';
    $lang->agreement = 'Acuerdo del registro del usuario';
    $lang->accept_agreement = 'Acepto';
    $lang->member_info = 'Informacion del Usuario';
    $lang->current_password = 'Contrasena actual';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = 'Nombre del Webmaster';
    $lang->webmaster_email = 'Correo electronico Webmaster';

    $lang->about_keep_signed = '브라우저를 닫더라도 로그인이 계속 유지될 수 있습니다.\n\n로그인 유지 기능을 사용할 경우 다음 접속부터는 로그인을 하실 필요가 없습니다.\n\n단, 게임방, 학교 등 공공장소에서 이용시 개인정보가 유출될 수 있으니 꼭 로그아웃을 해주세요';
	$lang->about_webmaster_name = 'Por favor, webmaster de entrada el nombre que se utilizara para la autenticacion de los correos u otros sitio de la administracion. (Por defecto: webmaster)';
    $lang->about_webmaster_email = 'Introduzca la direccion de correo electronico webmaster.';

    $lang->search_target_list = array(
        'user_id' => 'ID',
        'user_name' => 'Nombre',
        'nick_name' => 'Apodo',
        'email_address' => 'Direccion de Email',
        'regdate' => 'Fecha del registro',
        'last_login' => 'Fecha de su ultima coneccion',
        'extra_vars' => 'Variables Extra',
    );


    $lang->cmd_login = 'Conectar';
    $lang->cmd_logout = 'Desconectar';
    $lang->cmd_signup = 'Registrar';
    $lang->cmd_modify_member_info = 'Modificar nombre del usuario';
    $lang->cmd_modify_member_password = 'Cambiar la contrasena';
    $lang->cmd_view_member_info = 'Informacion del usuario';
    $lang->cmd_leave = 'Dejar de ser usuario';
    $lang->cmd_find_member_account = 'Find Account Info';

    $lang->cmd_member_list = 'Lista de los Usuarios';
    $lang->cmd_module_config = 'Configuracion predefinidos';
    $lang->cmd_member_group = 'Manejo del grupo';
    $lang->cmd_send_mail = 'Enviar Email';
    $lang->cmd_manage_id = 'Manejo de ID prohibidos';
    $lang->cmd_manage_form = 'Manejo de forma del registro';
    $lang->cmd_view_own_document = 'Ver documentos escritos';
    $lang->cmd_trace_document = 'Trace Written Articles';
    $lang->cmd_trace_comment = 'Trace Written Comments';
    $lang->cmd_view_scrapped_document = 'Ver Scraps';
    $lang->cmd_view_saved_document = 'Ver articulos guardados';
    $lang->cmd_send_email = 'Enviar Email';

    $lang->msg_email_not_exists = "Email address doesn't exists";

    $lang->msg_alreay_scrapped = 'Este documento ya esta hecho scrap';

    $lang->msg_cart_is_null = 'Seleciona el objetivo';
    $lang->msg_checked_file_is_deleted = '%d archivos adjuntos son eliminados';

    $lang->msg_find_account_title = 'Account Info';
    $lang->msg_find_account_info = 'Esto se solicita la informacion de la cuenta';
    $lang->msg_find_account_comment = 'La contrasena se modificara para arriba al hacer clic en un enlace a continuacion. <br /> Por favor, despues de modificar la contrasena de acceso.';
    $lang->msg_confirm_account_title = '가입 인증 메일 입니다';
    $lang->msg_confirm_account_info = '가입하신 계정 정보는 아래와 같습니다';
    $lang->msg_confirm_account_comment = '아래 링크를 클릭하시면 가입 인증이 이루어집니다.';
    $lang->msg_auth_mail_sent = 'La autenticacion de correo ha sido enviado a% s. Por favor, compruebe su correo.';
    $lang->msg_confirm_mail_sent = '%s 메일로 가입 인증 메일이 발송되었습니다. 메일을 확인하세요.';
    $lang->msg_invalid_auth_key = '잘못된 계정 인증 요청입니다.<br />아이디/비밀번호 찾기를 다시 하시거나 사이트 관리자에게 계정 정보를 문의해주세요';
    $lang->msg_success_authed = 'Esto no es valido solicitud de autenticacion. <br /> Por favor, intentelo encontrar informacion de la cuenta o pongase en contacto con el administrador.';
    $lang->msg_success_confirmed = '가입 인증이 정상적으로 처리 되었습니다.';

    $lang->msg_new_member = 'Agregar usuario';
    $lang->msg_update_member = 'Modificar informacion del usuario';
    $lang->msg_leave_member = 'Dejar de ser usuario';
    $lang->msg_group_is_null = 'No es un grupo registrado';
    $lang->msg_not_delete_default = 'Los articulos predefinidos no pueden ser eliminados';
    $lang->msg_not_exists_member = "Este usuario no existe";
    $lang->msg_cannot_delete_admin = 'ID del Administrador no puede ser eliminado. Remover el ID desde la Administracion y trate nuevamente.';
    $lang->msg_exists_user_id = 'Este ID ya existe. Por favor trate con otro ID';
    $lang->msg_exists_email_address = 'Esta direccion del email ya existe. Por favor trate con otra direccion del email.';
    $lang->msg_exists_nick_name = 'Este apodo ya existe. Por favor trate con otro apodo.';
    $lang->msg_signup_disabled = 'Usted no puede ser registrado';
    $lang->msg_already_logged = 'Usted ya ha sido registrado';
    $lang->msg_not_logged = 'Por favor conectese primero';
    $lang->msg_insert_group_name = 'Por favor ingresar el nombre del grupo';
    $lang->msg_check_group = 'Introduce nombre de grupo';

    $lang->msg_not_uploaded_image_name = 'Imagen del nombre no puede ser registrado';
    $lang->msg_not_uploaded_image_mark = 'Imagen de marca no puede ser resistrado';

    $lang->msg_accept_agreement = 'Usted primero debe aceptar el acuerdo'; 

    $lang->msg_user_denied = 'ID ingresado ha sido prohibido para su uso';
    $lang->msg_user_not_confirmed = '아직 메일 인증이 이루어지지 않았습니다. 메일을 확인해 주세요';
    $lang->msg_user_limited = 'ID ingresado puede ser usado luego de %s';

    $lang->about_user_id = 'ID del usuario debe ser entre 3-20 letras que consiste en alfabetos+numero con alfabeto como primera letra.';
    $lang->about_password = 'Contrasena debe ser entre 6-20 letras';
    $lang->about_user_name = 'Nombre debe ser entre 2-20 letras';
    $lang->about_nick_name = 'Apodo debe ser entre 2-20 letras';
    $lang->about_email_address = 'Direccion de email es usado para modificar/encontrar contrasena luego de la certificacion de email';
    $lang->about_homepage = 'Ingresar su sitio web, si es que lo tiene';
    $lang->about_blog_url = 'Ingresar su blog, si es que lo tiene';
    $lang->about_birthday = 'Ingresar su fecha de nacimiento';
    $lang->about_allow_mailing = "Si usted no se ha registrado el envio de email, usted no podra recibir el email del grupo";
    $lang->about_denied = 'Si selecciona esta opcion prohibira el uso del ID';
    $lang->about_is_admin = 'Si selecciona esta opcion para obtener la atribucion del administrador superior';
    $lang->about_member_description = "Nota del administrador acerca de los usuarios";
    $lang->about_group = 'Un ID puede pertenecer a muchos grupos';

    $lang->about_column_type = 'Definir el estilo de la forma de registro que desea agregar';
    $lang->about_column_name = 'Ingresar el nombre en espanol para poder usar en plantilla (nombre como variable)';
    $lang->about_column_title = 'Esto sera mostrado cuando el usuario registra o modifica/visualiza la informacion del usuario';
    $lang->about_default_value = 'Usted puede predefinir los valores';
    $lang->about_active = 'Si selecciona "Activar" los articulos seran mostrados en el momento de registrar';
    $lang->about_form_description = 'Si Usted ingresa la forma de la descripcion, sera mostrado en el momento de registrar';
    $lang->about_required = 'Si selecciona esta opcion, sera articulo esencial para el registro';

    $lang->about_enable_openid = 'Selecciona esta opcion si activado OpenID';
    $lang->about_enable_join = 'Debe seleccionar esta opcion para permitir el registro de los usuarios';
    $lang->about_enable_confirm = '입력된 메일 주소로 인증 메일을 보내 회원 가입을 확인 합니다';
    $lang->about_enable_ssl = '서버에서 SSL지원이 될 경우 회원가입/정보수정/로그인등의 개인정보가 서버로 보내질때 SSL(https)를 이용하도록 할 수 있습니다';
    $lang->about_limit_day = 'Usted puede limitar la fecha de la certificacion luego de registrar';
    $lang->about_limit_date = 'Usuario no podra conectarse hasta la fecha indicada';
    $lang->about_after_login_url = '로그인 후 이동할 URL을 정하실 수 있습니다. 비어 있으면 해당 페이지가 유지됩니다.';
    $lang->about_after_logout_url = '로그아웃 후 이동할 URL을 정하실 수 있습니다. 비어 있으면 해당 페이지가 유지됩니다.';
    $lang->about_redirect_url = 'Ingresar la URL que va a abrir luego de registrar los usuarios. Cuando esto esta vacio, se habrira la pagina anterior a la del registro.';
    $lang->about_agreement = "Acuerdo del registro no sera mostrado si esta vacio";

    $lang->about_image_name = "Permitir a los usuarios el uso de imagen del nombre en ves del nombre del usuario";
    $lang->about_image_mark = "Permitir a los usuarios el uso de imagen de marca delante de sus nombres";
    $lang->about_profile_image = 'Permitir a los usuarios a utilizar nombre de la imagen en lugar de texto nombre';
    $lang->about_accept_agreement = "Yo he leido todo el acuerdo y acepto"; 

    $lang->about_member_default = 'Al registrar se configura como grupo predefinido';

    $lang->about_openid = 'Cuando tu registra como OpenID, la informacion basica como ID o la direccion del email sera guardado en este sitio, pero la contrasena y la resolucion de la certificacion se hara en el servicio ofrecido por openID';
    $lang->about_openid_leave = 'La secesion de OpenID medios eliminacion de su informacion de miembros de este sitio. <br /> Si se registra despues de la secesion, se le reconocera como un nuevo miembro, de modo que ya no tienen el permiso para que su ex-escrito articulos.';

    $lang->about_member = "Esto es un modulo para crear/modificar/eliminar usuarios y manejar grupos o el formato del registro.\n Usted puede manejar usuarios creando nuevos grupos, y obtener informacion adicional manejando el formato del registro";
    $lang->about_find_member_account = 'Su informacion de la cuenta sera observado por direccion de correo electronico registrada. <br /> Introduce la direccion de correo electronico que usted tiene de entrada en el registro, y pulse "Buscar" Informacion de la cuenta ". <br />';
?>
