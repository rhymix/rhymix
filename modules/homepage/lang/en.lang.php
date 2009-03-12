<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Default language pack for the CafeXE module.
     **/

    $lang->cafe = "CafeXE"; 
    $lang->cafe_title = "Title of cafe";
    $lang->module_type = "Type of target module";
    $lang->board = "Board";
    $lang->page = "Page";
    $lang->module_id = "module ID";
    $lang->item_group_grant = "Accessible group";
    $lang->cafe_info = "Cafe Infomation";
    $lang->cafe_admin = "CafeXE administrator";
    $lang->do_selected_member = "Change the selected members into : ";

    $lang->default_menus = array(
        'home' => 'Home',
        'notice' => 'Notice',
        'levelup' => 'Request rating up',
        'freeboard' => 'Off-topics',
        'view_total' => 'View full text',
        'view_comment' => 'Story line',
        'cafe_album' => 'Cafe album',
        'menu' => 'menu',
        'default_group1' => 'Pending members ',
        'default_group2' => 'Associate',
        'default_group3' => 'Member',
    );

    $lang->cmd_admin_menus = array(
        "dispHomepageManage" => "Configuration",
        "dispHomepageMemberGroupManage" => "Manage groups",
        "dispHomepageMemberManage" => "Member list",
        "dispHomepageTopMenu" => "Manage default menu",
        "dispHomepageComponent" => "기능 설정",
        "dispHomepageCounter" => "접속 통계",
        "dispHomepageMidSetup" => "Module configuration",
    );
    $lang->cmd_cafe_registration = "Create a cafe";
    $lang->cmd_cafe_setup = "Configure cafe";
    $lang->cmd_cafe_delete = "Delete ";
    $lang->cmd_go_home = "Move home";
    $lang->cmd_go_cafe_admin = 'CafeXE package';
    $lang->cmd_change_layout = "Change";
    $lang->cmd_select_index = "Select the index page";
    $lang->cmd_add_new_menu = "Add a new menu";
    $lang->default_language = "기본 언어";
    $lang->about_default_language = "처음 접속하는 사용자의 언어 설정을 지정할 수 있습니다.";

    $lang->about_cafe_act = array(
        "dispHomepageManage" => "You can decorate cafe's layout here.",
        "dispHomepageMemberGroupManage" => "You can manage groups used in this cafe.",
        "dispHomepageMemberManage" => "You can list up members registered and manage them.",
        "dispHomepageTopMenu" => "You can manage the default menus",
        "dispHomepageComponent" => "에디터 컴포넌트/ 애드온을 활성화 하거나 설정을 변경할 수 있습니다",
        "dispHomepageCounter" => "Cafe의 접속 현황을 볼 수 있습니다",
        "dispHomepageMidSetup" => "You can configure modules, such as pages and boards, used in the cafe."
    );
    $lang->about_cafe = "CafeXE package provides features to create cafes and to configure them conveniently";
    $lang->about_cafe_title = "The title is only used for management, it would not be displayed.";
    $lang->about_domain = "In order to create more than one cafe, each of them needs to have own domain name.<br />Sub-domain (e.g., aaa.bbb.com of bbb.com) also can be used. Input the address including the path installed xe. <br /> ex) www.zeroboard.com/zbxe";
    $lang->about_menu_names = "You can set the title of the menu for each language displayed in the menus<br />If you input one of the titles, titles for other languages will be set as same.";
    $lang->about_menu_option = "You can assign it to open a page in a new window when the menu clicked.<br />The option for menu expand may work depending on the layout.";
    
    $lang->about_group_grant = 'If you select a group, only the group members can see the menu. (if xml file is directly opened, it will be shown.)';
    $lang->about_module_type = "For boards and pages, it would create a module, and for URLs, it would make a link to the url.<br/> The type cannot be modified after creation.";
    $lang->about_browser_title = "It will be displayed on the title of the web browser, when users connect to the menu.";
    $lang->about_module_id = "The module id will be used for accessing the board or page, <br /> ex) http://address/[moduleID]";
    $lang->about_menu_item_url = "If the target is URL, input the address here <br />Do not include http://";
    $lang->about_menu_image_button = "Instead of the title, the menu image can be used.";
    $lang->about_cafe_delete = "Caution! If you delete the cafe, all the modules(boards, pages) linked to the cafe and all the documents will be removed.";
    $lang->about_cafe_admin = "You can set administrators of the cafe. <br />The administrators can access the administration page via http://address/?act=dispHomepageManage. Only existing IDs can be registered as administrator";
    
    $lang->confirm_change_layout = "If you change the layout, some information of the layout might be reset. Would you like to change it?";
    $lang->confirm_delete_menu_item = "If you delete the menu item, the linked module(board or page) will be removed, too. Would you like to delete it?";
    $lang->msg_already_registed_domain = "It is already registered domain name. Please use the different one.";
?>
