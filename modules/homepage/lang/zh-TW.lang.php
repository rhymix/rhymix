<?php
    /**
     * @file   zh-TW.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  首頁(homepage) 基本模組
     **/

    $lang->homepage = "首頁"; 
    $lang->homepage_title = "首頁名稱";
    $lang->domain = "域名";
    $lang->module_type = "目標";
    $lang->board = "討論板";
    $lang->page = "頁面";
    $lang->url = "URL";
    $lang->module_id = "模組 ID";
    $lang->item_group_grant = "顯示群組";
    $lang->homepage_admin = "首頁管理者";
    $lang->do_selected_member = "選擇會員 : ";

    $lang->homepage_default_menus = array(
        'first' => array(
            'home' => '首頁',
            'notice' => '公告事項',
            'download' => '下載',
            'gallery' => '相簿',
            'community' => '討論',
            'freeboard' => '自由討論',
            'humor' => '新鮮趣事',
            'qa' => 'Q & A',
        ),
        'second' => array(
            'profile' => '網站介紹',
            'rule' => '使用規範',
        ),
        'menu' => array(
            'first' => '基本選單',
            'second' => '子選單',
        ),
        'widget' => array(
            'download_rank' => '下載排行',
        ),
    );

    $lang->cmd_homepage_menus = array(
        "dispHomepageManage" => "首頁設定",
        "dispHomepageMemberGroupManage" => "會員群組管理",
        "dispHomepageMemberManage" => "會員列表",
        "dispHomepageTopMenu" => "主選單",
        "dispHomepageBottomMenu" => "底部選單",
        "dispHomepageMidSetup" => "詳細設定",
    );
    $lang->cmd_homepage_registration = "建立首頁";
    $lang->cmd_homepage_setup = "首頁設置";
    $lang->cmd_homepage_delete = "刪除首頁";
    $lang->cmd_go_home = "移至首頁";
    $lang->cmd_go_homepage_admin = '管理頁面';
    $lang->cmd_change_layout = "變更";
    $lang->cmd_change_layout = "變更";
    $lang->cmd_select_index = "選擇初始頁面";
    $lang->cmd_add_new_menu = "新增選單";

    $lang->about_homepage_act = array(
        "dispHomepageManage" => "此模組可建立 Homepage的樣貌",
        "dispHomepageMemberGroupManage" => "Homepage management within the group can be used",
        "dispHomepageMemberManage" => "ホームページに登録された会員たちを見るとか管理することができます",
        "dispHomepageTopMenu" => "ホームページの上端や左側等に現われる一般的なメニューを修正するとか追加することができます",
        "dispHomepageBottomMenu" => "ホームページの下端に現われる小さなメニューたちを修正するとか追加することができます",
        "dispHomepageMidSetup" => "可詳細設定 Homepage所使用的討論板，頁面等模組",
    );
    $lang->about_homepage = "ホームページサービス管理者は多数のホームページを作ることができるしまた各ホームページを楽に設定するようにします.";
    $lang->about_homepage_title = "ホームページ名前は管理のためにだけで使われるだけサービスには現われないです";
    $lang->about_domain = "1個以上のホームページを作るためには専用ドメインがなければなりません.<br/>独立ドメインやサーブドメインがあれば良いし XEが設置された経路まで一緒に入れてください.<br />例) www.zeroboard.com/zbxe";
    $lang->about_menu_names = "可指定語言。<br/>如果只輸入其中一項，其他語言將會顯示一樣。";
    $lang->about_menu_option = "メニューを選択の時新しいウィンドーで開くのを選択することができます.<br/>広げメニューはレイアウトによって動作します";
    $lang->about_group_grant = "그룹을 선택하면 선택된 그룹만 메뉴가 보입니다.<br/>모두 해제하면 비회원도 볼 수 있습니다";
    $lang->about_module_type = "게시판,페이지는 모듈을 생성하고 URL은 링크만 합니다.<br/>생성후 수정할 수 없습니다";
    $lang->about_browser_title = "連結選單後，顯示在瀏覽器視窗的標題。";
    $lang->about_module_id = "連結討論板，頁面等模組時，所要輸入的格式。<br/>例) http://域名/[模組 ID], http://域名/?mid=[模組 ID]";
    $lang->about_menu_item_url = "대상을 URL로 할때 연결할 링크주소입니다.<br/>請勿輸入 http://";
    $lang->about_menu_image_button = "메뉴명 대신 이미지로 메뉴를 사용할 수 있습니다.";
    $lang->about_homepage_delete = "홈페이지를 삭제하게 되면 연결되어 있는 모든 모듈(게시판,페이지등)과 그에 따른 글들이 삭제됩니다.<br />주의가 필요합니다";
    $lang->about_homepage_admin = "可以建立網站管理員。<br/>管理員 http://網址/?module=homepage 로 관리자 페이지로 접속할 수 있으며 존재하지 않는 사용자는 관리자로 등록되지 않습니다";

    $lang->confirm_change_layout = "레이아웃을 변경할 경우 레이아웃 정보들 중 일부가 사라질 수가 있습니다. 변경하시겠습니까?";
    $lang->confirm_delete_menu_item = "메뉴 항목 삭제시 연결되어 있는 게시판이나 페이지 모듈도 같이 삭제가 됩니다. 그래도 삭제하시겠습니까?";
    $lang->msg_already_registed_domain = "已註冊的域名。請使用其他的網域。";
?>
