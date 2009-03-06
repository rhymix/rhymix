<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  站点(homepage)模块语言包
     **/

    $lang->cafe = "站点"; 
    $lang->cafe_title = "站点名称";
    $lang->module_type = "对象";
    $lang->board = "版面";
    $lang->page = "页面";
    $lang->module_id = "模块ID";
    $lang->item_group_grant = "用户组";
    $lang->cafe_info = "站点信息";
    $lang->cafe_admin = "管理员";
    $lang->do_selected_member = "把所选用户 : ";

    $lang->default_menus = array(
        'home' => '首页',
        'notice' => '站点公告',
        'levelup' => '级别审批',
        'freeboard' => '自由交流区',
        'view_total' => '查看全文',
        'view_comment' => '问候一句',
        'cafe_album' => '站点相册',
        'menu' => '菜单',
        'default_group1' => '待审批会员',
        'default_group2' => '准会员',
        'default_group3' => '正会员',
    );

    $lang->cmd_admin_menus = array(
        "dispHomepageManage" => "站点设置",
        "dispHomepageMemberGroupManage" => "用户组管理",
        "dispHomepageMemberManage" => "用户列表",
        "dispHomepageTopMenu" => "菜单管理",
        "dispHomepageComponent" => "扩展管理",
        "dispHomepageCounter" => "访问统计",
        "dispHomepageMidSetup" => "模块设置",
    );
    $lang->cmd_cafe_registration = "生成站点";
    $lang->cmd_cafe_setup = "站点设置";
    $lang->cmd_cafe_delete = "删除站点";
    $lang->cmd_go_home = "查看主页";
    $lang->cmd_go_cafe_admin = '站点管理';
    $lang->cmd_change_layout = "修改";
    $lang->cmd_select_index = "选择默认首页";
    $lang->cmd_add_new_menu = "添加新菜单";
    $lang->default_language = "默认语言";
    $lang->about_default_language = "可以设置显示给首次访问者的同一语言环境。";

    $lang->about_cafe_act = array(
        "dispHomepageManage" => "在此可以设置站点风格。",
        "dispHomepageMemberGroupManage" => "在此可以管理站点内的用户组。",
        "dispHomepageMemberManage" => "在此可以查看或管理用户。",
        "dispHomepageTopMenu" => "在此可以设置主菜单及所属子菜单。",
        "dispHomepageComponent" => "可以激活及设置网页编辑器组件/插件。",
        "dispHomepageCounter" => "可以查看站点的访问统计数据。",
        "dispHomepageMidSetup" => "在此可以设置站点内的版面，页面等模块的详细设置。",
    );
    $lang->about_cafe = "站点工具不仅可以迅速建立多个站点，而且非常方便各项设置。";
    $lang->about_cafe_title = "建议使用一个即简洁又直观的名称。此名称不会显示到用户页面当中。";
    $lang->about_domain = "要创建一个站点必须有一个专用域名。<br/>一级域名或二级域名皆可。输入的时候请把XE安装路径也一起输入。<br />ex) www.zeroboard.com/zbxe";
    $lang->about_menu_names = "在此可以指定多国语言菜单。<br/>如只输入一项，其他语言同时只应用此项语言。";
    $lang->about_menu_option = "可以设置点击菜单时是否要在新窗口中打开。<br />展开选项随布局。";
    $lang->about_group_grant = "如选择用户组，只有所属组用户才能看到此菜单。<br/>不选非用户也可以查看。";
    $lang->about_module_type = "版面，页面选项可直接生成该模块，URL就是链接。<br/>注意：生成后不能修改。";
    $lang->about_browser_title = "显示在浏览器顶端标题栏里的文档。";
    $lang->about_module_id = "访问版面，页面时使用的地址。<br/>例) http://域名/[模块ID], http://域名/?mid=[模块ID]";
    $lang->about_menu_item_url = "对象选择URL时，要链接的地址。<br/>输入的时候请不要输入http://头。";
    $lang->about_menu_image_button = "可以用图片来代替菜单名。";
    $lang->about_cafe_delete = "删除站点：即删除所以所属模块(版面，页面等)及所属主题。<br />一定要慎重操作。";
    $lang->about_cafe_admin = "可以指定站点管理员。<br/>管理员登录口为http://域名/?act=dispHomepageManage。管理员只能在已有的用户中指定。";

    $lang->confirm_change_layout = "切换布局可能一些原有的信息将无法显示。你确定要切换吗？";
    $lang->confirm_delete_menu_item = "删除菜单：即同时删除链接到此菜单的版面或页面模块。你确定要删除吗？";
    $lang->msg_already_registed_domain = "对不起！已有相同的域名。请重新输入。";
?>
