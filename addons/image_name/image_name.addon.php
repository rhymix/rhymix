<?php
    if(!__ZBXE__) exit();

    /**
    * @file image_name.addon.php
    * @author zero (zero@nzeo.com)
    * @brief 사용자의 이름을 이미지로 바꾸거나 닉 아이콘을 추가하는 애드온
    *
    * 이 addOn은 화면을 출력하는 바로 앞 단계에서 요청이 올때 작동하도록 한다.
    **/

    // 출력 되기 바로 직전이 아니라면 모두 무시 
    if($called_position != "before_display_content") return;

    // 출력문서중에서 <div class="member_번호">content</div>를 찾아서 변경
    $oMemberController = &getController('member');
    $output = preg_replace_callback('!<div([^\>]*)member_([0-9]*)([^\>]*)>(.*?)\<\/div\>!is', array($oMemberController, 'transImageName'), $output);
?>
