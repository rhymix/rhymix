<?php
    /**
     * @file   zh-TW.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  正體中文語言包 (包含基本內容)
     **/

    $lang->admin_info = '管理員資訊';
    $lang->admin_index = '管理首頁';

    $lang->module_category_title = array(
        'service' => '服務類模組',
        'manager' => '管理類模組',
        'utility' => '功能模組',
        'accessory' => '附加模組',
        'base' => '基本模組',
    );

    $lang->newest_news = "最新消息";

    $lang->env_setup = "系統設置";

    $lang->env_information = "系統訊息";
    $lang->current_version = "安裝版本";
    $lang->current_path = "安裝路徑";
    $lang->released_version = "最新版本";
    $lang->about_download_link = "官方網站已發佈新版本。請點擊download連結下載最新版本。";

    $lang->item_module = "模組目錄";
    $lang->item_addon  = "插件目錄";
    $lang->item_widget = "控件目錄";
    $lang->item_layout = "佈局目錄";

    $lang->module_name = "模組名稱";
    $lang->addon_name = "插件名稱";
    $lang->version = "版本";
    $lang->author = "作者";
    $lang->table_count = "表格數";
    $lang->installed_path = "安裝路徑";

    $lang->cmd_shortcut_management = "編輯選單";

    $lang->msg_is_not_administrator = '只有管理員才可以檢視';
    $lang->msg_manage_module_cannot_delete = '模組，插件，佈局，控件模組的快捷選單是不能刪除的。';
    $lang->msg_default_act_is_null = '沒有指定預設管理員的動作，是不能新增到快捷選單的。';

    $lang->welcome_to_zeroboard_xe = 'zeroboard XE 管理頁面';
    $lang->about_admin_page = "後台管理頁面未完成";
    $lang->about_lang_env = "可以設置顯示語言給首次訪問的使用者。修改語言環境後請點擊 [儲存] 按鈕進行儲存。";

    $lang->zeroboard_xe_user_links = '為用戶提供的連結';
    $lang->zeroboard_xe_developer_links = '為開發人員提供的連結';

    $lang->xe_user_links = array(
        '韓國官方主頁' => 'http://www.zeroboard.com',
        //'封測主頁' => 'http://spring.zeroboard.com',
        //'模組下載地址' => 'http://www.zeroboard.com',
        //'插件下載地址' => 'http://www.zeroboard.com',
        //'控件下載地址' => 'http://www.zeroboard.com',
        //'模組面板下載地址' => 'http://www.zeroboard.com',
        //'控件面板下載地址' => 'http://www.zeroboard.com',
        //'佈局面板下載地址' => 'http://www.zeroboard.com',
    );

    $lang->xe_developer_links = array(
        '使用手冊' => 'http://www.zeroboard.com/manual',
        //'Developer 論壇' => 'http://spring.zeroboard.com',
        '問題跟蹤' => 'http://trac.zeroboard.com',
        'SVN Repository' => 'http://svn.zeroboard.com',
        'doxygen document' => 'http://doc.zeroboard.com',
        'PDF 檔案' => 'http://doc.zeroboard.com/zeroboard_xe.pdf',
        'XE Tools' => './tools/',
    );

    $lang->zeroboard_xe_usefulness_module = '常用模組';
    $lang->xe_usefulness_modules = array(
        'dispEditorAdminIndex' => '編輯器管理',
        'dispDocumentAdminList' => '主題管理',
        'dispCommentAdminList' => '評論管理',
        'dispFileAdminList' => '附件管理',
        'dispPollAdminList' => '投票管理',
        'dispSpamfilterAdminConfig' => '垃圾過濾管理',
        'dispCounterAdminIndex' => '統計日誌',

    );

    $lang->xe_license = 'Zeroboard XE遵循 GPL協議';
    $lang->about_shortcut = '可以刪除新增到常用模組中的快捷選單。';
?>
