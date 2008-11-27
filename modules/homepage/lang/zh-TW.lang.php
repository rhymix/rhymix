<?php
    /**
     * @file   zh-TW.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  網站(homepage) 基本模組
     **/

    $lang->homepage = "網站"; 
    $lang->homepage_title = "網站名稱";
    $lang->domain = "域名";
    $lang->module_type = "目標";
    $lang->board = "討論板";
    $lang->page = "頁面";
    $lang->url = "URL";
    $lang->module_id = "模組 ID";
    $lang->item_group_grant = "顯示群組";
    $lang->homepage_admin = "網站管理者";
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
        "dispHomepageManage" => "網站設定",
        "dispHomepageMemberGroupManage" => "會員群組管理",
        "dispHomepageMemberManage" => "會員列表",
        "dispHomepageTopMenu" => "主選單",
        "dispHomepageBottomMenu" => "底部選單",
        "dispHomepageMidSetup" => "詳細設定",
    );
    $lang->cmd_homepage_registration = "建立網站";
    $lang->cmd_homepage_setup = "網站設置";
    $lang->cmd_homepage_delete = "刪除網站";
    $lang->cmd_go_home = "移至首頁";
    $lang->cmd_go_homepage_admin = '管理頁面';
    $lang->cmd_change_layout = "變更";
    $lang->cmd_select_index = "選擇初始頁面";
    $lang->cmd_add_new_menu = "新增選單";

    $lang->about_homepage_act = array(
        "dispHomepageManage" => "可設定網站風格",
        "dispHomepageMemberGroupManage" => "可管理網站內的用戶群組",
        "dispHomepageMemberManage" => "可以檢視和管理會員",
        "dispHomepageTopMenu" => "可建立或管理左側或上端的選單",
        "dispHomepageBottomMenu" => "可設置和管理底部選單",
        "dispHomepageMidSetup" => "可詳細設定網站所使用的討論板，頁面等模組",
    );
    $lang->about_homepage = "網站模組可快速建立網站，且容易進行設定。";
    $lang->about_homepage_title = "只有在管理時才看的到此標題。";
    $lang->about_domain = "要建立網站必須要有個專屬域名。<br/>頂級域名或次級域名都可以。輸入時，請將XE安裝路徑也一起輸入。<br />例) www.zeroboard.com/zbxe";
    $lang->about_menu_names = "可指定語言。<br/>如果只輸入其中一項，其他語言將會顯示一樣。";
    $lang->about_menu_option = "可設定案選單時，是否要以新視窗開啟。<br />選展開的話，是隨版面。";

    $lang->about_group_grant = "有選擇用戶群組的話，只有被選擇的用戶群組才看的到。<br/>沒有選擇的話，非會員也能觀看。";
    $lang->about_module_type = "討論板，頁面可直接建立該模組和 URL。<br/>注意：建立後無法再修改";
    $lang->about_browser_title = "連結選單後，顯示在瀏覽器視窗的標題。";
    $lang->about_module_id = "連結討論板，頁面等模組時，所要輸入的格式。<br/>例) http://域名/[模組 ID], http://域名/?mid=[模組 ID]";
    $lang->about_menu_item_url = "目標是 URL時，在此輸入網址。<br/>請勿輸入 http://";
    $lang->about_menu_image_button = "可用圖片代替選單名稱";
    $lang->about_homepage_delete = "刪除網站：即刪除所有相關的模組(討論板，頁面等)以及相關文章。請慎重使用。";
    $lang->about_homepage_admin = "可以建立網站管理員。<br/>管理員登入網址是 http://域名/?module=homepage。只能在現有的會員中指定管理員。";

    $lang->confirm_change_layout = "變換版面可能會使原來的資料無法顯示。確定要變換嗎？";
    $lang->confirm_delete_menu_item = "刪除選單：刪除選單的同時，將會使連結到選單的討論板或頁面一起刪除。確定要刪除嗎？";
    $lang->msg_already_registed_domain = "已註冊的域名。請使用其他的網域。";
?>
