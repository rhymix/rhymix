<?php
    /**
     * @file   fr.lang.php
     * @author zero (zero@nzeo.com)  Traduit par Pierre Duvent (PierreDuvent@gmail.com)
     * @brief  Paquet de la langue française (Choses fondamentales seulement) 
     **/

    $lang->member = 'Membre';
    $lang->member_default_info = 'Informations fondamentales';
    $lang->member_extend_info = 'Informations additionnelles';
    $lang->default_group_1 = "Membre Associé";
    $lang->default_group_2 = "Membre Régulier";
    $lang->admin_group = "Groupe des administrateurs";
    $lang->keep_signed = 'Gardez la session ouverte';
    $lang->remember_user_id = 'Mémorisez mon Compte';
    $lang->already_logged = "Vous avez déjà ouvert une session";
    $lang->denied_user_id = 'Vous avez entré un comte interdit.';
    $lang->null_user_id = 'Entrez votre compte';
    $lang->null_password = 'Entrez le mot de passe';
    $lang->invalid_authorization = 'Votre compte n\'est pas certifié.';
    $lang->invalid_user_id= "Vous avez entré un compte invalide";
    $lang->invalid_password = 'Vous avez entré un mot de passe invalide';
    $lang->allow_mailing = 'Joindre au Mailing';
    $lang->denied = 'Interdit';
    $lang->is_admin = 'Permission Superadministrative';
    $lang->group = 'Groupe assigné';
    $lang->group_title = 'Nom du Groupe';
    $lang->group_srl = 'Numéro du Groupe';
    $lang->signature = 'Signature';
    $lang->profile_image = 'Image du profil';
    $lang->profile_image_max_width = 'Largeur Maximum';
    $lang->profile_image_max_height = 'Hauteur Maximum';
    $lang->image_name = 'Nom en Image';
    $lang->image_name_max_width = 'Largeur Maximum';
    $lang->image_name_max_height = 'Hauteur Maximum';
    $lang->image_mark = 'Marque en Image';
    $lang->image_mark_max_width = 'Largeur Maximum';
    $lang->image_mark_max_height = 'Hauteur Maximum';
	$lang->signature_max_height = 'Hauteur Maximum de la Signature';
    $lang->enable_openid = 'Permettre OpenID';
    $lang->enable_join = 'Permettre à s\'inscrire';
    $lang->enable_confirm = 'Utiliser Authentification par mél';
	$lang->enable_ssl = 'Utiliser SSL';
	$lang->security_sign_in = 'Ouvrir une Session en utilisant sécurité rehaussé';
    $lang->limit_day = 'Jour de Limite Temporaire';
    $lang->limit_date = 'Jour de Limite';
    $lang->after_login_url = 'URL après la connexion';
    $lang->after_logout_url = 'URL après la déconnexion ';
    $lang->redirect_url = 'URL après Inscription';
    $lang->agreement = 'Accord d\'Inscription comme Membre';
	$lang->accept_agreement = 'D\'accord';
    $lang->member_info = 'Informations du Membre';
    $lang->current_password = 'Mot de Passe courrant';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = "Nom de Webmestre";
    $lang->webmaster_email = "Mél de Webmestre";

    $lang->about_keep_signed = 'Malgré que le navigateur est fermé, votre session peut être ouverte. \n\nSi vous utilisez cette fonction sur l\'ordinateur commun, vos informations privé peut être exposé. Nous vous recommandons de ne pas utiliser cette fonctions sur l\'ordinateur commun.';
    $lang->about_webmaster_name = "Entrez le nom de webmestre qui va être utilisé pour le mél de certification ou l\'autre administration du site. (défaut : webmestre)";
    $lang->about_webmaster_email = "Entrez l\'address du mél de webmestre, S.V.P.";

    $lang->search_target_list = array(
        'user_id' => 'Compte',
        'user_name' => 'Nom',
        'nick_name' => 'Surnom',
        'email_address' => 'Mél',
        'regdate' => 'Jour d\'enregistrer',
        'last_login' => 'Jour de la connexion dernière',
        'extra_vars' => 'Variables extra ',
    );

    $lang->cmd_login = 'Login';
    $lang->cmd_logout = 'Logout';
    $lang->cmd_signup = 'Join';
    $lang->cmd_modify_member_info = 'Modify Member Info';
    $lang->cmd_modify_member_password = 'Modify Password';
    $lang->cmd_view_member_info = 'Member Info';
    $lang->cmd_leave = 'Leave';
    $lang->cmd_find_member_account = 'Find Account Info';

    $lang->cmd_member_list = 'Member List';
    $lang->cmd_module_config = 'Default Setting';
    $lang->cmd_member_group = 'Manage Groups';
    $lang->cmd_send_mail = 'Send Mail';
    $lang->cmd_manage_id = 'Manage Prohibited IDs';
    $lang->cmd_manage_form = 'Manage Join Form';
    $lang->cmd_view_own_document = 'Written Articles';
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
    $lang->msg_confirm_account_title = 'Authentication confirmation mail';
    $lang->msg_confirm_account_info = 'This is the registered account information:';
    $lang->msg_confirm_account_comment = 'Click the following confirmation link to complete your sign up.';
    $lang->msg_auth_mail_sent = 'The certification mail has been sent to %s. Please check your mail.';
    $lang->msg_confirm_mail_sent = 'We just sent you a confirmation email to %s. Click on the confirmation link in the email to complete your sign up.';
    $lang->msg_invalid_auth_key = 'This is an invalid request of certification.<br />Please retry finding account info or contact to administrator.';
    $lang->msg_success_authed = 'Your account has been successfully certificated and logged on.\n Please modify the password to your own one with the password in the mail.';
    $lang->msg_success_confirmed = 'The autentication completed successfully.';

    $lang->msg_new_member = 'Add Member';
    $lang->msg_update_member = 'Modify Member Info';
    $lang->msg_leave_member = 'Leave';
    $lang->msg_group_is_null = 'There is no registered group';
    $lang->msg_not_delete_default = 'Default items cannot be deleted';
    $lang->msg_not_exists_member = "Invalid member";
    $lang->msg_cannot_delete_admin = 'Admin ID cannot be deleted. Please remove the ID from administration and try again.';
    $lang->msg_exists_user_id = 'This ID already exists. Please try with another one.';
    $lang->msg_exists_email_address = 'This email address already exists. Please try with another one.';
    $lang->msg_exists_nick_name = 'This nickname already exists. Please try with another one.';
    $lang->msg_signup_disabled = 'You are not able to sign up';
    $lang->msg_already_logged = 'You have already signed up';
    $lang->msg_not_logged = 'Please login first';
    $lang->msg_insert_group_name = 'Please input the name of group';
    $lang->msg_check_group = 'Please select the group';

    $lang->msg_not_uploaded_profile_image = 'Profile image could not be registered';
    $lang->msg_not_uploaded_image_name = 'Image name could not be registered';
    $lang->msg_not_uploaded_image_mark = 'Image mark could not be registered';

    $lang->msg_accept_agreement = 'You have to agree the agreement'; 

    $lang->msg_user_denied = 'You have entered a prohibited ID';
    $lang->msg_user_not_confirmed = 'You are not authenticated yet. Please check your email.';
    $lang->msg_user_limited = 'You have entered an ID that can be used after %s';

    $lang->about_user_id = 'User ID should be 3~20 letters long and consist of alphabet+number with alphabet as first letter.';
    $lang->about_password = 'Password should be 6~20 letters long';
    $lang->about_user_name = 'Name should be 2~20 letters long';
    $lang->about_nick_name = 'Nickname should be 2~20 letters long';
    $lang->about_email_address = 'Email address will be used to modify/find password after email certification';
    $lang->about_homepage = 'Please input if you have your websites';
    $lang->about_blog_url = 'Please input if you have your blogs';
    $lang->about_birthday = 'Please input your birth date';
    $lang->about_allow_mailing = "If you don't join mailing, you will not able to receive group mail";
    $lang->about_denied = 'Check to prohibit the ID';
    $lang->about_is_admin = 'Check to give Superadmin permission';
    $lang->about_description = "Administrator's memo about members";
    $lang->about_group = 'An ID can belong to many groups';

    $lang->about_column_type = 'Please set the format of additional sign up form';
    $lang->about_column_name = 'Please input English name that can be used in template (name as variable)';
    $lang->about_column_title = 'This will be displayed on sign up or modifying/viewing member info form';
    $lang->about_default_value = 'You can set default values';
    $lang->about_active = 'You have to check on active items to show on sign up form';
    $lang->about_form_description = 'If you input in description form, it will be displayed on sign up form';
    $lang->about_required = 'If you check, it will be essential item for sign up';

    $lang->about_enable_openid = 'Allow users to sign up as OpenID';
    $lang->about_enable_join = 'Allow users to sign up';
    $lang->about_enable_confirm = 'Send confirmation email to complete signup.';
    $lang->about_enable_ssl = 'Personal information from Join/Modify Member Info/Login can be sent as SSL(https) mode if server provides SSL service.';
    $lang->about_limit_day = 'You can limit certification date after sign up';
    $lang->about_limit_date = 'User cannot login until assigned date';
    $lang->about_after_login_url = 'You can set URL after login. Blank means current page.';
    $lang->about_after_logout_url = 'You can set URL after logout. Blank means current page.';
    $lang->about_redirect_url = 'Please input URL where users will go after sign up. When this is empty, it will be set as the previous page of sign up page.';
    $lang->about_agreement = "Sign up agreement will only be displayed when it's not empty";

    $lang->about_image_name = "Allow users to use image name instead of text name";
    $lang->about_image_mark = "Allow users to use mark in front of their names";
    $lang->about_profile_image = 'Allow users to use profile images';
    $lang->about_accept_agreement = "I have read the agreement and agree"; 

    $lang->about_member_default = 'It will be set as default group on sign up';

    $lang->about_openid = 'When you join as OpenID, basic info like ID or email address will be saved on this site, process for password and certification management will be done on current OpenID offering service';
    $lang->about_openid_leave = 'The secession of OpenID means deletion of your member info from this site.<br />If you login after secession, it will be recognized as a new member, so you will no longer have the permission for your ex-written articles.';

    $lang->about_member = "This is a module for creating/modifying/deleting members and managing group or join form.\nYou can manage members by creating new groups, and get additional information by managing join form";
    $lang->about_find_member_account = 'Your account info will be noticed by registered email address.<br />Please input email address which you have input on registration, and press "Find Account Info" button.<br />';
?>
