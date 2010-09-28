<?php
  /**
     * @file   ru.lang.php
     * @author NHN (developers@xpressengine.com) | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack
     **/

    $lang->file = 'Вложение';
    $lang->file_name = 'Имя файла';
    $lang->file_size = 'Размер файла';
    $lang->download_count = 'Загружено';
    $lang->status = 'Состояние';
    $lang->is_valid = 'Верно';
    $lang->is_stand_by = 'Ожидание';
    $lang->file_list = 'Список Вложений';
    $lang->allow_outlink = 'Внешняя ссылка файла';
    $lang->allow_outlink_site = 'Allowed Websites';
    $lang->allow_outlink_format = 'Allowed Formats';
    $lang->allowed_filesize = 'Размера файла';
    $lang->allowed_attach_size = 'Размер прикрепленного документа';
    $lang->allowed_filetypes = 'Разрешенные расширения';
    $lang->enable_download_group = 'Группы, имеющие право на скачивание';

   	$lang->about_allow_outlink = 'You can shut external links according to referers. (except media files like *.wmv, *.mp3)';
    $lang->about_allow_outlink_format = 'These formats will always be allowed. Please use comma(,) for multiple input.<br />eg)hwp,doc,zip,pdf';
    $lang->about_allow_outlink_site = 'These websites will alyways be allowed. Please use new line for multiple input.<br />ex)http://www.hotmail.com';
	$lang->about_allowed_filesize = 'Вы можете присвоить лимит размера для каждого файла. (Исключая администраторов)';
    $lang->about_allowed_attach_size = 'Вы можете присвоить лимит размера для каждого документа. (Исключая администраторов';
    $lang->about_allowed_filetypes = 'Только файлы с разрешенными расширениями могут быть вложены. Чтобы разрешить расширение, ипользуйте "*.расширение". Чтобы разрешить несколько расширений, используйте ";" между ними.<br />например: *.* или *.jpg;*.gif;<br />(Исключая администраторов)';

    $lang->cmd_delete_checked_file = 'Удалить выделенные файлы';
    $lang->cmd_move_to_document = 'Перейти в документ';
    $lang->cmd_download = 'Скачать';

    $lang->msg_not_permitted_download = 'У Вас нет прав доступа для скачивания файлов';
    $lang->msg_cart_is_null = 'Выберите файл, который Вы хотите удалить';
    $lang->msg_checked_file_is_deleted = 'Всего %d вложений было удалено';
    $lang->msg_exceeds_limit_size = 'Прикрепить файл не удалось: превышен лимит размера файлов';
    $lang->msg_file_not_found = 'Невозможно найти запрашиваемый файл.';

    $lang->file_search_target_list = array(
        'filename' => 'Имя файла',
        'filesize_more' => 'Размер файла (Байт, свыше)',
        'filesize_mega_more' => 'Размер файла (Мегабайт, свыше)',
		'filesize_less' => 'Размер файла (Байт, ниже)',
		'filesize_mega_less' => 'Размер файла (Мегабайт, ниже)',
        'download_count' => 'Скачано (свыше)',
        'user_id' => 'ID',
        'user_name' => 'Имя',
        'nick_name' => 'Ник',
        'regdate' => 'Дата регистрации',
        'ipaddress' => 'IP-Адрес',
    );
	$lang->msg_not_allowed_outlink = 'It is not allowed to download files not from this site.'; 
    $lang->msg_not_permitted_create = '파일 또는 디렉토리를 생성할 수 없습니다.';
	$lang->msg_file_upload_error = '파일 업로드 중 에러가 발생하였습니다.';

?>
