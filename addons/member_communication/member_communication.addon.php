<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file member_communication.addon.php
     * @author NHN (developers@xpressengine.com)
     * @brief Promote user communication 
     *
     * - Pop-up the message if new message comes in
     * - When calling MemberModel::getMemberMenu, feature to send a message is added
     * - When caliing MemberModel::getMemberMenu, feature to add a friend is added
     **/
    // Stop if non-logged-in user is
    $logged_info = Context::get('logged_info');
    if(!$logged_info) return;

    /**
     * Message/Friend munus are added on the pop-up window and member profile. Check if a new message is received
     **/
    if($called_position == 'before_module_init' && $this->module != 'member') {
        // Load a language file from the communication module
        Context::loadLang('./modules/communication/lang');
        // Add menus on the member login information
        $oMemberController = &getController('member');
        $oMemberController->addMemberMenu('dispCommunicationFriend', 'cmd_view_friend');
        $oMemberController->addMemberMenu('dispCommunicationMessages', 'cmd_view_message_box');
        // Pop-up to display messages if a flag on new message is set
        $flag_path = './files/member_extra_info/new_message_flags/'.getNumberingPath($logged_info->member_srl);
        $flag_file = $flag_path.$logged_info->member_srl;

        if(file_exists($flag_file)) {
            $new_message_count = trim(FileHandler::readFile($flag_file));
            FileHandler::removeFile($flag_file);
            Context::loadLang('./addons/member_communication/lang');
			Context::loadFile(array('./addons/member_communication/tpl/member_communication.js'), true);

			$text   = preg_replace('@\r?\n@', '\\n', addslashes(Context::getLang('alert_new_message_arrived')));
			$link   = Context::getRequestUri().'?module=communication&act=dispCommunicationNewMessage';
            $script = "<script type=\"text/javascript\">jQuery(function(){ xeNotifyMessage('{$text}','{$new_message_count}'); });</script>";

			Context::addHtmlFooter($script);
		}
    } elseif($called_position == 'before_module_proc' && $this->act == 'getMemberMenu') {

        $oMemberController = &getController('member');
        $member_srl = Context::get('target_srl');
        $mid = Context::get('cur_mid');
        // Creates communication model object
        $oCommunicationModel = &getModel('communication');
        // Add a feature to display own message box. 
        if($logged_info->member_srl == $member_srl) {
            // Add your own viewing Note Template
            $oMemberController->addMemberPopupMenu(getUrl('','mid',$mid,'act','dispCommunicationMessages'), 'cmd_view_message_box', '', 'self');
            // Display a list of friends
            $oMemberController->addMemberPopupMenu(getUrl('','mid',$mid,'act','dispCommunicationFriend'), 'cmd_view_friend', '', 'self');
        // If not, Add menus to send message and to add friends
        } else {
            // Get member information
            $oMemberModel = &getModel('member'); 
            $target_member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            if(!$target_member_info->member_srl) return;
            // Get logged-in user information
            $logged_info = Context::get('logged_info');
            // Add a menu for sending message
            if( $logged_info->is_admin == 'Y' || $target_member_info->allow_message =='Y' || ($target_member_info->allow_message == 'F' && $oCommunicationModel->isFriend($member_srl)))
                $oMemberController->addMemberPopupMenu(getUrl('','module','communication','act','dispCommunicationSendMessage','receiver_srl',$member_srl), 'cmd_send_message', '', 'popup');
            // Add a menu for listing friends (if a friend is new)
            if(!$oCommunicationModel->isAddedFriend($member_srl))
                $oMemberController->addMemberPopupMenu(getUrl('','module','communication','act','dispCommunicationAddFriend','target_srl',$member_srl), 'cmd_add_friend', '', 'popup');
        }
    }
?>
