<?php
    /**
     * @file   modules/admin/lang/zh-TW.lang.php
     * @author zero (zero@nzeo.com) 翻譯：royallin
     * @brief  管理(admin)模組正體中文語言 (包含基本內容)
     **/

    $lang->admin_info = '管理員資訊';
    $lang->admin_index = '管理頁面';
    $lang->control_panel = '控制介面';
    $lang->start_module = '預設首頁';
    $lang->about_start_module = '可將所選擇的模組作為預設首頁。';

    $lang->module_category_title = array(
        'service' => '服務設定',
        'member' => '會員管理',
        'content' => '內容管理',
        'statistics' => '統計資料',
        'construction' => '界面設定',
        'utility' => '擴充功能',
        'interlock' => '連動設定',
        'accessory' => '附加功能管理',
        'migration' => '資料轉換',
        'system' => '系統管理',
    );

    $lang->newest_news = "最新消息";

    $lang->env_setup = "系統設置";
    $lang->default_url = "預設網址";
    $lang->about_default_url = "XE虛擬網站必須要先輸入預設的網址確保虛擬網站的運作，請輸入預設程式安裝路徑。<br />(例: http://網域名稱/安裝路徑)";


    $lang->env_information = "系統資訊";
    $lang->current_version = "安裝版本";
    $lang->current_path = "安裝路徑";
    $lang->released_version = "最新版本";
    $lang->about_download_link = "官方網站已發佈最新版本。\n請按[下載]下載最新版本。";

    $lang->item_module = "模組列表";
    $lang->item_addon  = "元件列表";
    $lang->item_widget = "Widget列表";
    $lang->item_layout = "版面列表";

    $lang->module_name = "模組名稱";
    $lang->addon_name = "元件名稱";
    $lang->version = "版本";
    $lang->author = "作者";
    $lang->table_count = "表格數";
    $lang->installed_path = "安裝路徑";

    $lang->cmd_shortcut_management = "編輯選單";

    $lang->msg_is_not_administrator = '只有管理員才可以檢視';
    $lang->msg_manage_module_cannot_delete = '模組，附加元件，版面設計，Widget的快捷選單是無法刪除的。';
    $lang->msg_default_act_is_null = '沒有指定預設管理員的動作，是無法新增到快捷選單的。';

    $lang->welcome_to_xe = 'XE管理頁面';
    $lang->about_lang_env = "可以設置顯示語言給首次訪問的使用者。修改語言環境後，請按[儲存]按鈕進行儲存。";

    $lang->xe_license = 'XE遵循 GPL 協議';
    $lang->about_shortcut = '可以刪除新增到常用模組中的快捷選單。';

    $lang->yesterday = "昨天";
    $lang->today = "今天";

    $lang->cmd_lang_select = "選擇語言";
    $lang->about_cmd_lang_select = "只提供所選擇的語言服務";
    $lang->about_recompile_cache = "可有效的整理錯誤的暫存檔";
    $lang->use_ssl = "SSL功能";
    $lang->ssl_options = array(
        'none' => "關閉",
        'optional' => "手動",
        'always' => "開啟"
    );
    $lang->about_use_ssl = "選擇手動時，在會員註冊或修改資料等動作時才會使用 SSL 功能。<br/>選擇開啟時，所有的服務都會使用 SSL 功能。";
    $lang->server_ports = "主機埠口";
    $lang->about_server_ports = "HTTP預設埠口是『80』、HTTPS是『443』，如果想使用其他的埠口的話，請自行設定。";
    $lang->use_db_session = 'DB session認證';
    $lang->about_db_session = '使用 PHP session 進行 DB 認證。<br/>關閉此功能對於負荷較低的網站可提高效率。<br/>使用此功能會無法統計線上人數。';
    $lang->sftp = "使用 SFTP";
    $lang->ftp_get_list = "取得列表";
    $lang->ftp_remove_info = '移除 FTP 資料';
	$lang->msg_ftp_invalid_path = '指定的 FTP 路徑讀取失敗。';
	$lang->msg_self_restart_cache_engine = '請重新啟動 Memcached 快取程式。';
	$lang->mobile_view = '手機瀏覽';
	$lang->about_mobile_view = '使用手機瀏覽時將會顯示最適當的畫面。';
    $lang->autoinstall = '自動安裝';
?>
