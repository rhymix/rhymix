<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  한국어 언어팩 (기본적인 내용만 수록)
     **/

    $lang->admin_info = '관리자 정보';
    $lang->admin_index = '관리자 초기 페이지';
    $lang->control_panel = '제어판';

    $lang->module_category_title = array(
        'service' => '서비스 관리',
        'member' => '회원 관리',
        'content' => '정보 관리',
        'statistics' => '통계 열람',
        'construction' => '사이트 설정',
        'utility' => '기능 설정',
        'interlock' => '연동 설정',
        'accessory' => '부가 기능 설정',
        'migration' => '데이터 관리/복원',
        'system' => '시스템 관리',
    );

    $lang->newest_news = '최신 소식';

    $lang->env_setup = '환경 설정';
    $lang->default_url = '기본 URL';
    $lang->about_default_url = 'XE 가상 사이트(cafeXE등)의 기능을 사용할때 기본 URL을 입력해 주셔야 가상 사이트간 인증 연동이 되고 게시글/모듈등의 연결이 정상적으로 이루어집니다. (ex: http://도메인/설치경로)';


    $lang->env_information = '환경 정보';
    $lang->current_version = '설치된 버전';
    $lang->current_path = '설치된 경로';
    $lang->released_version = '최신 버전';
    $lang->about_download_link = "최신 버전이 배포되었습니다.\ndownload 링크를 클릭하시면 다운 받으실 수 있습니다.";

    $lang->item_module = '모듈 목록';
    $lang->item_addon  = '애드온 목록';
    $lang->item_widget = '위젯 목록';
    $lang->item_layout = '레이아웃 목록';

    $lang->module_name = '모듈 이름';
    $lang->addon_name = '애드온 이름';
    $lang->version = '버전';
    $lang->author = '제작자';
    $lang->table_count = '테이블수';
    $lang->installed_path = '설치경로';

    $lang->cmd_shortcut_management = '메뉴 편집하기';

    $lang->msg_is_not_administrator = '관리자만 접속이 가능합니다';
    $lang->msg_manage_module_cannot_delete = '모듈, 애드온, 레이아웃, 위젯 모듈의 바로가기는 삭제 불가능합니다';
    $lang->msg_default_act_is_null = '기본 관리자 Action이 지정되어 있지 않아 바로가기 등록을 할 수가 없습니다';

    $lang->welcome_to_xe = 'XE 관리자';
    $lang->about_admin_page = "관리자 페이지는 아직 미완성입니다.\n클로즈 베타동안 좋은 의견 받아서 꼭 필요한 컨텐츠를 채우도록 하겠습니다.";
    $lang->about_lang_env = '위 설정한 언어셋을 처음 방문하는 사용자들에게 동일하게 적용하기 위해서는 원하는 언어로 변경후 아래 [저장] 버튼을 클릭하시면 됩니다';

    $lang->xe_license = 'XE는 GPL을 따릅니다';
    $lang->about_shortcut = '자주 사용하는 모듈에 등록된 모듈의 바로가기를 삭제할 수 있습니다';

    $lang->yesterday = '어제';
    $lang->today = '오늘';

    $lang->cmd_lang_select = '언어선택';
    $lang->about_cmd_lang_select = '선택된 언어들만 서비스 됩니다';
    $lang->about_recompile_cache = '쓸모 없어졌거나 잘못된 캐시파일들을 정리할 수 있습니다';
    $lang->use_ssl = 'SSL 사용';
    $lang->ssl_options = array(
        'none' => '사용안함',
        'optional' => '선택적으로',
        'always' => '항상사용'
    );
    $lang->about_use_ssl = '선택적으로에서는 회원가입/정보수정등의 지정된 action에서 SSL을 사용하고 항상 사용은 모든 서비스가 SSL을 이용하게 됩니다.';
    $lang->server_ports = '서버포트지정';
    $lang->about_server_ports = 'HTTP는 80, HTTPS는 443이외의 다른 포트를 사용하는 경우에 포트를 지정해주어야합니다.';
?>
