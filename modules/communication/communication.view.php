<?php
    /**
     * @class  communicationView
     * @author zero (zero@nzeo.com)
     * @brief  communication module의 View class
     **/

    class communicationView extends communication {

        /**
         * @brief 초기화
         **/
        function init() {
            $oCommunicationModel = &getModel('communication');

            $this->communication_config = $oCommunicationModel->getConfig();
            $skin = $this->communication_config->skin;

            Context::set('communication_config', $this->communication_config);

            $tpl_path = sprintf('%sskins/%s', $this->module_path, $skin);
            $this->setTemplatePath($tpl_path);
        }

        /**
         * @brief 쪽지함 출력
         **/
        function dispCommunicationMessages() {
            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 설정
            $message_srl = Context::get('message_srl');
            $message_type = Context::get('message_type');
            if(!in_array($message_type, array('R','S','T'))) {
                $message_type = 'R';
                Context::set('message_type', $message_type);
            }

            $oCommunicationModel = &getModel('communication');

            // message_srl이 있으면 내용 추출
            if($message_srl) {
                $message = $oCommunicationModel->getSelectedMessage($message_srl);
                if($message->message_srl == $message_srl && ($message->receiver_srl == $logged_info->member_srl || $message->sender_srl == $logged_info->member_srl) ) Context::set('message', $message);
            }

            // 목록 추출
            $output = $oCommunicationModel->getMessages($message_type);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('message_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('messages');
        }

        /**
         * @brief 새 쪽지 보여줌
         **/
        function dispCommunicationNewMessage() {
            $this->setLayoutFile('popup_layout');

            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
            $logged_info = Context::get('logged_info');

            $oCommunicationModel = &getModel('communication');

            // 새 쪽지를 가져옴
            $message = $oCommunicationModel->getNewMessage();
            if($message) Context::set('message', $message);
            
            // 플래그 삭제
            $flag_path = './files/communication_extra_info/new_message_flags/'.getNumberingPath($logged_info->member_srl);
            $flag_file = sprintf('%s%s', $flag_path, $logged_info->member_srl);
            FileHandler::removeFile($flag_file);

            $this->setTemplateFile('new_message');
        }

        /**
         * @brief 쪽지 발송 출력
         **/
        function dispCommunicationSendMessage() {
            $this->setLayoutFile("popup_layout");
            $oCommunicationModel = &getModel('communication');
            $oMemberModel = &getModel('member');

            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 쪽지 받을 사용자 정보 구함
            $receiver_srl = Context::get('receiver_srl');
            if(!$receiver_srl || $logged_info->member_srl == $receiver_srl) return $this->stop('msg_not_logged');

            // 답글 쪽지일 경우 원본 메세지의 글번호를 구함
            $message_srl = Context::get('message_srl');
            if($message_srl) {
                $source_message = $oCommunicationModel->getSelectedMessage($message_srl);
                if($source_message->message_srl == $message_srl && $source_message->sender_srl == $receiver_srl) {
                    $source_message->title = "[re] ".$source_message->title;
                    $source_message->content = "\r\n<br />\r\n<br /><div style=\"padding-left:5px; border-left:5px solid #DDDDDD;\">".trim($source_message->content)."</div>";
                    Context::set('source_message', $source_message);
                }
            }

            $receiver_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
            Context::set('receiver_info', $receiver_info);

            // 에디터 모듈의 getEditor를 호출하여 서명용으로 세팅
            $oEditorModel = &getModel('editor');
            $option->primary_key_name = 'receiver_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = false;
            $option->enable_autosave = false;
            $option->enable_default_component = true;// false;
            $option->enable_component = false;
            $option->resizable = false;
            $option->disable_html = true;
            $option->height = 300;
            $option->skin = $this->communication_config->editor_skin;
            $editor = $oEditorModel->getEditor($logged_info->member_srl, $option);
            Context::set('editor', $editor);

            $this->setTemplateFile('send_message');
        }

        /**
         * @brief 친구 목록 보기
         **/
        function dispCommunicationFriend() {
            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');

            $oCommunicationModel = &getModel('communication');

            // 그룹 목록을 가져옴
            $tmp_group_list = $oCommunicationModel->getFriendGroups();
            $group_count = count($tmp_group_list);
            for($i=0;$i<$group_count;$i++) $friend_group_list[$tmp_group_list[$i]->friend_group_srl] = $tmp_group_list[$i];
            Context::set('friend_group_list', $friend_group_list);

            // 친구 목록을 가져옴
            $friend_group_srl = Context::get('friend_group_srl');
            $output = $oCommunicationModel->getFriends($friend_group_srl);
            $friend_count = count($output->data);
            if($friend_count) {
                foreach($output->data as $key => $val) {
                    $group_srl = $val->friend_group_srl;
                    $group_title = $friend_group_list[$group_srl]->title;
                    if(!$group_title) $group_title = Context::get('default_friend_group');
                    $output->data[$key]->group_title = $group_title;
                }
            }

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('friend_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('friends');
        }

        /**
         * @brief 친구 추가
         **/
        function dispCommunicationAddFriend() {
            $this->setLayoutFile("popup_layout");

            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
            $logged_info = Context::get('logged_info');

            $target_srl = Context::get('target_srl');
            if(!$target_srl) return $this->stop('msg_invalid_request');

            // 대상 회원의 정보를 구함
            $oMemberModel = &getModel('member');
            $oCommunicationModel = &getModel('communication');
            $communication_info = $oMemberModel->getMemberInfoByMemberSrl($target_srl);
            if($communication_info->member_srl != $target_srl) return $this->stop('msg_invalid_request');
            Context::set('target_info', $communication_info);

            // 그룹의 목록을 구함
            $friend_group_list = $oCommunicationModel->getFriendGroups();
            Context::set('friend_group_list', $friend_group_list);

            $this->setTemplateFile('add_friend');
        }

        /**
         * @brief 친구 그룹 추가
         **/
        function dispCommunicationAddFriendGroup() {
            $this->setLayoutFile("popup_layout");

            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 그룹 번호가 넘어오면 수정모드로..
            $friend_group_srl = Context::get('friend_group_srl');
            if($friend_group_srl) {
                $oCommunicationModel = &getModel('communication');
                $friend_group = $oCommunicationModel->getFriendGroupInfo($friend_group_srl);
                if($friend_group->friend_group_srl == $friend_group_srl) Context::set('friend_group', $friend_group);
            }

            $this->setTemplateFile('add_friend_group');
        }

    }
?>
