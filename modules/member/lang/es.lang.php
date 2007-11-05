<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  English Language Pack (Only Basic Things)
     **/

    $lang->member = 'Usuario';
    $lang->member_default_info = 'Información Basica';
    $lang->member_extend_info = 'Información adicional';
    $lang->default_group_1 = "Usuario registrado";
    $lang->default_group_2 = "Usuario regular";
    $lang->admin_group = "Manejo del grupo";
    $lang->remember_user_id = 'Guardar ID';
    $lang->already_logged = "Ya está conectado";
    $lang->denied_user_id = 'Este ID está prohibido.';
    $lang->null_user_id = 'IngresarID';
    $lang->null_password = 'Ingresar la contraseña';
    $lang->invalid_authorization = 'No está certificado';
    $lang->invalid_user_id= "Este ID no existe";
    $lang->invalid_password = 'Contraseña incorrecta';
    $lang->allow_mailing = 'Registro del envío de mail';
    $lang->allow_message = 'Permitir la recepción del mensaje';
    $lang->allow_message_type = array(
             'Y' => 'Recibir todo',
             'N' => 'Rechazar',
             'F' => 'Sólo amigos',
        );
    $lang->denied = 'Prohibido';
    $lang->is_admin = 'Atribución del administrador superior';
    $lang->group = 'Grupo asignado';
    $lang->group_title = 'Nombre del grupo';
    $lang->group_srl = 'Número del grupo';
    $lang->signature = 'Firma';
    $lang->profile_image = '프로필 사진';
    $lang->profile_image_max_width = '가로 제한 크기';
    $lang->profile_image_max_height = '세로 제한 크기';
    $lang->image_name = 'Nombre de la imagen';
    $lang->image_name_max_width = 'Ancho Máximo';
    $lang->image_name_max_height = 'Altura Máxima';
    $lang->image_mark = 'Marca de la imagen';
    $lang->image_mark_max_width = 'Ancho Máximo';
    $lang->image_mark_max_height = 'Altura Máxima';
    $lang->enable_openid = 'Activar OpenID';
    $lang->enable_join = 'Permitir el registro del usuario';
    $lang->limit_day = 'Límite de la fecha temporal';
    $lang->limit_date = 'Límite de fecha';
    $lang->redirect_url = 'URL luego del registro';
    $lang->agreement = 'Acuerdo del registro del usuario';
    $lang->accept_agreement = 'Acepto';
    $lang->sender = 'Remitente';
    $lang->receiver = 'Receptor';
    $lang->friend_group = 'Grupo de amigos';
    $lang->default_friend_group = 'Grupo desasignado';
    $lang->member_info = 'Información del Usuario';
    $lang->current_password = 'Contraseña actual';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = '웹마스터 이름';
    $lang->webmaster_email = '웹마스터 메일주소';

    $lang->about_webmaster_name = '인증 메일이나 기타 사이트 관리시 사용될 웹마스터의 이름을 입력해주세요. (기본 : webmaster)';
    $lang->about_webmaster_email = '웹마스터의 메일 주소를 입력해주세요.';

    $lang->search_target_list = array(
        'user_id' => 'ID',
        'user_name' => 'Nombre',
        'nick_name' => 'Apodo',
        'email_address' => 'Dirección de Email',
        'regdate' => 'Fecha del registro',
        'last_login' => 'Fecha de su última conección',
        'extra_vars' => 'Variables Extra',
    );

    $lang->message_box = array(
        'R' => 'Recibido',
        'S' => 'Enviado',
        'T' => 'Buzon de Email',
    );

    $lang->readed_date = "Fecha Leído"; 

    $lang->cmd_login = 'Conectar';
    $lang->cmd_logout = 'Desconectar';
    $lang->cmd_signup = 'Registrar';
    $lang->cmd_modify_member_info = 'Modificar nombre del usuario';
    $lang->cmd_modify_member_password = 'Cambiar la contraseña';
    $lang->cmd_view_member_info = 'Información del usuario';
    $lang->cmd_leave = 'Dejar de ser usuario';
    $lang->cmd_find_member_account = 'Find Account Info';

    $lang->cmd_member_list = 'Lista de los Usuarios';
    $lang->cmd_module_config = 'Configuración predefinidos';
    $lang->cmd_member_group = 'Manejo del grupo';
    $lang->cmd_send_mail = 'Enviar Email';
    $lang->cmd_manage_id = 'Manejo de ID prohibidos';
    $lang->cmd_manage_form = 'Manejo de forma del registro';
    $lang->cmd_view_own_document = 'Ver documentos escritos';
    $lang->cmd_view_scrapped_document = 'Ver Scraps';
    $lang->cmd_view_saved_document = '저장함 보기';
    $lang->cmd_send_email = 'Enviar Email';
    $lang->cmd_send_message = 'Enviar Mensaje';
    $lang->cmd_reply_message = 'Responder el mensaje';
    $lang->cmd_view_friend = 'Amigos';
    $lang->cmd_add_friend = 'Registrar como Amigo';
    $lang->cmd_view_message_box = 'Buzón de mensajes';
    $lang->cmd_store = "Guardar";
    $lang->cmd_add_friend_group = 'agregar grupo de amigos';
    $lang->cmd_rename_friend_group = 'Cambiar el nombre del grupo de amigos';

    $lang->msg_email_not_exists = "Email address doesn't exists";

    $lang->msg_alreay_scrapped = 'Este documento ya esta hecho scrap';

    $lang->msg_cart_is_null = 'Seleciona el objetivo';
    $lang->msg_checked_file_is_deleted = '%d archivos adjuntos son eliminados';

    $lang->msg_find_account_title = 'Account Info';
    $lang->msg_find_account_info = '요청하신 계정 정보는 아래와 같습니다';
    $lang->msg_find_account_comment = '아래 링크를 클릭하시면 위에 적힌 비밀번호로 바뀌게 됩니다.<br />로그인 하신 후 비밀번호를 바꾸어주세요.';
    $lang->msg_auth_mail_sended = '%s 메일로 인증 정보를 담은 메일이 발송되었습니다. 메일을 확인하세요.';
    $lang->msg_success_authed = '인증이 정상적으로 되어 로그인 처리가 되었습니다. 꼭 인증 메일에 표시된 비밀번호를 이용하여 원하시는 비밀번호로 변경하세요.';

    $lang->msg_no_message = 'No hay mensajes';
    $lang->message_received = 'Usted ha recibido un mensaje';

    $lang->msg_new_member = 'Agregar usuario';
    $lang->msg_update_member = 'Modificar información del usuario';
    $lang->msg_leave_member = 'Dejar de ser usuario';
    $lang->msg_group_is_null = 'No es un grupo registrado';
    $lang->msg_not_delete_default = 'Los artículos predefinidos no pueden ser eliminados';
    $lang->msg_not_exists_member = "Este usuario no existe";
    $lang->msg_cannot_delete_admin = 'ID del Administrador no puede ser eliminado. Remover el ID desde la Administración y trate nuevamente.';
    $lang->msg_exists_user_id = 'Este ID ya existe. Por favor trate con otro ID';
    $lang->msg_exists_email_address = 'Esta dirección del email ya existe. Por favor trate con otra dirección del email.';
    $lang->msg_exists_nick_name = 'Este apodo ya existe. Por favor trate con otro apodo.';
    $lang->msg_signup_disabled = 'Usted no puede ser registrado';
    $lang->msg_already_logged = 'Usted ya ha sido registrado';
    $lang->msg_not_logged = 'Por favor conectese primero';
    $lang->msg_title_is_null = 'Por favor ingresar el título de la nota';
    $lang->msg_content_is_null = 'Por favor ingresar el contenido';
    $lang->msg_allow_message_to_friend = "Falló el envío por permitir sólo mensajes de sus amigos";
    $lang->msg_disallow_message = 'Falló el envío por ser usuario rechazado para recibir mensajes';
    $lang->msg_insert_group_name = 'Por favor ingresar el nombre del grupo';

    $lang->msg_not_uploaded_image_name = 'Imagen del nombre no puede ser registrado';
    $lang->msg_not_uploaded_image_mark = 'Imagen de marca no puede ser resistrado';

    $lang->msg_accept_agreement = 'Usted primero debe aceptar el acuerdo'; 

    $lang->msg_user_denied = 'ID ingresado ha sido prohibido para su uso';
    $lang->msg_user_limited = 'ID ingresado puede ser usado luego de %s';

    $lang->about_user_id = 'ID del usuario debe ser entre 3-20 letras que consiste en alfabetos+número con alfabeto como primera letra.';
    $lang->about_password = 'Contraseña debe ser entre 6-20 letras';
    $lang->about_user_name = 'Nombre debe ser entre 2-20 letras';
    $lang->about_nick_name = 'Apodo debe ser entre 2-20 letras';
    $lang->about_email_address = 'Dirección de email es usado para modificar/encontrar contraseña luego de la certificación de email';
    $lang->about_homepage = 'Ingresar su sitio web, si es que lo tiene';
    $lang->about_blog_url = 'Ingresar su blog, si es que lo tiene';
    $lang->about_birthday = 'Ingresar su fecha de nacimiento';
    $lang->about_allow_mailing = "Si usted no se ha registrado el envío de email, usted no podrá recibir el email del grupo";
    $lang->about_allow_message = 'Usted puede decidir la recepción del mensaje';
    $lang->about_denied = 'Si selecciona esta opción prohibirá el uso del ID';
    $lang->about_is_admin = 'Si selecciona esta opción para obtener la atribución del administrador superior';
    $lang->about_description = "Nota del administrador acerca de los usuarios";
    $lang->about_group = 'Un ID puede pertenecer a muchos grupos';

    $lang->about_column_type = 'Definir el estilo de la forma de registro que desea agregar';
    $lang->about_column_name = 'Ingresar el nombre en español para poder usar en plantilla (nombre como variable)';
    $lang->about_column_title = 'Esto sera mostrado cuando el usuario registra o modifica/visualiza la información del usuario';
    $lang->about_default_value = 'Usted puede predefinir los valores';
    $lang->about_active = 'Si selecciona "Activar" los artículos seran mostrados en el momento de registrar';
    $lang->about_form_description = 'Si Usted ingresa la forma de la descripción, será mostrado en el momento de registrar';
    $lang->about_required = 'Si selecciona esta opción, será artículo esencial para el registro';

    $lang->about_enable_openid = 'Selecciona esta opción si activado OpenID';
    $lang->about_enable_join = 'Debe seleccionar esta opción para permitir el registro de los usuarios';
    $lang->about_limit_day = 'Usted puede limitar la fecha de la certificación luego de registrar';
    $lang->about_limit_date = 'Usuario no podra conectarse hasta la fecha indicada';
    $lang->about_redirect_url = 'Ingresar la URL que va a abrir luego de registrar los usuarios. Cuando esto esta vacío, se habrirá la página anterior a la del registro.';
    $lang->about_agreement = "Acuerdo del registro no será mostrado si esta vacío";

    $lang->about_image_name = "Permitir a los usuarios el uso de imagen del nombre en ves del nombre del usuario";
    $lang->about_image_mark = "Permitir a los usuarios el uso de imagen de marca delante de sus nombres";
    $lang->about_profile_image = '사용자의 프로필 이미지를 사용할 수 있게 합니다';
    $lang->about_accept_agreement = "Yo he leído todo el acuerdo y acepto"; 

    $lang->about_member_default = 'Al registrar se configura como grupo predefinido';

    $lang->about_openid = 'Cuando tu registra como OpenID, la información básica como ID o la dirección del email sera guardado en este sitio, pero la contraseña y la resolución de la certificación se hará en el servicio ofrecido por openID';
    $lang->about_openid_leave = '오픈아이디의 탈퇴는 현 사이트에서의 회원 정보를 삭제하는 것입니다.<br />탈퇴 후 로그인하시면 새로 가입하시는 것으로 되어 작성한 글에 대한 권한을 가질 수 없게 됩니다';

    $lang->about_member = "Esto es un módulo para crear/modificar/eliminar usuarios y manejar grupos o el formato del registro.\n Usted puede manejar usuarios creando nuevos grupos, y obtener información adicional manejando el formato del registro";
    $lang->about_find_member_account = '아이디/ 비밀번호는 가입시 등록한 메일 주소로 알려드립니다<br />가입할때 등록하신 메일 주소를 입력하시고 "아이디/ 비밀번호 찾기" 버튼을 클릭해주세요.<br />';
?>
