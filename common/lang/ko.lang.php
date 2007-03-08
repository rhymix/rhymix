<?php
    /**
     * @file   : common/lang/ko.lang.php
     * @author : zero <zero@nzeo.com>
     * @desc   : 한국어 언어팩 (기본적인 내용만 수록)
     **/

    // 기본적으로 사용되는 action 언어
    $lang->cmd_write = '쓰기';
    $lang->cmd_reply = '답글';
    $lang->cmd_delete = '삭제';
    $lang->cmd_modify = '수정';
    $lang->cmd_list = '목록';
    $lang->cmd_prev = '이전';
    $lang->cmd_next = '다음';
    $lang->cmd_send_trackback = '엮인글발송';
    $lang->cmd_registration = $lang->cmd_submit = '등록';
    $lang->cmd_insert = '추가';
    $lang->cmd_save = '저장';
    $lang->cmd_input = '입력';
    $lang->cmd_search = '검색';
    $lang->cmd_cancel = '취소';
    $lang->cmd_back = '돌아가기';
    $lang->cmd_vote= '추천';
    $lang->cmd_login = '로그인';
    $lang->cmd_logout = '로그아웃';
    $lang->cmd_signup = '가입';
    $lang->cmd_modify_member_info = '회원정보 수정';
    $lang->cmd_leave = '탈퇴';
    $lang->cmd_move = '이동';
    $lang->cmd_move_up = '위로';
    $lang->cmd_move_down = '아래로';
    $lang->cmd_add_indent = '들이기';
    $lang->cmd_remove_indent = '빼내기';
    $lang->cmd_management = '관리';
    $lang->cmd_make = "생성";
    $lang->cmd_select = "선택";
    $lang->cmd_select_all = "모두선택";
    $lang->cmd_unselect_all = "모두해제";
    $lang->cmd_close_all = "모두닫기";
    $lang->cmd_open_all = "모두열기";
    $lang->cmd_reload = "다시읽기";
    $lang->cmd_remake_cache = "캐시파일 재생성";

    $lang->enable = '가능';
    $lang->disable = '불가능';

    // 기본 단어
    $lang->no = '번호';
    $lang->notice = '공지';
    $lang->secret = '비밀';
    $lang->category = '분류';
    $lang->document_srl = '문서번호';
    $lang->user_id = '아이디';
    $lang->author = '작성자';
    $lang->password = '비밀번호';
    $lang->password1 = '비밀번호';
    $lang->password2 = '비밀번호 확인';
    $lang->admin_id = '관리자ID';
    $lang->user_name = '이름';
    $lang->nick_name = '닉네임';
    $lang->email_address = '이메일 주소';
    $lang->homepage = '홈페이지';
    $lang->browser_title = '브라우저 제목';
    $lang->title = '제목';
    $lang->title_content = '제목+내용';
    $lang->content = '내용';
    $lang->document = '게시물';
    $lang->comment = '댓글';
    $lang->description = '설명';
    $lang->trackback = '엮인글';
    $lang->tag = '태그';
    $lang->allow_comment = '댓글허용';
    $lang->lock_comment = '댓글잠금';
    $lang->allow_trackback = '엮인글허용';
    $lang->uploaded_file = '첨부파일';
    $lang->grant = "권한";
    $lang->target = "대상";
    $lang->total_count = "전체개수";
    $lang->ipaddress = "IP 주소";
    $lang->path = "경로";
    $lang->layout = "레이아웃";

    $lang->document_url = '게시글 주소';
    $lang->trackback_url = '엮인글 주소';
    $lang->blog_name = '블로그이름';
    $lang->excerpt = '발췌';

    $lang->document_count = '글수';
    $lang->page_count = '페이지수';
    $lang->readed_count = '조회수';
    $lang->voted_count = '추천수';
    $lang->member_count = '회원수';
    $lang->date = '날짜';
    $lang->regdate = '등록일';
    $lang->last_update = '최근수정일';
    $lang->last_login = '최종로그인';
    $lang->first_page = '첫페이지';
    $lang->last_page = '끝페이지';
    $lang->search_target = '검색대상';
    $lang->search_keyword = '검색어';
    $lang->is_default = "기본";

    $lang->use = "사용";
    $lang->notuse = "미사용";
    $lang->not_exists = "없음";

    $lang->unit_sec = "초";
    $lang->unit_min = "분";
    $lang->unit_hour = "시";
    $lang->unit_day = "일";
    $lang->unit_week = "주";
    $lang->unit_month = "월";
    $lang->unit_year = "년";

    // 설명 관련 
    $lang->about_tag = '태그 입력시 , (쉼표)를 이용하시면 복수 등록이 가능합니다';
    $lang->about_layout = '레이아웃은 모듈의 껍데기를 꾸며줍니다. 상단 레이아웃 메뉴에서 관리하실 수 있습니다';

    // xml filter에서 사용되는 javascript용 alert msg
    $lang->filter->isnull = '%s의 값을 입력해주세요';
    $lang->filter->outofrange = '%s의 글자 길이를 맞추어 주세요.';
    $lang->filter->equalto = '%s의 값이 잘못 되었습니다.';
    $lang->filter->invalid_email = '%s의 형식이 잘못되었습니다. (예: zb5@zeroboard.com)';
    $lang->filter->invalid_user_id = $lang->filter->invalid_userid = "%s의 형식이 잘못되었습니다.\\n영문,숫자와 _로 만드실 수 있으며 제일 앞은 영문이어야 합니다";
    $lang->filter->invalid_homepage = '%s의 형식이 잘못되었습니다. (예: http://www.zeroboard.com)';
    $lang->filter->invalid_korean = '%s의 형식이 잘못되었습니다. 한글로만 입력해주셔야 합니다';
    $lang->filter->invalid_korean_number = '%s의 형식이 잘못되었습니다. 한글과 숫자로만 입력해주셔야 합니다';
    $lang->filter->invalid_alpha = '%s의 형식이 잘못되었습니다. 영문으로만 입력해주셔야 합니다';
    $lang->filter->invalid_alpha_number = '%s의 형식이 잘못되었습니다. 영문과 숫자로만 입력해주셔야 합니다';
    $lang->filter->invalid_number = '%s의 형식이 잘못되었습니다. 숫자로만 입력해주셔야 합니다';

    // 메세지 관련
    $lang->msg_call_server = '서버에 요청중입니다. 잠시만 기다려주세요.';
    $lang->msg_db_not_setted = 'DB설정이 되어 있지 않습니다';
    $lang->msg_invalid_queryid = 'Query ID값이 잘못 지정되었습니다';
    $lang->msg_not_permitted = '권한이 없습니다';
    $lang->msg_input_password = '비밀번호를 입력하여 주세요';
    $lang->msg_invalid_document = '잘못된 문서번호입니다';
    $lang->msg_invalid_request = '잘못된 요청입니다';
    $lang->msg_invalid_password = '비밀번호가 올바르지 않습니다';
    $lang->msg_error_occured = '오류가 발생하였습니다';
    $lang->msg_not_founded = '대상을 찾을 수 없습니다';

    $lang->msg_module_is_not_exists = '요청하신 모듈을 찾을 수 없습니다';
    $lang->msg_module_is_not_standalone = '요청하신 모듈은 독립적으로 동작할 수가 없습니다';

    $lang->success_registed = '등록되었습니다';
    $lang->success_updated = '수정되었습니다';
    $lang->success_deleted = '삭제되었습니다';
    $lang->success_voted = '추천되었습니다';
    $lang->success_moved = '이동되었습니다';

    $lang->failed_voted = '추천하실 수 없습니다';
    $lang->fail_to_delete_have_children = '답글이 있어서 삭제할 수 없습니다';

    $lang->confirm_submit = '등록하시겠습니까?';
    $lang->confirm_logout = '로그아웃하시겠습니까?';
    $lang->confirm_vote = '추천하시겠습니까?';
    $lang->confirm_delete = '삭제하시겠습니까?';

    $lang->column_type = '형식';
    $lang->column_type_list['text'] = '한줄 입력칸 (text)';
    $lang->column_type_list['homepage'] = '홈페이지 형식 (url)';
    $lang->column_type_list['email_address'] = '이메일 형식 (email)';
    $lang->column_type_list['tel'] = '전화번호 형식 (phone)';
    $lang->column_type_list['textarea'] = '여러줄 입력칸 (textarea)';
    $lang->column_type_list['checkbox'] = '다중 선택 (checkbox)';
    $lang->column_type_list['select'] = '단일 선택 (select)';
    $lang->column_type_list['kr_zip'] = '한국주소 (zip)';
    //$lang->column_type_list['jp_zip'] = '일본주소 (zip)';
    $lang->column_name = '입력항목 이름';
    $lang->column_title = '입력항목 제목';
    $lang->default_value = '기본 값';
    $lang->is_active = '활성';
    $lang->is_required = '필수항목';
?>
