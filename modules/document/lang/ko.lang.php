<?php
    /**
     * @file   modules/document/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  문서(document) 모듈의 기본 언어팩
     **/

    $lang->document_list = '문서 목록';
    $lang->thumbnail_type = '썸네일 생성 방법';
    $lang->thumbnail_crop = '잘라내기 (정해진 크기에 꽉 찬 모습의 썸네일을 만듭니다)';
    $lang->thumbnail_ratio = '비율 맞추기 (원본 이미지의 비율에 맞춥니다. 다만 정해진 크기에 여백이 생깁니다)';
    $lang->cmd_delete_all_thumbnail = '썸네일 모두 삭제';
    $lang->title_bold = '제목 굵게';
    $lang->title_color = '제목 색깔';
    $lang->new_document_count = '새글';

    $lang->parent_category_title = '상위 카테고리명';
    $lang->category_title = '분류명';
    $lang->category_color = '분류 폰트색깔';
    $lang->expand = '펼침';
    $lang->category_group_srls = '그룹제한';

    $lang->cmd_make_child = '하위 카테고리 추가';
    $lang->cmd_enable_move_category = '카테고리 위치 변경 (선택후 위 메뉴를 드래그하세요)';

    $lang->about_category_title = '카테고리 이름을 입력해주세요';
    $lang->about_expand = '선택하시면 늘 펼쳐진 상태로 있게 합니다';
    $lang->about_category_group_srls = '선택하신 그룹만 현재 카테고리를 지정할 수 있도록 합니다';
    $lang->about_category_color = '분류 폰트색깔을 지정합니다.';

    $lang->cmd_search_next = '계속 검색';

    $lang->cmd_temp_save = '임시 저장';

	$lang->cmd_toggle_checked_document = '선택항목 반전';
    $lang->cmd_delete_checked_document = '선택항목 삭제';
    $lang->cmd_document_do = '이 게시물을..';

    $lang->msg_cart_is_null = '삭제할 글을 선택해주세요';
    $lang->msg_category_not_moved = '이동할 수가 없습니다';
    $lang->msg_is_secret = '비밀글입니다';
    $lang->msg_checked_document_is_deleted = '%d개의 글이 삭제되었습니다';

    $lang->move_target_module = '대상 모듈';

    // 관리자 페이지에서 검색할 대상
    $lang->search_target_list = array(
        'title' => '제목',
        'content' => '내용',
        'user_id' => '아이디',
        'member_srl' => '회원번호',
        'user_name' => '사용자 이름',
        'nick_name' => '닉네임',
        'email_address' => '이메일',
        'homepage' => '홈페이지',
        'is_notice' => '공지사항',
        'is_secret' => '비밀글',
        'tags' => '태그',
        'readed_count' => '조회수 (이상)',
        'voted_count' => '추천수 (이상)',
        'comment_count ' => '코멘트수 (이상)',
        'trackback_count ' => '트랙백수 (이상)',
        'uploaded_count ' => '첨부파일수 (이상)',
        'regdate' => '등록일',
        'last_update' => '최근수정일',
        'ipaddress' => 'IP 주소',
    );

    $lang->alias = 'Alias';
    $lang->history = '히스토리';
    $lang->about_use_history = '히스토리 기능의 사용여부를 지정합니다. 히스토리 기능을 사용할 경우 문서 수정시 이전 리비전을 기록하고 복원할 수 있습니다.';
    $lang->trace_only = '흔적만 남김';
?>
