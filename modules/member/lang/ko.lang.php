<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  한국어 언어팩 (기본적인 내용만 수록)
     **/

    $lang->member = '회원';
    $lang->member_default_info = '기본 정보';
    $lang->member_extend_info = '추가 정보';
    $lang->default_group_1 = '준회원';
    $lang->default_group_2 = '정회원';
    $lang->admin_group = '관리그룹';
    $lang->keep_signed = '로그인 유지';
    $lang->remember_user_id = '아이디 저장';
    $lang->already_logged = '이미 로그인되어 있습니다';
    $lang->denied_user_id = '사용금지된 아이디입니다';
    $lang->null_user_id = '사용자 아이디를 입력해주세요';
    $lang->null_password = '비밀번호를 입력해주세요';
    $lang->invalid_authorization = '인증이 되지 않았습니다';
    $lang->invalid_user_id= '존재하지 않는 사용자 아이디입니다';
    $lang->invalid_password = '잘못된 비밀번호입니다';
    $lang->allow_mailing = '메일링 가입';
    $lang->denied = '사용중지';
    $lang->is_admin = '최고관리 권한';
    $lang->group = '소속 그룹';
    $lang->group_title = '그룹제목';
    $lang->group_srl = '그룹번호';
    $lang->signature = '서명';
    $lang->profile_image = '프로필 사진';
    $lang->profile_image_max_width = '가로 제한 크기';
    $lang->profile_image_max_height = '세로 제한 크기';
    $lang->image_name = '이미지 이름';
    $lang->image_name_max_width = '가로 제한 크기';
    $lang->image_name_max_height = '세로 제한 크기';
    $lang->image_mark = '이미지 마크';
    $lang->image_mark_max_width = '가로 제한 크기';
    $lang->image_mark_max_height = '세로 제한 크기';
    $lang->group_image_mark = '그룹 이미지 마크';
    $lang->group_image_mark_max_width = '가로 제한 크기';
    $lang->group_image_mark_max_height = '세로 제한 크기';
    $lang->group_image_mark_order = '그룹 이미지 마크 순서';
    $lang->signature_max_height = '서명 높이 제한';
    $lang->enable_openid = 'OpenID 지원';
    $lang->enable_join = '회원 가입 허가';
    $lang->enable_confirm = '메일 인증 사용';
    $lang->enable_ssl = 'SSL 기능 사용';
    $lang->security_sign_in = '보안로그인 사용';
    $lang->limit_day = '임시 제한 일자';
    $lang->limit_date = '제한일';
    $lang->after_login_url = '로그인 후 이동할 URL';
    $lang->after_logout_url = '로그아웃 후 이동할 URL';
    $lang->redirect_url = '회원 가입후 이동할 페이지';
    $lang->agreement = '회원 가입 약관';
    $lang->accept_agreement = '약관동의';
    $lang->member_info = '회원 정보';
    $lang->current_password = '현재 비밀번호';
    $lang->openid = 'OpenID';
    $lang->allow_message = '쪽지 허용';
    $lang->allow_message_type = array(
            'Y' => '모두 허용',
            'F' => '등록된 친구들만 허용',
            'N' => '모두 금지',
    );
    $lang->about_allow_message = '쪽지 허용 방법 및 대상을 지정할 수 있습니다';
    $lang->logged_users = '현재 접속자';

    $lang->webmaster_name = '웹마스터 이름';
    $lang->webmaster_email = '웹마스터 메일주소';

    $lang->about_keep_signed = '브라우저를 닫더라도 로그인이 계속 유지될 수 있습니다.\n\n로그인 유지 기능을 사용할 경우 다음 접속부터는 로그인을 하실 필요가 없습니다.\n\n단, 게임방, 학교 등 공공장소에서 이용시 개인정보가 유출될 수 있으니 꼭 로그아웃을 해주세요';
    $lang->about_webmaster_name = '인증 메일이나 기타 사이트 관리시 사용될 웹마스터의 이름을 입력해주세요. (기본 : webmaster)';
    $lang->about_webmaster_email = '웹마스터의 메일 주소를 입력해주세요.';

    $lang->search_target_list = array(
        'user_id' => '아이디',
        'user_name' => '이름',
        'nick_name' => '닉네임',
        'email_address' => '메일주소',
        'regdate' => '가입일시',
        'regdate_more' => '가입일시(이상)',
        'regdate_less' => '가입일시(이하)',
        'last_login' => '최근로그인일시',
        'last_login_more' => '최근로그인일시(이상)',
        'last_login_less' => '최근로그인일시(이하)',
        'extra_vars' => '확장변수',
    );

    $lang->cmd_login = '로그인';
    $lang->cmd_logout = '로그아웃';
    $lang->cmd_signup = '회원 가입';
    $lang->cmd_site_signup = '가입';
    $lang->cmd_modify_member_info = '회원 정보 수정';
    $lang->cmd_modify_member_password = '비밀번호 변경';
    $lang->cmd_view_member_info = '회원 정보 보기';
    $lang->cmd_leave = '탈퇴';
    $lang->cmd_find_member_account = '아이디/비밀번호 찾기';

    $lang->cmd_member_list = '회원 목록';
    $lang->cmd_module_config = '기본 설정';
    $lang->cmd_member_group = '그룹 관리';
    $lang->cmd_send_mail = '메일발송';
    $lang->cmd_manage_id = '금지아이디관리';
    $lang->cmd_manage_form = '가입폼관리';
    $lang->cmd_view_own_document = '작성 글 보기';
    $lang->cmd_trace_document = '작성글 추적';
    $lang->cmd_trace_comment = '작성 댓글 추적';
    $lang->cmd_view_scrapped_document = '스크랩 보기';
    $lang->cmd_view_saved_document = '저장함 보기';
    $lang->cmd_send_email = '메일 보내기';

    $lang->msg_email_not_exists = '이메일 주소가 존재하지 않습니다';

    $lang->msg_alreay_scrapped = '이미 스크랩된 게시물입니다';

    $lang->msg_cart_is_null = '대상을 선택해주세요';
    $lang->msg_checked_file_is_deleted = "%d개의 첨부파일이 삭제되었습니다";

    $lang->msg_find_account_title = '아이디/ 비밀번호 정보입니다';
    $lang->msg_find_account_info = '요청하신 계정 정보는 아래와 같습니다';
    $lang->msg_find_account_comment = '아래 링크를 클릭하시면 위에 적힌 비밀번호로 바뀌게 됩니다.<br />로그인 하신 후 비밀번호를 바꾸어주세요.';
    $lang->msg_confirm_account_title = '가입 인증 메일 입니다';
    $lang->msg_confirm_account_info = '가입하신 계정 정보는 아래와 같습니다';
    $lang->msg_confirm_account_comment = '아래 링크를 클릭하시면 가입 인증이 이루어집니다.';
    $lang->msg_auth_mail_sent = "%s 메일로 인증 정보를 담은 메일이 발송되었습니다. 메일을 확인하세요.";
    $lang->msg_confirm_mail_sent = "%s 메일로 가입 인증 메일이 발송되었습니다. 메일을 확인하세요.";
    $lang->msg_invalid_auth_key = '잘못된 계정 인증 요청입니다.<br />아이디/비밀번호 찾기를 다시 하시거나 사이트 관리자에게 계정 정보를 문의해주세요';
    $lang->msg_success_authed = '인증이 정상적으로 되어 로그인 처리가 되었습니다.\n꼭 인증 메일에 표시된 비밀번호를 이용하여 원하시는 비밀번호로 변경하세요.';
    $lang->msg_success_confirmed = '가입 인증이 정상적으로 처리 되었습니다.';

    $lang->msg_new_member = '회원 추가';
    $lang->msg_update_member = '회원 정보 수정';
    $lang->msg_leave_member = '회원 탈퇴';
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
    $lang->msg_insert_group_name = '그룹명을 입력해 주세요';
    $lang->msg_check_group = '그룹을 선택해 주세요';

    $lang->msg_not_uploaded_profile_image = '프로필 이미지를 등록할 수가 없습니다';
    $lang->msg_not_uploaded_image_name = '이미지 이름을 등록할 수가 없습니다';
    $lang->msg_not_uploaded_image_mark = '이미지 마크를 등록할 수가 없습니다';
    $lang->msg_not_uploaded_group_image_mark = '그룹 이미지 마크를 등록할 수가 없습니다';

    $lang->msg_accept_agreement = '약관에 동의하셔야 합니다'; 

    $lang->msg_user_denied = '입력하신 아이디의 사용이 중지되셨습니다';
    $lang->msg_user_not_confirmed = '아직 메일 인증이 이루어지지 않았습니다. 메일을 확인해 주세요';
    $lang->msg_user_limited = "입력하신 아이디는 %s 이후부터 사용하실 수 있습니다";

    $lang->about_user_id = '사용자 ID는 3~20자 사이의 영문+숫자로 이루어져야 하며 영문으로 시작되어야 합니다';
    $lang->about_password = '비밀번호는 6~20자로 되어야 합니다';
    $lang->about_user_name = '이름은 2~20자 이내여야 합니다';
    $lang->about_nick_name = '닉네임은 2~20자 이내여야 합니다';
    $lang->about_email_address = '메일주소는 메일인증 후 비밀번호 변경이나 찾기등에 사용됩니다.';
    $lang->about_homepage = '홈페이지가 있을 경우 입력해주세요';
    $lang->about_blog_url = '운영하는 블로그가 있을 경우 입력해주세요';
    $lang->about_birthday = '생년월일을 입력해주세요';
    $lang->about_allow_mailing = '메일링 가입이 체크되지 않으면 단체메일 발송시 메일을 받지 않습니다';
    $lang->about_denied = '체크시 아이디를 사용할 수 없도록 합니다';
    $lang->about_is_admin = '체크시 최고 관리자 권한을 가지게 됩니다';
    $lang->about_member_description = '회원에 대한 관리자 메모입니다';
    $lang->about_group = '한 아이디는 여러개의 group에 속할 수 있습니다';

    $lang->about_column_type = '추가하실 가입폼의 형식을 지정해주세요';
    $lang->about_column_name = '템플릿에서 사용할수 있는 영문으로 된 이름을 적어주세요 (변수명)';
    $lang->about_column_title = '가입 또는 정보 수정/조회시에 표시될 제목입니다';
    $lang->about_default_value = '기본으로 입력될 값을 정하실 수 있습니다';
    $lang->about_active = '활성 항목에 체크를 하셔야 가입시 정상적으로 노출됩니다';
    $lang->about_form_description = '설명란에 입력을 하시면 가입시 표시가 됩니다';
    $lang->about_required = '체크하시면 회원가입시 필수항목으로 입력하도록 됩니다';

    $lang->about_enable_openid = 'OpenID 지원을 할 경우 체크하시면 됩니다';
    $lang->about_enable_join = '체크를 하셔야 사용자들이 회원가입을 할 수 있습니다';
    $lang->about_enable_confirm = '입력된 메일 주소로 인증 메일을 보내 회원 가입을 확인 합니다';
    $lang->about_enable_ssl = '서버에서 SSL지원이 될 경우 회원가입/정보수정/로그인등의 개인정보가 서버로 보내질때 SSL(https)를 이용하도록 할 수 있습니다';
    $lang->about_limit_day = '회원 가입후 정해진 일자동안 인증 제한을 할 수 있습니다';
    $lang->about_limit_date = '지정된 일자까지 해당 사용자는 로그인을 할 수 없습니다';
    $lang->about_after_login_url = '로그인 후 이동할 URL을 정하실 수 있습니다. 비어 있으면 해당 페이지가 유지됩니다.';
    $lang->about_after_logout_url = '로그아웃 후 이동할 URL을 정하실 수 있습니다. 비어 있으면 해당 페이지가 유지됩니다.';
    $lang->about_redirect_url = '회원 가입후 이동할 url을 입력해 주세요. 비어 있으면 가입 이전 페이지로 돌아갑니다';
    $lang->about_agreement = '회원가입약관이 없을 경우 표시되지 않습니다';

    $lang->about_image_name = '사용자의 이름을 글자 대신 이미지로 사용할 수 있게 합니다';
    $lang->about_image_mark = '사용자의 이름앞에 마크를 달 수 있습니다';
    $lang->about_group_image_mark = '사용자의 이름앞에 그룹 마크를 달 수 있습니다';
    $lang->about_profile_image = '사용자의 프로필 이미지를 사용할 수 있게 합니다';
    $lang->about_signature_max_height = '서명란의 높이를 제한 할 수 있습니다. (0 또는 비워두면 제한하지 않습니다)';
    $lang->about_accept_agreement = '약관을 모두 읽었으며 동의합니다'; 

    $lang->about_member_default = '회원가입시 기본 그룹으로 설정됩니다';

    $lang->about_openid = '오픈아이디로 가입시 아이디와 메일등의 기본 정보는 이 사이트에 저장이 되지만 비밀번호와 인증을 위한 처리는 해당 오픈아이디 제공서비스에서 이루어집니다.';
    $lang->about_openid_leave = '오픈아이디의 탈퇴는 현 사이트에서의 회원 정보를 삭제하는 것입니다.<br />탈퇴 후 로그인하시면 새로 가입하시는 것으로 되어 작성한 글에 대한 권한을 가질 수 없게 됩니다';
    $lang->about_find_member_account = '아이디/ 비밀번호는 가입시 등록한 메일 주소로 알려드립니다<br />가입할때 등록하신 메일 주소를 입력하시고 "아이디/ 비밀번호 찾기" 버튼을 클릭해주세요.<br />';

    $lang->about_member = "회원을 생성/수정/삭제 할 수 있고 그룹관리나 가입폼 관리등을 할 수 있는 회원 관리 모듈입니다.\n기본으로 생성된 그룹외의 그룹을 생성하여 회원 관리가 가능하고 가입폼관리를 통한 기본 정보외의 추가 정보를 요구받을 수도 있습니다.";
    $lang->about_ssl_port = '기본 포트 이외의 SSL 포트를 사용하는 경우 포트번호를 입력해주세요';
    $lang->add_openid = '오픈아이디 추가';
?>
