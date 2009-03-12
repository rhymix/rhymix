<?php
    /**
     * @file   modules/integration_search/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  한국어 언어팩 (기본적인 내용만 수록)
     **/

    $lang->integration_search = '통합검색';

    $lang->sample_code = '샘플코드';
    $lang->about_target_module = '선택된 모듈만 검색 대상으로 정합니다. 권한설정에 대한 주의를 바랍니다';
    $lang->about_sample_code = '위 코드를 레이아웃등에 추가하시면 통합검색이 가능합니다';
    $lang->msg_no_keyword = '검색어를 입력해주세요';
    $lang->msg_document_more_search  = '계속 검색 버튼을 선택하시면 아직 검색하지 않은 부분까지 계속 검색 하실 수 있습니다';

    $lang->is_result_text = "<strong>'%s'</strong> 에 대한 검색결과 <strong>%d</strong>건";
    $lang->multimedia = '이미지/동영상';

    $lang->is_search_option = array(
        'document' => array(
            'title_content' => '제목+내용',
            'title' => '제목',
            'content' => '내용',
            'tag' => '태그',
        ),
        'trackback' => array(
            'url' => '대상 URL',
            'blog_name' => '대상 사이트 이름',
            'title' => '제목',
            'excerpt' => '내용',
        ),
    );

    $lang->is_sort_option = array(
        'regdate' => '등록일',
        'comment_count' => '댓글수',
        'readed_count' => '조회수',
        'voted_count' => '추천수',
    );
?>
