<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  简体中文语言包 (只收录基本内容)
     **/

    $lang->member = '会员';
    $lang->member_default_info = '基本信息';
    $lang->member_extend_info = '扩展信息';
    $lang->default_group_1 = "准会员";
    $lang->default_group_2 = "正会员";
    $lang->admin_group = "管理组";
    $lang->keep_signed = '自动登录';
    $lang->remember_user_id = '保存ID';
    $lang->already_logged = '您已经登录！';
    $lang->denied_user_id = '被禁止的用户名。';
    $lang->null_user_id = '请输入用户名。';
    $lang->null_password = '请输入密码。';
    $lang->invalid_authorization = '还没有认证！';
    $lang->invalid_user_id= '该用户名不存在，请检查您的输入是否有误！';
    $lang->invalid_password = '您的密码不正确！';
    $lang->allow_mailing = '接收邮件';
    $lang->allow_message = '允许接收短消息';
    $lang->allow_message_type = array(
             'Y' => '全部接收',
             'N' => '拒收',
             'F' => '只允许好友',
        );
    $lang->denied = '禁止使用';
    $lang->is_admin = '最高管理权限';
    $lang->group = '用户组';
    $lang->group_title = '用户组标题';
    $lang->group_srl = '用户组编号';
    $lang->signature = '签名';
    $lang->profile_image = '签名图片';
    $lang->profile_image_max_width = '宽度限制';
    $lang->profile_image_max_height = '高度限制';
    $lang->image_name = '昵称图片';
    $lang->image_name_max_width = '宽度限制';
    $lang->image_name_max_height = '高度限制';
    $lang->image_mark = '用户图标';
    $lang->image_mark_max_width = '宽度限制';
    $lang->image_mark_max_height = '高度限制';
    $lang->signature_max_height = '签名高度限制';
    $lang->enable_openid = '支持OpenID';
    $lang->enable_join = '允许会员注册';
    $lang->enable_ssl = 'SSL 기능 사용';
    $lang->security_sign_in = 'Sign in using enhanced security';
    $lang->limit_day = '认证限制';
    $lang->limit_date = '限制日期';
    $lang->after_login_url = '登录后页面转向';
    $lang->after_logout_url = '退出后页面转向';
    $lang->redirect_url = '注册会员后页面转向';
    $lang->agreement = '会员注册条款';
    $lang->accept_agreement = '同意条款';
    $lang->sender = '寄件人';
    $lang->receiver = '收件人';
    $lang->friend_group = '好友组';
    $lang->default_friend_group = '组未指定';
    $lang->member_info = '会员信息';
    $lang->current_password = '当前密码';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = '管理员名';
    $lang->webmaster_email = '管理员电子邮件';

    $lang->about_keep_signed = '关闭浏览器后也将维持登录状态。\n\n使用自动登录功能，可解决每次访问都要输入用户名及密码的麻烦。\n\n为防止个人信息泄露，在网吧，学校等公共场所请务必要确认解除登录状态。';
	$lang->about_webmaster_name = '请输入认证所需的电子邮件地址或管理其他网站时要使用的网站管理员名称。(默认 : webmaster)';
    $lang->about_webmaster_email = '请输入网站管理员的电子邮件地址。';

    $lang->search_target_list = array(
        'user_id' => '用户名',
        'user_name' => '姓名',
        'nick_name' => '昵称',
        'email_address' => '邮箱地址',
        'regdate' => '注册日期',
        'last_login' => '最近登录',
        'extra_vars' => '扩展变数',
    );

    $lang->message_box = array(
        'R' => '收件箱',
        'S' => '发件箱',
        'T' => '保管箱',
    );

    $lang->readed_date = "阅读日期"; 

    $lang->cmd_login = '登录';
    $lang->cmd_logout = '退出';
    $lang->cmd_signup = '新会员注册';
    $lang->cmd_modify_member_info = '修改会员信息';
    $lang->cmd_modify_member_password = '修改密码';
    $lang->cmd_view_member_info = '查看会员信息';
    $lang->cmd_leave = '注销';
    $lang->cmd_find_member_account = '查找用户名/密码';

    $lang->cmd_member_list = '会员目录';
    $lang->cmd_module_config = '基本设置';
    $lang->cmd_member_group = '用户组管理';
    $lang->cmd_send_mail = '发送邮件';
    $lang->cmd_manage_id = '禁止用户名管理';
    $lang->cmd_manage_form = '注册表单管理';
    $lang->cmd_view_own_document = '查看我的帖子';
    $lang->cmd_view_scrapped_document = '查看收藏';
    $lang->cmd_view_saved_document = '查看临时保存箱';
    $lang->cmd_send_email = '发送邮件';
    $lang->cmd_send_message = '发送短消息';
    $lang->cmd_reply_message = '回复短消息';
    $lang->cmd_view_friend = '查看好友';
    $lang->cmd_add_friend = '加为好友';
    $lang->cmd_view_message_box = '查看短信箱';
    $lang->cmd_store = "保管";
    $lang->cmd_add_friend_group = '添加好友组';
    $lang->cmd_rename_friend_group = '修改好友组名称';

    $lang->msg_email_not_exists = "没有找到您输入的Email地址。";

    $lang->msg_alreay_scrapped = '已收藏的主题！';

    $lang->msg_cart_is_null = '请选择对象。';
    $lang->msg_checked_file_is_deleted = '已删除%d个附件。';

    $lang->msg_find_account_title = '注册信息。';
    $lang->msg_find_account_info = '您要查找的注册信息如下。';
    $lang->msg_find_account_comment = '点击下面的链接您的注册密码将更新为上述的系统自动生成密码。<br />请重新登录后把密码改为您所熟悉的密码。';
    $lang->msg_auth_mail_sended = '已向%s发送了认证邮件。请确认！！';
    $lang->msg_invalid_auth_key = '错误的注册信息请求。<br />请重新查找用户名及密码， 或联系管理员。';
    $lang->msg_success_authed = '新的注册信息已得到认证。请用邮件中的新密码修改您要想使用的密码。';

    $lang->msg_no_message = '没有短消息。';
    $lang->message_received = '您有新消息。';

    $lang->msg_new_member = '会员注册';
    $lang->msg_update_member = '修改会员信息';
    $lang->msg_leave_member = '注销会员';
    $lang->msg_group_is_null = '没有用户组。';
    $lang->msg_not_delete_default = '不能删除基本项目';
    $lang->msg_not_exists_member = '不存在的用户';
    $lang->msg_cannot_delete_admin = '不能删除管理员 ID .解除管理后再删除';
    $lang->msg_exists_user_id = '重复的用户名 ，请重新输入用户名。';
    $lang->msg_exists_email_address = '重复的电子邮件地址，请重新输入电子邮件地址。';
    $lang->msg_exists_nick_name = '重复的昵称，请重新输入昵称。';
    $lang->msg_signup_disabled = '不能注册会员';
    $lang->msg_already_logged = '您是注册会员。';
    $lang->msg_not_logged = '您还没有登录。';
    $lang->msg_title_is_null = '请输入短消息标题。';
    $lang->msg_content_is_null = '请输入内容。';
    $lang->msg_allow_message_to_friend = '因其为只允许接收好友短消息的用户，所以不能发送短消息。';
    $lang->msg_disallow_message = '因其为拒绝接收短消息的用户，所以不能发送短消息。';
    $lang->msg_insert_group_name = '请输入组名称';
    $lang->msg_check_group = '请选择组';

    $lang->msg_not_uploaded_profile_image = '不能登录签名图像！';
    $lang->msg_not_uploaded_image_name = '不能登录昵称图像！';
    $lang->msg_not_uploaded_image_mark = '不能登录用户图标！';

    $lang->msg_accept_agreement = '您必须同意条款。'; 

    $lang->msg_user_denied = '您输入的用户名已禁止使用！';
    $lang->msg_user_limited = '您输入的用户名%s以后才可以开始使用。';

    $lang->about_user_id = '用户名长度必须由 3 ~20 字以内的英文+数字组成，且首个字母必须是英文字母。';
    $lang->about_password = '密码长度必须在6~20字以内。';
    $lang->about_user_name = '姓名必须是2~20字以内。';
    $lang->about_nick_name = '昵称必须是2~20字以内。';
    $lang->about_email_address = '电子邮件地址除邮件认证外，在修改密码或找回密码时使用。';
    $lang->about_homepage = '请输入您的主页地址。';
    $lang->about_blog_url = '请输入博客地址。';
    $lang->about_birthday = '请输入您的出生年月日。';
    $lang->about_allow_mailing = '如不选择此项，以后不能接收站内发送的重要信息。';
    $lang->about_allow_message = '可以选择短消息接收与否。';
    $lang->about_denied = '选择时不能使用此用户名。';
    $lang->about_is_admin = '选择时将具有最高管理权限。';
    $lang->about_description = '管理员对会员的笔记。';
    $lang->about_group = '一个用户名可属多个用户组。';

    $lang->about_column_type = '请选择要添加的注册表单格式。';
    $lang->about_column_name = '请输入在模板中可以使用的英文名称。（变数名）';
    $lang->about_column_title = '注册或修改/查看信息时要显示的标题。';
    $lang->about_default_value = '可以设置缺省值。';
    $lang->about_active = '必须选择此项后才可以正常启用。';
    $lang->about_form_description = '说明栏里输入的内容，注册时将会显示。';
    $lang->about_required = '注册时成为必填项目。';

    $lang->about_enable_openid = '要想网站支持OpenID时请勾选此项。';
    $lang->about_enable_join = '选择此项后用户才可以注册。';
    $lang->about_enable_ssl = '서버에서 SSL지원이 될 경우 회원가입/정보수정/로그인등의 개인정보가 서버로 보내질때 SSL(https)를 이용하도록 할 수 있습니다';
    $lang->about_limit_day = '注册会员后的认证有效期限。';
    $lang->about_limit_date = '直到指定日期该用户不能登录。';
    $lang->about_after_login_url = '可以指定登录后的页面转向url(留空为当前页面)。';
    $lang->about_after_logout_url = '可以指定退出登录后的页面转向url(留空为当前页面)。';
    $lang->about_redirect_url = '请输入注册会员后的页面转向 url。(留空为返回前页)';
    $lang->about_agreement = '没有会员条款时不显示。';

    $lang->about_image_name = '用户昵称可以用小图片来替代显示。';
    $lang->about_image_mark = '显示在用户昵称前的小图标。';
    $lang->about_profile_image = '可以使用签名图片。';
    $lang->about_signature_max_height = '可以限制签名栏高度(0或留空为不限制)。';
    $lang->about_accept_agreement = '已阅读全部条款并同意。'; 

    $lang->about_member_default = '将成为注册会员时的默认用户组。';

    $lang->about_openid = '用OpenID注册时该网站只保存用户名和 邮件等基本信息，密码和认证处理是在提供OpenID服务的站点中得到解决。';
    $lang->about_openid_leave = '删除OpenID就等于永久删除站内用户的信息。<br />被删除后的重新登录就等于新会员注册，因此对以前自己写的主题将失去相应权限。';
    $lang->about_find_member_account = '用户名/密码将发送到您注册时所输入的电子邮件当中。<br />输入注册时的电子邮件地址后，请点击“查找用户名/密码”按钮。<br />';

    $lang->about_member = "可以添加/修改/删除会员及管理用户组或注册表单的会员管理模块。\n此模块不仅可以生成缺省用户组以外的其他用户组来管理会员，并且通过注册表单的管理获得除会员基本信息以外的扩展信息。";
?>
