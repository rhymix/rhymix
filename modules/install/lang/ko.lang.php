<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  한국어 언어팩 (기본적인 내용만 수록)
     **/

    $lang->introduce_title = '제로보드 XE 설치';
    $lang->license = 
        "제로보드XE는 GPL라이센스를 따릅니다";

    $lang->install_condition_title = "설치 조건";

    $lang->install_checklist_title = array(
            'permission' => '퍼미션',
            'xml' => 'XML 라이브러리',
            'iconv' => 'ICONV 라이브러리',
            'gd' => 'GD 라이브러리',
            'session' => 'Session.auto_start 설정',
        );

    $lang->install_checklist_desc = array(
            'permission' => '[필수] 제로보드의 설치 경로 또는 ./files 디렉토리의 퍼미션이 707이어야 합니다',
            'xml' => '[필수] XML통신을 위하여 XML 라이브러리가 필요합니다',
            'session' => '[필수] 제로보드에서 세션 사용을 위해 php.ini 설정의 session.auto_start=0 이어야 합니다',
            'iconv' => 'UTF-8과 다른 언어셋의 변환을 위한 iconv설치가 필요합니다',
            'gd' => '이미지변환 기능을 사용하기 위해 GD라이브러리가 설치되어 있어야 합니다',
        );

    $lang->install_checklist_xml = 'XML라이브러리 설치';
    $lang->install_without_xml = 'xml 라이브러리가 설치되어 있지 않습니다';
    $lang->install_checklist_gd = 'GD라이브러리 설치';
    $lang->install_without_gd  = '이미지 변환을 위한 gd 라이브러리가 설치되어 있지 않습니다';
    $lang->install_checklist_gd = 'GD라이브러리 설치';
    $lang->install_without_iconv = '문자열을 처리하기 위한 iconv 라이브러리가 설치되어 있지 않습니다';
    $lang->install_session_auto_start = 'php설정의 session.auto_start==1 이라 세션 처리에 문제가 발생할 수 있습니다';
    $lang->install_permission_denied = '설치대상 디렉토리의 퍼미션이 707이 아닙니다';

    $lang->cmd_agree_license = '라이센스에 동의합니다';
    $lang->cmd_install_fix_checklist = '필수 조건을 설정후 다음 버튼을 눌러 주세요.';
    $lang->cmd_install_next = '설치를 진행합니다';

    $lang->db_desc = array(
        'mysql' => 'mysql DB를 php의 mysql*()함수를 이용하여 사용합니다.<br />DB 파일은 myisam으로 생성되기에 트랜잭션이 이루어지지 않습니다.',
        'mysqli' => 'mysql DB를 php의 mysqli*()함수를 이용하여 사용합니다.<br />DB 파일을 INNODB로 생성하여 트랜잭션 기능을 수행할 수 있습니다.<br />(안정화 테스트가 되지 않았습니다)',
        'sqlite2' => '파일로 데이터를 저장하는 sqlite2를 지원합니다.<br />설치시 DB파일은 웹에서 접근할 수 없는 곳에 생성하여 주셔야 합니다.<br />(안정화 테스트가 되지 않았습니다)',
        'sqlite3_pdo' => 'PHP의 PDO로 sqlite3를 지원합니다.<br />설치시 DB파일은 웹에서 접근할 수 없는 곳에 생성하여 주셔야 합니다.',
        'cubrid' => 'CUBRID DB를 이용합니다.<br />(안정화 테스트 및 튜닝이 되지 않았습니다)',
    );

    $lang->db_title = 'DB정보 입력';
    $lang->db_type = 'DB 종류';
    $lang->db_hostname = 'DB 호스트네임';
    $lang->db_port = 'DB Port';
    $lang->db_userid = 'DB 아이디';
    $lang->db_password = 'DB 비밀번호';
    $lang->db_database = 'DB 데이터베이스';
    $lang->db_database_file = 'DB 데이터베이스 파일';
    $lang->db_table_prefix = '테이블 머릿말';

    $lang->admin_title = '관리자정보';

    $lang->default_group_1 = "준회원";
    $lang->default_group_2 = "정회원";

    $lang->about_database_file = 'Sqlite는 파일에 데이터를 저장합니다. 데이터베이스 파일의 위치를 웹에서 접근할 수 없는 곳으로 하셔야 합니다';

    $lang->msg_cannot_proc = '설치 환경이 갖춰지지 않아 요청을 실행할 수가 없습니다';
    $lang->msg_already_installed = '이미 설치가 되어 있습니다';
    $lang->msg_dbconnect_failed = "DB접속 오류가 발생하였습니다.\nDB정보를 다시 확인해주세요";
    $lang->msg_table_is_exists = "이미 DB에 테이블이 생성되어 있습니다.\nconfig파일을 재생성하였습니다";
    $lang->msg_install_completed = "설치가 완료되었습니다.\n감사합니다";
    $lang->msg_install_failed = "설치 파일 생성시에 오류가 발생하였습니다.";
?>
