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
    $lang->denied_user_id = 'Sorry. This ID is prohibited.';
    $lang->null_user_id = 'Please input user ID';
    $lang->null_password = 'Please input password';
    $lang->invalid_authorization = 'It is not certificated';
    $lang->invalid_user_id= "This ID doesn't exist";
    $lang->invalid_password = 'This is wrong password';
    $lang->allow_mailing = 'Join Mailing';
    $lang->allow_message = 'Allow Message Reception';
    $lang->allow_message_type = array(
             'Y' => 'Receive All',
             'N' => 'Reject All',
             'F' => 'Only Friends',
        );
    $lang->denied = 'Prohibited';
    $lang->is_admin = 'Superadmin Permission';
    $lang->group = 'Assigned Group';
    $lang->group_title = 'Group Name';
    $lang->group_srl = 'Group Number';
    $lang->signature = 'Signature';
    $lang->profile_image = 'Profile Image';
    $lang->profile_image_max_width = 'Max Width';
    $lang->profile_image_max_height = 'Max Height';
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
    $lang->default_friend_group = 'Unassigned Group';
    $lang->member_info = 'Member Info';
    $lang->current_password = 'Current Password';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = "Webmaster's Name";
    $lang->webmaster_email = "Webmaster's Email";

    $lang->about_webmaster_name = "Please input webmaster's name which will be used for authentication mails or other site administration. (default : webmaster)";
    $lang->about_webmaster_email = "Please input webmaster's email address.";

    $lang->search_target_list = array(
        'user_id' => 'ID',
        'user_name' => 'Name',
        'nick_name' => 'Nickname',
        'email_address' => 'Email Address',
        'regdate' => 'Join Date',
        'last_login' => 'Last Login Date',
        'extra_vars' => 'Extra Vars',
    );

    $lang->message_box = array(
        'R' => 'Received',
        'S' => 'Sent',
        'T' => 'Mailbox',
    );

    $lang->readed_date = "Read Date"; 

    $lang->cmd_login = 'Login';
    $lang->cmd_logout = 'Logout';
    $lang->cmd_signup = 'Join';
    $lang->cmd_modify_member_info = 'Modify Member Info';
    $lang->cmd_modify_member_password = 'Change Password';
    $lang->cmd_view_member_info = 'Member Info';
    $lang->cmd_leave = 'Leave';
    $lang->cmd_find_member_account = 'Find Account Info';

    $lang->cmd_member_list = 'Member List';
    $lang->cmd_module_config = 'Default Setting';
    $lang->cmd_member_group = 'Manage Group';
    $lang->cmd_send_mail = 'Send Mail';
    $lang->cmd_manage_id = 'Manage Prohibited ID';
    $lang->cmd_manage_form = 'Manage Join Form';
    $lang->cmd_view_own_document = 'View Written Articles';
    $lang->cmd_view_scrapped_document = 'Scraps';
    $lang->cmd_view_saved_document = 'View Saved Articles';
    $lang->cmd_send_email = 'Send Mail';
    $lang->cmd_send_message = 'Send Message';
    $lang->cmd_reply_message = 'Reply Message';
    $lang->cmd_view_friend = 'Friends';
    $lang->cmd_add_friend = 'Register as Friend';
    $lang->cmd_view_message_box = 'Message Box';
    $lang->cmd_store = "Save";
    $lang->cmd_add_friend_group = 'Add Friend Group';
    $lang->cmd_rename_friend_group = 'Change Name of Friend Group';

    $lang->msg_email_not_exists = "Email address doesn't exists";

    $lang->msg_alreay_scrapped = 'This article is already scrapped';

    $lang->msg_cart_is_null = 'Please select the target';
    $lang->msg_checked_file_is_deleted = '%d attached files are deleted';

    $lang->msg_find_account_title = 'Account Info';
    $lang->msg_find_account_info = 'This is requested account info.';
    $lang->msg_find_account_comment = 'The password will be modified to above one when you click below link.<br />Please modify the password after login.';
    $lang->msg_auth_mail_sended = 'The authentication mail has been sent to %s. Please check your mail.';
	$lang->msg_invalid_auth_key = 'This is an invalid request of authentication.<br />Please retry finding account info or contact to administrator.';
    $lang->msg_success_authed = 'Your account has been successfully authenticated and logged on. Please modify the password to your own one using the password in the authentication mail.';

    $lang->msg_no_message = 'There are no messages';
    $lang->message_received = 'You got a new message';

    $lang->msg_new_member = 'Add Member';
    $lang->msg_update_member = 'Modify Member Info';
    $lang->msg_leave_member = 'Leave';
    $lang->msg_group_is_null = 'There is no registered group';
    $lang->msg_not_delete_default = 'Default items cannot be deleted';
    $lang->msg_not_exists_member = "This member doesn't exist";
    $lang->msg_cannot_delete_admin = 'Admin ID cannot be deleted. Please remove the ID from administration and try again.';
    $lang->msg_exists_user_id = 'This ID already exists. Please try with another ID';
    $lang->msg_exists_email_address = 'This email address already exists. Please try with another email address.';
    $lang->msg_exists_nick_name = 'This nickname already exists. Please try with another nickname.';
    $lang->msg_signup_disabled = 'You are not able to join';
    $lang->msg_already_logged = 'You have already joined';
    $lang->msg_not_logged = 'Please login first';
    $lang->msg_title_is_null = 'Please input title of message';
    $lang->msg_content_is_null = 'Please input content';
    $lang->msg_allow_message_to_friend = "Failed to send because receiver only allows friends' messages";
    $lang->msg_disallow_message = 'Failed to send because receiver rejects message reception';
    $lang->msg_insert_group_name = 'Please input name of group';

    $lang->msg_not_uploaded_image_name = 'Image name could not be registered';
    $lang->msg_not_uploaded_image_mark = 'Image mark could not be registered';

    $lang->msg_accept_agreement = 'You have to agree to agreement first'; 

    $lang->msg_user_denied = 'Inputted ID is now prohibited';
    $lang->msg_user_limited = 'Inputted ID can be used after %s';

    $lang->about_user_id = 'User ID should be 3~20 letters long and consist of alphabet+number with alphabet as first letter.';
    $lang->about_password = 'Password should be 6~20 letters long';
    $lang->about_user_name = 'Name should be 2~20 letters long';
    $lang->about_nick_name = 'Nickname should be 2~20 letters long';
    $lang->about_email_address = 'Email address is used to modify/find password after email certification';
    $lang->about_homepage = 'Please input if you have your websites';
    $lang->about_blog_url = 'Please input if you have your blogs';
    $lang->about_birthday = 'Please input your birth date';
    $lang->about_allow_mailing = "If you don't join mailing, you will not able to receive group mail";
    $lang->about_allow_message = 'You can decide message reception';
    $lang->about_denied = 'Check to prohibit the ID';
    $lang->about_is_admin = 'Check to give Superadmin permission';
    $lang->about_description = "Administrator's memo about members";
    $lang->about_group = 'An ID can belong to many groups';

    $lang->about_column_type = 'Please set the format of additional join form';
    $lang->about_column_name = 'Please input English name that can be used in template (name as variable)';
    $lang->about_column_title = 'This will be displayed when member joining or modifing/viewing member info';
    $lang->about_default_value = 'You can set default values';
    $lang->about_active = 'You have to check on active items to show on join form';
    $lang->about_form_description = 'If you input in description form, it will be displayed on join form';
    $lang->about_required = 'If you check, it will be essential item for join';

    $lang->about_enable_openid = 'Allow users to join as OpenID';
    $lang->about_enable_join = 'Allow users to join';
    $lang->about_limit_day = 'You can limit certification date after join';
    $lang->about_limit_date = 'User cannot login until assigned date';
    $lang->about_redirect_url = 'Please input URL where users will go after join. When this is empty, it will be set as the previous page of join page.';
    $lang->about_agreement = "Join agreement will only be displayed when it's not empty";

    $lang->about_image_name = "Allow users to use image name instead of text name";
    $lang->about_image_mark = "Allow users to use mark in front of their names";
    $lang->about_profile_image = 'Allow users to use profile images';
    $lang->about_accept_agreement = "I've read the agreement all and agree"; 

    $lang->about_member_default = 'It will be set as default group on join';

    $lang->about_openid = 'When you join as OpenID, basic info like ID or email address will be saved on this site, but password and certification management will be done on current OpenID offering service';
    $lang->about_openid_leave = 'The secession of OpenID means deleting your member info in this site.<br />If you login after secession, it will be recognized as a new member, so you will no longer have the permission for your ex-written articles.';

    $lang->about_member = "This is a module for creating/modifing/deleting members and managing group or join form.\nYou can manage members by creating new groups, and get additional information by managing join form";
    $lang->about_find_member_account = 'Your account info will be noticed by registered email address.<br />Please input email address which you have input on registration, and press "Find Account Info" button.<br />';
?>
