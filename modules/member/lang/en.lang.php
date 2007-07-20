<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  English Language Pack (Only Basic Things)
     **/

    $lang->member = 'Member';
    $lang->member_default_info = 'Basic Info';
    $lang->member_extend_info = 'Additional Info';
    $lang->default_group_1 = "Associate Member";
    $lang->default_group_2 = "Regular Member";
    $lang->admin_group = "Managing Group";
    $lang->remember_user_id = 'Save ID';
    $lang->already_logged = "You're already logged on";
    $lang->denied_user_id = 'This is prohibited ID';
    $lang->null_user_id = 'Input user ID';
    $lang->null_password = 'Input password';
    $lang->invalid_authorization = 'It is not certificated';
    $lang->invalid_user_id= "This ID doesn't exist";
    $lang->invalid_password = 'This is wrong password';
    $lang->allow_mailing = 'Join Mailing';
    $lang->allow_message = 'Allow Message Reception';
    $lang->allow_message_type = array(
             'Y' => 'Receive All',
             'N' => 'Reject',
             'F' => 'Only Friends',
        );
    $lang->denied = 'Prohibited';
    $lang->is_admin = 'Superadmin Authority';
    $lang->group = 'Attached Group';
    $lang->group_title = 'Group Name';
    $lang->group_srl = 'Group Number';
    $lang->signature = 'Signature';
    $lang->image_name = 'Image Name';
    $lang->image_name_max_width = 'Max Width';
    $lang->image_name_max_height = 'Max Height';
    $lang->image_mark = 'Image Mark';
    $lang->image_mark_max_width = 'Max Width';
    $lang->image_mark_max_height = 'Max Height';
    $lang->enable_openid = 'Enable OpenID';
    $lang->enable_join = 'Allow Member Join';
    $lang->limit_day = 'Temporary Limit Date';
    $lang->limit_date = 'Limit Date';
    $lang->redirect_url = 'URL after Join';
    $lang->agreement = 'Member Join Agreement';
    $lang->accept_agreement = 'Agree';
    $lang->sender = 'Sender';
    $lang->receiver = 'Receiver';
    $lang->friend_group = 'Friend Group';
    $lang->default_friend_group = 'Group not appointed';
    $lang->member_info = 'Member Info';
    $lang->current_password = 'Current Password';
    $lang->openid = 'OpenID';

    $lang->search_target_list = array(
        'user_id' => 'ID',
        'user_name' => 'Name',
        'nick_name' => 'Nickname',
        'email_address' => 'Email Address',
        'regdate' => 'Registered Date',
        'last_login' => 'Latest Login Date',
    );

    $lang->message_box = array(
        'R' => 'Received',
        'S' => 'Sent',
        'T' => 'Mailbox',
    );

    $lang->readed_date = "Read Date"; 

    $lang->cmd_login = 'Login';
    $lang->cmd_logout = 'Logout';
    $lang->cmd_signup = 'Join Member';
    $lang->cmd_modify_member_info = 'Modify Member Info';
    $lang->cmd_modify_member_password = 'Chagne Password';
    $lang->cmd_view_member_info = 'View Member Info';
    $lang->cmd_leave = 'Leave';

    $lang->cmd_member_list = 'Member List';
    $lang->cmd_module_config = 'Default Setting';
    $lang->cmd_member_group = 'Manage Group';
    $lang->cmd_send_mail = 'Send Mail';
    $lang->cmd_manage_id = 'Manage Prohibited ID';
    $lang->cmd_manage_form = 'Manage Join Form';
    $lang->cmd_view_own_document = 'View Written Articles';
    $lang->cmd_send_email = 'Send Mail';
    $lang->cmd_send_message = 'Send Message';
    $lang->cmd_reply_message = 'Reply Message';
    $lang->cmd_view_friend = 'View Friends';
    $lang->cmd_add_friend = 'Register Friend';
    $lang->cmd_view_message_box = 'View Message Box';
    $lang->cmd_store = "Keep";
    $lang->cmd_add_friend_group = 'Add Friend Group';

    $lang->msg_cart_is_null = 'Select Target';
    $lang->msg_checked_file_is_deleted = '%d Attached files are deleted';

    $lang->msg_no_message = 'There are no messages';
    $lang->message_received = 'You got a new message';

    $lang->msg_new_member = 'Add Member';
    $lang->msg_update_member = 'Modify Member Info';
    $lang->msg_leave_member = 'Leave Member';
    $lang->msg_group_is_null = 'There is no registered group';
    $lang->msg_not_delete_default = 'Default items cannot be deleted';
    $lang->msg_not_exists_member = "This member doesn't exist";
    $lang->msg_cannot_delete_admin = 'Admin ID cannot be deleted. Try again after remove from Admin';
    $lang->msg_exists_user_id = 'This ID already exists. Input other ID';
    $lang->msg_exists_email_address = 'This email address already exists. Input other email address';
    $lang->msg_exists_nick_name = 'This nickname already exists. Input other nickname';
    $lang->msg_signup_disabled = 'You cannot join';
    $lang->msg_already_logged = 'You are already joined';
    $lang->msg_not_logged = 'You are not logged on yet';
    $lang->msg_title_is_null = 'Input message title';
    $lang->msg_content_is_null = 'Input content';
    $lang->msg_allow_message_to_friend = "Failed to send because receiver only allows friends' messages";
    $lang->msg_disallow_message = 'Failed to send because receiver rejects message reception';
    $lang->msg_insert_group_name = 'Input group name';

    $lang->msg_not_uploaded_image_name = 'Image name cannot be registered';
    $lang->msg_not_uploaded_image_mark = 'Image mark cannot be registered';

    $lang->msg_accept_agreement = 'You have to agree to agreement'; 

    $lang->msg_user_denied = 'Input ID is now prohibited';
    $lang->msg_user_limited = 'Input ID can be used after %s';

    $lang->about_user_id = 'User ID should be 3~20 long with english+number and it should be started in English';
    $lang->about_password = 'Password should be 6~20 long';
    $lang->about_user_name = 'Name should be 2~20 long';
    $lang->about_nick_name = 'Nickname should be 2~20 long';
    $lang->about_email_address = 'Email address is used to modify/find password after email certification';
    $lang->about_homepage = 'Input if you have any websites';
    $lang->about_blog_url = 'Input if you have any blogs';
    $lang->about_birthday = 'Input your birth date';
    $lang->about_allow_mailing = "If you don't check join mailing, you cannot receive group mail";
    $lang->about_allow_message = 'You can decide message reception';
    $lang->about_denied = 'Check to make ID prohibit';
    $lang->about_is_admin = 'Check to give SuperAdmin power';
    $lang->about_description = 'Admin memo about members';
    $lang->about_group = 'An ID can be belong to many groups';

    $lang->about_column_type = 'Appoint the format of additional join form';
    $lang->about_column_name = 'Input English name that can be used in template (variable name)';
    $lang->about_column_title = 'This will be displayed when joining or modifing info/view';
    $lang->about_default_value = 'You can decide default values';
    $lang->about_active = 'You have to check on active items to show on join';
    $lang->about_form_description = 'If you input in description, it will be displayed on join';
    $lang->about_required = 'If you check, it will be essential item on join';

    $lang->about_enable_openid = 'Allow users to join as OpenID';
    $lang->about_enable_join = 'Allow users to join';
    $lang->about_limit_day = 'You can limit certification date after join';
    $lang->about_limit_date = 'User cannot login until assigned date';
    $lang->about_redirect_url = 'Input URL where users will go after join. When this is empty, users will go the previous page of join page.';
    $lang->about_agreement = "Join agreement will not displayed when there isn't";

    $lang->about_image_name = "Allow users' name as image instead of text";
    $lang->about_image_mark = "Put mark in front of users' name";
    $lang->about_accept_agreement = "I've read the agreement all and I agree"; 

    $lang->about_member_default = 'It will be set as default group on join';

    $lang->about_openid = 'When you join as OpenID, basic info like ID or email address will be saved on this site, but password and certification managing will be done on current OpenID offering service';

    $lang->about_member = "This is a module for creating/modifing/deleting members and managing group or join form.\nYou can manage members by creating new groups, and get additional information by managing join form";
?>
