<?php
    /**
     * @file   en.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  Default language pack for the CafeXE module.
     **/

    $lang->cafe = "CafeXE"; 
    $lang->cafe_id = "카페 접속 ID"; 
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
    $lang->cafe_latest_documents = '카페 최신 글';
    $lang->cafe_latest_comments = '카페 최신 댓글';
    $lang->mycafe_list = '가입한 카페';
    $lang->cafe_creation_type = '카페 접속 방법';
    $lang->about_cafe_creation_type = '사용자들이 카페를 생성할때 카페 접속 방법을 정해야 합니다. Site ID는 http://기본주소/ID 로 접속 가능하고 Domain 접속은 입력하신 도메인의 2차 도메인(http://domain.mydomain.net) 으로 카페가 생성됩니다';
    $lang->cafe_main_layout = '카페 메인 레이아웃';

    $lang->default_layout = '기본 레이아웃';
    $lang->about_default_layout = '카페가 생성될때 설정될 기본 레이아웃을 지정할 수 있습니다';
    $lang->enable_change_layout = '레이아웃 변경';
    $lang->about_change_layout = '선택하시면 개별 카페에서 레이아웃 변경을 허용할 수 있습니다';
    $lang->allow_service = '허용 서비스';
    $lang->about_allow_service = '개별 카페에서 사용할 기본 서비스를 설정할 수 있습니다';

    $lang->cmd_make_cafe = 'Cafe creation';
    $lang->cmd_import = 'Import';
    $lang->cmd_export = 'Export';
    $lang->cafe_creation_privilege = 'Cafe creation privilege';

    $lang->cafe_main_mid = '카페 메인 ID';
    $lang->about_cafe_main_mid = '카페 메인 페이지를 http://주소/ID 값으로 접속하기 위한 ID값을 입력해주세요.';

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
    $lang->about_default_language = "처음 접속하는 사용자의 언어 설정을 지정할 수 있습니다.";

    $lang->about_cafe_act = array(
        "dispHomepageManage" => "You can decorate cafe's layout here.",
        "dispHomepageMemberGroupManage" => "You can manage groups used in this cafe.",
        "dispHomepageMemberManage" => "You can list up members registered and manage them.",
        "dispHomepageTopMenu" => "You can manage the default menus",
        "dispHomepageComponent" => "에디터 컴포넌트/ 애드온을 활성화 하거나 설정을 변경할 수 있습니다",
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
    $lang->msg_module_count_exceed = '허용된 모듈의 개수를 초과하였기에 생성할 수 없습니다';
    $lang->msg_not_enabled_id = '사용할 수 없는 아이디입니다';
    $lang->msg_same_site = '동일한 가상 사이트의 모듈은 이동할 수가 없습니다';
    $lang->about_move_module = '가상사이트와 기본사이트간의 모듈을 옮길 수 있습니다.<br/>다만 가상사이트끼리 모듈을 이동하거나 같은 이름의 mid가 있을 경우 예기치 않은 오류가 생길 수 있으니 꼭 가상 사이트와 기본 사이트간의 다른 이름을 가지는 모듈만 이동하세요';
?>
