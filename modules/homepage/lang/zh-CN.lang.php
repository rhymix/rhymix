<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  站点(homepage)模块语言包
     **/

    $lang->homepage = "站点"; 
    $lang->homepage_title = "站点名称";
    $lang->module_type = "对象";
    $lang->board = "版面";
    $lang->page = "页面";
    $lang->module_id = "模块ID";
    $lang->item_group_grant = "用户组";
    $lang->homepage_admin = "管理员";
    $lang->do_selected_member = "把被选用户 : ";

    $lang->homepage_default_menus = array(
        'first' => array(
            'home' => '首页',
            'notice' => '公告',
            'download' => '下载',
            'gallery' => '图片',
            'community' => '社区',
            'freeboard' => '自由交流区',
            'humor' => '幽默/休闲',
            'qa' => 'Q and A',
        ),
        'second' => array(
            'profile' => '关于我们',
            'rule' => '使用条款',
        ),
        'menu' => array(
            'first' => '主菜单',
            'second' => '尾部菜单',
        ),
        'widget' => array(
            'download_rank' => '下载排名',
        ),
    );

    $lang->cmd_homepage_menus = array(
        "dispHomepageManage" => "站点设置",
        "dispHomepageMemberGroupManage" => "用户组管理",
        "dispHomepageMemberManage" => "用户列表",
        "dispHomepageTopMenu" => "主菜单管理",
        "dispHomepageBottomMenu" => "尾部菜单管理",
        "dispHomepageMidSetup" => "模块详细设置",
    );
    $lang->cmd_homepage_registration = "生成站点";
    $lang->cmd_homepage_setup = "站点设置";
    $lang->cmd_homepage_delete = "删除站点";
    $lang->cmd_go_home = "查看主页";
    $lang->cmd_go_homepage_admin = '站点管理';
    $lang->cmd_change_layout = "修改";
    $lang->cmd_select_index = "选择默认首页";
    $lang->cmd_add_new_menu = "添加新菜单";

    $lang->about_homepage_act = array(
        "dispHomepageManage" => "在此可以设置站点风格。",
        "dispHomepageMemberGroupManage" => "在此可以管理站点内的用户组。",
        "dispHomepageMemberManage" => "在此可以查看或管理用户。",
        "dispHomepageTopMenu" => "在此可以设置主菜单及所属子菜单。",
        "dispHomepageBottomMenu" => "在此可以设置站点尾部菜单。",
        "dispHomepageMidSetup" => "在此可以设置站点内的版面，页面等模块的详细设置。",
    );
    $lang->about_homepage = "站点工具不仅可以迅速建立多个站点，而且非常方便各项设置。";
    $lang->about_homepage_title = "建议使用一个即简洁又直观的名称。此名称不会显示到用户页面当中。";
    $lang->about_domain = "要创建一个站点必须有一个专用域名。<br/>一级域名或二级域名皆可。输入的时候请把XE安装路径也一起输入。<br />ex) www.zeroboard.com/zbxe";
    $lang->about_menu_names = "在此可以指定多国语言菜单。<br/>如只输入一项，其他语言同时只应用此项语言。";
    $lang->about_menu_option = "可以设置点击菜单时是否要在新窗口中打开。<br />展开选项随布局。";
    $lang->about_group_grant = "如选择用户组，只有所属组用户才能看到此菜单。<br/>不选非用户也可以查看。";
    $lang->about_module_type = "版面，页面选项可直接生成该模块，URL就是链接。<br/>注意：生成后不能修改。";
    $lang->about_browser_title = "显示在浏览器顶端标题栏里的文档。";
    $lang->about_module_id = "访问版面，页面时使用的地址。<br/>例) http://域名/[模块ID], http://域名/?mid=[模块ID]";
    $lang->about_menu_item_url = "对象选择URL时，要链接的地址。<br/>输入的时候请不要输入http://头。";
    $lang->about_menu_image_button = "可以用图片来代替菜单名。";
    $lang->about_homepage_delete = "删除站点：即删除所以所属模块(版面，页面等)及所属主题。<br />一定要慎重操作。";
    $lang->about_homepage_admin = "可以指定站点管理员。<br/>管理员登录口为http://域名/?module=homepage。管理员只能在已有的用户中指定。";

    $lang->confirm_change_layout = "切换布局可能一些原有的信息将无法显示。你确定要切换吗？";
    $lang->confirm_delete_menu_item = "删除菜单：即同时删除链接到此菜单的版面或页面模块。你确定要删除吗？";
    $lang->msg_already_registed_domain = "对不起！已有相同的域名。请重新输入。";
?>
