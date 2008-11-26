<?php
    /**
     * @file   zh-CN.lang.php
     * @author sol (sol@ngleader.com)
     * @brief  微博(planet)模块语言包
     **/

    $lang->planet = "微博";
    $lang->planet_title = "微博标题";
    $lang->planet_url = "微博URL";
    $lang->planet_myplanet = "我的微博";
    $lang->planet_whos_planet = "%s的微博";
    $lang->planet_whos_favorite = "%s的收藏";
    $lang->planet_whos_favorite_list = "'%s'的收藏";

    $lang->planet_welcome = "欢迎您来到个人微博！";

    $lang->planet_reply_content = "微博评论内容";

    $lang->about_planet =
        "XE的微博模块。用户可以创建自己的微博并使用。
        注意：主站使用的域名可能无法链接微博。
        注意：要想把微博设置为默认首页，你要链接的域名应是唯一的，不能同时用在其他站点。";

    $lang->planet_mid = "微博地址名称";
    $lang->about_planet_mid = "是指可以直接访问个人微博的标示名。例：http://域名/微博地址名称/";

    $lang->planet_default_skin = "微博默认皮肤";
    $lang->about_planet_default_skin = "设置微博主站及已生成微博的皮肤。";

    $lang->planet_comment = "微博简单介绍";
    $lang->about_planet_comment = "就是微博的简单介绍，同时也出现在浏览器顶端的标题栏。";

    $lang->use_signup = "用户注册链接";
    $lang->about_use_signup = "勾选此项,微博主站顶端显示‘新用户注册’链接。";


    $lang->cmd_create_planet = "创建微博";
    $lang->create_message = "简单微博介绍";
    $lang->about_create_message = "可以输入简单的关于微博的简介。";

    $lang->cmd_planet_setup = "基本设置";
    $lang->cmd_planet_list = "微博列表";

    $lang->msg_not_logged = "请登录。";
    $lang->msg_planet_exists = "您已有生成的微博，不能另行创建微博。";

    $lang->planet_userinfo = "个人资料";
    $lang->planet_change_userinfo = "修改个人资料";

    $lang->planet_change_photo = "修改头像";
    $lang->about_planet_change_photo = "图片大小些为96x96px(与MSN头像相同)。";
    $lang->cmd_planet_image_upload = "上传图片";

    $lang->cmd_planet_good = "推荐";
    $lang->cmd_planet_addfavorite = "收藏";

    $lang->planet_hot_tag = "实时人气标签";
    $lang->planet_home = "首页";
    $lang->cmd_planet_more_tag = "更多";

    $lang->planet_memo = "留言";
    $lang->cmd_planet_show_memo_write_form = "留言";
    $lang->cmd_planet_delete_memo = "删除留言";
    $lang->cmd_planet_memo_write_ok = "提交";

    $lang->planet_interest_tag = "关注标签";
    $lang->planet_interest_content = "关注主题";
    $lang->cmd_planet_show_interest_tag = "查看关注标签";
    $lang->cmd_planet_close_interest_tag = "关闭关注标签";
    $lang->msg_planet_already_added_interest_tag = "已添加的关注标签。";

    $lang->cmd_planet_edit_subject = "修改标题";
    $lang->cmd_planet_edit_intro = "修改简介";
    $lang->cmd_planet_edit_tag = "修改标签";

    $lang->cmd_planet_openclose_memo = "'展开/折叠'留言";
    $lang->cmd_planet_del_tag = "删除标签";


    $lang->cmd_planet_openclose_recommend_search = "'打开/关闭'推荐关键词";
    $lang->about_planet_input_search_text = "输入关键词";


    $lang->about_planet_make_planet = "创建我的微博";
    $lang->about_planet_make_planet_info = "显示在微博头部的个人资料，您可以随意设置即修改。";
    $lang->planet_input_personalinfo = "输入个人资料";
    $lang->planet_photo = "头像";
    $lang->planet_myintro = "个人简介";

    $lang->about_planet_url = "请认真填写，此项输入后无法修改";
    $lang->planet_mytag = "形象标签";
    $lang->about_planet_mytag = "添加展现自我的个性标签，多个标签用逗号(,)来区分。";

    $lang->about_planet_tag = "多个标签用逗号(,)来区分。";

    $lang->cmd_planet_makeOk_move_myplanet = "确认 : 跳转到我的微博";
    $lang->cmd_planet_ok_move_myplanet = "确认 : 跳转到我的微博";


    $lang->about_planet_login = "输入用户名及密码后请点击登录按钮。";

    $lang->cmd_planet_login = "登录";


    $lang->planet_nowhot_tag = "微博实时人气标签";
    $lang->cmd_planet_close_nowhot_tag = "关闭实时人气标签";

    $lang->about_planet_whats_textSearch_in_planet = "在<strong>%s</strong>的微博搜索 <strong>'%s'</strong> 的结果。";
    $lang->about_planet_whats_textSearch = "<strong>'%s'</strong> 的搜索结果。";

    $lang->planet_acticle = "主题";
    $lang->planet_persontag = "形象标签";

    $lang->planet_recent_acticle = "最新更新";


    $lang->cmd_planet_add_tag = "添加关注标签";
    $lang->cmd_planet_add_article = "发布主题";
    $lang->cmd_planet_post_article = "发布";
    $lang->planet_postscript = "P.S.";
    $lang->planet_article_preview = "预览";


    $lang->planet_notice_title = "您好，%s!<br>先来了解一下什么是微博？<br>微博就是自由的与别人共享自己的想法，建议，信息，知识的小博客。下面简单介绍一下微博的使用方法：";
    $lang->planet_notice_list = array(
        "下面的'发布主题'栏可以'展开/折叠'。",
        "微博访问者不仅可以查看您发布的主题，而且还可以发表自己的评论。",
        "利用'添加收藏'和'添加关注标签'，你随时可以查看你关注的微博及相关主题。",
        "%s，您的'头像, 昵称, 形象标签'等信息都可以在本页面进行修改。",
        "如果您想了解别人的微博，建议使用实时人气标签或直接搜索相关主题。",
        "有其他疑问您可以试着搜索'提问'标签。或许能得到别人的帮助。",
    );
    $lang->planet_notice_disable = "不再提示我";

    $lang->msg_planet_about_postscript = "这里可以添加附言。";
    $lang->msg_planet_about_tag = "请输入标签(多个标签用逗号(,)来区分)";
    $lang->msg_planet_already_added_favorite = "已添加的收藏。";
    $lang->msg_planet_no_memo = "尚无被添加的留言";

    $lang->msg_planet_rss_enabled = "正在使有RSS发布功能";
    $lang->msg_planet_rss_disabled = "目前不使用RSS发布功能";

    $lang->msg_me2day_sync = "发送到me2day";
    $lang->msg_me2day_sync_q = "你确定要发送吗？";
    $lang->me2day_id = "me2day地址";
    $lang->me2day_ukey = "用户key";
    $lang->msg_me2day_activate = "自动发送";
    $lang->msg_fail_auth_me2day = "Me2day认证失败。";
    $lang->msg_success_auth_me2day = "已成功获得Me2day认证。";

    $lang->planet_total_articles = "全部";
    $lang->planet_wantyou = "推荐";
    $lang->planet_best = "回复排行";
    $lang->planet_catch = "跟我帖";
    $lang->planet_fish = "我跟帖";
    $lang->planet_bigfish = "回复排行";
    $lang->cmd_send_me2day = "发送到me2";

    $lang->msg_already_have_phone_number = '已添加的手机号。';
    $lang->planet_mobile_receive = '绑定手机';
    $lang->planet_mobile_number = '手机号';
    $lang->msg_success_set_phone_number = '已成功添加手机号。';

    $lang->planet_tagtab = "添加首页Tag标签页";
    $lang->about_planet_tagtab = "用逗号(,)来区分而难多个Tag标签页。";
    $lang->planet_tagtab_after = "뒤 추가 Tag 탭";
    $lang->about_planet_tagtab_after = "기본 태그탭 이후에 여러개의 Tag 탭을 추가할 수 있습니다. 콤마(,)로 여러개를 지정할 수 있습니다";
    $lang->planet_smstag = "添加SMS标签";
    $lang->about_planet_smstag = "用SMS发送时自动添加的标签，用逗号(,)来区分多个标签。";

    $lang->planet_use_mobile = "绑定SMS";
    $lang->about_use_mobile = "可以用手机SMS发送。";
    $lang->planet_use_me2day = "绑定me2day";
    $lang->about_use_me2day = "发布主题的同时发送到me2day。";
    $lang->msg_search_thisplanet = "이 플래닛에서 검색";
?>
