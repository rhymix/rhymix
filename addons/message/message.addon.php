<?php
    if(!__ZBXE__) exit();

    /**
    * @file message.addon.php
    * @author zero (zero@nzeo.com)
    * @brief 쪽지기능을 사이트내에 연결
    *
    * 1. MemberModel::getMemberMenu 다음 -> menu_list에 쪽지 보내기 기능 추가
    * 2. before
    **/

    // MemberModel::getMemberMenu의 결과값인 menu_list에 쪽지 관련 기능 추가 (아이디 클릭시 팝업메뉴)
    if($called_position == 'after_module_proc' && $this->module == 'member' && $this->act == 'getMemberMenu') {
        // 템플릿에서 사용되기 전의 menu_list
        $menu_list = $this->get('menu_list');

        // 로그인된 사용자 정보를 구함
        $logged_info = Context::get('logged_info');
        $member_srl = Context::get('member_srl');
        if($logged_info->member_srl != $member_srl) {
            $menu_list .= "\nhaha,gg,kk";
            $this->add('menu_list', $menu_list);
        }

    // 출력 되기 바로 직전일 경우
    } else if($called_position == "before_display_content") {
        
    }




?>
