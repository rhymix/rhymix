<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file image_name.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 사용자의 이미지이름/ 이미지마크나 커뮤니케이션 기능을 추가시킴
     *
     * 1. 출력되기 직전 <div class="member_회원번호">....</div> 로 정의가 된 부분을 찾아 회원번호를 구해서 
     *    이미지이름, 이미지마크가 있는지를 확인하여 있으면 내용을 변경해버립니다.
     *
     * 2. 출력되기 직전 <div class="document_회원번호">...</div>로 정의된 곳을 찾아 글의 내용이라 판단, 
     *    하단에 서명을 추가합니다.
     *
     * 3. 새로운 쪽지가 왔을 경우 팝업으로 띄움
     *
     * 4. MemberModel::getMemberMenu 호출시 대상이 회원일 경우 쪽지 보내기 기능 추가합니다.
     *
     * 5. MemberModel::getMemberMenu 호출시 친구 등록 메뉴를 추가합니다.
     *
     **/

    /**
     * 1,2 기능 수행 : 출력되기 바로 직전일 경우에 이미지이름/이미지마크등을 변경
     * 조건          : called_position == 'before_display_content' 
     **/
    if($called_position == "before_display_content") {

        // 기본적인 기능이라 MemberController 에 변경 코드가 있음
        $oMemberController = &getController('member');

        // 1. 출력문서중에서 <div class="member_번호">content</div>를 찾아 MemberController::transImageName() 를 이용하여 이미지이름/마크로 변경
        $output = preg_replace_callback('!<(div|span)([^\>]*)member_([0-9]+)([^\>]*)>(.*?)\<\/(div|span)\>!is', array($oMemberController, 'transImageName'), $output);

        // 2. 출력문서중에 <!--AfterDocument(문서번호,회원번호)--> 를 찾아서 member_controller::transSignature()를 이용해서 서명을 추가
        $output = preg_replace_callback('/<!--AfterDocument\(([0-9]+),([0-9]+)\)-->/i', array($oMemberController, 'transSignature'), $output);

    /**
     * 3 기능 수행 : 시작할때 새쪽지가 왔는지 검사
     * 조건        : called_position = 'before_module_init', module != 'member'
     **/
    } elseif($called_position == 'before_module_init' && $this->module != 'member' && Context::get('is_logged') ) {

        // 로그인된 사용자 정보를 구함
        $logged_info = Context::get('logged_info');

        $flag_path = './files/member_extra_info/new_message_flags/'.getNumberingPath($logged_info->member_srl);
        $flag_file = sprintf('%s%s', $flag_path, $logged_info->member_srl);

        // 새로운 쪽지에 대한 플래그가 있으면 쪽지 보기 팝업 띄움 
        if(file_exists($flag_file)) {
            @unlink($flag_file);
            Context::loadLang('./addons/member_extra_info/lang');
            $script =  sprintf('<script type="text/javascript"> xAddEventListener(window,"load", function() {if(confirm("%s")) { popopen("%s"); }}); </script>', Context::getLang('alert_new_message_arrived'), Context::getRequestUri().'?module=member&act=dispMemberNewMessage');
            Context::addHtmlHeader( $script );
        }

    /**
     * 4,5 기능 수행 : 사용자 이름을 클릭시 요청되는 MemberModel::getMemberMenu 후에 $menu_list에 쪽지 발송, 친구추가등의 링크 추가
     * 조건          : called_position == 'after_module_proc', module = 'member', act = 'getMemberMenu'
     **/
    } elseif($called_position == 'after_module_proc' && $this->module == 'member' && $this->act == 'getMemberMenu') {
        // 비로그인 사용자라면 패스
        if(!Context::get('is_logged')) return;

        // 로그인된 사용자 정보를 구함
        $logged_info = Context::get('logged_info');
        $member_srl = Context::get('member_srl');

        // 템플릿에서 사용되기 전의 menu_list를 가져옴
        $menu_list = $this->get('menu_list');

        // 자신이라면 쪽지함 보기 기능 추가
        if($logged_info->member_srl == $member_srl) {

            // 4. 자신의 쪽지함 보기 기능 추가
            $menu_str = Context::getLang('cmd_view_message_box');
            $menu_link = "current_url.setQuery('act','dispMemberMessages').setQuery('message_type','')";
            $menu_list .= sprintf("\n%s,%s,move_url(%s,'Y')", Context::getRequestUri().'/modules/member/tpl/images/icon_message_box.gif', $menu_str, $menu_link);

            // 5. 친구 목록 보기
            $menu_str = Context::getLang('cmd_view_friend');
            $menu_link = "current_url.setQuery('act','dispMemberFriend')";
            $menu_list .= sprintf("\n%s,%s,move_url(%s,'Y')", Context::getRequestUri().'/modules/member/tpl/images/icon_friend_box.gif',$menu_str, $menu_link);


        // 아니라면 쪽지 발송, 친구 등록 추가
        } else {

            // 대상 회원의 정보를 가져옴
            $target_member_info = $this->getMemberInfoByMemberSrl($member_srl); 
            if(!$target_member_info->member_srl) return;
            
            // 4. 쪽지 발송 메뉴를 만듬
            if( $target_member_info->allow_message =='Y' || ($target_member_info->allow_message == 'F' && $this->isFriend($member_srl))) {
                $menu_str = Context::getLang('cmd_send_message');
                $menu_link = sprintf('%s?module=member&amp;act=dispMemberSendMessage&amp;receiver_srl=%s',Context::getRequestUri(),$member_srl);
                $menu_list .= sprintf("\n%s,%s,popopen('%s','sendMessage')", Context::getRequestUri().'/modules/member/tpl/images/icon_write_message.gif', $menu_str, $menu_link);
            }

            // 5. 친구 등록 메뉴를 만듬 (이미 등록된 친구가 아닐 경우) 
            if(!$this->isAddedFriend($member_srl)) {
                $menu_str = Context::getLang('cmd_add_friend');
                $menu_link = sprintf('%s?module=member&amp;act=dispMemberAddFriend&amp;target_srl=%s',Context::getRequestUri(),$member_srl);
                $menu_list .= sprintf("\n%s,%s,popopen('%s','addFriend')", Context::getRequestUri().'/modules/member/tpl/images/icon_add_friend.gif', $menu_str, $menu_link);
            }
        }

        // 템플릿에 적용되게 하기 위해 module의 variables에 재등록
        $this->add('menu_list', $menu_list);
    }
?>
