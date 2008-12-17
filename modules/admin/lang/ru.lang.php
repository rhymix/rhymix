<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack for XE
     **/

    $lang->admin_info = 'Информация администратора';
    $lang->admin_index = 'Индексная страница администратора';

    $lang->module_category_title = array(
        'service' => 'Служебные модули',
        'manager' => 'Управляющие модули',
        'utility' => 'Утилиратные модули',
        'accessory' => 'Дополнительные модули',
        'base' => 'Базовые модули',
    );

    $lang->newest_news = "Последние новости";
    
    $lang->env_setup = "Настройка";

    $lang->env_information = "Информация окружения";
    $lang->current_version = "Текущая версия";
    $lang->current_path = "Текущий путь";
    $lang->released_version = "Последняя версия";
    $lang->about_download_link = "Новая версия XE доступна.\nЧтобы скачать последнюю версию, нажмите ссылку закачки.";
    
    $lang->item_module = "Список модулей";
    $lang->item_addon  = "Список аддонов";
    $lang->item_widget = "Список виджетов";
    $lang->item_layout = "Список лейаутов";

    $lang->module_name = "Имя модуля";
    $lang->addon_name = "Имя аддона";
    $lang->version = "Версия";
    $lang->author = "Разработчик";
    $lang->table_count = "Номер таблицы";
    $lang->installed_path = "Путь установки";

    $lang->cmd_shortcut_management = "Редактировать меню";

    $lang->msg_is_not_administrator = 'Только для администраторов!';
    $lang->msg_manage_module_cannot_delete = 'Ярлыки модулей, аддонов, лейаутов, виджетов не могут быть удалены';
    $lang->msg_default_act_is_null = 'Ярлык не может быть зарегистрирован, поскольку стандартное административное действие не установлено';

    $lang->welcome_to_xe = 'Добро пожаловать на страницу администратора XE';
    $lang->about_admin_page = "Страница администратора все еще в разработке,\nМы добавим важные доработки, принимая много хороших предложений на этапе Closebeta.";
    $lang->about_lang_env = "Чтобы применить выбранный язык для пользователей как страндартный, нажмите кнопку Сохранить [Save] после изменения.";

    $lang->xe_license = 'XE подчиняется Стандартной Общественной Лицензии GPL';
    $lang->about_shortcut = 'Вы можете удалить ярлыки модулей, зарегистрированных в списке часто используемых модулей';

    $lang->yesterday = "Yesterday";
    $lang->today = "Today";

    $lang->cmd_lang_select = "언어선택";
    $lang->about_cmd_lang_select = "선택된 언어들만 서비스 됩니다";
    $lang->about_recompile_cache = "쓸모없어졌거나 잘못된 캐시파일들을 정리할 수 있습니다";
    $lang->use_ssl = "SSL 사용";
    $lang->ssl_options = array(
        'none' => "사용안함",
        'optional' => "선택적으로",
        'always' => "항상사용"
    );
    $lang->about_use_ssl = "선택적으로에서는 회원가입/정보수정등의 지정된 action에서 SSL을 사용하고 항상 사용은 모든 서비스가 SSL을 이용하게 됩니다.";
    $lang->server_ports = "서버포트지정";
    $lang->about_server_ports = "HTTP는 80, HTTPS는 443이외의 다른 포트를 사용하는 경우에 포트를 지정해주어야합니다.";
?>
