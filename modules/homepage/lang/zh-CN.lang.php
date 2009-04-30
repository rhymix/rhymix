<?php
    /**
     * @file   zh-CN.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  站点(homepage)模块语言包
     **/

    $lang->cafe = "站点"; 
    $lang->cafe_id = "카페 접속 ID"; 
    $lang->cafe_title = "站点名称";
    $lang->cafe_description = 'Description of cafe';
    $lang->cafe_banner = 'Banner of Cafe';
    $lang->module_type = "对象";
    $lang->board = "版面";
    $lang->page = "页面";
    $lang->module_id = "模块ID";
    $lang->item_group_grant = "用户组";
    $lang->cafe_info = "站点信息";
    $lang->cafe_admin = "管理员";
    $lang->do_selected_member = "把所选用户 : ";
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

    $lang->cmd_make_cafe = '카페 생성';
    $lang->cmd_import = 'Import';
    $lang->cmd_export = 'Export';
    $lang->cafe_creation_privilege = '咖啡厅建立特权';

    $lang->cafe_main_mid = '카페 메인 ID';
    $lang->about_cafe_main_mid = '카페 메인 페이지를 http://주소/ID 값으로 접속하기 위한 ID값을 입력해주세요.';

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
    $lang->msg_module_count_exceed = '허용된 모듈의 개수를 초과하였기에 생성할 수 없습니다';
    $lang->msg_not_enabled_id = '사용할 수 없는 아이디입니다';
    $lang->msg_same_site = '동일한 가상 사이트의 모듈은 이동할 수가 없습니다';
    $lang->about_move_module = '가상사이트와 기본사이트간의 모듈을 옮길 수 있습니다.<br/>다만 가상사이트끼리 모듈을 이동하거나 같은 이름의 mid가 있을 경우 예기치 않은 오류가 생길 수 있으니 꼭 가상 사이트와 기본 사이트간의 다른 이름을 가지는 모듈만 이동하세요';
?>
