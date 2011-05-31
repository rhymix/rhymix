<?php
    /**
     * @file   modules/install/lang/ko.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief Korean language pack (only the more basic)
     **/
    $lang->introduce_title = 'XE 설치';
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
    $lang->install_condition_title = '필수 설치조건을 확인하세요.';
    $lang->install_checklist_title = array(
            'php_version' => 'PHP Version',
            'permission' => '퍼미션',
            'xml' => 'XML 라이브러리',
            'iconv' => 'ICONV 라이브러리',
            'gd' => 'GD 라이브러리',
            'session' => 'Session.auto_start 설정',
            'db' => 'DB',
        );
    $lang->install_checklist_desc = array(
            'php_version' => '[필수] PHP버전이 5.2.2일 경우 PHP의 버그로 인하여 설치되지 않습니다.',
            'permission' => '[필수] XE의 설치 경로 또는 ./files 디렉토리의 퍼미션이 707이어야 합니다.',
            'xml' => '[필수] XML통신을 위하여 XML 라이브러리가 필요합니다.',
            'session' => '[필수] XE에서 세션 사용을 위해 php.ini 설정의 session.auto_start=0 이어야 합니다.',
            'iconv' => 'UTF-8과 다른 언어셋의 변환을 위한 iconv설치가 필요합니다.',
            'gd' => '이미지변환 기능을 사용하기 위해 GD라이브러리가 설치되어 있어야 합니다.',
        );
    $lang->install_checklist_xml = 'XML라이브러리 설치';
    $lang->install_without_xml = 'xml 라이브러리가 설치되어 있지 않습니다.';
    $lang->install_checklist_gd = 'GD라이브러리 설치';
    $lang->install_without_gd  = '이미지 변환을 위한 GD 라이브러리가 설치되어 있지 않습니다.';
    $lang->install_without_iconv = '문자열을 처리하기 위한 iconv 라이브러리가 설치되어 있지 않습니다.';
    $lang->install_session_auto_start = 'php설정의 session.auto_start==1 이라 세션 처리에 문제가 발생할 수 있습니다.';
    $lang->install_permission_denied = '설치대상 디렉토리의 퍼미션이 707이 아닙니다.';
    $lang->install_notandum = '모든 항목을 반드시 작성해야 합니다. 모든 항목을 관리자 환경에서 수정할 수 있습니다.';
    $lang->cmd_agree_license = '라이선스에 동의합니다.';
    $lang->cmd_install_fix_checklist = '필수 설치조건을 설정하였습니다.';
    $lang->cmd_install_next = '설치를 진행합니다.';
    $lang->cmd_ignore = '무시';
    $lang->db_desc = array(
        'mysql' => 'MySQL DB를 php의 mysql*()함수를 이용하여 사용합니다.<br />DB 파일은 myisam으로 생성되기에 트랜잭션이 이루어지지 않습니다.',
        'mysqli' => 'MySQL DB를 php의 mysqli*()함수를 이용하여 사용합니다.<br />DB 파일은 myisam으로 생성되기에 트랜잭션이 이루어지지 않습니다.',
        'mysql_innodb' => 'MySQL DB를 innodb를 이용하여 사용합니다.<br />innodb는 트랜잭션을 사용할 수 있습니다.',
        'sqlite2' => '파일로 데이터를 저장하는 sqlite2를 지원합니다.<br />설치 시 DB파일은 웹에서 접근할 수 없는 곳에 생성하여 주셔야 합니다.<br />(안정화 테스트가 되지 않았습니다.)',
        'sqlite3_pdo' => 'PHP의 PDO로 sqlite3를 지원합니다.<br />설치 시 DB파일은 웹에서 접근할 수 없는 곳에 생성하여 주셔야 합니다.',
        'cubrid' => 'CUBRID DB를 이용합니다. <a href="http://xe.xpressengine.net/18180659" onclick="window.open(this.href);return false;" class="manual">manual</a>',
        'mssql' => 'MSSQL DB를 이용합니다.',
        'postgresql' => 'PostgreSql을 이용합니다.',
        'firebird' => 'Firebird를 이용합니다.<br />DB 생성 방법 (create database "/path/dbname.fdb" page_size=8192 default character set UTF-8;)',
    );
    $lang->form_title = 'DB &amp; 관리자 정보 입력';
    $lang->db_title = 'DB정보 입력';
    $lang->db_type = 'DB 종류';
    $lang->select_db_type = '사용하시려는 DB를 선택해주세요.';
    $lang->db_hostname = 'DB 호스트네임';
    $lang->db_port = 'DB Port';
    $lang->db_userid = 'DB 아이디';
    $lang->db_password = 'DB 비밀번호';
    $lang->db_database = 'DB 데이터베이스';
    $lang->db_database_file = 'DB 데이터베이스 파일';
    $lang->db_table_prefix = '테이블 머리말';
	$lang->db_info_desc = '<p><strong>DB 호스트 네임</strong>, <strong>DB Port</strong>, <strong>DB 아이디</strong>, <strong>DB 비밀번호</strong>, <strong>DB 이름</strong> 정보는 서버 호스팅 관리자로부터 정보를 확인 하세요.</p><p><strong>DB 테이블 머리말</strong> 정보는 사용자 정의 할 수 있습니다. 영문 소문자를 권장 합니다. 숫자를 포함할 수 있습니다. 특수 문자를 사용할 수 없습니다.</p>';
    $lang->admin_title = '관리자 정보';
    $lang->env_title = '환경 설정';
    $lang->use_optimizer = 'Optimizer 사용';
    $lang->about_optimizer = 'Optimizer를 사용하면 다수의 CSS/JS파일을 통합/압축 전송하여 매우 빠르게 사이트 접속이 가능하게 합니다.<br />다만 CSS나 JS에 따라서 문제가 생길 수 있습니다. 이때는 Optimizer 비활성화 하시면 정상적인 동작은 가능합니다.';
    $lang->use_rewrite = '짧은 주소 사용';
    $lang->use_sso = 'SSO 사용';
    $lang->about_rewrite = '이 기능을 사용하면 <em>http://yourdomain/<strong>?document_srl=123</strong></em> 과 같이 복잡한 주소를 <em>http://yourdomain/<strong>123</strong></em> 과 같이 간단하게 줄일 수 있습니다. 이 기능을 사용하려면 웹 서버에서 rewrite_mod를 지원해야 합니다. 웹 서버에서 rewrite_mod를 지원하는지 여부는 서버 관리자에게 문의하세요.';
    $lang->time_zone = '표준 시간대';
    $lang->about_time_zone = '서버의 설정시간과 사용하려는 장소의 시간이 차이가 날 경우 표준 시간대를 지정하면 표시되는 시간을 지정된 곳의 시간으로 사용하실 수 있습니다.';
    $lang->qmail_compatibility = 'Qmail 호환';
    $lang->about_qmail_compatibility = 'Qmail등 CRLF를 줄 구분자로 인식하지 못하는 MTA에서 메일이 발송되도록 합니다.';
    $lang->about_database_file = 'Sqlite는 파일에 데이터를 저장합니다. 데이터베이스 파일의 위치를 웹에서 접근할 수 없는 곳으로 하셔야 합니다.<br/><span style="color:red">데이터 파일은 707퍼미션 설정된 곳으로 지정해주세요.</span>';
    $lang->success_installed = '설치가 되었습니다.';
    $lang->msg_cannot_proc = '설치 환경이 갖춰지지 않아 요청을 실행할 수가 없습니다.';
    $lang->msg_already_installed = '이미 설치가 되어 있습니다.';
    $lang->msg_dbconnect_failed = "DB접속 오류가 발생하였습니다.\nDB정보를 다시 확인해주세요.";
    $lang->msg_table_is_exists = "이미 DB에 테이블이 생성되어 있습니다.\nconfig파일을 재생성하였습니다.";
    $lang->msg_install_completed = "설치가 완료되었습니다.\n감사합니다.";
    $lang->msg_install_failed = '설치 파일 생성 시에 오류가 발생하였습니다.';
    $lang->ftp_get_list = '목록 가져오기';
	$lang->read_all = '전문 읽기';
	$lang->license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->intall_env_agreement = '설치 환경 수집 동의';
	$lang->intall_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>OS, DBMS, #, #</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://korea.gnu.org/people/chsong/copyleft/lgpl.ko.html';
?>
