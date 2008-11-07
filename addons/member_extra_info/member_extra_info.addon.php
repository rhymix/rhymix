<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file image_name.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 사용자의 이미지이름/ 이미지마크등을 출력
     *
     * <div class="member_회원번호">....</div> 로 정의가 된 부분을 찾아 회원번호를 구해서 
     * 이미지이름, 이미지마크가 있는지를 확인하여 있으면 내용을 변경해버립니다.
     **/

    /**
     * 출력되기 바로 직전일 경우에 이미지이름/이미지마크등을 변경
     **/
    if($called_position == "before_display_content") {

        // 회원 이미지이름/ 마크/ 찾아서 대체할 함수를 담고 있는 파일을 include
        require_once('./addons/member_extra_info/member_extra_info.lib.php');

        // 1. 출력문서중에서 <div class="member_번호">content</div>를 찾아 MemberController::transImageName() 를 이용하여 이미지이름/마크로 변경
        $output = preg_replace_callback('!<(div|span|a)([^\>]*)member_([0-9]+)([^\>]*)>(.*?)\<\/(div|span|a)\>!is', 'memberTransImageName', $output);

    }
?>
