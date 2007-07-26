<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  简体中文语言包 (只收录基本内容)
     **/

    $lang->member = '会员';
    $lang->member_default_info = '基本信息';
    $lang->member_extend_info = '追加信息';
    $lang->default_group_1 = "准会员";
    $lang->default_group_2 = "正会员";
    $lang->admin_group = "管理组";
    $lang->remember_user_id = '保存ID';
    $lang->already_logged = '您已经登录';
    $lang->denied_user_id = '被禁止的用户名。';
    $lang->null_user_id = '请输入用户名。';
    $lang->null_password = '请输入密码。';
    $lang->invalid_authorization = '还没有认证！';
    $lang->invalid_user_id= '不存在的用户名！';
    $lang->invalid_password = '密码有误！';
    $lang->allow_mailing = '接收邮件';
    $lang->allow_message = '允许接收短消息';
    $lang->allow_message_type = array(
             'Y' => '全部接收',
             'N' => '拒收',
             'F' => '只允许好友',
        );
    $lang->denied = '使用中止';
    $lang->is_admin = '最高管理权限';
    $lang->group = '所属组';
    $lang->group_title = '组标题';
    $lang->group_srl = '组编号';
    $lang->signature = '签名';
    $lang->image_name = '昵称图像';
    $lang->image_name_max_width = '宽度限制';
    $lang->image_name_max_height = '高度限制';
    $lang->image_mark = '用户图标';
    $lang->image_mark_max_width = '宽度限制';
    $lang->image_mark_max_height = '高度限制';
    $lang->enable_openid = '支持OpenID';
    $lang->enable_join = '允许会员注册';
    $lang->limit_day = '临时限制日期';
    $lang->limit_date = '限制日期';
    $lang->redirect_url = '注册会员后将移动的页面';
    $lang->agreement = '注册会员条款';
    $lang->accept_agreement = '同意条款';
    $lang->sender = '寄件人';
    $lang->receiver = '收件人';
    $lang->friend_group = '好友组';
    $lang->default_friend_group = '组未指定';
    $lang->member_info = '会员信息';
    $lang->current_password = '当前密码';
    $lang->openid = 'OpenID';

    $lang->search_target_list = array(
        'user_id' => 'I D',
        'user_name' => '姓名',
        'nick_name' => '昵称',
        'email_address' => '邮箱地址',
        'regdate' => '注册日期',
        'last_login' => '最近登录',
    );

    $lang->message_box = array(
        'R' => '收件箱',
        'S' => '发件箱',
        'T' => '保管箱',
    );

    $lang->readed_date = "阅读日期"; 

    $lang->cmd_login = '登录';
    $lang->cmd_logout = '退出';
    $lang->cmd_signup = '注册';
    $lang->cmd_modify_member_info = '修改会员信息';
    $lang->cmd_modify_member_password = '修改密码';
    $lang->cmd_view_member_info = '查看会员信息';
    $lang->cmd_leave = '注销';

    $lang->cmd_member_list = '会员目录';
    $lang->cmd_module_config = '基本设置';
    $lang->cmd_member_group = '管理组';
    $lang->cmd_send_mail = '发送邮件';
    $lang->cmd_manage_id = '禁止用户名管理';
    $lang->cmd_manage_form = '注册样式管理';
    $lang->cmd_view_own_document = '查看我的帖子';
    $lang->cmd_view_scrapped_document = '查看收藏';
    $lang->cmd_send_email = '发送邮件';
    $lang->cmd_send_message = '发送短消息';
    $lang->cmd_reply_message = '回复短消息';
    $lang->cmd_view_friend = '查看好友';
    $lang->cmd_add_friend = '添加好友';
    $lang->cmd_view_message_box = '查看短信箱';
    $lang->cmd_store = "保管";
    $lang->cmd_add_friend_group = '添加好友组';
    $lang->cmd_rename_friend_group = '修改好友组名称';

    $lang->msg_alreay_scrapped = '已收藏的主题！';

    $lang->msg_cart_is_null = '请选择对象。';
    $lang->msg_checked_file_is_deleted = '已删除%d个附件。';

    $lang->msg_no_message = '没有短消息。';
    $lang->message_received = '您有新消息。';

    $lang->msg_new_member = '添加会员';
    $lang->msg_update_member = '修改会员信息';
    $lang->msg_leave_member = '注销会员';
    $lang->msg_group_is_null = '没有登录的组。';
    $lang->msg_not_delete_default = '不能删除基本项目';
    $lang->msg_not_exists_member = '不存在的用户';
    $lang->msg_cannot_delete_admin = '不能删除管理员 ID .解除管理后再删除';
    $lang->msg_exists_user_id = '已存在的 I D ，请输入另一个 I D。';
    $lang->msg_exists_email_address = '已存在的电子邮件地址，请输入另一个电子邮件地址';
    $lang->msg_exists_nick_name = '已存在的昵称，请输入另一个昵称';
    $lang->msg_signup_disabled = '不能注册会员';
    $lang->msg_already_logged = '已注册会员的状态';
    $lang->msg_not_logged = '您还没有登录';
    $lang->msg_title_is_null = '请输入短信标题';
    $lang->msg_content_is_null = '请输入内容';
    $lang->msg_allow_message_to_friend = '您设定为只允许接收好友短信的状态，不能发送短信';
    $lang->msg_disallow_message = '拒绝接收短信状态，不能发送短信';
    $lang->msg_insert_group_name = '请输入组名称';

    $lang->msg_not_uploaded_image_name = '不能登录昵称图像';
    $lang->msg_not_uploaded_image_mark = '不能登录用户图标';

    $lang->msg_accept_agreement = '您必须同意条款'; 

    $lang->msg_user_denied = '您输入的 I D 已中止使用。';
    $lang->msg_user_limited = '您输入的 I D  %s 以后可以开始使用';

    $lang->about_user_id = '用户 I D 长度控制在 3 ~20 字以内的英文+数字组成，首个字母必须是英文字母。';
    $lang->about_password = '密码长度控制在6~20字以内。';
    $lang->about_user_name = '姓名控制在2~20字以内';
    $lang->about_nick_name = '昵称控制在2~20字以内';
    $lang->about_email_address = '电子邮件地址是邮件认证后使用在修改密码或找回密码。';
    $lang->about_homepage = '可以输入您的网站地址';
    $lang->about_blog_url = '有博客的用户请输入';
    $lang->about_birthday = '请输入您的出生年月日';
    $lang->about_allow_mailing = '您不选择接收邮件，以后不能接收站内发送的重要信息。';
    $lang->about_allow_message = '可以决定短信接收与否';
    $lang->about_denied = '选择时不能使用此 I D。';
    $lang->about_is_admin = '选择时具有最高管理员的权限';
    $lang->about_description = '对会员的管理员笔记';
    $lang->about_group = '一个 I D 可属于多个组';

    $lang->about_column_type = '请指定要添加的注册类型';
    $lang->about_column_name = '请记录在模板可以使用的英文名称（变数名）';
    $lang->about_column_title = '注册或信息修改/查询时显示的标题';
    $lang->about_default_value = '可以选定基本输入的值';
    $lang->about_active = '必须选择激活项目才可以正常启用';
    $lang->about_form_description = '在说明栏里输入内容，注册时将显示。';
    $lang->about_required = '选择将注册会员时改为必填项目';

    $lang->about_enable_openid = '支持 OpenID 时请选择';
    $lang->about_enable_join = '选择以后用户可以注册会员';
    $lang->about_limit_day = '注册会员后可以指定日期内认证的限制';
    $lang->about_limit_date = '指定日期之内相关用户不能登录';
    $lang->about_redirect_url = '请输入注册会员后将移动的页面 url.留空返回到前页';
    $lang->about_agreement = '没有会员条款时不会显示';

    $lang->about_image_name = '用户的姓名可以代替图片显示';
    $lang->about_image_mark = '用户姓名前显示的小图标';
    $lang->about_accept_agreement = '已阅读全部条款并同意。'; 

    $lang->about_member_default = '注册会员时设定为基本组';

    $lang->about_openid = '用OpenID注册时 I D 和 邮件等基本信息会保存在这个网站，但密码和认证的处理是属于提供OpenID服务的站点。';

    $lang->about_member = "可以管理会员添加/修改/删除/和组管理或注册管理的会员管理模块。\n在基本生成的组以外再生成组以后可以管理会员和通过注册形式的基本信息外还可以收录其他的信息。";
?>