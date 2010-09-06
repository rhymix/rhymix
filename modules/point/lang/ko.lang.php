<?php
    /**
     * @file   modules/point/lang/ko.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  포인트 (point) 모듈의 기본 언어팩
     **/

    $lang->point = '포인트';
    $lang->level = '레벨';

    $lang->about_point_module = '포인트 모듈은 글 작성/댓글작성/업로드/다운로드 등의 행동을 할 때 포인트를 부여할 수 있게 합니다.';
    $lang->about_act_config = '게시판, 블로그 등 각 모듈마다 글 작성/삭제/댓글작성/삭제 등의 action이 있습니다.<br />게시판/블로그 외에 다른 모듈에 포인트 기능 연동을 하고 싶을 때는 각 기능에 맞는 act값을 추가 하시면 됩니다.<br />연결은 ,(콤마)로 하시면 됩니다.';

    $lang->max_level = '최고 레벨';
    $lang->about_max_level = '최고레벨을 지정할 수 있습니다. 레벨 아이콘을 염두에 두셔야 하고 최고 레벨은 1000이 한계입니다.';

    $lang->level_icon = '레벨 아이콘';
    $lang->about_level_icon = '레벨 아이콘은 ./modules/point/icons/레벨.gif 로 지정되며 최고레벨과 아이콘셋이 다를 수 있으니 주의해주세요!';

    $lang->point_name = '포인트 명칭';
    $lang->about_point_name = '포인트 이름이나 단위를 정할 수 있습니다.';

    $lang->level_point = '레벨 포인트';
    $lang->about_level_point = '각 회원의 포인트가 아래 레벨별 포인트에 도달하거나 미달하게 되면 해당 회원의 레벨이 조절됩니다.';

    $lang->disable_download = '다운로드 금지';
    $lang->about_disable_download = '포인트가 부족할 경우 다운로드를 금지 합니다. (이미지 파일, 동영상 파일등 직접 링크가 가능한 파일들은 예외입니다.)';
    $lang->disable_read_document = '글 열람 금지';
    $lang->about_disable_read_document = '포인트가 부족할 경우 글 열람을 금지 합니다';

    $lang->level_point_calc = '레벨별 포인트 계산';
    $lang->expression = '레벨 변수 <b>i</b>를 사용하여 자바스크립트 수식을 입력하세요. 예: Math.pow(i, 2) * 90';
    $lang->cmd_exp_calc = '계산';
    $lang->cmd_exp_reset = '초기화';

    $lang->cmd_point_recal = '포인트 초기화';
    $lang->about_cmd_point_recal = '게시글/댓글/첨부파일/회원가입 점수만 이용하여 모든 포인트 점수를 초기화 합니다.<br />회원 가입 점수는 초기화 후 해당 회원이 활동을 하면 부여되고 그 전에는 부여되지 않습니다.<br />데이터 이전 등을 하여 포인트를 완전히 초기화해야 할 경우에만 사용하세요.';

    $lang->point_link_group = '그룹 연동';
    $lang->point_group_reset_and_add = '설정된 그룹 초기화 후 새 그룹 부여';
    $lang->point_group_add_only = '새 그룹만 부여';
    $lang->about_point_link_group = '그룹에 원하는 레벨을 지정하면, 회원의 포인트가 해당 레벨의 포인트에 도달할 때 그룹이 변경됩니다.';

    $lang->about_module_point = '모듈별 포인트를 지정할 수 있으며 지정되지 않은 모듈은 기본 포인트를 이용합니다.<br />모든 점수는 반대 행동을 하였을 경우 원상복구 됩니다.';

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

    $lang->msg_cannot_download = '포인트가 부족하여 다운로드를 하실 수 없습니다.';
    $lang->msg_disallow_by_point = "포인트가 부족하여 글을 읽으실 수 없습니다. (필요한 포인트 : %d, 현재 포인트 : %d)";

    $lang->point_recal_message = '포인트 적용중입니다. (%d / %d)';
    $lang->point_recal_finished = '포인트 재계산이 완료되었습니다.';
?>
