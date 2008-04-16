<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack for Zeroboard XE
     **/

    $lang->member = 'Пользователь';
    $lang->member_default_info = 'Базовая информация';
    $lang->member_extend_info = 'Дополнительная информация';
    $lang->default_group_1 = "Общая группа";
    $lang->default_group_2 = "Особая группа";
    $lang->admin_group = "Администативная группа";
    $lang->keep_signed = '로그인 유지';
    $lang->remember_user_id = 'Сохранить ID';
    $lang->already_logged = "Вы уже вошли";
    $lang->denied_user_id = 'Извините. Этот ID запрещен.';
    $lang->null_user_id = 'Пожалуйста, введите ID пользователя';
    $lang->null_password = 'Пожалуйста, введите пароль';
    $lang->invalid_authorization = 'Не авторизировано';
    $lang->invalid_user_id= "Указанный ID не существует";
    $lang->invalid_password = 'Неверный пароль';
    $lang->allow_mailing = 'Присоединиться к списку рассылки';
    $lang->allow_message = 'Разрешить прием сообщений';
    $lang->allow_message_type = array(
             'Y' => 'Принимать все',
             'N' => 'Отклонять все',
             'F' => 'Только друзья',
        );
    $lang->denied = 'Отказано';
    $lang->is_admin = 'Суперадминистративные права';
    $lang->group = 'Присвоенная группа';
    $lang->group_title = 'Имя группы';
    $lang->group_srl = 'Номер группы';
    $lang->signature = 'Подпись';
    $lang->profile_image = 'Изображение профиля';
    $lang->profile_image_max_width = 'Макс. ширина';
    $lang->profile_image_max_height = 'Макс. высота';
    $lang->image_name = 'Имя изображения';
    $lang->image_name_max_width = 'Макс. ширина';
    $lang->image_name_max_height = 'Макс. высота';
    $lang->image_mark = 'Изображение-марка';
    $lang->image_mark_max_width = 'Макс. ширина';
    $lang->image_mark_max_height = 'Макс. высота';
    $lang->enable_openid = 'Включить открытый ID (OpenID)';
    $lang->enable_join = 'Позволить пользователям регистрироваться';
    $lang->enable_ssl = 'SSL 기능 사용';
    $lang->security_sign_in = 'Sign in using enhanced security';
    $lang->limit_day = 'Временный лимит даты';
    $lang->limit_date = 'Дата лимита';
    $lang->after_login_url = '로그인 후 이동할 URL';
    $lang->after_logout_url = '로그아웃 후 이동할 URL';
    $lang->redirect_url = 'URL после регистрации';
    $lang->agreement = 'Пользовательское соглашение о регистрации';
    $lang->accept_agreement = 'Согласен';
    $lang->sender = 'Отправитель';
    $lang->receiver = 'Получатель';
    $lang->friend_group = 'Группа друзей';
    $lang->default_friend_group = 'Неприсвоенная группа';
    $lang->member_info = 'Пользовательская информация';
    $lang->current_password = 'Текущий пароль';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = "Имя веб-мастера";
    $lang->webmaster_email = "Email веб-мастера";

    $lang->about_keep_signed = '브라우저를 닫더라도 로그인이 계속 유지될 수 있습니다.\n\n로그인 유지 기능을 사용할 경우 다음 접속부터는 로그인을 하실 필요가 없습니다.\n\n단, 게임방, 학교 등 공공장소에서 이용시 개인정보가 유출될 수 있으니 꼭 로그아웃을 해주세요';    $lang->about_webmaster_name = "Пожалуйста, введите имя веб-мастера, которое будет использоваться для аутентификационных писем или другого адиминистрирования сайта. (по умолчанию : webmaster)";
    $lang->about_webmaster_email = "Пожалуйста, введите email адрес веб-мастера.";

    $lang->search_target_list = array(
        'user_id' => 'ID',
        'user_name' => 'Имя',
        'nick_name' => 'Ник',
        'email_address' => 'Email',
        'regdate' => 'Дата регистрации',
        'last_login' => 'Дата последнего входа',
        'extra_vars' => 'Экстра перем.',
    );

    $lang->message_box = array(
        'R' => 'Принятые',
        'S' => 'Отправленные',
        'T' => 'Почтовый ящик',
    );

    $lang->readed_date = "Дата прочтения"; 

    $lang->cmd_login = 'Войти';
    $lang->cmd_logout = 'Выйти';
    $lang->cmd_signup = 'Регистрация';
    $lang->cmd_modify_member_info = 'Изменить информацию пользователя';
    $lang->cmd_modify_member_password = 'Изменить пароль';
    $lang->cmd_view_member_info = 'Информация пользователя';
    $lang->cmd_leave = 'Покинуть';
    $lang->cmd_find_member_account = 'Найти информацию аккаунта';

    $lang->cmd_member_list = 'Список пользователей';
    $lang->cmd_module_config = 'Стандартные настройки';
    $lang->cmd_member_group = 'Увтавление группами';
    $lang->cmd_send_mail = 'Отправить почту';
    $lang->cmd_manage_id = 'Управление запрещенными ID';
    $lang->cmd_manage_form = 'Управление формой регистрации';
    $lang->cmd_view_own_document = 'Просмотреть написанные статьи';
    $lang->cmd_view_scrapped_document = 'Черновики';
    $lang->cmd_view_saved_document = 'Просмотреть сохраненные статьи';
    $lang->cmd_send_email = 'Отправить почту';
    $lang->cmd_send_message = 'Отправить сообщение';
    $lang->cmd_reply_message = 'Ответить';
    $lang->cmd_view_friend = 'Дзузья';
    $lang->cmd_add_friend = 'Сделать другом';
    $lang->cmd_view_message_box = 'Ящик сообщений';
    $lang->cmd_store = "Сохранить";
    $lang->cmd_add_friend_group = 'Добавить группу друзей';
    $lang->cmd_rename_friend_group = 'Изменить имя группы друзей';

    $lang->msg_email_not_exists = "Email адрес не существует";

    $lang->msg_alreay_scrapped = 'Эта статья уже в черновиках';

    $lang->msg_cart_is_null = 'Пожалуйста, выберите назначение';
    $lang->msg_checked_file_is_deleted = '%d вложенных файлов удалено';

    $lang->msg_find_account_title = 'Информация аккаунта';
    $lang->msg_find_account_info = 'Это требуемая информация аккаунта.';
    $lang->msg_find_account_comment = 'Пароль будет изменен на указанный выше после нажатия по ссылке ниже.<br />Пожалуйста, изменить пароль после входа.';
    $lang->msg_auth_mail_sended = 'Аутентификационное почтовое сообщение было отправлено для %s. Пожалуйста, проверьте Вашу почту.';
	$lang->msg_invalid_auth_key = 'Неверный запрос на аутентификацию.<br />Пожалуйста, попытайтеть найти информацию аккаунта или свяжитесь с администратором.';
    $lang->msg_success_authed = 'Ваш аккаунт был успешно аутентифицирован. Вход произведен. Пожалуйста, измените пароль на Ваш собственный.';

    $lang->msg_no_message = 'Нет сообщений';
    $lang->message_received = 'Новое сообщение';

    $lang->msg_new_member = 'Добавить пользователя';
    $lang->msg_update_member = 'Изменить информацию пользователя';
    $lang->msg_leave_member = 'Покинуть';
    $lang->msg_group_is_null = 'Нет зарегистрированной группы';
    $lang->msg_not_delete_default = 'Стандартные объекты не могут быть удалены';
    $lang->msg_not_exists_member = "Пользователь не существует";
    $lang->msg_cannot_delete_admin = 'Административный ID не может быть удален. Пожалуйста, удалить ID  из администрации и попробуйте снова.';
    $lang->msg_exists_user_id = 'Этот ID уже существует. Пожалуйста, попробуйте другой.';
    $lang->msg_exists_email_address = 'Этот email адрес уже существует. Пожалуйста, попробуйте другой.';
    $lang->msg_exists_nick_name = 'Этот ник уже существует. Пожалуйста, попробуйте другой.';
    $lang->msg_signup_disabled = 'Вы не можете зарегистрироваться';
    $lang->msg_already_logged = 'Вы уже зарегистрированы';
    $lang->msg_not_logged = 'Пожалуйста, сначала войдите';
    $lang->msg_title_is_null = 'Пожалуйста, введите тему сообщения';
    $lang->msg_content_is_null = 'Пожалуйста, введите содержание';
    $lang->msg_allow_message_to_friend = "Отправка провалена, поскольку получатель принимает сообщения только от друзей";
    $lang->msg_disallow_message = 'Отправка провалена, поскольку получатель отклоняет прием сообщений';
    $lang->msg_insert_group_name = 'Пожалуйста, введите имя группы';
    $lang->msg_check_group = 'Введите имя группы';

    $lang->msg_not_uploaded_image_name = 'Имя изображения не может быть зарегистрировано';
    $lang->msg_not_uploaded_image_mark = 'Марка не может быть зарегистрирована';

    $lang->msg_accept_agreement = 'Вы должны принять Соглашение'; 

    $lang->msg_user_denied = 'Введенный ID сейчас запрещен';
    $lang->msg_user_limited = 'Введенный ID может использоваться после %s';

    $lang->about_user_id = 'Юзер ID должен быть 3~20 знаков и содержать алфавит или цифры, начинаясь с алфафитного знака.';
    $lang->about_password = 'Пароль должен быть 6~20 знаков';
    $lang->about_user_name = 'Имя должно быть 2~20 знаков';
    $lang->about_nick_name = 'Ник должен быть 2~20 знаков';
    $lang->about_email_address = 'Email адрес используется для изменения/получения пароля после его сертификации.';
    $lang->about_homepage = 'Пожалуйста, введите, если у Вас есть веб-сайт';
    $lang->about_blog_url = 'Пожалуйста, введите, если у Вас есть блог';
    $lang->about_birthday = 'Пожалуйста, введите Вашу дату рождения';
    $lang->about_allow_mailing = "Если Вы не присоединитесь к списку рассылки, Вы не сможете получать почтовые сообщения, направленные Вашей группе";
    $lang->about_allow_message = 'Вы можете определить политику принятия сообщений';
    $lang->about_denied = 'Запретить ID';
    $lang->about_is_admin = 'Наделить Суперадминистративными правами';
    $lang->about_description = "Заметки администратора о пользователях";
    $lang->about_group = 'ID может принадлежать нескольким группам';

    $lang->about_column_type = 'Пожалуйста, установите формат дополнительной формы регистрации';
    $lang->about_column_name = 'Пожалуйста, введите английское название, которое будет использоваться в шаблоне как переменная';
    $lang->about_column_title = 'Это будет отображено, когда пользователь регистрируется или изменяет/просматривает информацию пользователя';
    $lang->about_default_value = 'Вы можете установить значения по умолчанию';
    $lang->about_active = 'Вам следует выбрать активные объекты для отображения на форме регистрации';
    $lang->about_form_description = 'Если Вы введете описание, оно будет отображено на форме регистрации';
    $lang->about_required = 'Сделать элемент обязательным на форме регистрации';

    $lang->about_enable_openid = 'Позволить пользователям регистрироваться как OpenID';
    $lang->about_enable_join = 'Позволить пользователям регистрироваться';
    $lang->about_enable_ssl = '서버에서 SSL지원이 될 경우 회원가입/정보수정/로그인등의 개인정보가 서버로 보내질때 SSL(https)를 이용하도록 할 수 있습니다';
    $lang->about_limit_day = 'Вы можете ограничить дату сертификации после регистрации';
    $lang->about_limit_date = 'Пользователь не может войти до указанной даты';
    $lang->about_after_login_url = '로그인 후 이동할 URL을 정하실 수 있습니다. 비어 있으면 해당 페이지가 유지됩니다.';
    $lang->about_after_logout_url = '로그아웃 후 이동할 URL을 정하실 수 있습니다. 비어 있으면 해당 페이지가 유지됩니다.';
    $lang->about_redirect_url = 'Пожалуйста, введите URL, куда пользователи попадут после регистрации. Когда поле пустое, будет установлена страница предшествуящая странице регистрации.';
    $lang->about_agreement = "Регистрационное соглашение будет показано, если оно содержит текст";

    $lang->about_image_name = "Позволить пользователям использовать изображение вместо текста";
    $lang->about_image_mark = "Позволить пользователям использовать марку перед их именами";
    $lang->about_profile_image = 'Позволить пользователям использовать изображения профиля';
    $lang->about_accept_agreement = "Я прочитал соглашение полностью и согласен"; 

    $lang->about_member_default = 'Это будет установлено как стандартная группа при регистрации';

    $lang->about_openid = 'Когда Вы регистрируетесь как OpenID, базовая информация такая как ID или email адрес будет сохранена на сайте, но пароль и менеджмент сертификации будет произведен над текущим OpenID';
    $lang->about_openid_leave = 'Покидание пользователей с OpenID означает удаление Вашей информации пользователя на сайте.<br />Если Вы войдете после покидания, это будет разпознано как новый пользователь, поэтому доступ к написанным Вами прежде статьям будет закрыт.';

    $lang->about_member = "Этот модуль служит для создания/изменения/удаления пользователей, управления их группами и формой регистрации.\nВы можете управлять пользователями посредством создания новых групп, и получить дополнительную информацию, управляя формой регистрации";
    $lang->about_find_member_account = 'Ваша информация аккаунта будет направлена на зарегистрированный email.<br />Пожалуйста, введите email адрес, который Вы ввели при регистрации и нажмите кнопку "Найти Информацию Аккаунта".<br />';
?>
