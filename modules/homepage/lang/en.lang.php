<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Default language pack for the homepage module.
     **/

    $lang->homepage = "Homepage"; 
    $lang->homepage_title = "Title of homepage";
    $lang->domain = "Domain Name";
    $lang->module_type = "Type of target module";
    $lang->board = "Board";
    $lang->page = "Page";
    $lang->url = "URL";
    $lang->module_id = "module ID";
    $lang->item_group_grant = "Accessible group";
    $lang->homepage_admin = "Homepage administrator";
    $lang->do_selected_member = "Change the selected members into : ";

    $lang->homepage_default_menus = array(
        'first' => array(
            'home' => 'Home',
            'notice' => 'Notice',
            'download' => 'Download',
            'gallery' => 'Gallery',
            'community' => 'Community',
            'freeboard' => 'Off-topics',
            'humor' => 'Humor',
            'qa' => 'Question&Answer',
        ),
        'second' => array(
            'profile' => 'Introduction',
            'rule' => 'Rules',
        ),
        'menu' => array(
            'first' => 'Default Menu',
            'second' => 'Bottom Menu',
        ),
        'widget' => array(
            'download_rank' => 'Download Ranking',
        ),
    );

    $lang->cmd_homepage_menus = array(
        "dispHomepageManage" => "Configuration",
        "dispHomepageMemberGroupManage" => "Manage groups",
        "dispHomepageMemberManage" => "Member list",
        "dispHomepageTopMenu" => "Manage default menu",
        "dispHomepageBottomMenu" => "Manage bottom menu",
        "dispHomepageMidSetup" => "Module configuration",
    );
    $lang->cmd_homepage_registration = "Create a homepage";
    $lang->cmd_homepage_setup = "Configure homepage";
    $lang->cmd_homepage_delete = "Delete ";
    $lang->cmd_go_home = "Move home";
    $lang->cmd_go_homepage_admin = 'Homepage package';
    $lang->cmd_change_layout = "Change";
    $lang->cmd_select_index = "Select the index page";
    $lang->cmd_add_new_menu = "Add a new menu";

    $lang->about_homepage_act = array(
        "dispHomepageManage" => "You can decorate homepage's layout here.",
        "dispHomepageMemberGroupManage" => "You can manage groups used in this homepage.",
        "dispHomepageMemberManage" => "You can list up members registered and manage them.",
        "dispHomepageTopMenu" => "You can manage the default menus",
        "dispHomepageBottomMenu" => "You can manage the bottom menus",
        "dispHomepageMidSetup" => "You can configure modules, such as pages and boards, used in the homepage."
    );
    $lang->about_homepage = "Homepage package provides features to create homepages and to configure them conveniently";
    $lang->about_homepage_title = "The title is only used for management, it would not be displayed.";
    $lang->about_domain = "In order to create more than one homepage, each of them needs to have own domain name.<br />Sub-domain (e.g., aaa.bbb.com of bbb.com) also can be used. Input the address including the path installed xe. <br /> ex) www.zeroboard.com/zbxe";
    $lang->about_menu_names = "You can set the title of the menu for each language displayed in the menus<br />If you input one of the titles, titles for other languages will be set as same.";
    $lang->about_menu_option = "You can assign it to open a page in a new window when the menu clicked.<br />The option for menu expand may work depending on the layout.";
    
    $lang->about_group_grant = 'If you select a group, only the group members can see the menu. (if xml file is directly opened, it will be shown.)';
    $lang->about_module_type = "For boards and pages, it would create a module, and for URLs, it would make a link to the url.<br/> The type cannot be modified after creation.";
    $lang->about_browser_title = "It will be displayed on the title of the web browser, when users connect to the menu.";
    $lang->about_module_id = "The module id will be used for accessing the board or page, <br /> ex) http://address/[moduleID]";
    $lang->about_menu_item_url = "If the target is URL, input the address here <br />Do not include http://";
    $lang->about_menu_image_button = "Instead of the title, the menu image can be used.";
    $lang->about_homepage_delete = "Caution! If you delete the homepage, all the modules(boards, pages) linked to the homepage and all the documents will be removed.";
    $lang->about_homepage_admin = "You can set administrators of the homepage. <br />The administrators can access the administration page via http://address/?module=homepage . Only existing IDs can be registered as administrator";
    
    $lang->confirm_change_layout = "If you change the layout, some information of the layout might be reset. Would you like to change it?";
    $lang->confirm_delete_menu_item = "If you delete the menu item, the linked module(board or page) will be removed, too. Would you like to delete it?";
    $lang->msg_already_registed_domain = "It is already registered domain name. Please use the different one.";
?>
