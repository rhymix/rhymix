<?php
    /**
     * @file   modules/point/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  포인트 (point) 모듈의 기본 언어팩
     **/

    $lang->point = "포인트"; 
    $lang->level = "레벨"; 

    $lang->about_point_module = "포인트 모듈은 글작성/댓글작성/업로드/다운로드등의 행동을 할때 포인트를 부여할 수 있습니다.<br />단 포인트 모듈에서는 설정만 할 뿐이고 포인트 애드온을 활성화 시켜야 포인트가 누적이 됩니다";
    $lang->about_act_config = "게시판,블로그등의 모듈마다 글작성/삭제/댓글작성/삭제등의 action이 있습니다.<br />게시판/블로그외의 모듈에 포인트 기능 연동을 하고 싶을때는 각 기능에 맞는 act값을 추가해주시면 됩니다.<br />연결은 ,(콤마)로 해주시면 됩니다.";

    $lang->max_level = '최고 레벨';
    $lang->about_max_level = '최고레벨을 지정하실 수 있습니다. 레벨 아이콘을 염두에 두셔야 하고 최고 레벨은 1000이 한계입니다';

    $lang->level_icon = '레벨 아이콘';
    $lang->about_level_icon = '레벨아이콘은 ./modules/point/icons/레벨.gif 로 지정되며 최고레벨과 아이콘셋이 다를 수 있으니 주의해주세요';

    $lang->point_name = '포인트 명칭';
    $lang->about_point_name = '포인트의 이름이나 단위를 정하실 수 있습니다';

    $lang->level_point = '레벨 포인트';
    $lang->about_level_point = '아래 각 레벨별 포인트에 도달하거나 감소하게 되면 레벨이 조절됩니다';

    $lang->disable_download = '다운로드 금지';
    $lang->about_disable_download = '포인트가 없을 경우 다운로드를 금지하게 합니다. (이미지파일은 제외입니다)';

    $lang->level_point_calc = '레벨별 포인트 계산';
    $lang->expression = '레벨 변수 <b>i</b>를 사용하여 자바스크립트 수식을 입력하세요. 예: Math.pow(i, 2) * 90';
    $lang->cmd_exp_calc = '계산';
    $lang->cmd_exp_reset = '초기화';

    $lang->cmd_point_recal = '포인트 초기화';
    $lang->about_cmd_point_recal = '게시글/댓글/첨부파일/회원가입 점수만 이용하여 모든 포인트 점수를 초기화 합니다.<br />회원 가입 점수는 초기화 후 해당 회원이 활동을 하면 부여되고 그 전에는 부여되지 않습니다.<br />데이터 이전등을 하여 포인트를 완전히 초기화 해야 할 경우에만 사용하세요.';

    $lang->point_link_group = '그룹 연동';
    $lang->about_point_link_group = '그룹에 원하는 레벨을 지정하면 해당 레벨에 도달할때 그룹이 변경됩니다. 단 새로운 그룹으로 변경될때 이전에 자동 등록된 그룹은 제거됩니다.';

    $lang->about_module_point = '모듈별로 포인트를 지정할 수 있으며 지정되지 않은 모듈은 기본 포인트를 이용하게 됩니다<br />모든 점수는 반대 행동을 하였을 경우 원상복귀 됩니다.';

    $lang->point_signup = '가입';
    $lang->point_insert_document = '글 작성';
    $lang->point_delete_document = '글 삭제';
    $lang->point_insert_comment = '댓글 작성';
    $lang->point_delete_comment = '댓글 삭제';
    $lang->point_upload_file = '파일 업로드';
    $lang->point_delete_file = '파일 삭제';
    $lang->point_download_file = '파일 다운로드 (이미지 제외)';
    $lang->point_read_document = '게시글 조회';
    $lang->point_voted = '추천 받음';
    $lang->point_blamed = '비추천 받음';

    $lang->cmd_point_config = '기본 설정';
    $lang->cmd_point_module_config = '모듈별 설정';
    $lang->cmd_point_act_config = '기능별 act 설정';
    $lang->cmd_point_member_list = '회원 포인트 목록';

    $lang->msg_cannot_download = '포인트가 부족하여 다운로드를 하실 수 없습니다';

    $lang->point_recal_message = '포인트 적용중입니다. (%d / %d)';
    $lang->point_recal_finished = '포인트 재계산이 모두 완료되었습니다';
?>
