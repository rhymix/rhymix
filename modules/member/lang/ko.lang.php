<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  한국어 언어팩 (기본적인 내용만 수록)
     **/

    $lang->member_default_info = '기본 정보';
    $lang->member_extend_info = '추가 정보';

    $lang->already_logged = '이미 로그인되어 있습니다';
    $lang->denied_user_id = '사용금지된 아이디입니다';
    $lang->null_user_id = '사용자 아이디를 입력해주세요';
    $lang->null_password = '비밀번호를 입력해주세요';
    $lang->invalid_user_id= '존재하지 않는 사용자 아이디입니다';
    $lang->invalid_password = '잘못된 비밀번호입니다';

    $lang->allow_mailing = '메일링 가입';
    $lang->denied = '사용중지';
    $lang->is_admin = '최고관리 권한';
    $lang->group = '소속 그룹';
    $lang->group_title = '그룹제목';
    $lang->group_srl = '그룹번호';
    $lang->signature = '서명';
    $lang->image_name = '이미지 이름';
    $lang->image_name_max_width = '가로 제한 크기';
    $lang->image_name_max_height = '세로 제한 크기';
    $lang->image_mark = '이미지 마크';
    $lang->image_mark_max_width = '가로 제한 크기';
    $lang->image_mark_max_height = '세로 제한 크기';
    $lang->enable_join = '회원 가입 허가';
    $lang->redirect_url = '회원 가입후 이동할 페이지';
    $lang->agreement = '회원 가입 약관';
    $lang->accept_agreement = '약관동의';
    $lang->sender = '보낸이';
    $lang->receiver = '받는이';
    $lang->friend_group = '친구 그룹';
    $lang->default_friend_group = '그룹 미지정';
    $lang->member_info = '회원 정보';

    $lang->search_target_list = array(
        'user_id' => '아이디',
        'user_name' => '이름',
        'nick_name' => '닉네임',
        'email_address' => '메일주소',
        'regdate' => '가입일시',
        'last_login' => '최근로그인일시',
    );

    $lang->message_box = array(
        'R' => '받은 쪽지함',
        'S' => '보낸 쪽지함',
        'T' => '보관함',
    );

    $lang->readed_date = "읽은 시간"; 

    $lang->cmd_login = '로그인';
    $lang->cmd_logout = '로그아웃';
    $lang->cmd_signup = '가입';
    $lang->cmd_modify_member_info = '회원정보 수정';
    $lang->cmd_modify_member_password = '비밀번호 변경';
    $lang->cmd_view_member_info = '회원정보 보기';
    $lang->cmd_leave = '탈퇴';

    $lang->cmd_member_list = '회원 목록';
    $lang->cmd_module_config = '기본 설정';
    $lang->cmd_member_group = '그룹 관리';
    $lang->cmd_send_mail = '메일발송';
    $lang->cmd_manage_id = '금지아이디관리';
    $lang->cmd_manage_form = '가입폼관리';
    $lang->cmd_view_own_document = '작성글 보기';
    $lang->cmd_send_email = '메일 보내기';
    $lang->cmd_send_message = '쪽지 보내기';
    $lang->cmd_view_friend = '친구 보기';
    $lang->cmd_add_friend = '친구 등록';
    $lang->cmd_view_message_box = '쪽지함 보기';
    $lang->cmd_store = "보관";
    $lang->cmd_add_friend_group = '친구 그룹 추가';

    $lang->msg_cart_is_null = '대상을  선택해주세요';
    $lang->msg_checked_file_is_deleted = '%d개의 첨부파일이 삭제되었습니다';

    $lang->msg_no_message = '쪽지가 없습니다';

    $lang->msg_new_member = '회원 추가';
    $lang->msg_update_member = '회원 정보 수정';
    $lang->msg_group_is_null = '등록된 그룹이 없습니다';
    $lang->msg_not_delete_default = '기본 항목을 삭제할 수 없습니다';
    $lang->msg_not_exists_member = '존재하지 않는 사용자입니다';
    $lang->msg_cannot_delete_admin = '관리자 아이디는 삭제할 수 없습니다. 관리자 해제후 다시 삭제시도해주세요';
    $lang->msg_exists_user_id = '이미 존재하는 아이디입니다. 다른 아이디를 입력해주세요';
    $lang->msg_exists_email_address = '이미 존재하는 메일주소입니다. 다른 메일주소를 입력해주세요';
    $lang->msg_exists_nick_name = '이미 존재하는 닉네임입니다. 다른 닉네임을 입력해주세요';
    $lang->msg_signup_disabled = '회원 가입을 하실 수 없습니다';
    $lang->msg_already_logged = '이미 회원 가입을 하신 상태입니다';
    $lang->msg_not_logged = '로그인을 하지 않으셨습니다';
    $lang->msg_title_is_null = '쪽지 제목을 입력해주세요';
    $lang->msg_content_is_null = '쪽지 내용을 입력해주세요';

    $lang->msg_not_uploaded_image_name = '이미지 이름을 등록할 수가 없습니다';
    $lang->msg_not_uploaded_image_mark = '이미지 마크를 등록할 수가 없습니다';

    $lang->msg_accept_agreement = '약관에 동의하셔야 합니다'; 

    $lang->about_user_id = '사용자 ID는 3~20자 사이의 영문+숫자로 이루어져야 하며 영문으로 시작되어야 합니다';
    $lang->about_password = '비밀번호는 6~20자로 되어야 합니다';
    $lang->about_user_name = '이름은 2~20자 이내여야 합니다';
    $lang->about_nick_name = '닉네임은 2~20자 이내여야 합니다';
    $lang->about_homepage = '홈페이지가 있을 경우 입력해주세요';
    $lang->about_blog = '운영하는 블로그가 있을 경우 입력해주세요';
    $lang->about_birthday = '생년월일을 입력해주세요';
    $lang->about_allow_mailing = '메일링 가입이 체크되지 않으면 단체메일 발송시 메일을 받지 않습니다';
    $lang->about_denied = '체크시 아이디를 사용할 수 없도록 합니다';
    $lang->about_is_admin = '체크시 최고 관리자 권한을 가지게 됩니다';
    $lang->about_description = '회원에 대한 관리자 메모입니다';
    $lang->about_group = '한 아이디는 여러개의 group에 속할 수 있습니다';

    $lang->about_column_type = '추가하실 가입폼의 형식을 지정해주세요';
    $lang->about_column_name = '템플릿에서 사용할수 있는 영문으로 된 이름을 적어주세요 (변수명)';
    $lang->about_column_title = '가입 또는 정보 수정/조회시에 표시될 제목입니다';
    $lang->about_default_value = '기본으로 입력될 값을 정하실 수 있습니다';
    $lang->about_active = '활성 항목에 체크를 하셔야 가입시 정상적으로 노출됩니다';
    $lang->about_form_description = '설명란에 입력을 하시면 가입시 표시가 됩니다';
    $lang->about_required = '체크하시면 회원가입시 필수항목으로 입력하도록 됩니다';

    $lang->about_enable_join = '체크를 하셔야 사용자들이 회원가입을 할 수 있습니다';
    $lang->about_redirect_url = '회원 가입후 이동할 url을 입력해 주세요. 비어 있으면 가입 이전 페이지로 돌아갑니다';
    $lang->about_agreement = '회원가입약관이 없을 경우 표시되지 않습니다';

    $lang->about_image_name = '사용자의 이름을 글자 대신 이미지로 사용할 수 있게 합니다';
    $lang->about_image_mark = '사용자의 이름앞에 마크를 달 수 있습니다';
    $lang->about_accept_agreement = '약관을 모두 읽었으며 동의합니다'; 
?>
