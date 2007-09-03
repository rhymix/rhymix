<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  English Language Pack (Only Basic Things)
     **/

    $lang->member = 'Usuario';
    $lang->member_default_info = 'Informacion Basica';
    $lang->member_extend_info = 'Informacion adicional';
    $lang->default_group_1 = "Usuario registrado";
    $lang->default_group_2 = "Usuario regular";
    $lang->admin_group = "Manejo del grupo";
    $lang->remember_user_id = 'Guardar ID';
    $lang->already_logged = "Ya esta conectado";
    $lang->denied_user_id = 'Este ID esta prohibido.';
    $lang->null_user_id = 'IngresarID';
    $lang->null_password = 'Ingresar la contrasena';
    $lang->invalid_authorization = 'No esta certificado';
    $lang->invalid_user_id= "Este ID no existe";
    $lang->invalid_password = 'Contrasena incorrecta';
    $lang->allow_mailing = 'Registro del envio de mail';
    $lang->allow_message = 'Permitir la recepcion del mensaje';
    $lang->allow_message_type = array(
             'Y' => 'Recibir todo',
             'N' => 'Rechazar',
             'F' => 'Solo amigos',
        );
    $lang->denied = 'Prohibido';
    $lang->is_admin = 'Atribucion del administrador superior';
    $lang->group = 'Grupo asignado';
    $lang->group_title = 'Nombre del grupo';
    $lang->group_srl = 'Numero del grupo';
    $lang->signature = 'Firma';
    $lang->image_name = 'Nombre de la imagen';
    $lang->image_name_max_width = 'Ancho Maximo';
    $lang->image_name_max_height = 'Altura Maxima';
    $lang->image_mark = 'Marca de la imagen';
    $lang->image_mark_max_width = 'Ancho Maximo';
    $lang->image_mark_max_height = 'Altura Maxima';
    $lang->enable_openid = 'Activar OpenID';
    $lang->enable_join = 'Permitir el registro del usuario';
    $lang->limit_day = 'Limite de la fecha temporal';
    $lang->limit_date = 'Limite de fecha';
    $lang->redirect_url = 'URL luego del registro';
    $lang->agreement = 'Acuerdo del registro del usuario';
    $lang->accept_agreement = 'Acepto';
    $lang->sender = 'Remitente';
    $lang->receiver = 'Receptor';
    $lang->friend_group = 'Grupo de amigos';
    $lang->default_friend_group = 'Grupo desasignado';
    $lang->member_info = 'Informacion del Usuario';
    $lang->current_password = 'Contrasena actual';
    $lang->openid = 'OpenID';

    $lang->search_target_list = array(
        'user_id' => 'ID',
        'user_name' => 'Nombre',
        'nick_name' => 'Apodo',
        'email_address' => 'Direccion de Email',
        'regdate' => 'Fecha del registro',
        'last_login' => 'Fecha de su ultima coneccion',
    );

    $lang->message_box = array(
        'R' => 'Recibido',
        'S' => 'Enviado',
        'T' => 'Buzon de Email',
    );

    $lang->readed_date = "Fecha Leido"; 

    $lang->cmd_login = 'Conectar';
    $lang->cmd_logout = 'Desconectar';
    $lang->cmd_signup = 'Registrar';
    $lang->cmd_modify_member_info = 'Modificar nombre del usuario';
    $lang->cmd_modify_member_password = 'Canbiar la contrasena';
    $lang->cmd_view_member_info = 'Informacion del usuario';
    $lang->cmd_leave = 'Dejar de ser usuario';

    $lang->cmd_member_list = 'Lista de los Usuarios';
    $lang->cmd_module_config = 'Configuracion predefinidos';
    $lang->cmd_member_group = 'Manejo del grupo';
    $lang->cmd_send_mail = 'Enviar Email';
    $lang->cmd_manage_id = 'Manejo de ID prohibidos';
    $lang->cmd_manage_form = 'Manejo de forma del registo';
    $lang->cmd_view_own_document = 'Ver documentos escritos';
    $lang->cmd_view_scrapped_document = 'Ver Scraps';
    $lang->cmd_send_email = 'Enviar Email';
    $lang->cmd_send_message = 'Enviar Mensaje';
    $lang->cmd_reply_message = 'Responder el mensaje';
    $lang->cmd_view_friend = 'Amigos';
    $lang->cmd_add_friend = 'Registrar como Amigo';
    $lang->cmd_view_message_box = 'Buzon de Notas';
    $lang->cmd_store = "Guardar";
    $lang->cmd_add_friend_group = 'agregar grupo de amigos';
    $lang->cmd_rename_friend_group = 'Cambiar el nombre del grupo de amigos';

    $lang->msg_alreay_scrapped = 'Este documento ya esta scrapeado';

    $lang->msg_cart_is_null = 'Seleciona el objetivo';
    $lang->msg_checked_file_is_deleted = '%d archivos adjuntos son eliminados';

    $lang->msg_no_message = 'No hay notas';
    $lang->message_received = 'Usted ha recibido una nota';

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
    $lang->msg_title_is_null = 'Por favor ingresar el titulo de la nota';
    $lang->msg_content_is_null = 'Por favor ingresar el contenido';
    $lang->msg_allow_message_to_friend = "Fallo el envio por permitir Solo mensajes de sus amigos";
    $lang->msg_disallow_message = 'Fallo el envio por ser usuario rechazado para recibir notas';
    $lang->msg_insert_group_name = 'Por favor ingresar el nombre del grupo';

    $lang->msg_not_uploaded_image_name = 'Nombre de la imagen no puede ser registrado';
    $lang->msg_not_uploaded_image_mark = 'Marca de la imagen no puede ser resistrado';

    $lang->msg_accept_agreement = 'Usted primero debe aceptar el acuerdo'; 

    $lang->msg_user_denied = 'ID ingresado ha sido prohibido para su uso';
    $lang->msg_user_limited = 'ID ingresado puede ser usado luego de %s';

    $lang->about_user_id = 'ID del usuario debe ser entre 3-20 letras que consiste en alfabetos+numero con alfabeto como primera letra.';
    $lang->about_password = 'Contrasena debe ser entre 6-20 letras';
    $lang->about_user_name = 'Nombre debe ser entre 2-20 letras';
    $lang->about_nick_name = 'Apodo debe ser entre 2-20 letras';
    $lang->about_email_address = 'Direccion de email es usado para modificar/encontrar contrasena luego de la certificacion de email';
    $lang->about_homepage = 'Ingresar su sitio web, si es que lo tiene';
    $lang->about_blog_url = 'Ingresar su blog, si es que lo tiene';
    $lang->about_birthday = 'Ingresar su fecha de nacimiento';
    $lang->about_allow_mailing = "Si usted no se ha registrado el envio de email, usted no pudra recibir el email del grupo";
    $lang->about_allow_message = 'Usted puede decidir la reception de nota';
    $lang->about_denied = 'Si selecciona esta opcion el Id sera prohibido su uso';
    $lang->about_is_admin = 'Si selecciona esta opcion para obtener la atribucion del administrador superior';
    $lang->about_description = "Nota del administrador acerca de los usuarios";
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
    $lang->about_limit_day = 'Usted puede limitar la fecha de la certificacion luego de registrar';
    $lang->about_limit_date = 'Usuario no podra conectarse hasta la fecha indicada';
    $lang->about_redirect_url = 'Ingresar la URL que va a abrir luego de registrar los usuarios. Cuando esto esta vacio, se habrira la pagina anterior a la del registro.';
    $lang->about_agreement = "Acuerdo del registro no sera mostrado si esta vacio";

    $lang->about_image_name = "Permitir a los usuarios el uso del nombre de imagen en ves del nombre del usuario";
    $lang->about_image_mark = "Permitir a los usuarios el uso de la marca delante de sus nombres";
    $lang->about_accept_agreement = "Yo he leido el todo el acuerdo y acepto"; 

    $lang->about_member_default = 'Al registrar se configura como grupo predefinido';

    $lang->about_openid = 'Cuando tu registra como OpenID, la informacion basica como ID o la direccion del email sera guardado en este sitio, pero la contrasena y el resolucion de la certificacion se hara en el servicio ofrecido por openID';

    $lang->about_member = "Esto es un modulo para crear/modificar/eliminar usuarios y manejar grupos o el formato del registro.\n Usted puede manejar usuarios creando nuevos grupos, y obtener informacion adicional manejando el formato del registro";
?>
