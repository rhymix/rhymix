<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack
     **/

    $lang->communication = 'Communication';
    $lang->about_communication = '회원간의 쪽지나 친구 관리등 커뮤니케이션 기능을 수행하는 모듈입니다';

    $lang->allow_message = 'Разрешить прием сообщений';
    $lang->allow_message_type = array(
             'Y' => 'Принимать все',
                         'N' => 'Отклонять все',
                                     'F' => 'Только друзья',
                );


    $lang->message_box = array(
        'R' => 'Принятые',
        'S' => 'Отправленные',
        'T' => 'Почтовый ящик',
    );

    $lang->readed_date = "Дата прочтения"; 

    $lang->sender = 'Отправитель';
    $lang->receiver = 'Получатель';
    $lang->friend_group = 'Группа друзей';
    $lang->default_friend_group = 'Неприсвоенная группа';

    $lang->cmd_send_message = 'Отправить сообщение';
    $lang->cmd_reply_message = 'Ответить';
    $lang->cmd_view_friend = 'Дзузья';
    $lang->cmd_add_friend = 'Сделать другом';
    $lang->cmd_view_message_box = 'Ящик сообщений';
    $lang->cmd_store = "Сохранить";
    $lang->cmd_add_friend_group = 'Добавить группу друзей';
    $lang->cmd_rename_friend_group = 'Изменить имя группы друзей';

    $lang->msg_no_message = 'Нет сообщений';
    $lang->message_received = 'Новое сообщение';

    $lang->msg_title_is_null = 'Пожалуйста, введите тему сообщения';
    $lang->msg_content_is_null = 'Пожалуйста, введите содержание';
    $lang->msg_allow_message_to_friend = "Отправка провалена, поскольку получатель принимает сообщения только от друзей";
    $lang->msg_disallow_message = 'Отправка провалена, поскольку получатель отклоняет прием сообщений';

    $lang->about_allow_message = 'Вы можете определить политику принятия сообщений';
?>
