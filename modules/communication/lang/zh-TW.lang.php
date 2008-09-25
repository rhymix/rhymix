<?php
    /**
     * @file   zh-TW.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  正體中文語言包 (包含基本內容)
     **/

    $lang->communication = '會員交流';
    $lang->about_communication = '管理在線會員間短信息及好友功能的模塊。';

    $lang->allow_message = '允許接收短消息';
    $lang->allow_message_type = array(
             'Y' => '全部接收',
             'N' => '拒收',
             'F' => '只允許好友',
        );

    $lang->message_box = array(
        'R' => '收件箱',
        'S' => '發件箱',
        'T' => '保管箱',
    );

    $lang->readed_date = "閱讀日期"; 

    $lang->sender = '寄件人';
    $lang->receiver = '收件人';
    $lang->friend_group = '好友組';
    $lang->default_friend_group = '組未指定';

    $lang->cmd_send_message = '發送短消息';
    $lang->cmd_reply_message = '回復短消息';
    $lang->cmd_view_friend = '檢視好友';
    $lang->cmd_add_friend = '加為好友';
    $lang->cmd_view_message_box = '檢視短信箱';
    $lang->cmd_store = "保存";
    $lang->cmd_add_friend_group = '添加好友組';
    $lang->cmd_rename_friend_group = '修改好友組名稱';

    $lang->msg_no_message = '沒有短消息。';
    $lang->message_received = '您有新消息。';

    $lang->msg_title_is_null = '請輸入短消息標題。';
    $lang->msg_content_is_null = '請輸入內容。';
    $lang->msg_allow_message_to_friend = '因其為只允許接收好友短消息的用戶，所以不能發送短消息。';
    $lang->msg_disallow_message = '因其為拒絕接收短消息的用戶，所以不能發送短消息。';

    $lang->about_allow_message = '可以選擇短消息接收與否。';
?>
