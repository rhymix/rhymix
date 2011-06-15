<?php
    /**
     * @file   en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  English language pack (Only basic contents are listed)
     **/

    $lang->introduce_title = 'XE Installation';
	$lang->lgpl_agree = 'GNU 약소 일반 공중 사용 허가서(LGPL v2) 동의';
	$lang->enviroment_gather = '설치 환경 수집 동의';
	$lang->install_progress_menu = array(
			'agree'=>'Acceptance of terms',
			'condition'=>'Check the installation conditions',
			'ftp'=>'Input FTP information',
			'dbSelect'=>'Choose database type',
			'dbInfo'=>'Input Database information',
			'configInfo'=>'Preferences',
			'adminInfo'=>'Enter Administrator information'
			);
    $lang->install_condition_title = "Please check the installation requirement.";
    $lang->install_checklist_title = array(
			'php_version' => 'PHP Version',
            'permission' => 'Permission',
            'xml' => 'XML Library',
            'iconv' => 'ICONV Library',
            'gd' => 'GD Library',
            'session' => 'Session.auto_start setting',
            'db' => 'DB',
        );

	$lang->install_license_desc = array(
			'lgpl' => 'GNU 약소 일반 공중 사용 허가서(LGPL v2)에 동의해야 합니다.'
		);
    $lang->install_checklist_desc = array(
			'php_version' => '[Required] If PHP version is 5.2.2, XE will not be installed because of a bug',
            'permission' => '[Required] XE installation path or ./files directory\'s permission must be 707',
            'xml' => '[Required] XML Library is needed for XML communication',
            'session' => '[Required] PHP setting file\'s (php.ini) \'Session.auto_start\' must equal to zero in order for XE to use the session',
            'iconv' => 'Iconv should be installed in order to convert between UTF-8 and other language sets',
            'gd' => 'GD Library should be installed in order to use functions to convert images',
        );

    $lang->install_checklist_xml = 'Install XML Library';
    $lang->install_without_xml = 'XML Library is not installed';
    $lang->install_checklist_gd = 'Install GD Library';
    $lang->install_without_gd  = 'GD Library is not installed for image convertion';
    $lang->install_without_iconv = 'Iconv Library is not installed for processing characters';
    $lang->install_session_auto_start = 'Possible problems might occur due to the php setting. session.auto_start is equal to 1';
    $lang->install_permission_denied = 'Installation path\'s permission doesn\'t equal to 707';
	$lang->install_notandum = 'All form must be filled, but you can modify all of settings after finish the installation.';
    $lang->cmd_agree_license = 'I agree with the license';
    $lang->cmd_install_fix_checklist = 'I have fixed the required conditions.';
    $lang->cmd_install_next = 'Continue installation';
    $lang->cmd_ignore = 'Ignore';

    $lang->db_desc = array(
        'mysql' => 'Use MySQL as a database with mysql*() functions in php.<br />Transactions will not be processed since DB file is created in myisam.',
        'mysqli' => 'Use MySQL as a database with mysqli*() functions in php.<br />Transactions will not be processed since DB file is created in myisam',
        'mysql_innodb' => 'Use MySQL as a database with innodb.<br />Transactions will be processed with innodb',
        'sqlite2' => 'Use sqlite2 as a database which saves the data in files.<br />DB file <b>must not be</b> accessible from the web.<br />(Never been tested for stabilization)',
        'sqlite3_pdo' => 'Use sqlite3 as a database which supports PHP PDO.<br />DB file <b>must not be</b> accessible from the web.',
        'cubrid' => 'Use CUBRID as a database. See <a href="http://www.xpressengine.com/?mid=manual&pageid=2880556" onclick="window.open(this.href);return false;" class="manual">manual</a> for more info',
        'mssql' => 'Use MSSQL as a database',
        'postgresql' => 'Use PostgreSql as a database.',
        'firebird' => 'Use Firebird as a database.<br />You can create a database with (create database "/path/dbname.fdb" page_size=8192 default character set UTF-8;)',
    );

    $lang->form_title = 'Database &amp; Administrator Information';
    $lang->db_title = 'Please input DB information';
    $lang->db_type = 'DB Type';
    $lang->select_db_type = 'Please select the DB you want to use.';
    $lang->db_hostname = 'DB Hostname';
    $lang->db_port = 'DB Port';
    $lang->db_userid = 'DB ID';
    $lang->db_password = 'DB Password';
    $lang->db_database = 'DB Database';
    $lang->db_database_file = 'DB Database File';
	$lang->db_table_prefix = 'Table Header';
	$lang->db_info_desc = '<p>Please check <strong>database information</strong> to server master.</p><p>You can modify database <strong>table preface</strong>, and can use small letters(small letter is recommended), and numbers, but you can not use special letters.</p>';

    $lang->admin_title = 'Administrator Info';

    $lang->env_title = 'Configuration';
    $lang->use_optimizer = 'Enable Optimizer';
    $lang->about_optimizer = 'If optimizer is enabled, users can quickly access to this site, since multiple CSS / JS files are put together and compressed before transmission. <br /> Nevertheless, this optimization might be problematic according to CSS or JS. If you disable it, it would work properly though it would work slower.';
    $lang->use_rewrite = 'Rewrite Mod';
    $lang->use_sso = 'Single Sign On';
    $lang->about_rewrite = "If web server provides rewrite mod, long URL such as http://blah/?document_srl=123 can be shortened like http://blah/123";
	$lang->about_sso = 'SSO will enable users to sign in just once for both default and virtual site. You will need this only if you are using virtual sites.';
    $lang->time_zone = 'Time Zone';
    $lang->about_time_zone = "If the server time and the time on your location don't accord each other, you can set the time to be same as your location by using the time zone";
    $lang->qmail_compatibility = 'Enable Qmail';
    $lang->about_qmail_compatibility = 'It will enable sending mails from MTA which cannot distinguish CRLF like Qmail.';
    $lang->about_database_file = 'Sqlite saves data in a file. Location of the database file should be unreachable by web<br/><span style="color:red">Data file should be inside the permission of 707.</span>';
    $lang->success_installed = 'Installation has been completed';
    $lang->msg_cannot_proc = 'Installation environment is not proper to proceed.';
    $lang->msg_already_installed = 'XE is already installed';
    $lang->msg_dbconnect_failed = "Error has occurred while connecting DB.\nPlease check DB information again";
    $lang->msg_table_is_exists = "Table is already created in the DB.\nConfig file is recreated";
    $lang->msg_install_completed = "Installation has been completed.\nThank you for choosing XE";
    $lang->msg_install_failed = "An error has occurred while creating installation file.";
    $lang->ftp_get_list = "Get List";
	$lang->read_all = '전문 읽기';
	$lang->license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->intall_env_agreement = '설치 환경 수집 동의';
	$lang->intall_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>OS, DBMS, #, #</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html';
?>
