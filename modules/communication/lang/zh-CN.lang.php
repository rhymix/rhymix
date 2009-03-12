<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  简体中文语言包 (只收录基本内容)
     **/

    $lang->communication = '会员交流';
    $lang->about_communication = '管理在线会员间短信息及好友功能的模块。';

    $lang->allow_message = '接收短消息';
    $lang->allow_message_type = array(
             'Y' => '全部接收',
             'N' => '拒收',
             'F' => '只允许好友',
        );

    $lang->message_box = array(
        'R' => '收件箱',
        'S' => '发件箱',
        'T' => '保管箱',
    );

    $lang->readed_date = "阅读日期"; 

    $lang->sender = '寄件人';
    $lang->receiver = '收件人';
    $lang->friend_group = '好友组';
    $lang->default_friend_group = '组未指定';

    $lang->cmd_send_message = '发送短消息';
    $lang->cmd_reply_message = '回复短消息';
    $lang->cmd_view_friend = '我的好友';
    $lang->cmd_add_friend = '加为好友';
    $lang->cmd_view_message_box = '短信箱';
    $lang->cmd_store = "保管";
    $lang->cmd_add_friend_group = '添加好友组';
    $lang->cmd_rename_friend_group = '修改好友组名称';

    $lang->msg_no_message = '没有短消息。';
    $lang->message_received = '您有新消息。';

    $lang->msg_title_is_null = '请输入短消息标题。';
    $lang->msg_content_is_null = '请输入内容。';
    $lang->msg_allow_message_to_friend = '因其为只允许接收好友短消息的用户，所以不能发送短消息。';
    $lang->msg_disallow_message = '因其为拒绝接收短消息的用户，所以不能发送短消息。';

    $lang->about_allow_message = '可以选择短消息接收与否。';
?>
