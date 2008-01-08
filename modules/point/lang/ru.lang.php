<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack for Zeroboard XE
     **/

    $lang->point = "Поинты"; 
    $lang->level = "Уровень"; 

    $lang->about_point_module = "Вы можете распределять поинты за написание/добавление комментариев, закачку/скачку файлов.<br />Модуль поинтов только конфигурирует настройки, а набор поинтов будет осуществлять, если только аддон поинтов будет активирован";
    $lang->about_act_config = "Каждый модуль, такой как форум/блог, имеет имеет свои действия, такие как\"написание/удаление/добавление комментариев/удаление комментариев\".<br />Вы можете просто добавить значения действий, чтобы связать ситему поинтов, за исключением форума/блога.<br />Запятая(,) используется как разделитель значений."; 

    $lang->max_level = 'Макс. уровень';
    $lang->about_max_level = 'Вы можете установить максимальный уровень. Иконки уровней должны быть присвоены. (макс. значение равно 1000)'; 

    $lang->level_icon = 'Иконка уровня';
    $lang->about_level_icon = 'Путь иконок уровней "./module/point/icons/[level].gif" и максимальный уровень может меняться с набором иконок. Поэтому будте осторожны'; 

    $lang->point_name = 'Имя поинта';
    $lang->about_point_name = 'Вы можете дать имя или единицу измерения для поинта'; 

    $lang->level_point = 'Уровень поинтов';
    $lang->about_level_point = 'Уровень будет изменен, когда поинты достигают каждого уровня поинтов или падают ниже его'; 

    $lang->disable_download = 'Запретить скачивание';
    $lang->about_disable_download = "Это запретит скачивание файлов, когда не хватает достаточного кол-ва поинтов. (За исключением файлов изображений)"; 

    $lang->level_point_calc = '레벨별 포인트 계산';
    $lang->expression = '레벨 변수 <b>i</b>를 사용하여 자바스크립트 수식을 입력하세요. 예: Math.pow(i, 2) * 90';
    $lang->cmd_exp_calc = '계산';
    $lang->cmd_exp_reset = '초기화';

    $lang->cmd_point_recal = '포인트 재계산';
    $lang->about_cmd_point_recal = '게시글/댓글/첨부파일등을 모두 검사하여 설정된 포인트 설정에 맞게 모든 회원들의 포인트를 재계산합니다';

    $lang->about_module_point = "Вы можете установть поинты для каждого модуля, а модули, не имеющие значения будут использовать значение по умолчанию для поинтов.<br />Все поинты будут восстановлены при обратном действии.";

    $lang->point_signup = 'Присвоить';
    $lang->point_insert_document = 'При написании';
    $lang->point_delete_document = 'При удалении';
    $lang->point_insert_comment = 'При добавлении комментариев';
    $lang->point_delete_comment = 'При удалении комментариев';
    $lang->point_upload_file = 'При закачке файлов';
    $lang->point_delete_file = 'При скачке файлов';
    $lang->point_download_file = 'При скачке файлов (кроме изображений)';
    $lang->point_read_document = '게시글 조회';


    $lang->cmd_point_config = 'Настройки по умолчанию';
    $lang->cmd_point_module_config = 'Настройки модуля';
    $lang->cmd_point_act_config = 'Настройки действий';
    $lang->cmd_point_member_list = 'Список поинтов пользователей';

    $lang->msg_cannot_download = "У Вас нет достаточного количества поитов, чтобы иметь разрешение скачивать файлы.";

    $lang->point_recal_message = '포인트 적용중입니다. (%d / %d)';
    $lang->point_recal_finished = '포인트 재계산이 모두 완료되었습니다';
?>
