<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  English Language Pack (Only Basic Things)
     **/

    $lang->communication = 'Communication';
    $lang->about_communication = 'This module performs communication functions such as messages or friends';

    $lang->allow_message = 'Receive Messages';
    $lang->allow_message_type = array(
             'Y' => 'Receive All',
             'N' => 'Reject All',
             'F' => 'Only Friends',
        );

    $lang->message_box = array(
        'R' => 'Received',
        'S' => 'Sent',
        'T' => 'Mailbox',
    );
    $lang->readed_date = "Read Date"; 

    $lang->sender = 'Sender';
    $lang->receiver = 'Receiver';
    $lang->friend_group = 'Friend Group';
    $lang->default_friend_group = 'Unassigned Group';

    $lang->cmd_send_message = 'Send Message';
    $lang->cmd_reply_message = 'Reply Message';
    $lang->cmd_view_friend = 'Friends';
    $lang->cmd_add_friend = 'Add Friend';
    $lang->cmd_view_message_box = 'Message Box';
    $lang->cmd_store = "Save";
    $lang->cmd_add_friend_group = 'Add Friend Group';
    $lang->cmd_rename_friend_group = 'Modify Friend Group Name';

    $lang->msg_no_message = 'There is no message';
    $lang->message_received = 'You have a new message';

    $lang->msg_title_is_null = 'Please input the title of message';
    $lang->msg_content_is_null = 'Please input the content';
    $lang->msg_allow_message_to_friend = "Failed to send because receiver only allows friends' messages";
    $lang->msg_disallow_message = 'Failed to send because receiver rejects message reception';

    $lang->about_allow_message = 'You can decide message reception';
?>
