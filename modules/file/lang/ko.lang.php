<?php
    /**
     * @file   modules/file/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  첨부파일(file) 모듈의 기본 언어팩
     **/

    $lang->file = '첨부파일';
    $lang->file_name = '파일이름';
    $lang->file_size = '파일크기';
    $lang->download_count = '다운로드 받은 수';
    $lang->status = '상태';
    $lang->is_valid = '유효';
    $lang->is_stand_by = '대기';
    $lang->file_list = '첨부 파일 목록';
    $lang->allow_outlink = '파일 외부 링크';
    $lang->allow_outlink_site = '파일 외부 허용 사이트';
    $lang->allowed_filesize = '파일 제한 크기';
    $lang->allowed_attach_size = '문서 첨부 제한';
    $lang->allowed_filetypes = '허용 확장자';
    $lang->enable_download_group = '다운로드 가능 그룹';

    $lang->about_allow_outlink = '리퍼러에 따라 파일 외부 링크를 차단할 수 있습니다.(*.wmv, *.mp3등 미디어 파일 제외)';
    $lang->about_allow_outlink_site = '파일 외부 링크 설정에 관계 없이 허용하는 사이트 주소입니다. 여러개 입력시에 줄을 바꿔서 구분해주세요.<br />ex)http://www.zeroboard.com';
    $lang->about_allowed_filesize = '하나의 파일에 대해 최고 용량을 지정할 수 있습니다. (관리자는 제외)';
    $lang->about_allowed_attach_size = '하나의 문서에 첨부할 수 있는 최고 용량을 지정할 수 있습니다. (관리자는 제외)';
    $lang->about_allowed_filetypes = '허용한 확장자만 첨부할 수 있습니다. "*.확장자"로 지정할 수 있고 ";" 으로 여러개 지정이 가능합니다.<br />ex) *.* or *.jpg;*.gif;<br />(관리자는 제외)';

    $lang->cmd_delete_checked_file = '선택항목 삭제';
    $lang->cmd_move_to_document = '문서로 이동';
    $lang->cmd_download = '다운로드';

    $lang->msg_not_permitted_download = '다운로드 할 수 있는 권한이 없습니다';
    $lang->msg_cart_is_null = '삭제할 파일을 선택해주세요';
    $lang->msg_checked_file_is_deleted = '%d개의 첨부파일이 삭제되었습니다';
    $lang->msg_exceeds_limit_size = '허용된 용량을 초과하여 첨부가 되지 않았습니다';

    $lang->file_search_target_list = array(
        'filename' => '파일이름',
        'filesize' => '파일크기 (byte, 이상)',
        'filesize_mega' => '파일크기 (Mb, 이상)',
        'download_count' => '다운로드 회수 (이상)',
        'user_id' => '아이디',
        'user_name' => '이름',
        'nick_name' => '닉네임',
        'regdate' => '등록일',
        'ipaddress' => 'IP 주소',
    );
?>
