<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  ??中文?言包 (只收?基本?容)
     **/

    $lang->member = '??';
    $lang->member_default_info = '基本信息';
    $lang->member_extend_info = '?展信息';
    $lang->default_group_1 = "准??";
    $lang->default_group_2 = "正??";
    $lang->admin_group = "管理?";
    $lang->keep_signed = '自?登?';
    $lang->remember_user_id = '保存ID';
    $lang->already_logged = '?已?登?！';
    $lang->denied_user_id = '被禁止的用?名。';
    $lang->null_user_id = '??入用?名。';
    $lang->null_password = '??入密?。';
    $lang->invalid_authorization = '??有??！';
    $lang->invalid_user_id= '?用?名不存在，????的?入是否有?！';
    $lang->invalid_password = '?的密?不正?！';
    $lang->allow_mailing = '接收?件';
    $lang->denied = '禁止使用';
    $lang->is_admin = '最高管理?限';
    $lang->group = '用??';
    $lang->group_title = '用????';
    $lang->group_srl = '用????';
    $lang->signature = '?名';
    $lang->profile_image = '?名?片';
    $lang->profile_image_max_width = '?度限制';
    $lang->profile_image_max_height = '高度限制';
    $lang->image_name = '???片';
    $lang->image_name_max_width = '?度限制';
    $lang->image_name_max_height = '高度限制';
    $lang->image_mark = '用???';
    $lang->image_mark_max_width = '?度限制';
    $lang->image_mark_max_height = '高度限制';
    $lang->signature_max_height = '?名高度限制';
    $lang->enable_openid = '支持OpenID';
    $lang->enable_join = '允???注?';
    $lang->enable_confirm = '使用?件??';
    $lang->enable_ssl = '使用SSL功能';
    $lang->security_sign_in = '使用安全登?';
    $lang->limit_day = '??限制';
    $lang->limit_date = '限制日期';
    $lang->after_login_url = '登?后?面?向';
    $lang->after_logout_url = '退出后?面?向';
    $lang->redirect_url = '注???后?面?向';
    $lang->agreement = '??注??款';
    $lang->accept_agreement = '同意?款';
    $lang->member_info = '??信息';
    $lang->current_password = '?前密?';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = '管理?名';
    $lang->webmaster_email = '管理??子?件';

    $lang->about_keep_signed = '????器后也??持登???。\n\n使用自?登?功能，可解?每次??都要?入用?名及密?的麻?。\n\n?防止?人信息泄露，在??，?校等公共?所??必要??解除登???。';
    $lang->about_webmaster_name = '??入??所需的?子?件地址或管理其他?站?要使用的?站管理?名?。(默? : webmaster)';
    $lang->about_webmaster_email = '??入?站管理?的?子?件地址。';

    $lang->search_target_list = array(
        'user_id' => '用?名',
        'user_name' => '姓名',
        'nick_name' => '??',
        'email_address' => '?箱地址',
        'regdate' => '注?日期',
        'last_login' => '最近登?',
        'extra_vars' => '?展??',
    );


    $lang->cmd_login = '登?';
    $lang->cmd_logout = '退出';
    $lang->cmd_signup = '新??注?';
    $lang->cmd_modify_member_info = '修改??信息';
    $lang->cmd_modify_member_password = '修改密?';
    $lang->cmd_view_member_info = '?看??信息';
    $lang->cmd_leave = '注?';
    $lang->cmd_find_member_account = '??用?名/密?';

    $lang->cmd_member_list = '??目?';
    $lang->cmd_module_config = '基本?置';
    $lang->cmd_member_group = '用??管理';
    $lang->cmd_send_mail = '?送?件';
    $lang->cmd_manage_id = '禁止用?名管理';
    $lang->cmd_manage_form = '注?表?管理';
    $lang->cmd_view_own_document = '?看?表主?';
    $lang->cmd_trace_document = '主?追踪';
    $lang->cmd_trace_comment = '??追踪';
    $lang->cmd_view_scrapped_document = '?看收藏';
    $lang->cmd_view_saved_document = '?看??保存箱';
    $lang->cmd_send_email = '?送?件';

    $lang->msg_email_not_exists = "?有?到??入的Email地址。";

    $lang->msg_alreay_scrapped = '已收藏的主?！';

    $lang->msg_cart_is_null = '????象。';
    $lang->msg_checked_file_is_deleted = '已?除%d?附件。';

    $lang->msg_find_account_title = '注?信息。';
    $lang->msg_find_account_info = '?要??的注?信息如下。';
    $lang->msg_find_account_comment = '点?下面的?接?的注?密??更新?上述的系?自?生成密?。<br />?重新登?后把密?改??所熟悉的密?。';
    $lang->msg_confirm_account_title = '??注?';
    $lang->msg_confirm_account_info = '?的注?信息如下:';
    $lang->msg_confirm_account_comment = '?点?下面?接完成????。';
    $lang->msg_auth_mail_sent = '已向%s?送了???件。???！！';
    $lang->msg_confirm_mail_sent = '已向%s?送了???件。???！！';
    $lang->msg_invalid_auth_key = '??的注?信息?求。<br />?重新??用?名及密?， 或?系管理?。';
    $lang->msg_success_authed = '新的注?信息已得到??。?用?件中的新密?修改?要想使用的密?。';
    $lang->msg_success_confirmed = '注?信息已成功激活！';

    $lang->msg_new_member = '??注?';
    $lang->msg_update_member = '修改??信息';
    $lang->msg_leave_member = '注???';
    $lang->msg_group_is_null = '?有用??。';
    $lang->msg_not_delete_default = '不能?除基本?目';
    $lang->msg_not_exists_member = '不存在的用?';
    $lang->msg_cannot_delete_admin = '不能?除管理? ID .解除管理后再?除';
    $lang->msg_exists_user_id = '重?的用?名 ，?重新?入用?名。';
    $lang->msg_exists_email_address = '重?的?子?件地址，?重新?入?子?件地址。';
    $lang->msg_exists_nick_name = '重?的??，?重新?入??。';
    $lang->msg_signup_disabled = '不能注???';
    $lang->msg_already_logged = '?是注???。';
    $lang->msg_not_logged = '???有登?。';
    $lang->msg_insert_group_name = '??入?名?';
    $lang->msg_check_group = '????';

    $lang->msg_not_uploaded_profile_image = '不能登??名?像！';
    $lang->msg_not_uploaded_image_name = '不能登????像！';
    $lang->msg_not_uploaded_image_mark = '不能登?用???！';

    $lang->msg_accept_agreement = '?必?同意?款。'; 

    $lang->msg_user_denied = '??入的用?名已禁止使用！';
    $lang->msg_user_not_confirmed = '?的注?信息??有被激活，????的?子?箱。';
    $lang->msg_user_limited = '??入的用?名%s以后才可以?始使用。';

    $lang->about_user_id = '用?名?度必?由 3 ~20 字以?的英文+?字?成，且首?字母必?是英文字母。';
    $lang->about_password = '密??度必?在6~20字以?。';
    $lang->about_user_name = '姓名必?是2~20字以?。';
    $lang->about_nick_name = '??必?是2~20字以?。';
    $lang->about_email_address = '?子?件地址除?件??外，在修改密?或?回密??使用。';
    $lang->about_homepage = '??入?的主?地址。';
    $lang->about_blog_url = '??入博客地址。';
    $lang->about_birthday = '??入?的出生年月日。';
    $lang->about_allow_mailing = '如不??此?，以后不能接收站??送的重要信息。';
    $lang->about_denied = '???不能使用此用?名。';
    $lang->about_is_admin = '????具有最高管理?限。';
    $lang->about_member_description = '管理????的??。';
    $lang->about_group = '一?用?名可?多?用??。';

    $lang->about_column_type = '???要添加的注?表?格式。';
    $lang->about_column_name = '??入在模板中可以使用的英文名?。（??名）';
    $lang->about_column_title = '注?或修改/?看信息?要?示的??。';
    $lang->about_default_value = '可以?置缺省?。';
    $lang->about_active = '必???此?后才可以正常?用。';
    $lang->about_form_description = '?明?里?入的?容，注?????示。';
    $lang->about_required = '注??成?必??目。';

    $lang->about_enable_openid = '要想?站支持OpenID??勾?此?。';
    $lang->about_enable_join = '??此?后用?才可以注?。';
    $lang->about_enable_confirm = '?激活??注?信息，?向???入的?件地址?送注????件。';
    $lang->about_enable_ssl = '如服?器提供SSL??服?，新??注?/修改??信息/登?等信息的?送?使用SSL(https)??。';
    $lang->about_limit_day = '注???后的??有效期限。';
    $lang->about_limit_date = '直到指定日期?用?不能登?。';
    $lang->about_after_login_url = '可以指定登?后的?面?向url(留空??前?面)。';
    $lang->about_after_logout_url = '可以指定退出登?后的?面?向url(留空??前?面)。';
    $lang->about_redirect_url = '??入注???后的?面?向 url。(留空?返回前?)';
    $lang->about_agreement = '?有???款?不?示。';

    $lang->about_image_name = '用???可以用小?片?替代?示。';
    $lang->about_image_mark = '?示在用???前的小??。';
    $lang->about_profile_image = '可以使用?名?片。';
    $lang->about_signature_max_height = '可以限制?名?高度(0或留空?不限制)。';
    $lang->about_accept_agreement = '已??全部?款?同意。'; 

    $lang->about_member_default = '?成?注????的默?用??。';

    $lang->about_openid = '用OpenID注????站只保存用?名和 ?件等基本信息，密?和???理是在提供OpenID服?的站点中得到解?。';
    $lang->about_openid_leave = '?除OpenID就等于永久?除站?用?的信息。<br />被?除后的重新登?就等于新??注?，因此?以前自己?的主??失去相??限。';
    $lang->about_find_member_account = '用?名/密???送到?注??所?入的?子?件?中。<br />?入注??的?子?件地址后，?点?“??用?名/密?”按?。<br />';

    $lang->about_member = "可以添加/修改/?除??及管理用??或注?表?的??管理模?。\n此模?不?可以生成缺省用??以外的其他用???管理??，?且通?注?表?的管理?得除??基本信息以外的?展信息。";
?>
