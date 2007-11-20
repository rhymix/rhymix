<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack for Zeroboard XE
     **/

    $lang->file = 'Вложение';
    $lang->file_name = 'Имя файла';
    $lang->file_size = 'Размер файла';
    $lang->download_count = 'Скачано';
    $lang->status = 'Состояние';
    $lang->is_valid = 'Верно';
    $lang->is_stand_by = 'Ожидание';
    $lang->file_list = 'Список Вложений';
    $lang->allowed_filesize = 'Лимит размера файла';
    $lang->allowed_attach_size = 'Общий лимит размера';
    $lang->allowed_filetypes = 'Разрешенные расширения';
    $lang->enable_download_group = 'Группы с разрешением на скачивание';

    $lang->about_allowed_filesize = 'Вы можете присвоить лимит на размер для каждого файла. (Исключая администраторов)';
    $lang->about_allowed_attach_size = 'Вы можете присвоить лимит на размер для каждого документа. (Исключая администраторов';
    $lang->about_allowed_filetypes = 'Только файлы с разрешенными расширениями могут быть вложены. Чтобы разрешить расширение, ипользуйте "*.расширение". Чтобы разрешить несколько расширений, используйте ";" между ними.<br />например: *.* или *.jpg;*.gif;<br />(Исключая администраторов)';

    $lang->cmd_delete_checked_file = 'Удалить Выделенные';
    $lang->cmd_move_to_document = 'Переместить в документ';
    $lang->cmd_download = 'Скачать';

    $lang->msg_not_permitted_download = 'У Вас нет прав доступа для скачивания';
    $lang->msg_cart_is_null = 'Выберите файл, который Вы хотите удалить';
    $lang->msg_checked_file_is_deleted = 'Всего %d вложений было удалено';
    $lang->msg_exceeds_limit_size = 'Вложение провалено: превышен лимит размера файлов';

    $lang->search_target_list = array(
        'filename' => 'Имя файла',
        'filesize' => 'Размер файла (байт, Выше)',
        'download_count' => 'Скачано (Выше)',
        'regdate' => 'Дата',
        'ipaddress' => 'IP-Адрес',
    );
?>
