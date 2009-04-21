<?php
    /**
     * @file   modules/module/lang/zh-TW.lang.php
     * @author zero (zero@nzeo.com) 翻譯：royallin
     * @brief  模組(module)正體中文語言
     **/

    $lang->virtual_site = "虛擬網站";
    $lang->module_list = "模組列表";
    $lang->module_index = "模組列表";
    $lang->module_category = "模組分類";
    $lang->module_info = "模組資料";
    $lang->add_shortcut = "新增到快捷選單";
    $lang->module_action = "動作";
    $lang->module_maker = "模組作者";
    $lang->module_license = '版權';
    $lang->module_history = "更新事項";
    $lang->category_title = "分類名稱";
    $lang->header_text = '頂端內容';
    $lang->footer_text = '底端內容';
    $lang->use_category = '使用分類';
    $lang->category_title = '分類名稱';
    $lang->checked_count = '所選擇的主題數';
    $lang->skin_default_info = '預設面板資料';
    $lang->skin_author = '面板作者';
    $lang->skin_license = '版權';
    $lang->skin_history = '更新事項';
    $lang->module_copy = '複製模組';
    $lang->module_selector = '模組選擇器';
    $lang->do_selected = '選擇項目...';
    $lang->bundle_setup = '批次設定-設置';
    $lang->bundle_addition_setup = '批次設定-延伸設置';
    $lang->bundle_grant_setup = '批次設定-權限管理';
    $lang->lang_code = '語言代碼';
    $lang->filebox = '檔案管理';
    $lang->access_type = '접속 방법';
    $lang->access_domain = 'Domain 접속';
    $lang->access_vid = 'Site ID 접속';
    $lang->about_domain = "要建立網站必須要有個專屬域名。<br/>頂級域名或次級域名都可以。輸入時，請將XE安裝路徑也一起輸入。<br />例) www.xpressengine.com/xe";
    $lang->about_vid = '별도의 도메인이 아닌 http://XE주소/ID 로 접속할 수 있습니다. 모듈명(mid)와 중복될 수 없습니다.<br/>첫글자는 영문으로 시작해야 하고 영문과 숫자 그리고 _ 만 사용할 수 있습니다';
    $lang->msg_already_registed_vid = '이미 등록된 사이트 ID 입니다. 게시판등의 mid와도 중복이 되지 않습니다. 다른 ID를 입력해주세요.';
    $lang->msg_already_registed_domain = "已註冊的域名。請使用其他的網域。";

    $lang->header_script = "Header Script";
    $lang->about_header_script = "可以直接輸入，並插入到HTML的&lt;head&gt;之間。<br />可使用&lt;script&gt;,&lt;style&gt;或&lt;meta&gt;等標籤。";

    $lang->grant_access = "訪問";
    $lang->grant_manager = "管理";

    $lang->grant_to_all = "所有使用者";
    $lang->grant_to_login_user = "已登入用戶";
    $lang->grant_to_site_user = "網站用戶";
    $lang->grant_to_group = "特定使用群組";

    $lang->cmd_add_shortcut = "新增到快捷選單";
    $lang->cmd_install = "安裝";
    $lang->cmd_update = "升級";
    $lang->cmd_manage_category = '分類管理';
    $lang->cmd_manage_grant = '權限管理';
    $lang->cmd_manage_skin = '面板管理';
    $lang->cmd_manage_document = '主題管理';
    $lang->cmd_find_module = '搜尋模組';
    $lang->cmd_find_langcode = '搜尋語言代碼';

    $lang->msg_new_module = "建立模組";
    $lang->msg_update_module = "修改模組";
    $lang->msg_module_name_exists = "已存在的模組名稱。請輸入其他名稱。";
    $lang->msg_category_is_null = '沒有登錄的分類';
    $lang->msg_grant_is_null = '沒有權限';
    $lang->msg_no_checked_document = '沒有被選擇的主題';
    $lang->msg_move_failed = '移動失敗！';
    $lang->msg_cannot_delete_for_child = '無法刪除有子分類的分類！';
    $lang->msg_limit_mid ="模組名稱可由英文+[英文+數字+_]等非常多種組合。";
    $lang->msg_extra_name_exists = '이미 존재하는 확장변수 이름입니다. 다른 이름을 입력해주세요.';

    $lang->about_browser_title = "顯示在瀏覽器視窗的標題。在RSS/Trackback也可以使用。";
    $lang->about_mid = "模組名稱只允許使用英文，數字和底線。 The maximum length is 40.";
    $lang->about_default = "用沒有mid值的網址訪問網站時，將會顯示預設。";
    $lang->about_module_category = "可以分類管理模組。模組分類可以在<a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">模組管理 > 模組分類 </a>中進行管理。";
    $lang->about_description= '管理使用說明。';
    $lang->about_default = '用沒有mid值的網址訪問網站時，將會顯示預設。';
    $lang->about_header_text = '顯示在模組頂部的內容。(可使用 HTML)';
    $lang->about_footer_text = '顯示在模組底部的內容。(可使用 HTML)';
    $lang->about_skin = '可以選擇模組面板。';
    $lang->about_use_category = '選擇此項可以使用分類功能。';
    $lang->about_list_count = '可以指定每頁顯示的主題數。(預設為20個)';
    $lang->about_search_list_count = '可以指定搜尋或選擇分類時，每頁要顯示的文章數(預設為20個)。';
    $lang->about_page_count = '可以指定顯示在清單下方的頁面數(預設為10個)。 ';
    $lang->about_admin_id = '可以對該模組指定最高管理權限。';
    $lang->about_grant = '全部解除特定權限的對象時，沒有登錄的會員也將具有相關權限。';
    $lang->about_grant_deatil = '가입한 사용자는 cafeXE등 분양형 가상 사이트에 가입을 한 로그인 사용자를 의미합니다';
    $lang->about_module = "除基本函式庫以外XE全部由模組組成。\n模組管理中列出所有已安裝的模組，因此易於管理。";
    $lang->about_extra_vars_default_value = '當復選或單選的預設值需要很多個時，用逗號(,)區隔。';
    $lang->about_search_virtual_site = '가상 사이트(카페XE등)의 도메인을 입력하신 후 검색하세요.<br/>가상 사이트이외의 모듈은 내용을 비우고 검색하시면 됩니다.  (http:// 不用輸入)';
    $lang->about_extra_vars_eid_value = '확장변수의 이름을 적어주세요. ( 영문+[영문+숫자+_] 만 가능)';
    $lang->about_langcode = "想要實現多國語言功能，請按[語言代碼]按鈕。";
    $lang->about_file_extension= "只允許%s檔案。";
?>
