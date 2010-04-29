<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack
     **/

    $lang->member = 'Пользователь';
    $lang->member_default_info = 'Основная информация';
    $lang->member_extend_info = 'Дополнительная информация';
    $lang->default_group_1 = "Новичок";
    $lang->default_group_2 = "Постоянный участник";
    $lang->admin_group = "Администратор";
    $lang->keep_signed = 'Сохранить логин';
    $lang->remember_user_id = 'Сохранить ID';
    $lang->already_logged = "Вы уже вошли";
    $lang->denied_user_id = 'Извините. Этот ID запрещен';
    $lang->null_user_id = 'Пожалуйста, введите ID';
    $lang->null_password = 'Пожалуйста, введите пароль';
    $lang->invalid_authorization = 'Вы не авторизированы';
    $lang->invalid_user_id= "Указанный ID не существует";
    $lang->invalid_password = 'Неверный пароль';
    $lang->allow_mailing = 'Получать рассылки';
    $lang->denied = 'Отменить пользование';
    $lang->is_admin = 'Суперадминистративные права';
    $lang->group = 'Группа';
    $lang->group_title = 'Имя группы';
    $lang->group_srl = 'Номер группы';
    $lang->signature = 'Подпись';
    $lang->profile_image = 'Фотография участника';
    $lang->profile_image_max_width = 'Макс. ширина';
    $lang->profile_image_max_height = 'Макс. высота';
    $lang->image_name = 'Имя изображения';
    $lang->image_name_max_width = 'Макс. ширина';
    $lang->image_name_max_height = 'Макс. высота';
    $lang->image_mark = 'Image Mark';
    $lang->image_mark_max_width = 'Макс. ширина';
    $lang->image_mark_max_height = 'Макс. высота';
    $lang->group_image_mark = 'Group Image Mark';
    $lang->group_image_mark_max_width = 'Макс. ширина';
    $lang->group_image_mark_max_height = 'Макс. высота';
    $lang->group_image_mark_order = 'Group Image Mark Order';
	$lang->signature_max_height = 'Max Signature Height';
    $lang->enable_openid = 'Включить открытый ID (OpenID)';
    $lang->enable_join = 'Разрешение на регистрацию';
    $lang->enable_confirm = 'Активация по email';
    $lang->enable_ssl = 'использоватьSSL';
    $lang->security_sign_in = 'Sign in using enhanced security';
    $lang->limit_day = 'Дата временного лимит';
    $lang->limit_date = 'Дата лимита';
    $lang->after_login_url = 'URL после логин';
    $lang->after_logout_url = 'URL после логаут';
    $lang->redirect_url = 'URL после регистрации';
    $lang->agreement = 'Пользовательское соглашение о регистрации';
    $lang->accept_agreement = 'Согласен';
    $lang->member_info = 'Пользовательская информация';
    $lang->current_password = 'Текущий пароль';
    $lang->openid = 'OpenID';
    $lang->allow_message = 'Сообщения разрешены';
    $lang->allow_message_type = array(
            'Y' => 'Разрешено всем',
            'F' => 'Разрешено только зарегистрированным друзьям',
            'N' => 'Запрещено всем',
    );
    $lang->about_allow_message = '쪽지 허용 방법 및 대상을 지정할 수 있습니다';
    $lang->logged_users = 'Logged Users';

    $lang->webmaster_name = "Имя веб-мастера";
    $lang->webmaster_email = "Email веб-мастера";

    $lang->about_keep_signed = 'Вы будете в состоянии логин, даже при закрытии окна браузера.\n\nЕсли вы пользуетесь общественным компьютером, сделайте выход в целях сохранения персональных данных';    
    $lang->about_keep_warning = 'Вы будете в состоянии логин, даже при закрытии окна браузера.Если вы пользуетесь общественным компьютером, сделайте выход в целях сохранения персональных данных';   
    $lang->about_webmaster_name = "Пожалуйста, введите имя вебмастера, которое будет использоваться для авторизационных писем или другого адиминистрирования сайта. (по умолчанию : webmaster)";
	$lang->about_webmaster_email = "Пожалуйста, введите email адрес вебмастера.";

    $lang->search_target_list = array(
        'user_id' => 'ID',
        'user_name' => 'Имя',
        'nick_name' => 'Ник',
        'email_address' => 'Email',
        'regdate' => 'Дата регистрации',
        'regdate_more' => 'Дата регистрации(more)',
        'regdate_less' => 'Дата регистрации(less)',
        'last_login' => 'Дата последнего входа',
        'last_login_more' => 'Last Sign in Date (more)',
        'last_login_less' => 'Last Sign in Date (less)',
        'extra_vars' => 'Экстра перем.',
    );

    $lang->cmd_login = 'Вход на сайт';
    $lang->cmd_logout = 'Выход';
    $lang->cmd_signup = 'Регистрация';
    $lang->cmd_site_signup = 'Регистрация';
    $lang->cmd_modify_member_info = 'Изменить информацию пользователя';
    $lang->cmd_modify_member_password = 'Изменить пароль';
    $lang->cmd_view_member_info = 'Личная информация';
    $lang->cmd_leave = 'Удалить аккаунт';
    $lang->cmd_find_member_account = 'Найти ID/пароль';
	$lang->cmd_resend_auth_mail = 'Послать email для авторизации';

    $lang->cmd_member_list = 'Список пользователей';
    $lang->cmd_module_config = 'Стандартные настройки';
    $lang->cmd_member_group = 'Управление группами';
    $lang->cmd_send_mail = 'Отправить письмо';
    $lang->cmd_manage_id = 'Управление запрещенными ID';
    $lang->cmd_manage_form = 'Управление формой регистрации';
    $lang->cmd_view_own_document = 'Просмотреть написанные записи';
    $lang->cmd_manage_member_info = 'Изменить информацию пользователя';
    $lang->cmd_trace_document = 'Trace Written Articles';
    $lang->cmd_trace_comment = 'Trace Written Comments';
    $lang->cmd_view_scrapped_document = 'Просмотреть Scraps';
    $lang->cmd_view_saved_document = 'Просмотреть сохраненные записи';
    $lang->cmd_send_email = 'Отправить письмо';

    $lang->msg_email_not_exists = "Email адрес не существует";

    $lang->msg_alreay_scrapped = 'Эта статья уже в Scraps';

    $lang->msg_cart_is_null = 'Пожалуйста, выберите назначение';
    $lang->msg_checked_file_is_deleted = '%d вложенные файлы удалены';

    $lang->msg_find_account_title = 'Информация аккаунта';
    $lang->msg_find_account_info = 'Запрашиваемая информация аккаунта.';
    $lang->msg_find_account_comment = 'Кликните на ссылку ниже и пароль будет изменен на указанный вами.<br />Пожалуйста, измените пароль после входа на сайт';
    $lang->msg_confirm_account_title = 'Письмо, подтверждающее регистрацию';
    $lang->msg_confirm_account_info = 'Информация аккаунта при регистрации';
    $lang->msg_confirm_account_comment = 'Подтвердите авторизацию, кликнув на ссылку ниже';
    $lang->msg_auth_mail_sent = 'Письмо с регистрационной информацией отправлено для %s. Пожалуйста, проверьте Вашу почту';
    $lang->msg_confirm_mail_sent = 'Письмо для авторизации отправлено для %s. Пожалуйста, проверьте Вашу почту';
	$lang->msg_invalid_auth_key = 'Неверный запрос на авторизацию.<br />Пожалуйста, попытайтеть найти информацию аккаунта или свяжитесь с администратором.';
    $lang->msg_success_authed = 'Ваш аккаунт был успешно авторизован.\nВход произведен. Пожалуйста, измените пароль на Ваш собственный.';
    $lang->msg_success_confirmed = 'Авторизация Вашего аккаунта прошла успешно';

    $lang->msg_new_member = 'Добавить пользователя';
    $lang->msg_update_member = 'Изменить информацию пользователя';
    $lang->msg_leave_member = 'Удалить аккаунт';
    $lang->msg_group_is_null = 'Зарегистрированной группы нет';
    $lang->msg_not_delete_default = 'Стандартные объекты не могут быть удалены';
    $lang->msg_not_exists_member = "Пользователь не существует";
    $lang->msg_cannot_delete_admin = 'Административный ID не может быть удален. Пожалуйста, удалить ID  из администрации и попробуйте снова.';
    $lang->msg_exists_user_id = 'Этот ID уже существует. Пожалуйста, попробуйте другой.';
    $lang->msg_exists_email_address = 'Этот email адрес уже зарегистрирован. Пожалуйста, попробуйте другой.';
    $lang->msg_exists_nick_name = 'Этот ник уже существует. Пожалуйста, попробуйте другой.';
    $lang->msg_signup_disabled = 'Вы не можете зарегистрироваться';
    $lang->msg_already_logged = 'Вы уже зарегистрированы';
    $lang->msg_not_logged = 'Пожалуйста, сначала сделайте вход на сайт';
    $lang->msg_insert_group_name = 'Пожалуйста, введите имя группы';
    $lang->msg_check_group = 'Выберите группу';

    $lang->msg_not_uploaded_image_name = 'Невозможно зарегистрировать фотографию профиля';
    $lang->msg_not_uploaded_image_name = 'Невозможно зарегистрировать имя фотографии';
    $lang->msg_not_uploaded_image_mark = 'Невозможно зарегистрировать марку фотографии';
    $lang->msg_not_uploaded_group_image_mark = 'Невозможно зарегистрировать марку изображения группы';
	
	$lang->msg_accept_agreement = 'Вы должны принять Соглашение';

    $lang->msg_user_denied = 'Введенный ID запрещен';
    $lang->msg_user_not_confirmed = 'Вы еще не прошли авторизацию, проверьте свою почту';
    $lang->msg_user_limited = 'Введенный ID возможно использовать после %s';

    $lang->about_user_id = 'Юзер ID должен состоять из 3~20 знаков и содержать алфавит или цифры, начинаясь с алфавитного знака.';
    $lang->about_password = 'Пароль должен состоять из 6~20 знаков';
    $lang->about_user_name = 'Имя должно состоять из 2~20 знаков';
    $lang->about_nick_name = 'Ник должен состоять из  2~20 знаков';
    $lang->about_email_address = 'Email адрес используется для изменения/получения пароля после его авторизации.';
    $lang->about_homepage = 'Пожалуйста, введите, если у Вас есть вебсайт';
    $lang->about_blog_url = 'Пожалуйста, введите, если у Вас есть блог';
    $lang->about_birthday = 'Пожалуйста, введите Вашу дату рождения';
    $lang->about_allow_mailing = "Если Вы не присоединитесь к списку рассылки, Вы не сможете получать почтовые сообщения, направленные Вашей группе";
    $lang->about_denied = 'Запретить ID';
    $lang->about_is_admin = 'Наделить Суперадминистративными правами';
    $lang->about_member_description = "Заметки администратора о пользователях";
    $lang->about_group = 'ID может принадлежать нескольким группам';

    $lang->about_column_type = 'Пожалуйста, установите формат дополнительной формы регистрации';
    $lang->about_column_name = 'Пожалуйста, введите английское название, которое будет использоваться в шаблоне как переменная';
    $lang->about_column_title = 'Это будет отображено, когда пользователь регистрируется или изменяет/просматривает информацию пользователя';
    $lang->about_default_value = 'Вы можете установить значения по умолчанию';
    $lang->about_active = 'Вам следует выбрать активные объекты для отображения в форме регистрации';
    $lang->about_form_description = 'Если Вы введете описание, оно будет отображено в форме регистрации';
    $lang->about_required = 'Сделать элемент обязательным в форме регистрации';

    $lang->about_enable_openid = 'Позволить пользователям регистрироваться как OpenID';
    $lang->about_enable_join = 'Позволить пользователям регистрироваться';
    $lang->about_enable_confirm = 'Please check if you want new members to activate their accounts via their emails.';
    $lang->about_enable_ssl = 'Personal information from Sign up/Modify Member Info/Sign in can be sent as SSL(https) mode if server provides SSL service';
    $lang->about_limit_day = 'Вы можете ограничить дату авторизации после регистрации';
    $lang->about_limit_date = 'Пользователь не может войти до указанной даты';
    $lang->about_after_login_url = 'Возможен переход на указанный URL после логин. Если не укажете, то данная страница отобразиться.';
    $lang->about_after_logout_url = 'Возможен переход на указанный URL после выхода. Если не укажете, то данная страница отобразиться.';
    $lang->about_redirect_url = 'Пожалуйста, введите URL, куда пользователи попадут после регистрации. Когда поле пустое, будет установлена страница предшествующая странице регистрации.';
    $lang->about_agreement = 'Регистрационное соглашение будет показано, если оно содержит текст';

    $lang->about_image_name = 'Возможно использование изображение вместо текста';
    $lang->about_image_mark = 'Возможно использование марки перед именем';
    $lang->about_group_image_mark = 'Возможно использование групповой марки перед именем';
    $lang->about_profile_image = 'Позволить пользователям использовать изображения профиля';
    $lang->about_signature_max_height = 'Возможно установить лимит подписи. (0 Если оставите пустым, то лимита не будет.)';
	$lang->about_accept_agreement = 'Я прочитал соглашение полностью и согласен';

    $lang->about_member_default = 'При регистрации устанавливается стандартная группа';

    $lang->about_openid = 'При регистраци как OpenID, основнаая информация (ID или email адрес) будет сохранена на сайте, но операции с паролем и авторизацией будут произведены над текущим OpenID в представленном сервисе';
    $lang->about_openid_leave = 'Удаление аккаунта с OpenID производит удаление Вашей информации пользователя на сайте.<br />Если Вы войдете после удаления аккаунта, Вы будете разпознаны как новый пользователь и доступ к написанным Вами ранее записям будет закрыт.';
    $lang->about_find_member_account = 'ID и пароль при регистрации будут посланы Вам по имейл адресу.<br /> Введите имейл адрес, указанный при регистрации и кликните на кнопку "Поиск ID/пароль".<br />';

    $lang->about_member = "Этот модуль служит для создания/изменения/удаления пользователей, управления их группами и формой регистрации.\nВы можете управлять пользователями посредством создания новых групп, и получить дополнительную информацию, управляя формой регистрации";
    $lang->about_ssl_port = 'Please input if you are using different SSL port with default one';
    $lang->add_openid = 'Добавить OpenID';

	$lang->about_resend_auth_mail = 'Вы можете получить снова письмо для авторизации, если еще не получили.';
    $lang->no_article = 'Записей нет';
?>
