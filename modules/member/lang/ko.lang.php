<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  한국어 언어팩 (기본적인 내용만 수록)
     **/

    $lang->cmd_member_group = "그룹 관리";
    $lang->cmd_send_mail = "메일발송";
    $lang->cmd_manage_id = "금지아이디관리";
    $lang->cmd_manage_form = "가입폼관리";

    $lang->already_logged = "이미 로그인되어 있습니다";
    $lang->denied_user_id = "사용금지된 아이디입니다";
    $lang->null_user_id = "사용자 아이디를 입력해주세요";
    $lang->null_password = "비밀번호를 입력해주세요";
    $lang->invalid_user_id= "존재하지 않는 사용자 아이디입니다";
    $lang->invalid_password = "잘못된 비밀번호입니다";

    $lang->allow_mailing = "메일링 가입";
    $lang->denied = "사용중지";
    $lang->is_admin = "최고관리 권한";
    $lang->group = "소속 그룹";
    $lang->group_title = "그룹제목";
    $lang->group_srl = "그룹번호";

    $lang->column_type = "형식";
    $lang->column_type_list->text = "한줄 입력칸";
    $lang->column_type_list->homepage = "홈페이지 형식";
    $lang->column_type_list->email_address = "이메일 형식";
    $lang->column_type_list->tel = "전화번호 형식";
    $lang->column_type_list->textarea = "여러줄 입력칸";
    $lang->column_type_list->checkbox = "다중 선택";
    $lang->column_type_list->radio = "단일 선택";
    $lang->column_type_list->kr_zip = "한국주소";
    $lang->column_type_list->jp_zip = "일본주소";
    $lang->column_name = "입력항목 이름";
    $lang->column_title = "입력항목 제목";

    $lang->msg_new_member = "회원 추가";
    $lang->msg_update_member = "회원 정보 수정";
    $lang->msg_group_is_null = "등록된 그룹이 없습니다";
    $lang->msg_not_delete_default = "기본 항목을 삭제할 수 없습니다";
    $lang->msg_not_exists_member = '존재하지 않는 사용자입니다';
    $lang->msg_cannot_delete_admin = '관리자 아이디는 삭제할 수 없습니다. 관리자 해제후 다시 삭제시도해주세요';
    $lang->msg_exists_user_id = '이미 존재하는 아이디입니다. 다른 아이디를 입력해주세요';
    $lang->msg_exists_email_address = '이미 존재하는 메일주소입니다. 다른 메일주소를 입력해주세요';
    $lang->msg_exists_nick_name = '이미 존재하는 닉네임입니다. 다른 닉네임을 입력해주세요';

    $lang->about_user_id = "사용자 ID는 3~20자 사이의 영문+숫자로 이루어져야 하며 영문으로 시작되어야 합니다";
    $lang->about_user_name = "이름은 2~20자 이내여야 합니다";
    $lang->about_nick_name = "닉네임은 2~20자 이내여야 합니다";
    $lang->about_allow_mailing = "메일링 가입이 체크되지 않으면 단체메일 발송시 메일을 받지 않습니다";
    $lang->about_denied = "체크시 아이디를 사용할 수 없도록 합니다";
    $lang->about_is_admin = "체크시 최고 관리자 권한을 가지게 됩니다";
    $lang->about_description = "회원에 대한 관리자 메모입니다";
    $lang->about_group = "한 아이디는 여러개의 group에 속할 수 있습니다";

    $lang->about_column_type = "추가하실 가입폼의 형식을 지정해주세요";
    $lang->about_column_name = "템플릿에서 사용할수 있는 영문으로 된 이름을 적어주세요 (변수명)";
    $lang->about_column_title = "가입 또는 정보 수정/조회시에 표시될 제목입니다";
?>
