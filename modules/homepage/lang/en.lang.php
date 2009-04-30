<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Default language pack for the CafeXE module.
     **/

    $lang->cafe = "CafeXE"; 
    $lang->cafe_id = "Cafe ID"; 
    $lang->cafe_title = "Title of cafe";
    $lang->cafe_description = 'Description of cafe';
    $lang->cafe_banner = 'Banner of Cafe';
    $lang->module_type = "Type of target module";
    $lang->board = "Board";
    $lang->page = "Page";
    $lang->module_id = "module ID";
    $lang->item_group_grant = "Accessible group";
    $lang->cafe_info = "Cafe Infomation";
    $lang->cafe_admin = "CafeXE administrator";
    $lang->do_selected_member = "Change the selected members into : ";
    $lang->cafe_latest_documents = "Cafe's latest documents";
    $lang->cafe_latest_comments = "Cafe's latest comments";
    $lang->mycafe_list = "Joined Cafes";
    $lang->cafe_creation_type = "Type of Cafe";
    $lang->about_cafe_creation_type = "Please choose how users access the created cafe. If you choose Site ID, they would access via http://defaultAddr/SiteID, and in the case of Domain name, they would access via sub-domain of registered domain name, http://subdomain.defaultDomain";
    $lang->cafe_main_layout = "Cafe's main layout";

    $lang->default_layout = 'Default layout';
    $lang->about_default_layout = 'You may set the default layout will be applied to newly created cafes';
    $lang->enable_change_layout = 'Allow change layout';
    $lang->about_change_layout = 'Allow each cafe to change its layout';
    $lang->allow_service = 'Allowed services';
    $lang->about_allow_service = 'You may configure default setting for the number and the type of services allowed in newly created cafes';

    $lang->cmd_make_cafe = 'Cafe creation';
    $lang->cmd_import = 'Import';
    $lang->cmd_export = 'Export';
    $lang->cafe_creation_privilege = 'Cafe creation privilege';

    $lang->cafe_main_mid = 'Cafe ID';
    $lang->about_cafe_main_mid = "Input an ID of cafe's main page address, http://addr/cafeID";

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
        "dispHomepageTopMenu" => "Manage menu",
        "dispHomepageComponent" => "Setting Skill",
        "dispHomepageCounter" => "connecting status",
        "dispHomepageMidSetup" => "Module configuration",
    );
    $lang->cmd_cafe_registration = "Create a cafe";
    $lang->cmd_cafe_setup = "Configure cafe";
    $lang->cmd_cafe_delete = "Delete ";
    $lang->cmd_go_home = "Move to home";
    $lang->cmd_go_cafe_admin = 'CafeXE package';
    $lang->cmd_change_layout = "Change";
    $lang->cmd_select_index = "Select the index page";
    $lang->cmd_add_new_menu = "Add a new menu";
    $lang->default_language = "Default Language";
    $lang->about_default_language = "You may set the default language";

    $lang->about_cafe_act = array(
        "dispHomepageManage" => "You can decorate cafe's layout here.",
        "dispHomepageMemberGroupManage" => "You can manage groups used in this cafe.",
        "dispHomepageMemberManage" => "You can list up members registered and manage them.",
        "dispHomepageTopMenu" => "You can manage the default menus",
        "dispHomepageComponent" => "You can enable editor components / addons and configure them.",
        "dispHomepageCounter" => "You can see the connecting status of Cafe",
        "dispHomepageMidSetup" => "You can configure modules, such as pages and boards, used in the cafe."
    );
    $lang->about_cafe = "CafeXE package provides features to create cafes and to configure them conveniently";
    $lang->about_cafe_title = "The title is only used for management, it would not be displayed.";
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
    $lang->msg_module_count_exceed = "The number of modules are limited, you cannot create more modules";
    $lang->msg_not_enabled_id = 'The ID cannot be used';
    $lang->msg_same_site = 'Modules cannot be moved between virtual sites.';
    $lang->about_move_module = "You may move modules between default site and virtual sites.<br />Moving modules among virtual sites is note allowed. Also, if there exists a module with same mid, there can be unexpected errors, thus move only modules which have unique mid.";
?>
