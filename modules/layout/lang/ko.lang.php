<?php
    /**
     * @file   modules/layout/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  레이아웃(layout) 모듈의 기본 언어팩
     **/
    $lang->cmd_make_child = '하부 메뉴 추가';

    $lang->layout = '레이아웃';
    $lang->layout_name = '레이아웃 이름';
    $lang->menu_count = '메뉴의 수';
    $lang->menu_management = '메뉴 관리';
    $lang->depth = '단계';
    $lang->parent_menu_name = '상위  메뉴명';
    $lang->menu_name = '메뉴명';
    $lang->menu_srl = '메뉴의 고유 번호';
    $lang->menu_id = '메뉴의 이름';
    $lang->menu_url = '연결 url';
    $lang->menu_open_window = '새창 열기';
    $lang->menu_expand = '펼침';
    $lang->menu_img_btn = '이미지 버튼';
    $lang->menu_normal_btn = '일반';
    $lang->menu_hover_btn = '마우스오버';
    $lang->menu_active_btn = '선택시';
    $lang->menu_group_srls = '그룹 제한';

    $lang->layout_list = '레이아웃 목록';
    $lang->downloaded_list = '다운로드 목록';

    $lang->cmd_move_to_installed_list = "생성된 목록 보기";

    $lang->cmd_enable_move_menu = "메뉴 옮기기";

    $lang->about_downloaded_layouts = "다운로드 되어 있는 레이아웃 목록";
    $lang->about_title = '모듈에 연결시 쉽게 구분할 수 있는 제목을 입력해주세요';
    $lang->about_menu_management = "메뉴관리는 선택하신 레이아웃에서 사용하는 메뉴를 구성할 수 있도록 합니다.\n정해진 단계까지 메뉴를 구성 가능하며 입력하신 메뉴를 클릭하시면 상세 정보를 입력할 수 있습니다.\n폴더그림을 클릭하시면 메뉴를 확장하실 수 있습니다.\n간혹 메뉴가 정상적으로 나타나지 않으면 \"캐시파일 재생성\" 버튼을 눌러서 정보를 갱신하세요.\n* 정해진 단계 이상의 메뉴는 제대로 표시되지 않을 수 있습니다.";

    $lang->about_menu_name = '관리 및 이미지 버튼이 아닐경우 메뉴 명으로 나타날 제목입니다';
    $lang->about_menu_url = "메뉴를 선택시 이동한 URL입니다.<br /> 다른 모듈을 설치하시고자 할때는 \"module=모듈명\" 또는 \"mid=모듈\"등으로<br />입력하시면 됩니다.<br />내용이 없을시 메뉴를 선택하여도 아무런 동작이 없게 됩니다.";
    $lang->about_menu_open_window = '메뉴 선택시 새창으로 띄울 것인지를 정할 수 있습니다';
    $lang->about_menu_expand = '트리메뉴(tree_menu.js)를 사용시 늘 펼쳐진 상태로 있게 합니다';
    $lang->about_menu_img_btn = '이미지 버튼을 등록하시면 레이아웃에서 자동으로 이미지 버튼으로 교체되어 표시가 됩니다.';
    $lang->about_menu_group_srls = '그룹을 선택하시면 해당 그룹의 사용자만 메뉴가 보이게 됩니다. (xml파일을 직접 열람하면 노출이 됩니다)';

    $lang->msg_cannot_delete_for_child = '하부 메뉴가 있는 메뉴는 삭제하실 수 없습니다';
?>
