<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  홈페이지(homepage) 모듈의 기본 언어팩
     **/

    $lang->homepage = "홈페이지"; 
    $lang->homepage_title = "홈페이지 이름";
    $lang->domain = "도메인";
    $lang->module_type = "대상";
    $lang->board = "게시판";
    $lang->page = "페이지";
    $lang->url = "URL";
    $lang->module_id = "모듈 ID";
    $lang->item_group_grant = "보여줄 그룹";
    $lang->homepage_admin = "홈페이지 관리자";
    $lang->do_selected_member = "선택된 회원을 : ";

    $lang->homepage_default_menus = array(
        'first' => array(
            'home' => '홈',
            'notice' => '공지사항',
            'download' => '자료실',
            'gallery' => '갤러리',
            'community' => '커뮤니티',
            'freeboard' => '자유게시판',
            'humor' => '재밌는 이야기',
            'qa' => '질문&답변',
        ),
        'second' => array(
            'profile' => '홈페이지 소개',
            'rule' => '운영원칙',
        ),
        'menu' => array(
            'first' => '기본메뉴',
            'second' => '아래메뉴',
        ),
        'widget' => array(
            'download_rank' => '다운로드 순위',
        ),
    );

    $lang->cmd_homepage_menus = array(
        "dispHomepageManage" => "홈페이지 설정",
        "dispHomepageMemberGroupManage" => "회원그룹관리",
        "dispHomepageMemberManage" => "회원 목록",
        "dispHomepageTopMenu" => "기본 메뉴 관리",
        "dispHomepageBottomMenu" => "하부 메뉴 관리",
        "dispHomepageMidSetup" => "모듈 세부 설정",
    );
    $lang->cmd_homepage_registration = "홈페이지 생성";
    $lang->cmd_homepage_setup = "홈페이지 설정";
    $lang->cmd_homepage_delete = "홈페이지 삭제";
    $lang->cmd_go_home = "홈으로 이동";
    $lang->cmd_go_homepage_admin = '홈페이지 전체 관리';
    $lang->cmd_change_layout = "변경";
    $lang->cmd_change_layout = "변경";
    $lang->cmd_select_index = "초기화면 선택";
    $lang->cmd_add_new_menu = "새로운 메뉴 추가";

    $lang->about_homepage_act = array(
        "dispHomepageManage" => "홈페이지의 모양을 꾸밀 수 있습니다",
        "dispHomepageMemberGroupManage" => "홈페이지 내에서 사용되는 그룹 관리를 할 수 있습니다",
        "dispHomepageMemberManage" => "홈페이지에 등록된 회원들을 보거나 관리할 수 있습니다",
        "dispHomepageTopMenu" => "홈페이지의 상단이나 좌측등에 나타나는 일반적인 메뉴를 수정하거나 추가할 수 있습니다",
        "dispHomepageBottomMenu" => "홈페이지의 하단에 나타나는 작은 메뉴들을 수정하거나 추가할 수 있습니다",
        "dispHomepageMidSetup" => "홈페이지에서 사용하는 게시판, 페이지등의 모듈 세부 설정을 할 수 있습니다",
    );
    $lang->about_homepage = "홈페이지 서비스 관리자는 다수의 홈페이지를 만들 수 있고 또 각 홈페이지를 편하게 설정할 수 있도록 합니다.";
    $lang->about_homepage_title = "홈페이지 이름은 관리를 위해서만 사용될 뿐 서비스에는 나타나지 않습니다";
    $lang->about_domain = "1개 이상의 홈페이지를 만들기 위해서는 전용 도메인이 있어야 합니다.<br/>독립 도메인이나 서브 도메인이 있으면 되고 XE가 설치된 경로까지 같이 넣어주세요.<br />ex) www.zeroboard.com/zbxe";
    $lang->about_menu_names = "홈페이지에 나타날 메뉴 이름을 언어에 따라서 지정할 수 있습니다.<br/>하나만 입력하셔도 모두 같이 적용됩니다";
    $lang->about_menu_option = "메뉴를 선택시 새창으로 열지를 선택할 수 있습니다.<br />펼침 메뉴는 레이아웃에 따라 동작합니다";
    $lang->about_group_grant = "그룹을 선택하면 선택된 그룹만 메뉴가 보입니다.<br/>모두 해제하면 비회원도 볼 수 있습니다";
    $lang->about_module_type = "게시판,페이지는 모듈을 생성하고 URL은 링크만 합니다.<br/>생성후 수정할 수 없습니다";
    $lang->about_browser_title = "메뉴에 접속시 브라우저의 제목으로 나타날 내용입니다";
    $lang->about_module_id = "게시판,페이지등 접속할때 사용될 주소입니다.<br/>예) http://도메인/[모듈ID], http://도메인/?mid=[모듈ID]";
    $lang->about_menu_item_url = "대상을 URL로 할때 연결할 링크주소입니다.<br/>http://는 빼고 입력해주세요";
    $lang->about_menu_image_button = "메뉴명 대신 이미지로 메뉴를 사용할 수 있습니다.";
    $lang->about_homepage_delete = "홈페이지를 삭제하게 되면 연결되어 있는 모든 모듈(게시판,페이지등)과 그에 따른 글들이 삭제됩니다.<br />주의가 필요합니다";
    $lang->about_homepage_admin = "홈페이지 관리자를 설정할 수 있습니다.<br/>홈페이지 관리자는 http://주소/?module=homepage 로 관리자 페이지로 접속할 수 있으며 존재하지 않는 사용자는 관리자로 등록되지 않습니다";

    $lang->confirm_change_layout = "레이아웃을 변경할 경우 레이아웃 정보들 중 일부가 사라질 수가 있습니다. 변경하시겠습니까?";
    $lang->confirm_delete_menu_item = "메뉴 항목 삭제시 연결되어 있는 게시판이나 페이지 모듈도 같이 삭제가 됩니다. 그래도 삭제하시겠습니까?";
    $lang->msg_already_registed_domain = "이미 등록된 도메인입니다. 다른 도메인을 사용해주세요";
?>
