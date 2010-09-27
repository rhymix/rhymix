<?php
  /**
     * @file   ru.lang.php
     * @author NHN (developers@xpressengine.com) | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack
     **/

    $lang->document_list = 'Список документов';
    $lang->thumbnail_type = 'Тип миниатюры';
    $lang->thumbnail_crop = 'Обрезать';
    $lang->thumbnail_ratio = 'Соотношение';
    $lang->cmd_delete_all_thumbnail = 'Удалить все миниарюры';
    $lang->move_target_module = "Переместить в";
    $lang->title_bold = 'Жирное название';
    $lang->title_color = 'Цвет названия';
    $lang->new_document_count = 'Новые документы';

    $lang->parent_category_title = 'Название верхней категории';
    $lang->category_title = 'Категория';
    $lang->category_color = 'Цвет шрифта категории';
    $lang->expand = 'Развернуть';
    $lang->category_group_srls = 'Доступные группы';
    
    $lang->about_category_title = 'Добавьте дочернюю категорию';
	$lang->cmd_enable_move_category = 'Изменить местоположение категории(после выделения перетащите верхнее меню)';

    $lang->about_category_title = 'Введите название категории';
    $lang->about_expand = 'Если эта опция выбрана, расширение будут применено всегда';
    $lang->about_category_group_srls = 'Только выбранные группы можно отнести к этой категории';
    $lang->about_category_color = 'Установить цвет шрифта категории. ex) red или #ff0000';

    $lang->cmd_search_next = 'Искать дальше';

    $lang->about_category_color = '분류 폰트색깔을 지정합니다.';
    $lang->cmd_temp_save = 'Сохранить временно';

	$lang->cmd_toggle_checked_document = 'Перевернуть выбранные объекты';
    $lang->cmd_delete_checked_document = 'Удалить выбранные';
    $lang->cmd_document_do = 'Эту запись...';

    $lang->msg_cart_is_null = 'Выберите записи,которые Вы хотите удалить';
    $lang->msg_category_not_moved = 'Невозможно переместить';
    $lang->msg_is_secret = 'Секретная запись';
    $lang->msg_checked_document_is_deleted = '%d записей удалено';

    $lang->move_target_module = 'Этот модуль';

        // Search targets in admin page
        $lang->search_target_list = array(
        'title' => 'Тема',
        'content' => 'Содержание',
        'user_id' => 'ID пользователя',
        'member_srl' => 'No. пользователя',
        'user_name' => 'Имя пользователя',
        'nick_name' => 'Ник',
        'email_address' => 'Email',
        'homepage' => 'Домашняя страница',
        'is_notice' => 'Объявления',
        'is_secret' => 'Секретная запись',
        'tags' => 'Тег',
        'readed_count' => 'Количество просмотров (свыше)',
        'voted_count' => 'Количество голосов (свыше)',
        'comment_count ' => 'Количество записей (свыше)',
        'trackback_count ' => 'Количество трекбеков (свыше)',
        'uploaded_count ' => 'Количество вложений (прикрепленных файлов) (свыше)',
        'regdate' => 'Дата регистрации',
        'last_update' => 'Дата последнего обновления',
        'ipaddress' => 'IP-Адрес',
    );

    $lang->alias = "Alias";
    $lang->history = "History";
    $lang->about_use_history = "Determine whether to enable history feature, if it is enabled, update history would be stored and possible to restore old revisions.";
    $lang->trace_only = "Trace only";

    $lang->cmd_trash = 'Корзина';
    $lang->cmd_restore = 'Восстановить';
    $lang->cmd_restore_all = 'Восстановить все';

    $lang->in_trash = 'Корзина';
    $lang->trash_nick_name = 'Ник удалителя';
    $lang->trash_date = 'Дата удаления';
    $lang->trash_description = 'Описание';

	// Возможен поиск на странице админа
    $lang->search_target_trash_list = array(
        'title' => 'Тема',
        'content' => 'Содержание',
        'user_id' => 'ID',
        'member_srl' =>'No пользователя',
        'user_name' => 'Имя пользователя',
        'nick_name' => 'Ник',
        'trash_member_srl' => 'Номер удалителя',
        'trash_user_name' => 'Имя удалителя',
        'trash_nick_name' => 'Ник удалителя',
        'trash_date' => 'Дата удаления',
        'trash_ipaddress' => 'IP адрес удалителя',
    );

    $lang->success_trashed = 'Удалено в корзину';

    $lang->success_trashed = "Successfully moved to trashcan";
    $lang->msg_not_selected_document = '선택된 문서가 없습니다.';
?>
