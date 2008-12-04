<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file member_communication.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 사용자의 커뮤니케이션 기능을 활성화
     *
     * - 새로운 쪽지가 왔을 경우 팝업으로 띄움
     * - MemberModel::getMemberMenu 호출시 대상이 회원일 경우 쪽지 보내기 기능 추가합니다.
     * - MemberModel::getMemberMenu 호출시 친구 등록 메뉴를 추가합니다.
     **/

    // 비로그인 사용자면 중지
    $logged_info = Context::get('logged_info');
    if(!$logged_info) return;

    /**
     * 기능 수행 : 팝업 및 회원정보 보기에서 쪽지/친구 메뉴 추가. 시작할때 새쪽지가 왔는지 검사
     **/
    if($called_position == 'before_module_init' && $this->module != 'member') {

        // 커뮤니케이션 모듈의 언어파일을 읽음
        Context::loadLang('./modules/communication/lang');

        // 회원 로그인 정보중에서 쪽지등의 메뉴를 추가
        $oMemberController = &getController('member');
        $oMemberController->addMemberMenu('dispCommunicationFriend', 'cmd_view_friend');
        $oMemberController->addMemberMenu('dispCommunicationMessages', 'cmd_view_message_box');

        // 새로운 쪽지에 대한 플래그가 있으면 쪽지 보기 팝업 띄움
        $flag_path = './files/member_extra_info/new_message_flags/'.getNumberingPath($logged_info->member_srl);
        $flag_file = sprintf('%s%s', $flag_path, $logged_info->member_srl);

        if(file_exists($flag_file)) {
            $new_message_count = FileHandler::readFile($flag_file);
            FileHandler::removeFile($flag_file);
            Context::loadLang('./addons/member_communication/lang');

            $script =  sprintf('<script type="text/javascript"> jQuery(function() { if(confirm("%s")) { popopen("%s"); } }); </script>', sprintf(Context::getLang('alert_new_message_arrived'), $new_message_count), Context::getRequestUri().'?module=communication&act=dispCommunicationNewMessage');

            Context::addHtmlHeader( $script );
        }

    /**
     * 기능 수행 : 사용자 이름을 클릭시 요청되는 팝업메뉴의 메뉴에 쪽지 발송, 친구추가등의 링크 추가
     **/
    } elseif($called_position == 'before_module_proc' && $this->module == 'member' && $this->act == 'getMemberMenu') {

        $oMemberController = &getController('member');
        $member_srl = Context::get('target_srl');
        $mid = Context::get('cur_mid');

        // communication 모델 객체 생성
        $oCommunicationModel = &getModel('communication');

        // 자신이라면 쪽지함 보기 기능 추가
        if($logged_info->member_srl == $member_srl) {

            // 자신의 쪽지함 보기 기능 추가
            $oMemberController->addMemberPopupMenu(getUrl('','mid',$mid,'act','dispCommunicationMessages'), 'cmd_view_message_box', './modules/communication/tpl/images/icon_message_box.gif', 'self');

            // 친구 목록 보기
            $oMemberController->addMemberPopupMenu(getUrl('','mid',$mid,'act','dispCommunicationFriend'), 'cmd_view_friend', './modules/communication/tpl/images/icon_friend_box.gif', 'self');

        // 아니라면 쪽지 발송, 친구 등록 추가
        } else {
            // 대상 회원의 정보를 가져옴
            $target_member_info = $this->getMemberInfoByMemberSrl($member_srl);
            if(!$target_member_info->member_srl) return;

            // 로그인된 사용자 정보를 구함
            $logged_info = Context::get('logged_info');

            // 쪽지 발송 메뉴를 만듬
            if( $logged_info->is_admin == 'Y' || $target_member_info->allow_message =='Y' || ($target_member_info->allow_message == 'F' && $oCommunicationModel->isFriend($member_srl)))
                $oMemberController->addMemberPopupMenu(getUrl('','module','communication','act','dispCommunicationSendMessage','receiver_srl',$member_srl), 'cmd_send_message', './modules/communication/tpl/images/icon_write_message.gif', 'popup');

            // 친구 등록 메뉴를 만듬 (이미 등록된 친구가 아닐 경우)
            if(!$oCommunicationModel->isAddedFriend($member_srl))
                $oMemberController->addMemberPopupMenu(getUrl('','module','communication','act','dispCommunicationAddFriend','target_srl',$member_srl), 'cmd_add_friend', './modules/communication/tpl/images/icon_add_friend.gif', 'popup');
        }
    }
?>