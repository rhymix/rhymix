<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack for Zeroboard XE
     **/

    $lang->module_list = "Список модулей";
    $lang->module_index = "Список модулей";
    $lang->module_category = "Категория модуля";
    $lang->module_info = "Информация";
    $lang->add_shortcut = "Добавить ярлыки";
    $lang->module_action = "Действия";
    $lang->module_maker = "Разработчик модуля";
    $lang->module_history = "История обновлений";
    $lang->category_title = "Название категории";
    $lang->header_text = 'Верхний колонтитул';
    $lang->footer_text = 'Нижний колонтитул';
    $lang->use_category = 'Включить категорию';
    $lang->category_title = 'Название категории';
    $lang->checked_count = 'Число выбранных статей'; // translator's note: возможно "checked" следует перевести как "проверенных"
    $lang->skin_default_info = 'Информация стандартного скина';
    $lang->skin_maker = 'Разработчик скина';
    $lang->skin_maker_homepage = "Домашняя страница разработчика";
    $lang->module_copy = "Копировать модуль";

    $lang->header_script = "헤더 스크립트";
    $lang->about_header_script = "html의 &lt;header&gt;와 &lt;/header&gt; 사이에 들어가는 코드를 직접 입력할 수 있습니다.<br />&lt;script, &lt;style 또는 &lt;meta 태그등을 이용하실 수 있습니다";

    $lang->cmd_add_shortcut = "Добавить ярлык";
    $lang->cmd_install = "Установить";
    $lang->cmd_update = "Обновить";
    $lang->cmd_manage_category = 'Управление категориями';
    $lang->cmd_manage_grant = 'Управление правами доступа';
    $lang->cmd_manage_skin = 'Управление скинами';
    $lang->cmd_manage_document = 'Управление статьями';

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
    $lang->about_list_count = 'Вы можете установить лимит показа статей на страницу. (по умолчанию: 20)';
    $lang->about_search_list_count = '검색 또는 카테고리 선택등을 할 경우 표시될 글의 수를 지정하실 수 있습니다. 기본(20개)';
    $lang->about_page_count = 'Вы можете установить число страниц внизу. (по умолчанию: 10)';
    $lang->about_admin_id = 'Вы можете разрешить менеджеру иметь полные права доступа к этому модулю.\nВы можете ввести несколько ID, используя <br />запятую \n(но менеджер модуля не имеет права доступа к странице администрирования сайта.)';
    $lang->about_grant = 'Если Вы отключите все права доступа для отдельного объекта, не прошедшие процедуру входа на сайт пользователи получат доступ.'; 
    $lang->about_module = "Zeroboard XE состоит из модулей, за исключением базовой библиотеки.\n Управление модулем покажет все установленные модули и поможет управлять ими.\nПосредством функции добавления ярлыка, Вы можете легче управлять часто используемыми модулями.";

	$lang->about_extra_vars_default_value = 'Если нужно несколько значений по умолчанию, разделите их запятыми(,).';
?>
