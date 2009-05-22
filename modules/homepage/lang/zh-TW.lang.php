<?php
    /**
     * @file   modules/homepage/lang/zh-TW.lang.phpzh-TW.lang.php
     * @author zero (zero@nzeo.com) 翻譯︰royallin
     * @brief  虛擬網站(homepage)模組正體中文語言
     **/

    $lang->cafe = "虛擬網站"; 
    $lang->cafe_id = "網站 ID"; 
    $lang->cafe_title = "網站名稱";
    $lang->cafe_description = '網站說明';
    $lang->cafe_banner = '網站廣告';
    $lang->module_type = "目標";
    $lang->board = "討論板";
    $lang->page = "頁面";
    $lang->module_id = "模組 ID";
    $lang->item_group_grant = "顯示群組";
    $lang->cafe_info = "網站資訊";
    $lang->cafe_admin = "網站管理者";
    $lang->do_selected_member = "選擇會員 : ";
    $lang->cafe_latest_documents = '最新主題';
    $lang->cafe_latest_comments = '最新評論';
    $lang->mycafe_list = '加入的網站';
    $lang->cafe_creation_type = '網站類型';
    $lang->about_cafe_creation_type = "Please choose how users access the created cafe. If you choose Site ID, they would access via http://defaultAddr/SiteID, and in the case of Domain name, they would access via sub-domain of registered domain name, http://subdomain.defaultDomain";
    $lang->cafe_main_layout = '主要版面';

    $lang->default_layout = '預設版面';
    $lang->about_default_layout = '可指定建立網站時預設的版面。';
    $lang->enable_change_layout = '變更版面';
    $lang->about_change_layout = '允許所有網站都能夠變更版面。';
    $lang->allow_service = '允許服務';
    $lang->about_allow_service = 'You may configure default setting for the number and the type of services allowed in newly created cafes';

    $lang->cmd_make_cafe = '建立網站';
    $lang->cmd_import = '匯入';
    $lang->cmd_export = '匯出';
    $lang->cafe_creation_privilege = '網站建立權限';

    $lang->cafe_main_mid = '網站 ID';
    $lang->about_cafe_main_mid = '虛擬網站的網址為「http://網址/<b>ID</b>」，請輸入想要的ID值。';

    $lang->default_menus = array(
        'home' => '首頁',
        'notice' => '公告事項',
        'levelup' => '等級審核',
        'freeboard' => '自由討論',
        'view_total' => '所有主題',
        'view_comment' => '所有評論',
        'cafe_album' => '網站相簿',
        'menu' => '選單',
        'default_group1' => '待審會員',
        'default_group2' => '準會員',
        'default_group3' => '正會員',
    );

    $lang->cmd_admin_menus = array(
        "dispHomepageManage" => "網站設定",
        "dispHomepageMemberGroupManage" => "會員群組管理",
        "dispHomepageMemberManage" => "會員列表",
        "dispHomepageTopMenu" => "主選單",
        "dispHomepageComponent" => "功能設定",
        "dispHomepageCounter" => "訪問統計",
        "dispHomepageMidSetup" => "詳細設定",
    );
    $lang->cmd_cafe_registration = "建立網站";
    $lang->cmd_cafe_setup = "網站設置";
    $lang->cmd_cafe_delete = "刪除網站";
    $lang->cmd_go_home = "移至首頁";
    $lang->cmd_go_cafe_admin = '管理頁面';
    $lang->cmd_change_layout = "變更";
    $lang->cmd_select_index = "選擇初始頁面";
    $lang->cmd_add_new_menu = "新增選單";
    $lang->default_language = "預設語言";
    $lang->about_default_language = "可以設置顯示語言給首次訪問的使用者。";

    $lang->about_cafe_act = array(
        "dispHomepageManage" => "可設定網站風格",
        "dispHomepageMemberGroupManage" => "可管理網站內的用戶群組",
        "dispHomepageMemberManage" => "可以檢視和管理會員",
        "dispHomepageTopMenu" => "可建立或管理左側或上端的選單",
        "dispHomepageComponent" => "可選擇是否啟用網頁編輯器組件/附加元件",
        "dispHomepageCounter" => "可以檢視虛擬網站的訪問數據統計。",
        "dispHomepageMidSetup" => "可詳細設定網站所使用的討論板，頁面等模組",
    );
    $lang->about_cafe = "虛擬網站模組可快速建立網站，且容易進行設定。";
    $lang->about_cafe_title = "只有在管理時才看的到此標題。";
    $lang->about_menu_names = "可指定語言。<br/>如果只輸入其中一項，其他語言將會顯示一樣。";
    $lang->about_menu_option = "可設定按選單時，是否要以新視窗開啟。<br />選展開的話，是隨版面。";

    $lang->about_group_grant = "有選擇用戶群組的話，只有被選擇的用戶群組才看的到。<br/>沒有選擇的話，非會員也能觀看。";
    $lang->about_module_type = "討論板，頁面可直接建立該模組和連結網址。<br/>注意：建立後無法再修改";
    $lang->about_browser_title = "連結選單後，顯示在瀏覽器視窗的標題。";
    $lang->about_module_id = "連結討論板，頁面等模組時，所要輸入的格式。<br/>例) http://域名/[模組 ID]，或 http://域名/?mid=[模組 ID]";
    $lang->about_menu_item_url = "目標是連結網址時，在此輸入網址。<br/>請勿輸入 http://";
    $lang->about_menu_image_button = "可用圖片代替選單名稱";
    $lang->about_cafe_delete = "刪除網站：即刪除所有相關的模組(討論板，頁面等)以及相關文章。請慎重使用。";
    $lang->about_cafe_admin = "可以建立網站管理員。<br/>管理員登入網址是 http://域名/?act=dispHomepageManage。只能在現有的會員中指定管理員。";

    $lang->confirm_change_layout = "變換版面可能會使原來的資料無法顯示。確定要變換嗎？";
    $lang->confirm_delete_menu_item = "刪除選單：刪除選單的同時，將會使連結到選單的討論板或頁面一起刪除。確定要刪除嗎？";
    $lang->msg_module_count_exceed = '模組數量已達到上限，無法再新建模組。';
    $lang->msg_not_enabled_id = '無法使用的帳號';
    $lang->msg_same_site = '無法在虛擬網站間移動模組。';
    $lang->about_move_module = '可在預設網站與虛擬網站間移動模組。<br/>Moving modules among virtual sites is note allowed. Also, if there exists a module with same mid, there can be unexpected errors, thus move only modules which have unique mid.';
?>
