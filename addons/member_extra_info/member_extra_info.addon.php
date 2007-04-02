<?php
    if(!__ZBXE__) exit();

    /**
    * @file image_name.addon.php
    * @author zero (zero@nzeo.com)
    * @brief 사용자의 이름을 이미지로 바꾸거나 닉 아이콘을 추가하는 애드온
    *
    * 이 addOn은 모든 처리가 끝나고 화면에 출력하기 바로 전에 요청이 되어서
    * 사용자의 이름으로 등록된 부분을 찾아서 정규표현식으로 변환을 합니다.
    * 1. 사용자의 이름은 <div class="member_회원번호">....</div> 로 정의가 되어야 합니다.
    * 이렇게 정의된 부분의 회원번호를 구해서 이미지이름, 이미지마크가 있는지를 확인하여 있으면 내용을 변경해버립니다.
    *
    * 2. 사용자의 서명을 <div class="document_회원번호">...</div>로 정의된 곳이 글의 내용이라 판단, 하단에 서명을 추가합니다.
    *
    * 내용 변경은 MemberController::transImageName method를 이용해서 변경합니다.
    **/

    // 출력 되기 바로 직전이 아니라면 모두 무시 
    if($called_position != "before_display_content") return;

    // 기본적인 기능이라 MemberController 에 변경 코드가 있음
    $oMemberController = &getController('member');

    // 출력문서중에서 <div class="member_번호">content</div>를 찾아 MemberController::transImageName() 를 이용하여 이미지이름/마크로 변경
    $output = preg_replace_callback('!<div([^\>]*)member_([0-9]*)([^\>]*)>(.*?)\<\/div\>!is', array($oMemberController, 'transImageName'), $output);

    // 출력문서중에 <div class="document_번호">내용</div> 를 찾아서 member_controller::transSignature()를 이용해서 서명을 추가
    $output = preg_replace_callback('!<div([^\>]*)document_([0-9]*)([^\>]*)>(.*?)\<\/div\>!is', array($oMemberController, 'transSignature'), $output);
?>
