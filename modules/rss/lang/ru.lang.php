<?php
  /**
     * @file   ru.lang.php
     * @author NHN (developers@xpressengine.com) | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack
     **/

    // главные слова
    $lang->feed = 'Создать(Feed)';
    $lang->total_feed = 'Общий Feed';
	$lang->rss_disable = "Отключить RSS";
	$lang->feed_copyright = 'Копирайт';
    $lang->feed_document_count = 'Количество записей на страницу';
    $lang->feed_image = 'Картинка Feed';
    $lang->rss_type = "Тип RSS";
    $lang->open_rss = 'Показать RSS';
    $lang->open_rss_types = array(
        'Y' => 'Показать все',
        'H' => 'Показать выдержку',
        'N' => 'Не показывать',
    );
    $lang->open_feed_to_total = 'Включено в общий Feed';
	   
		// для описаний
    $lang->about_rss_disable = "Если выбрано, RSS будет отключен.";
    $lang->about_rss_type = "Вы можете присвоить тип RSS.";
    $lang->about_open_rss = 'Вы можете выбрать для того, чтобы RSS доступен публично.\nНезависимо от разрешений для статьи, RSS будет доступна публично согласно ее настройке.';
    $lang->about_feed_description = '발행될 피드에 대한 설명을 입력하실 수 있습니다. 설명을 입력하지 않으실 경우, 해당 모듈에 설정된 관리용 설명이 포함됩니다.';
    $lang->about_feed_copyright = '발행될 피드에 대한 저작권 정보를 입력하실 수 있습니다.';
    $lang->about_part_feed_copyright = '입력하지 않으면 전체 피드 저작권 설정과 동일하게 적용됩니다.';
    $lang->about_feed_document_count = '피드 한 페이지에 공개되는 글 수. (기본 값 : 15)';

     // для ошибок
    $lang->msg_rss_is_disabled = "Функция RSS выключена.";
    $lang->msg_rss_invalid_image_format = 'Неправильный тип картинки\nПоддерживаются только JPEG, GIF, PNG файлы';
?>
