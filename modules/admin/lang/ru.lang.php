<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack for XE
     **/

    $lang->admin_info = 'Информация администратора';
    $lang->admin_index = 'Индексная страница администратора';
    $lang->control_panel = 'Контрольная панель';
    $lang->start_module = 'Стартовый модуль';
    $lang->about_start_module = 'Вы можете указать модуль запуска по умолчанию.';

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

    $lang->newest_news = 'Последние новости';
    
    $lang->env_setup = 'Настройка';
    $lang->default_url = 'Основной URL';
    $lang->about_default_url = 'If you use a virtual site feature (e.g., cafeXE), input default URL (parent-sites address), then SSO would be enabled, thus connection to documents/modules works properly';

	$lang->env_information = 'Информация окружения';
    $lang->current_version = 'Текущая версия';
    $lang->current_path = 'Текущий путь';
    $lang->released_version = 'Последняя версия';
    $lang->about_download_link = 'Новая версия XE доступна.\nЧтобы скачать последнюю версию, нажмите ссылку закачки';
    
    $lang->item_module = 'Список модулей';
    $lang->item_addon  = 'Список аддонов';
    $lang->item_widget = 'Список виджетов';
    $lang->item_layout = 'Список лейаутов';

    $lang->module_name = 'Имя модуля';
    $lang->addon_name = 'Имя аддона';
    $lang->version = 'Версия';
    $lang->author = 'Разработчик';
    $lang->table_count = 'Номер таблицы';
    $lang->installed_path = 'Путь установки';

    $lang->cmd_shortcut_management = 'Редактировать меню';

    $lang->msg_is_not_administrator = 'Только для администраторов!';
    $lang->msg_manage_module_cannot_delete = 'Ярлыки модулей, аддонов, лейаутов, виджетов не могут быть удалены';
    $lang->msg_default_act_is_null = 'Ярлык не может быть зарегистрирован, поскольку стандартное административное действие не установлено';

    $lang->welcome_to_xe = 'Добро пожаловать на страницу администратора XE';
    $lang->about_lang_env = 'Чтобы применить выбранный язык для пользователей как страндартный, нажмите кнопку Сохранить [Save] после изменения';

    $lang->xe_license = 'XE подчиняется Стандартной Общественной Лицензии GPL';
    $lang->about_shortcut = 'Вы можете удалить ярлыки модулей, зарегистрированных в списке часто используемых модулей';

    $lang->yesterday = 'Вчера';
    $lang->today = 'Сегодня';

    $lang->cmd_lang_select = 'Выбор языка';
    $lang->about_cmd_lang_select = 'Возможно использование только выбранных языков';
    $lang->about_recompile_cache = 'You can delete useless or invalid cache files';
    $lang->use_ssl = 'Использовать SSL';
    $lang->ssl_options = array(
        'none' => 'Никогда',
        'optional' => 'На выбор',
        'always' => 'Всегда'
    );
    $lang->about_use_ssl = 'In case of "Optional", SSL will be used for actions such as signing up / changing information. And for "Always", your site will be served only via https';
    $lang->server_ports = 'Server Port';
    $lang->about_server_ports = 'If your web server does not use 80 for HTTP or 443 for HTTPS port, you should specify server ports';
    $lang->use_db_session = 'Use Session DB';
    $lang->about_db_session = 'It will use php session with DB when authenticating.<br/>Websites with infrequent usage of web server may expect faster response when this function is disabled.<br/>However session DB will make it unable to get current users, so you cannot use related functions';
    $lang->sftp = 'Use SFTP';
    $lang->ftp_get_list = 'Get List';
    $lang->ftp_remove_info = 'Remove FTP Info';
	$lang->msg_ftp_invalid_path = 'Failed to read the specified FTP Path.';
	$lang->msg_self_restart_cache_engine = 'Please restart Memcached or cache daemon.';
	$lang->mobile_view = 'Use Mobile View';
	$lang->about_mobile_view = 'If accessing with a smartphone, display content with mobile layout.';
    $lang->autoinstall = 'EasyInstall';
?>
