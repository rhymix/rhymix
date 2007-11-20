<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack for Zeroboard XE
     **/

    $lang->module_list = "Список Модулей";
    $lang->module_index = "Список Модулей";
    $lang->module_category = "Категория Модуля";
    $lang->module_info = "Информация";
    $lang->add_shortcut = "Добавить Ярлыки";
    $lang->module_action = "Действия";
    $lang->module_maker = "Разработчик Модуля";
    $lang->module_history = "История Обновлений";
    $lang->category_title = "Название Категории";
    $lang->header_text = 'Верхний Колонтитул';
    $lang->footer_text = 'Нижний Колонтитул';
    $lang->use_category = 'Включить Категорию';
    $lang->category_title = 'Название Категории';
    $lang->checked_count = 'Число выбранных статей'; // translator's note: возможно "checked" следует перевести как "проверенных"
    $lang->skin_default_info = 'Информация Стандартного Скина';
    $lang->skin_maker = 'Разработчик Скина';
    $lang->skin_maker_homepage = "Домашняя Страница Разработчика";
    $lang->module_copy = "Копировать Модуль";

    $lang->cmd_add_shortcut = "Добавить Ярлык";
    $lang->cmd_install = "Установить";
    $lang->cmd_update = "Обновить";
    $lang->cmd_manage_category = 'Управление Категориями';
    $lang->cmd_manage_grant = 'Управление Правами Доступа';
    $lang->cmd_manage_skin = 'Управление Скинами';
    $lang->cmd_manage_document = 'Управление Статьями';

    $lang->msg_new_module = "Создать новый модуль";
    $lang->msg_update_module = "Изменить модуль";
    $lang->msg_module_name_exists = "Имя уже существует. Пожалуйста, попробуйте другое.";
    $lang->msg_category_is_null = 'Зарегистрированной категории не существует.';
    $lang->msg_grant_is_null = 'Списка для управления правами доступа не существует.';
    $lang->msg_no_checked_document = 'Нет выбранных статей.'; // translator's note: выше...
    $lang->msg_move_failed = 'Невозможно переместить';
    $lang->msg_cannot_delete_for_child = 'Невозможно удалить категорию, имеющую дочерние категории.';

    $lang->about_browser_title = "Это будет показано в заголовке браузера. Также, это будет использоваться в RSS/Трекбеке.";
    $lang->about_mid = "Имя модуля будет использовано как http://address/?mid=Имя_модуля.\n(только латиница, цифры и символ подчеркивания(_) разрешены.)";
    $lang->about_default = "Если выбрано, модуль будет главным на сайте. Для доступа не нужен будет идентификатор модуля.";
    $lang->about_module_category = "Это позволяет Вам управлять посредством категорий модулей.\nURL для менеджера модулей <a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">Manage module > Категория Модуля </a>.";
    $lang->about_description= 'Это описание только для менеджера.';
    $lang->about_default = 'Если выбрано, этот модуль будет показан, когда пользователи входят на сайт без идентификатора модуля (mid=NoValue).';
    $lang->about_header_text = 'Это содержимое будет показано сверху модуля. (HTML разрешен)';
    $lang->about_footer_text = 'Это содержимое будет показано снизу модуля. (HTML разрешен)';
    $lang->about_skin = 'Вы можете выбрать скин модуля.';
    $lang->about_use_category = 'Если выбрано, функция категорий будет включена.';
    $lang->about_list_count = 'Вы можете установить лимит показа статей на страницу. (по умолчанию: 1)';
    $lang->about_page_count = 'Вы можете установить число страниц внизу. (по умолчанию: 10)';
    $lang->about_admin_id = 'Вы можете разрешить менеджеру иметь полные права доступа к этому модулю.\nВы можете ввести несколько ID, используя <br />запятую \n(но менеджер модуля не имеет права доступа к странице администрирования сайта.)';
    $lang->about_grant = 'Если Вы отключите все права доступа для отдельного объекта, не прошедшие процедуру входа на сайт пользователи получат доступ.'; 
    $lang->about_module = "Zeroboard XE состоит из модулей, за исключением базовой библиотеки.\n Управление модулем покажет все установленные модули и поможет управлять ими.\nПосредством функции добавления ярлыка, Вы можете легче управлять часто используемыми модулями.";

	$lang->about_extra_vars_default_value = 'Если нужно несколько значений по умолчанию, разделите их запятыми(,).';
?>
