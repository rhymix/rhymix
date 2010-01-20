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
    $lang->keep_signed = 'Keep me signed in';
    $lang->remember_user_id = 'Remember ID';
    $lang->already_logged = "You are already signed in";
    $lang->denied_user_id = 'You have entered a prohibited ID.';
    $lang->null_user_id = 'Please input ID';
    $lang->null_password = 'Please input password';
    $lang->invalid_authorization = 'The account is not activated.';
    $lang->invalid_user_id= "You have entered an invalid ID";
    $lang->invalid_password = 'You have entered an invalid password';
    $lang->allow_mailing = 'Join Mailing';
    $lang->denied = 'Prohibited';
    $lang->is_admin = 'Superadmin Permission';
    $lang->group = 'Group';
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
    $lang->group_image_mark = 'Group Image Mark';
    $lang->group_image_mark_max_width = 'Max Width';
    $lang->group_image_mark_max_height = 'Max Height';
    $lang->group_image_mark_order = 'Group Image Mark Order';
    $lang->signature_max_height = 'Max Signature Height';
    $lang->enable_openid = 'Enable OpenID';
    $lang->enable_join = 'Accept New Members';
    $lang->enable_confirm = 'Email Activation';
    $lang->enable_ssl = 'Enable SSL';
    $lang->security_sign_in = 'Sign in using enhanced security';
    $lang->limit_day = 'Temporary Limit Date';
    $lang->limit_date = 'Limit Date';
    $lang->after_login_url = 'URL after Sign in';
    $lang->after_logout_url = 'URL after Sign out';
    $lang->redirect_url = 'URL after Sign up';
    $lang->agreement = 'Sign up Agreement';
    $lang->accept_agreement = 'Agree';
    $lang->member_info = 'Member Info';
    $lang->current_password = 'Current Password';
    $lang->openid = 'OpenID';
    $lang->allow_message = 'Allow Messages';
    $lang->allow_message_type = array(
            'Y' => 'Allow All',
            'F' => 'Allow for Friends',
            'N' => 'Reject All',
    );
    $lang->about_allow_message = 'You may allow or reject messages.';
    $lang->logged_users = 'Logged on Users';

    $lang->webmaster_name = "Webmaster Name";
    $lang->webmaster_email = "Webmaster Email";

    $lang->about_keep_signed = 'You will be still signed in even when the browser is closed.\n\nIt is not recommended to use this if you are using a public computer for your personal information could be violated';
    $lang->about_keep_warning = 'You will be still signed in even when the browser is closed. It is not recommended to use this if you are using a public computer for your personal information could be violated';
	$lang->about_webmaster_name = "Please input webmaster's name which will be used for verification mails or other site administration. (default : webmaster)";
    $lang->about_webmaster_email = "Please input webmaster's email address.";

    $lang->search_target_list = array(
        'user_id' => 'ID',
        'user_name' => 'Name',
        'nick_name' => 'Nickname',
        'email_address' => 'Email Address',
        'regdate' => 'Sign up Date',
        'regdate_more' => 'Sign up Date (more)',
        'regdate_less' => 'Sign up Date (less)',
        'last_login' => 'Last Sign in Date',
        'last_login_more' => 'Last Sign in Date (more)',
        'last_login_less' => 'Last Sign in Date (less)',
        'extra_vars' => 'Extra Vars',
    );

    $lang->cmd_login = 'Sign In';
    $lang->cmd_logout = 'Sign Out';
    $lang->cmd_signup = 'Sign Up';
    $lang->cmd_site_signup = 'Sign Up';
    $lang->cmd_modify_member_info = 'Modify Member Info';
    $lang->cmd_modify_member_password = 'Modify Password';
    $lang->cmd_view_member_info = 'Member Info';
    $lang->cmd_leave = 'Leave';
    $lang->cmd_find_member_account = 'Find Account Info';
	$lang->cmd_resend_auth_mail = 'Request for Activation Mail';

    $lang->cmd_member_list = 'Member List';
    $lang->cmd_module_config = 'Default Setting';
    $lang->cmd_member_group = 'Member Groups';
    $lang->cmd_send_mail = 'Send Mail';
    $lang->cmd_manage_id = 'Prohibited IDs';
    $lang->cmd_manage_form = 'Sign up Form';
    $lang->cmd_view_own_document = 'Written Articles';
    $lang->cmd_manage_member_info = 'Manage Member Info';
    $lang->cmd_trace_document = 'Trace Written Articles';
    $lang->cmd_trace_comment = 'Trace Written Comments';
    $lang->cmd_view_scrapped_document = 'Scraps';
    $lang->cmd_view_saved_document = 'Saved Articles';
    $lang->cmd_send_email = 'Send Mail';

    $lang->msg_email_not_exists = "You have entered an invalid email address";

    $lang->msg_alreay_scrapped = 'This article is already scrapped';

    $lang->msg_cart_is_null = 'Please select the target';
    $lang->msg_checked_file_is_deleted = '%d attached file(s) is(are) deleted';

    $lang->msg_find_account_title = 'Account Info';
    $lang->msg_find_account_info = 'This is requested account info.';
    $lang->msg_find_account_comment = 'The password will be modified as above one as you click below link.<br />Please modify the password after login.';
    $lang->msg_confirm_account_title = 'Zeroboard XE Account Activation';
    $lang->msg_confirm_account_info = 'This is your account information:';
    $lang->msg_confirm_account_comment = 'Click on the followed link to complete your account activation.';
    $lang->msg_auth_mail_sent = 'The activation mail has been sent to %s. Please check your mail.';
    $lang->msg_confirm_mail_sent = 'We just sent the activation email to %s. Please check your mail.';
    $lang->msg_invalid_auth_key = 'This is an invalid request of verification.<br />Please retry finding account info or contact to administrator.';
    $lang->msg_success_authed = 'Your account has been successfully activated and logged on.\n Please modify the password to your own one with the password in the mail.';
    $lang->msg_success_confirmed = 'Your account has been activated successfully.';

    $lang->msg_new_member = 'Add Member';
    $lang->msg_update_member = 'Modify Member Info';
    $lang->msg_leave_member = 'Leave';
    $lang->msg_group_is_null = 'There is no group';
    $lang->msg_not_delete_default = 'Default items cannot be deleted';
    $lang->msg_not_exists_member = "Invalid member";
    $lang->msg_cannot_delete_admin = 'Admin ID cannot be deleted. Please remove the ID from administration and try again.';
    $lang->msg_exists_user_id = 'This ID already exists. Please try with another one.';
    $lang->msg_exists_email_address = 'This email address already exists. Please try with another one.';
    $lang->msg_exists_nick_name = 'This nickname already exists. Please try with another one.';
    $lang->msg_signup_disabled = 'You are not able to sign up';
    $lang->msg_already_logged = 'You have already signed up';
    $lang->msg_not_logged = 'Please sign in first';
    $lang->msg_insert_group_name = 'Please input the name of group';
    $lang->msg_check_group = 'Please select the group';

    $lang->msg_not_uploaded_profile_image = 'Profile image could not be registered';
    $lang->msg_not_uploaded_image_name = 'Image name could not be registered';
    $lang->msg_not_uploaded_image_mark = 'Image mark could not be registered';
    $lang->msg_not_uploaded_group_image_mark = 'Group image mark could not be registered';

    $lang->msg_accept_agreement = 'You have to agree with the agreement';

    $lang->msg_user_denied = 'You have entered a prohibited ID';
    $lang->msg_user_not_confirmed = 'Your account is not activated yet. Please check your email.';
    $lang->msg_user_limited = 'You have entered an ID that can be used after %s';

    $lang->about_user_id = 'User ID should be 3~20 letters long and consist of alphabet+number with alphabet as first letter.';
    $lang->about_password = 'Password should be 6~20 letters long';
    $lang->about_user_name = 'Name should be 2~20 letters long';
    $lang->about_nick_name = 'Nickname should be 2~20 letters long';
    $lang->about_email_address = 'Email address will be used to modify/find password after email verification';
    $lang->about_homepage = 'Please input if you have your websites';
    $lang->about_blog_url = 'Please input if you have your blogs';
    $lang->about_birthday = 'Please input your birth date';
    $lang->about_allow_mailing = "If you don't join mailing, you will not able to receive group mail";
    $lang->about_denied = 'Check to prohibit the ID';
    $lang->about_is_admin = 'Check to give Superadmin permission';
    $lang->about_member_description = "Administrator's memo about members";
    $lang->about_group = 'An ID can belong to many groups';

    $lang->about_column_type = 'Please set the format of additional sign up form';
    $lang->about_column_name = 'Please input English name that can be used in template (name as variable)';
    $lang->about_column_title = 'This will be displayed on sign up or modifying/viewing member info form';
    $lang->about_default_value = 'You can set default values';
    $lang->about_active = 'You have to check on active items to show on sign up form';
    $lang->about_form_description = 'If you input in description form, it will be displayed on join form';
    $lang->about_required = 'If you check, it will be essential item for sign up';

    $lang->about_enable_openid = 'Please check if you want to provide OpenID service';
    $lang->about_enable_join = 'Please check if you want to allow new members to sign up your site';
    $lang->about_enable_confirm = 'Please check if you want new members to activate their accounts via their emails.';
    $lang->about_enable_ssl = 'Personal information from Sign up/Modify Member Info/Sign in can be sent as SSL(https) mode if server provides SSL service.';
    $lang->about_limit_day = 'You can limit activation date after sign up';
    $lang->about_limit_date = 'Users cannot sign in until assigned date';
    $lang->about_after_login_url = 'You can set URL after login. Blank means current page.';
    $lang->about_after_logout_url = 'You can set URL after logout. Blank means current page.';
    $lang->about_redirect_url = 'Please input URL where users will go after sign up. When this is empty, it will be set as the previous page of sign up page.';
    $lang->about_agreement = "Sign up Agreement will be displayed if it's not empty";

    $lang->about_image_name = "Members will be able to use image name instead of text";
    $lang->about_image_mark = "Members will be able to use image mark in front of their names";
    $lang->about_group_image_mark = "You may use group marks shown before their names";
    $lang->about_profile_image = 'Members will be able to use profile images';
    $lang->about_accept_agreement = "I have read the agreement and agree with it";

    $lang->about_member_default = 'It will be set as default group on sign up';

    $lang->about_openid = 'When you join as OpenID, basic info like ID or email address will be saved on this site, process for password and verification management will be done on current OpenID offering service';
    $lang->about_openid_leave = 'The secession of OpenID means deletion of your member info from this site.<br />If you login after secession, it will be recognized as a new member, so you will no longer have the permission for your ex-written articles.';

    $lang->about_member = "Member module will help you create, modify and remove members and manage groups or sign up form.\nYou can add a custom group to manage members, and also get additional information by modifying sign up form.";
    $lang->about_find_member_account = 'Your account info will be noticed by registered email address.<br />Please input email address which you have input on registration, and press "Find Account Info" button.<br />';
	$lang->about_ssl_port = 'Please input if you are using non-default SSL port';
    $lang->add_openid = 'Add OpenID';

	$lang->about_resend_auth_mail = 'You can request for activation mail if you have not activated before';
    $lang->no_article = 'There exists no article';
?>
