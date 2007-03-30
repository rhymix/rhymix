<?php
    if(!__ZBXE__) exit();

    /**
     * @file message.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 쪽지기능을 사이트내에 연결
     *
     * 1. 게시판등의 페이지에서 사용자 이름을 클릭시 요청되는 MemberModel::getMemberMenu 후에 $menu_list에 쪽지 발송 링크 추가
     * 2. 새로운 쪽지가 왔을 경우 팝업으로 띄움
     **/

    /**
     * 1. 게시판등의 페이지에서 사용자 이름을 클릭시 요청되는 MemberModel::getMemberMenu 후에 $menu_list에 쪽지 발송 링크 추가
     *    조건 : called_position == 'after_module_proc', module = 'member', act = 'getMemberMenu'
     **/
    if($called_position == 'after_module_proc' && $this->module == 'member' && $this->act == 'getMemberMenu') {

        // 비로그인 사용자라면 패스
        if(!Context::get('is_logged')) return;

        // 로그인된 사용자 정보를 구함
        $logged_info = Context::get('logged_info');
        $member_srl = Context::get('member_srl');

        // 자신이라면 패스
        if($logged_info->member_srl == $member_srl) return;

        // 언어파일 읽음
        Context::loadLang($addon_path."lang");

        // 템플릿에서 사용되기 전의 menu_list를 가져옴
        $menu_list = $this->get('menu_list');

        // 쪽지 발송 메뉴를 만듬
        $menu_str = Context::getLang('cmd_send_message');
        $menu_link = sprintf('./?module=message&amp;act=dispSendMessage&amp;target_member_srl=%s',$member_srl);

        // 메뉴에 새로 만든 쪽지 발송 메뉴를 추가
        $menu_list .= sprintf("\n%s,%s", $menu_str, $menu_link);

        // 템플릿에 적용되게 하기 위해 module의 variables에 재등록
        $this->add('menu_list', $menu_list);

    /**
     * 2. 새로운 쪽지가 왔을 경우 팝업으로 띄움
     *    조건 : called_position = 'before_display_content'
     **/
    } else if($called_position == 'before_display_content') {


        
    }
?>
