<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack
     **/

    $lang->communication = 'Общение';
    $lang->about_communication = 'Модуль для общения между пользователями';

    $lang->allow_message = 'Получать сообщения';
    $lang->allow_message_type = array(
             'Y' => 'Принимать все',
             'N' => 'Отклонять все',
             'F' => 'Принимать только от друзей',
                );


    $lang->message_box = array(
        'R' => 'Полученные',
        'S' => 'Отправленные',
        'T' => 'Почтовый ящик',
    );

    $lang->readed_date = "Дата прочтения сообщения"; 

    $lang->sender = 'Отправитель';
    $lang->receiver = 'Получатель';
    $lang->friend_group = 'Группа Друзья';
    $lang->default_friend_group = 'Незарегистрированная группа';

    $lang->cmd_send_message = 'Отправить сообщение';
    $lang->cmd_reply_message = 'Ответить';
    $lang->cmd_view_friend = 'Друзья';
    $lang->cmd_add_friend = 'Добавить в друзья';
    $lang->cmd_view_message_box = 'Личные сообщений';
    $lang->cmd_store = "Сохранить";
    $lang->cmd_add_friend_group = 'Добавить в группу друзей';
    $lang->cmd_rename_friend_group = 'Изменить имя группы друзей';

    $lang->msg_no_message = 'Сообщений нет';
    $lang->message_received = 'У Вас новое сообщение';

    $lang->msg_title_is_null = 'Пожалуйста, введите тему сообщения';
    $lang->msg_content_is_null = 'Пожалуйста, введите содержание';
    $lang->msg_allow_message_to_friend = "Сообщение не отправлено, поскольку являетесь пользователем, имеющим право посылать сообщения только друзьям";
    $lang->msg_disallow_message = 'Сообщение не отправлено, поскольку получатель запретил прием сообщений';
    $lang->about_allow_message = 'Вы можете установить режим принятия сообщений';
?>
