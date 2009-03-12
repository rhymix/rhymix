<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  简体中文语言包 (只收录基本内容)
     **/

    $lang->admin_info = '管理员信息';
    $lang->admin_index = '管理首页';
    $lang->control_panel = '控制面板';

    $lang->module_category_title = array(
        'service' => '应用管理',
        'member' => '用户管理',
        'content' => '资源管理',
        'statistics' => '统计管理',
        'construction' => '界面管理',
        'utility' => '扩展管理',
        'interlock' => '辅助联动',
        'accessory' => '附加功能',
        'migration' => '数据导入',
        'system' => '系统管理',
    );

    $lang->newest_news = "最新消息";

    $lang->env_setup = "系统设置";
    $lang->default_url = "XE通行证";
    $lang->about_default_url = "请输入默认站点的XE安装地址(ex: http://域名/xe)。 <br /><strong>说明：</strong>简单的说，就是绑定帐号系统。只需要登录一次，就可以在用站点模块生成的多个子站点中随意漫游。";

    $lang->env_information = "系统信息";
    $lang->current_version = "安装版本";
    $lang->current_path = "安装路径";
    $lang->released_version = "最新版本";
    $lang->about_download_link = "官方网站已发布新版本。\n请点击download链接下载最新版本。";

    $lang->item_module = "模块目录";
    $lang->item_addon  = "插件目录";
    $lang->item_widget = "控件目录";
    $lang->item_layout = "布局目录";

    $lang->module_name = "模块名称";
    $lang->addon_name = "插件名称";
    $lang->version = "版本";
    $lang->author = "作者";
    $lang->table_count = "表格数";
    $lang->installed_path = "安装路径";

    $lang->cmd_shortcut_management = "编辑菜单";

    $lang->msg_is_not_administrator = '只允许管理员访问';
    $lang->msg_manage_module_cannot_delete = '模块，插件，布局，控件模块的快捷菜单是不能删除的。';
    $lang->msg_default_act_is_null = '没有指定默认管理员的动作，是不能添加到快捷菜单的。';

    $lang->welcome_to_xe = 'XE 管理页面';
    $lang->about_admin_page = "后台管理页面未完成";
    $lang->about_lang_env = "可以设置显示给首次访问者的同一语言环境。修改语言环境后请点击 [保存] 按钮进行保存。";


    $lang->xe_license = 'XE遵循 GPL协议';
    $lang->about_shortcut = '可以删除添加到常用模块中的快捷菜单。';

    $lang->yesterday = "Yesterday";
    $lang->today = "Today";

    $lang->cmd_lang_select = "多国语言支持";
    $lang->about_cmd_lang_select = "请选择要使用的语言。";
    $lang->about_recompile_cache = "整理无用的或错误的缓冲文件。";
    $lang->use_ssl = "SSL使用";
    $lang->ssl_options = array(
        'none' => "不使用",
        'optional' => "选择性",
        'always' => "使用"
    );
    $lang->about_use_ssl = "选择性使用选项应用于新用户注册/修改用户信息等已指定的action当中，使用选项应用于所有服务。";
    $lang->server_ports = "指定服务器端口";
    $lang->about_server_ports = "使用除HTTP（80）, HTTPS（443）以外的端口时，必须得指定该服务器端口号。";
?>
