<?php
    /**
     * @file   /modules/editor/components/naver_map/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  위지윅에디터(editor) 모듈 > 멀티미디어 링크 (naver_map) 컴포넌트의 언어팩
     **/

    $lang->map_width = "가로크기";
    $lang->map_height = "세로크기";

    // 문구
    $lang->about_address = "예) 분당 정자동, 역삼";
    $lang->about_address_use = "검색창에서 원하는 주소를 검색하신후 출력된 결과물을 선택하시고 [추가] 버튼을 눌러주시면 글에 지도가 추가가 됩니다";

    // 에러 메세지들
    $lang->msg_not_exists_addr = "검색하려는 대상이 없습니다";
    $lang->msg_fail_to_socket_open = "우편번호 검색 대상 서버 접속이 실패하였습니다";
    $lang->msg_no_result = "검색 결과가 없습니다";

    $lang->msg_no_apikey = "네이버맵 사용을 위해서는 네이버맵 open api key가 있어야 합니다.\nopen api key를 관리자 >  위지윅에디터 > <a href=\"#\" onclick=\"popopen('./?module=editor&amp;act=setupComponent&amp;component_name=naver_map','SetupComponent');return false;\">네이버 지도 연동 컴포넌트 설정</a>을 선택한 후 입력하여 주세요";
?>
