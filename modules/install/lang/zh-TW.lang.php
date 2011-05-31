<?php
    /**
     * @file   modules/install/lang/zh-TW.lang.php
     * @author NHN (developers@xpressengine.com) 翻譯：royallin
     * @brief  安裝(install)模組正體中文語言(包含基本內容)
     **/

    $lang->introduce_title = 'XE程式安裝';
	$lang->lgpl_agree = 'GNU 약소 일반 공중 사용 허가서(LGPL v2) 동의';
	$lang->enviroment_gather = '설치 환경 수집 동의';
	$lang->install_progress_menu = array(
			'agree'=>'라이선스 동의',
			'condition'=>'설치 조건 확인',
			'ftp'=>'FTP 정보 입력',
			'dbSelect'=>'DB 선택',
			'dbInfo'=>'DB 정보 입력',
			'configInfo'=>'환경 설정',
			'adminInfo'=>'관리자 정보 입력'
		);

    $lang->install_condition_title = '確認安裝時必備的條件';

    $lang->install_checklist_title = array(
			'php_version' => 'PHP版本',
            'permission' => '權限',
            'xml' => 'XML Library',
            'iconv' => 'ICONV Library',
            'gd' => 'GD Library',
            'session' => 'Session.auto_start設置',
            'db' => 'DB',
        );

    $lang->install_checklist_desc = array(
	        'php_version' => '[必須] 由於 PHP 5.2.2 版本的問題，無法安裝 XE 程式。',
            'permission' => '[必須] XE的資料夾或『./files』資料夾權限必須是『707』。',
            'xml' => '[必須] 必須要安裝『XML Library』，才能夠使用 XML 通訊。',
            'session' => '[必須] 在『php.ini』中必須要設定『session.auto_start=0』，才能使用暫存功能',
            'iconv' => '安裝『iconv』，才能使 UTF-8 和其他語言文字作互相轉換。',
            'gd' => '安裝『GD Library』才可以使用圖片轉換功能。',
        );

    $lang->install_checklist_xml = '安裝 XML Library';
    $lang->install_without_xml = '尚未安裝 XML Library！';
    $lang->install_checklist_gd = '安裝 GD Library';
    $lang->install_without_gd  = '尚未安裝負責轉換圖片功能的 GD Library！';
    $lang->install_checklist_gd = '安裝 GD Library';
    $lang->install_without_iconv = '尚未安裝負責處理字串的『iconv』！';
    $lang->install_session_auto_start = 'PHP設置中設置成『session.auto_start==1』，可能在處理 session 時會發生錯誤。';
    $lang->install_permission_denied = '安裝目錄權限不是『707』！';

    $lang->cmd_agree_license = '同意使用條款';
    $lang->cmd_install_fix_checklist = '重新檢查';
    $lang->cmd_install_next = '開始進行安裝';
    $lang->cmd_ignore = '忽略';

    $lang->db_desc = array(
        'mysql' => '利用 PHP 的『mysql*()』函數使用 MySQL 資料庫。<br />利用『myisam』建立資料庫檔案，因此不能實現transaction。',
        'mysqli' => '利用 PHP 的『mysqli*()』函數使用 MySQL 資料庫。<br />利用『myisam』建立資料庫檔案，因此不能實現transaction。',
        'mysql_innodb' => '利用『innodb』使用 Mysql 資料庫。<br />innodb可以使用 transaction。',
        'sqlite2' => '支援用檔案形式保存數據的『sqlite2』。<br />安裝時，資料庫數據應建立在 web 無法訪問的地方。<br />(尚未通過安全測試)',
        'sqlite3_pdo' => '用 PHP 的 PDO 支援『sqlite3』。<br />安裝時，資料庫數據應建立在網頁無法訪問的地方。',
        'cubrid' => '使用 CUBRID DB。 <a href="http://www.xpressengine.com/?mid=manual&pageid=2880556" onclick="window.open(this.href);return false;" class="manual">manual</a>',
        'mssql' => '使用 MSSQL DB。',
        'postgresql' => '使用 PostgreSql DB。',
        'firebird' => '使用 Firebird DB。<br />DB 建立方式 (create database "/path/dbname.fdb" page_size=8192 default character set UTF8;)',
    );

    $lang->form_title = '輸入資料庫及管理員資訊';
    $lang->db_title = '輸入資料庫資訊';
    $lang->db_type = '資料庫類型';
    $lang->select_db_type = '請選擇要使用的資料庫。';
    $lang->db_hostname = '主機名稱';
    $lang->db_port = '埠口';
    $lang->db_userid = '使用者名稱';
    $lang->db_password = '密碼';
    $lang->db_database = '資料庫名稱';
    $lang->db_database_file = '資料庫檔案';
    $lang->db_table_prefix = '前置字元';

    $lang->admin_title = '管理員資料';

    $lang->env_title = '環境設置';
    $lang->use_optimizer = 'Optimizer';
    $lang->about_optimizer = '使用 Optimizer 可對大部分的『CSS/JS』檔案進行整合/壓縮加快網站訪問速度。<br />只是有時會發生小小的問題，這時候請暫時不要使用 Optimizer 功能。';
    $lang->use_rewrite = 'Rewrite';
    $lang->use_sso = '單一登入';
    $lang->about_rewrite = '如果支援 rewrite 功能，可縮短冗長的網址。<br />例>『http://域名/?document_srl=123』縮短成『http://域名/123』。';
	$lang->about_sso = '此功能可讓用戶只需登入一次即可訪問多個網站。<br />使用虛擬網站，這將會是很重要的功能。';
    $lang->time_zone = '時區';
    $lang->about_time_zone = '主機時間和您所處的時間有差異時，可以設置時區來滿足你所需要的時間顯示。';
    $lang->qmail_compatibility = 'Qmail互換';
    $lang->about_qmail_compatibility = '支援無法識別 CRLF 為換行符的 Qmail 等 MTA，也能發送電子郵件。';
    $lang->about_database_file = 'Sqlite是保存資料於檔案中。資料庫的檔案位置應該放在 web 不能訪問的地方。<br/><span style="color:red">資料檔案應放在具有 707 權限的位置。</span>';
    $lang->success_installed = '已完成安裝。';
    $lang->msg_cannot_proc = '不具備安裝所需環境，無法繼續安裝。';
    $lang->msg_already_installed = '已安裝';
    $lang->msg_dbconnect_failed = "連接資料庫時發生錯誤。\n請重新確認資料庫資訊。";
    $lang->msg_table_is_exists = "已建立資料表。\n重新建立 config 檔案。";
    $lang->msg_install_completed = "安裝完成。\n非常感謝。";
    $lang->msg_install_failed = "建立安裝檔案時，發生錯誤。";
    $lang->ftp_get_list = "取得列表";
	$lang->read_all = '전문 읽기';
	$lang->license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->intall_env_agreement = '설치 환경 수집 동의';
	$lang->intall_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>OS, DBMS, #, #</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html';
?>
