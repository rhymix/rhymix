<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack for Zeroboard XE
     **/

    $lang->poll = "Опрос"; 
    $lang->poll_stop_date = "Дата истечения";
    $lang->poll_join_count = "Участников";
    $lang->poll_checkcount = "Число полей выбора";

    $lang->cmd_poll_list = 'Просмотреть список опросов';
    $lang->cmd_delete_checked_poll = 'Удалить выбранные опросы'; 
    $lang->cmd_apply_poll = 'Применеть опрос';
    $lang->cmd_view_result = 'Предпросмотр результата';
    $lang->cmd_delete_checked_poll = 'Удалить выбранные опросы'; // translator's remark for devs: double entry, already has  $lang->cmd_delete_checked_poll

    $lang->success_poll = 'Благодарим за присоединение к опросу.';

    $lang->msg_already_poll = 'Вы уже голосовали!';
    $lang->msg_cart_is_null = 'Пожалуйста, выберите статью для удаления.';
    $lang->msg_checked_poll_is_deleted = '%d опросов удалено.';
    $lang->msg_check_poll_item = 'Пожалуйста, выберите ответ, чтобы проголосовать.\n(Требуемые объекты могут различаться для каждого из опросов.)';
    $lang->msg_cart_is_null = 'Пожалуйста, выберите опрос для удаления.'; // translator's remark for devs: double entry, already has it...
    $lang->msg_checked_poll_is_deleted = '%d опросов удалено.'; // translator's remark for devs: double entry, already has it...
    $lang->msg_poll_not_exists = 'Выбранный опрос не существует.';

    $lang->cmd_null_item = "Не выбран ответ для голосования. Пожалуйста, попробуйте еще.";

    $lang->confirm_poll_submit = "Подтверждаете ли Вы размещение опроса?";

    $lang->search_target_list = array(
        'title' => 'Заголовок',
        'regdate' => 'Дата Размещения', // // translator's remark: this may be as "Дата Регистрации"
        'ipaddress' => 'IP-адрес',
    );
?>
