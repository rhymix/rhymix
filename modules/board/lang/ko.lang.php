<?php
  /**
   * @file   : modules/board/lang/ko.lang.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 게시판(board) 모듈의 기본 언어팩
   **/

  // 버튼에 사용되는 언어
  $lang->cmd_view_info = "모듈정보";
  $lang->cmd_manage_category = "분류관리";
  $lang->cmd_manage_grant = "권한관리";
  $lang->cmd_manage_skin = "스킨관리";

  // 항목
  $lang->mid = "모듈이름";
  $lang->header_text = "상단 내용";
  $lang->footer_text = "하단 내용";
  $lang->skin = "스킨";
  $lang->use_category = "분류 사용";
  $lang->category_title = "분류명";
  $lang->module = "모듈";

  $lang->skin_default_info = "기본정보";
  $lang->skin_maker = "스킨제작자";
  $lang->skin_maker_homepage = "홈페이지";

  $lang->colorset = "컬러셋";
  $lang->extra_vars = "확장변수";

  // 권한의 명칭
  $lang->grant_title = array(
    'list' => "목록 접근 권한",
    'view' => "내용 접근 권한",
    'write_document' => "게시물 작성 권한",
    'write_comment' => "코멘트 작성 권한",
    'fileupload' => "파일첨부 권한",
    'management' => "관리 권한",
  );

  // 주절 주절..
  $lang->about_mid = "모듈이름은 http://주소/?mid=모듈이름 처럼 직접 호출할 수 있는 값입니다. (영문+숫자만 가능)";
  $lang->about_browser_title = "브라우저의 제목에 나타나는 값입니다. RSS/Trackback에서도 사용됩니다.";
  $lang->about_description= "관리용으로 사용되는 설명입니다";
  $lang->about_default = "선택하시면 사이트에 mid값 없이 접속하였을 경우 기본으로 보여줍니다";
  $lang->about_header_text = "모듈의 상단에 표시되는 내용입니다 (html 태그 사용 가능)";
  $lang->about_footer_text = "모듈의 하단에 표시되는 내용입니다 (html 태그 사용 가능)";
  $lang->about_skin = "모듈의 스킨을 선택하실 수 있습니다";
  $lang->about_use_category = "선택하시면 분류기능을 사용할 수 있습니다";
  $lang->about_admin_id = "해당 모듈에 대해 최고 권한을 가지는 관리자를 지정할 수 있습니다. ,(콤마)로 다수 아이디 지정이 가능합니다. (관리자페이지 접근은 불가능)";
  $lang->about_grant = "특정 권한의 대상을 모두 해제하시면 로그인하지 않은 회원까지 권한을 가질 수 있습니다";

  $lang->msg_new_module = "모듈 생성";
  $lang->msg_update_module = "모듈 수정";
  $lang->msg_category_is_null = "등록된 분류가 없습니다";
  $lang->msg_grant_is_null = "등록된 권한 대상이 없습니다";
?>
