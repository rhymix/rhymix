<?php
    /**
     * @file   common/lang/ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  한국어 언어팩 (기본적인 내용만 수록)
     **/

    // 기본적으로 사용되는 action 언어
    $lang->cmd_write = '쓰기';
    $lang->cmd_reply = '답글';
    $lang->cmd_delete = '삭제';
    $lang->cmd_modify = '수정';
    $lang->cmd_edit = '편집';
    $lang->cmd_view = '보기';
    $lang->cmd_view_all = '전체 보기';
    $lang->cmd_list = '목록';
    $lang->cmd_prev = '이전';
    $lang->cmd_next = '다음';
    $lang->cmd_send_trackback = '엮인글발송';
    $lang->cmd_registration = $lang->cmd_submit = '등록';
    $lang->cmd_comment_registration = '댓글 등록';
    $lang->cmd_insert = '추가';
    $lang->cmd_save = '저장';
    $lang->cmd_load = '불러오기';
    $lang->cmd_input = '입력';
    $lang->cmd_search = '검색';
    $lang->cmd_cancel = '취소';
    $lang->cmd_back = '돌아가기';
    $lang->cmd_vote = '추천';
    $lang->cmd_vote_down = '비추천';
    $lang->cmd_declare = '신고';
    $lang->cmd_cancel_declare = '신고 취소';
    $lang->cmd_declared_list = '신고 목록';
    $lang->cmd_copy = '복사';
    $lang->cmd_move = '이동';
    $lang->cmd_move_up = '위로';
    $lang->cmd_move_down = '아래로';
    $lang->cmd_add_indent = '들이기';
    $lang->cmd_remove_indent = '빼내기';
    $lang->cmd_management = '관리';
    $lang->cmd_make = '생성';
    $lang->cmd_select = '선택';
    $lang->cmd_select_all = '모두선택';
    $lang->cmd_unselect_all = '모두해제';
    $lang->cmd_reverse_all = "선택반전";
    $lang->cmd_close_all = '모두닫기';
    $lang->cmd_open_all = '모두열기';
    $lang->cmd_reload = '다시읽기';
    $lang->cmd_close = '닫기';
    $lang->cmd_open = '열기';
    $lang->cmd_setup = '설정';
    $lang->cmd_addition_setup = '추가 설정';
	$lang->cmd_option = '옵션';
    $lang->cmd_apply = '적용';
    $lang->cmd_open_calendar = '날짜 선택';
    $lang->cmd_send = '발송';
    $lang->cmd_print = '인쇄';
    $lang->cmd_scrap = '스크랩';
    $lang->cmd_preview = '미리 보기';
    $lang->cmd_reset = '초기화';
    $lang->cmd_remake_cache = "캐시파일 재생성";
    $lang->cmd_publish = "발행";
    $lang->cmd_layout_setup = '레이아웃 설정';
    $lang->cmd_layout_edit = '레이아웃 편집';
    $lang->cmd_search_by_ipaddress = 'IP주소로 검색';
    $lang->cmd_add_ip_to_spamfilter = '스팸필터에 IP추가';

    $lang->enable = '가능';
    $lang->disable = '불가능';

    // 기본 단어
    $lang->no = '번호';
    $lang->notice = '공지';
    $lang->secret = '비밀';
    $lang->category = $lang->category_srl = '분류';
    $lang->none_category = '분류 없음';
    $lang->document_srl = '문서번호';
    $lang->user_id = '아이디';
    $lang->author = '작성자';
    $lang->password = '비밀번호';
    $lang->password1 = '비밀번호';
    $lang->password2 = '비밀번호 확인';
    $lang->admin_id = '관리자ID';
    $lang->writer = '글쓴이';
    $lang->user_name = '이름';
    $lang->nick_name = '닉네임';
    $lang->email_address = '이메일 주소';
    $lang->homepage = '홈페이지';
    $lang->blog = '블로그';
    $lang->birthday = '생일';
    $lang->browser_title = '브라우저 제목';
    $lang->title = '제목';
    $lang->title_content = '제목+내용';
    $lang->topic = '주제';
    $lang->replies = '댓글';
    $lang->content = '내용';
    $lang->document = '게시물';
    $lang->comment = '댓글';
    $lang->description = '설명';
    $lang->trackback = '엮인글';
    $lang->tag = '태그';
    $lang->allow_comment = '댓글허용';
    $lang->lock_comment = '댓글잠금';
    $lang->allow_trackback = '엮인글허용';
    $lang->uploaded_file = '첨부';
    $lang->grant = '권한';
    $lang->target = '대상';
    $lang->total = '전체';
    $lang->total_count = '전체개수';
    $lang->ipaddress = 'IP 주소';
    $lang->path = '경로';
    $lang->cart = '선택항목';
    $lang->friend = '친구';
    $lang->notify = '알림';
    $lang->order_target = '정렬대상';
    $lang->order_type = '정렬방법';
    $lang->order_asc = '올림차순';
    $lang->order_desc = '내림차순';

    $lang->mid = '모듈이름';
    $lang->layout = '레이아웃';
    $lang->widget = '위젯 ';
    $lang->module = '모듈';
    $lang->skin = '스킨';
    $lang->colorset = '컬러셋';
    $lang->extra_vars = '확장변수';

    $lang->document_url = '게시글 주소';
    $lang->trackback_url = '엮인글 주소';
    $lang->blog_name = '블로그이름';
    $lang->excerpt = '발췌';

    $lang->document_count = '글수';
    $lang->page_count = '페이지수';
    $lang->list_count = '목록 수';
    $lang->search_list_count = '검색 목록 수';
    $lang->readed_count = '조회수';
    $lang->voted_count = '추천수';
    $lang->comment_count = '댓글수';
    $lang->member_count = '회원수';
    $lang->date = '날짜';
    $lang->regdate = '등록일';
    $lang->last_update = '최근 수정일';
    $lang->last_post = '마지막 댓글';
    $lang->signup_date = '가입일';
    $lang->last_login = '최근로그인';
    $lang->first_page = '첫페이지';
    $lang->last_page = '끝페이지';
    $lang->search_target = '검색대상';
    $lang->search_keyword = '검색어';
    $lang->is_default = '기본';

    $lang->no_documents = '등록된 글이 없습니다';

    $lang->board_manager = '게시판 관리';
    $lang->member_manager = '회원 관리';
    $lang->layout_manager = '레이아웃 관리';

    $lang->use = '사용';
    $lang->notuse = '미사용';
    $lang->not_exists = '없음';

    $lang->public = '공개';
    $lang->private = '비공개';

    $lang->unit_sec = '초';
    $lang->unit_min = '분';
    $lang->unit_hour = '시';
    $lang->unit_day = '일';
    $lang->unit_week = '주';
    $lang->unit_month = '월';
    $lang->unit_year = '년';

    $lang->unit_week = array(
        'Monday' => '월',
        'Tuesday' => '화',
        'Wednesday' => '수',
        'Thursday' => '목',
        'Friday' => '금',
        'Saturday' => '토',
        'Sunday' => '일',
    );

    $lang->unit_meridiem = array(
        'am' => '오전',
        'pm' => '오후',
        'AM' => '오전',
        'PM' => '오후',
    );

    $lang->time_gap = array(
        'min' => '%d 분전',
        'mins' => '%d 분전',
        'hour' => '%d 시간전',
        'hours' => '%d 시간전',
    );

    // 설명 관련 
    $lang->about_tag = '태그 입력시 , (쉼표)를 이용하시면 복수 등록이 가능합니다';
    $lang->about_layout = '레이아웃은 모듈의 껍데기를 꾸며줍니다. 상단 레이아웃 메뉴에서 관리하실 수 있습니다';

    // 메세지 관련
    $lang->msg_call_server = '서버에 요청중입니다. 잠시만 기다려주세요.';
    $lang->msg_db_not_setted = 'DB설정이 되어 있지 않습니다';
    $lang->msg_dbconnect_failed = "DB접속 오류가 발생하였습니다.\nDB정보를 다시 확인해주세요.";
    $lang->msg_invalid_queryid = 'Query ID값이 잘못 지정되었습니다';
    $lang->msg_not_permitted = '권한이 없습니다';
    $lang->msg_input_password = '비밀번호를 입력하여 주세요';
    $lang->msg_invalid_document = '잘못된 문서번호입니다';
    $lang->msg_invalid_request = '잘못된 요청입니다';
    $lang->msg_invalid_password = '비밀번호가 올바르지 않습니다';
    $lang->msg_error_occured = '오류가 발생하였습니다';
    $lang->msg_not_founded = '대상을 찾을 수 없습니다';
    $lang->msg_no_result = '검색 결과가 없습니다';

    $lang->msg_not_permitted_act = '요청하신 action을 실행할 수 있는 권한이 없습니다';
    $lang->msg_module_is_not_exists = '요청하신 모듈을 찾을 수 없습니다';
    $lang->msg_module_is_not_standalone = '요청하신 모듈은 독립적으로 동작할 수가 없습니다';

    $lang->success_registed = '등록되었습니다';
    $lang->success_declared = '신고되었습니다';
    $lang->success_updated = '수정되었습니다';
    $lang->success_deleted = '삭제되었습니다';
    $lang->success_voted = '추천되었습니다';
    $lang->success_blamed = '비추천되었습니다';
    $lang->success_moved = '이동되었습니다';
    $lang->success_sended = '발송되었습니다';
    $lang->success_reset = '초기화되었습니다';
    $lang->success_leaved = '탈퇴되었습니다';
    $lang->success_saved = '저장되었습니다';

    $lang->fail_to_delete = '삭제 실패하였습니다';
    $lang->fail_to_move = '이동 실패하였습니다';

    $lang->failed_voted = '추천하실 수 없습니다';
    $lang->failed_blamed = '비추천하실 수 없습니다';
    $lang->failed_declared = '신고를 하실 수 없습니다';
    $lang->fail_to_delete_have_children = '답글이 있어서 삭제할 수 없습니다';

    $lang->confirm_submit = '등록하시겠습니까?';
    $lang->confirm_logout = '로그아웃하시겠습니까?';
    $lang->confirm_vote = '추천하시겠습니까?';
    $lang->confirm_delete = '삭제하시겠습니까?';
    $lang->confirm_move = '이동하시겠습니까?';
    $lang->confirm_reset = '초기화 하시겠습니까??';
    $lang->confirm_leave = '탈퇴 하시겠습니까??';

    $lang->column_type = '형식';
    $lang->column_type_list['text'] = '한줄 입력칸 (text)';
    $lang->column_type_list['homepage'] = '홈페이지 형식 (url)';
    $lang->column_type_list['email_address'] = '이메일 형식 (email)';
    $lang->column_type_list['tel'] = '전화번호 형식 (phone)';
    $lang->column_type_list['textarea'] = '여러줄 입력칸 (textarea)';
    $lang->column_type_list['checkbox'] = '다중 선택 (checkbox)';
    $lang->column_type_list['select'] = '단일 선택 (select)';
    $lang->column_type_list['kr_zip'] = '한국주소 (zip)';
    $lang->column_type_list['date'] = '일자 (년월일)';
    //$lang->column_type_list['jp_zip'] = '일본주소 (zip)';
    $lang->column_name = '입력항목 이름';
    $lang->column_title = '입력항목 제목';
    $lang->default_value = '기본 값';
    $lang->is_active = '활성';
    $lang->is_required = '필수항목';

    // ftp 관련
    $lang->ftp_form_title = 'FTP 정보 입력';
    $lang->ftp = 'FTP';
    $lang->ftp_port = 'FTP port';
    $lang->cmd_check_ftp_connect = 'FTP 접속 확인';
    $lang->about_ftp_info = "
        FTP 정보는 다음의 경우에 이용될 수 있습니다.<br/>
        1. PHP의 safe_mode=On일 경우에 FTP 정보를 이용해서 XE를 정상적으로 동작할 수 있게 합니다.<br/>
        2. 자동 업데이트등에서 FTP 정보를 이용할 수 있습니다.<br/>
        이 FTP정보는 files/config/ftp.config.php 파일내에 정보가 저장됩니다.<br/>
        그리고 설치 후 관리자 페이지에서 FTP 정보를 변경하거나 제거할 수 있습니다.<br />
    ";

    $lang->msg_safe_mode_ftp_needed = "PHP의 safe_mode가 On일 경우 FTP 정보를 꼭 입력해주셔야 XE의 설치 및 사용이 가능합니다";
    $lang->msg_ftp_not_connected = "localhost로의 FTP 접속 오류가 발생하였습니다. ftp 포트 번호를 확인해주시거나 ftp 서비스가 가능한지 확인해주세요";
    $lang->msg_ftp_invalid_auth_info = "입력하신 FTP 정보로 로그인을 하지 못했습니다. FTP정보를 확인해주세요";
    $lang->msg_ftp_mkdir_fail = "FTP를 이용한 디렉토리 생성 명령을 실패하였습니다. FTP 서버의 설정을 확인해주세요";
    $lang->msg_ftp_chmod_fail = "FTP를 이용한 디렉토리의 속성 변경을 실패하였습니다. FTP 서버의 설정을 확인해주세요";
    $lang->msg_ftp_connect_success = "FTP 접속 및 인증 성공하였습니다";

    // xml filter에서 사용되는 javascript용 alert msg
    $lang->filter->isnull = '%s의 값을 입력해주세요';
    $lang->filter->outofrange = '%s의 글자 길이를 맞추어 주세요.';
    $lang->filter->equalto = '%s의 값이 잘못 되었습니다.';
    $lang->filter->invalid_email = '%s의 형식이 잘못되었습니다. (예: zbxe@zeroboard.com)';
    $lang->filter->invalid_user_id = $lang->filter->invalid_userid = "%s의 형식이 잘못되었습니다.\\n영문,숫자와 _로 만드실 수 있으며 제일 앞은 영문이어야 합니다";
    $lang->filter->invalid_homepage = '%s의 형식이 잘못되었습니다. (예: http://www.zeroboard.com)';
    $lang->filter->invalid_korean = '%s의 형식이 잘못되었습니다. 한글로만 입력해주셔야 합니다';
    $lang->filter->invalid_korean_number = '%s의 형식이 잘못되었습니다. 한글과 숫자로만 입력해주셔야 합니다';
    $lang->filter->invalid_alpha = '%s의 형식이 잘못되었습니다. 영문으로만 입력해주셔야 합니다';
    $lang->filter->invalid_alpha_number = '%s의 형식이 잘못되었습니다. 영문과 숫자로만 입력해주셔야 합니다';
    $lang->filter->invalid_number = '%s의 형식이 잘못되었습니다. 숫자로만 입력해주셔야 합니다';
?>
