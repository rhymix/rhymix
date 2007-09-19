<?php
    /**
     * @class  memberView
     * @author zero (zero@nzeo.com)
     * @brief  member module의 View class
     **/

    class memberView extends member {

        var $group_list = NULL; ///< 그룹 목록 정보
        var $member_info = NULL; ///< 선택된 사용자의 정보
        var $skin = 'default';
        var $my_menu = null;

        /**
         * @brief 초기화
         **/
        function init() {
            // 회원 관리 정보를 받음
            $oModuleModel = &getModel('module');
            $this->member_config = $oModuleModel->getModuleConfig('member');
            if(!$this->member_config->skin) $this->member_config->skin = "default";
            if(!$this->member_config->colorset) $this->member_config->colorset = "white";

            Context::set('member_config', $this->member_config);
            $skin = $this->member_config->skin;

            // template path 지정
            $tpl_path = sprintf('%sskins/%s', $this->module_path, $skin);
            $this->setTemplatePath($tpl_path);

            // my_menu 변수 설정 (자신의 정보와 관련된 부분, 차후 애드온등에서 변수 조절 가능)
            $this->my_menu = array(
                'dispMemberInfo' => Context::getLang('cmd_view_member_info'),
                'dispMemberMessages' => Context::getLang('cmd_view_message_box'),
                'dispMemberFriend' => Context::getLang('cmd_view_friend'),
                'dispMemberOwnDocument' => Context::getLang('cmd_view_own_document'),
                'dispMemberScrappedDocument' => Context::getLang('cmd_view_scrapped_document'),
            );
        }

        /**
         * @brief 회원 정보 출력
         **/
        function dispMemberInfo() {
            $oMemberModel = &getModel('member');
            $logged_info = Context::get('logged_info');

            // 비회원일 경우 정보 열람 중지
            if(!$logged_info->member_srl) return $this->stop('msg_not_permitted');

            $member_srl = Context::get('member_srl');
            if(!$member_srl && Context::get('is_logged')) {
                $member_srl = $logged_info->member_srl;
            } elseif(!$member_srl) {
                return $this->dispMemberSignUpForm();
            }

            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            unset($member_info->password);
            unset($member_info->email_id);
            unset($member_info->email_host);
            unset($member_info->email_address);

            if(!$member_info->member_srl) return $this->dispMemberSignUpForm();

            Context::set('member_info', $member_info);
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($member_info));

            if($member_info->member_srl == $logged_info->member_srl) Context::set('my_menu', $this->my_menu);

            $this->setTemplateFile('member_info');
        }

        /**
         * @brief 회원 가입 폼 출력
         **/
        function dispMemberSignUpForm() {
            $oMemberModel = &getModel('member');

            // 로그인한 회원일 경우 해당 회원의 정보를 받음
            if($oMemberModel->isLogged()) return $this->stop('msg_already_logged');

            // 회원가입을 중지시켰을 때는 에러 표시
            if($this->member_config->enable_join != 'Y') return $this->stop('msg_signup_disabled');

            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($member_info));
            
            // 템플릿 파일 지정
            $this->setTemplateFile('signup_form');
        }

        /**
         * @brief 회원 정보 수정
         **/
        function dispMemberModifyInfo() {
            $oMemberModel = &getModel('member');

            // 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
            if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            $member_info->signature = $oMemberModel->getSignature($member_srl);
            Context::set('member_info',$member_info);
            
            // 추가 가입폼 목록을 받음
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($member_info));

            // 에디터 모듈의 getEditor를 호출하여 서명용으로 세팅
            if($member_info->member_srl) {
                $oEditorModel = &getModel('editor');
                $option->primary_key_name = 'member_srl';
                $option->content_key_name = 'signature';
                $option->allow_fileupload = false;
                $option->enable_autosave = false;
                $option->enable_default_component = true;
                $option->enable_component = true;
                $option->resizable = false;
                $option->height = 200;
                $editor = $oEditorModel->getEditor($member_info->member_srl, $option);
                Context::set('editor', $editor);
            }

            if($member_info->member_srl == $logged_info->member_srl) Context::set('my_menu', $this->my_menu);

            // 템플릿 파일 지정
            $this->setTemplateFile('modify_info');
        }

        /**
         * @brief 회원 작성글 보기
         **/
        function dispMemberOwnDocument() {
            $oMemberModel = &getModel('member');

            // 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
            if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            $module_srl = Context::get('module_srl');
            Context::set('module_srl',Context::get('selected_module_srl'));
            Context::set('search_target','member_srl');
            Context::set('search_keyword',$member_srl);

            $oDocumentAdminView = &getAdminView('document');
            $oDocumentAdminView->dispDocumentAdminList();

            Context::get('module_srl', $module_srl);
            Context::set('my_menu', $this->my_menu);

            $this->setTemplateFile('document_list');
        }

        /**
         * @brief 회원 스크랩 게시물 보기
         **/
        function dispMemberScrappedDocument() {
            $oMemberModel = &getModel('member');

            // 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
            if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;
            $args->page = (int)Context::get('page');

            $output = executeQuery('member.getScrapDocumentList', $args);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('document_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            Context::set('my_menu', $this->my_menu);

            $this->setTemplateFile('scrapped_list');
        }

        /**
         * @brief 로그인 폼 출력
         **/
        function dispMemberLoginForm() {
            // 템플릿 파일 지정
            $this->setTemplateFile('login_form');
        }

        /**
         * @brief 회원 비밀번호 수정
         **/
        function dispMemberModifyPassword() {
            $oMemberModel = &getModel('member');

            // 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
            if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            Context::set('member_info',$member_info);

            if($member_info->member_srl == $logged_info->member_srl) Context::set('my_menu', $this->my_menu);

            // 템플릿 파일 지정
            $this->setTemplateFile('modify_password');
        }

        /**
         * @brief 탈퇴 화면
         **/
        function dispMemberLeave() {
            $oMemberModel = &getModel('member');

            // 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
            if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            Context::set('member_info',$member_info);

            if($member_info->member_srl == $logged_info->member_srl) Context::set('my_menu', $this->my_menu);

            // 템플릿 파일 지정
            $this->setTemplateFile('leave_form');
        }

        /**
         * @brief 오픈 아이디 탈퇴 화면
         **/
        function dispMemberOpenIDLeave() {
            $oMemberModel = &getModel('member');

            // 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
            if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            Context::set('member_info',$member_info);

            if($member_info->member_srl == $logged_info->member_srl) Context::set('my_menu', $this->my_menu);

            // 템플릿 파일 지정
            $this->setTemplateFile('openid_leave_form');
        }

        /**
         * @brief 로그아웃 출력
         **/
        function dispMemberLogout() {
            // 템플릿 파일 지정
            $this->setTemplateFile('logout');
        }

        /**
         * @brief 쪽지함 출력
         **/
        function dispMemberMessages() {
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

            $oMemberModel = &getModel('member');

            // message_srl이 있으면 내용 추출
            if($message_srl) {
                $message = $oMemberModel->getSelectedMessage($message_srl);
                if($message->message_srl == $message_srl) Context::set('message', $message);
            }

            Context::set('my_menu', $this->my_menu);

            // 목록 추출
            $output = $oMemberModel->getMessages($message_type);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('message_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('member_messages');
        }

        /**
         * @brief 새 쪽지 보여줌
         **/
        function dispMemberNewMessage() {
            $this->setLayoutFile('popup_layout');

            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
            $logged_info = Context::get('logged_info');

            $oMemberModel = &getModel('member');

            // 새 쪽지를 가져옴
            $message = $oMemberModel->getNewMessage();
            if($message) Context::set('message', $message);
            
            // 플래그 삭제
            $flag_path = './files/member_extra_info/new_message_flags/'.getNumberingPath($logged_info->member_srl);
            $flag_file = sprintf('%s%s', $flag_path, $logged_info->member_srl);
            @unlink($flag_file);

            $this->setTemplateFile('member_new_message');
        }

        /**
         * @brief 쪽지 발송 출력
         **/
        function dispMemberSendMessage() {
            $this->setLayoutFile("popup_layout");

            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 쪽지 받을 사용자 정보 구함
            $receiver_srl = Context::get('receiver_srl');
            if(!$receiver_srl || $logged_info->member_srl == $receiver_srl) return $this->stop('msg_not_logged');

            $oMemberModel = &getModel('member');
            $receiver_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
            Context::set('receiver_info', $receiver_info);

            // 에디터 모듈의 getEditor를 호출하여 서명용으로 세팅
            $oEditorModel = &getModel('editor');
            $option->primary_key_name = 'receiver_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = false;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = false;
            $option->height = 250;
            $editor = $oEditorModel->getEditor($logged_info->member_srl, $option);
            Context::set('editor', $editor);

            $this->setTemplateFile('send_message');
        }

        /**
         * @brief 친구 목록 보기
         **/
        function dispMemberFriend() {
            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');

            $oMemberModel = &getModel('member');

            // 그룹 목록을 가져옴
            $tmp_group_list = $oMemberModel->getFriendGroups();
            $group_count = count($tmp_group_list);
            for($i=0;$i<$group_count;$i++) $friend_group_list[$tmp_group_list[$i]->friend_group_srl] = $tmp_group_list[$i];
            Context::set('friend_group_list', $friend_group_list);

            // 친구 목록을 가져옴
            $friend_group_srl = Context::get('friend_group_srl');
            $output = $oMemberModel->getFriends($friend_group_srl);
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
            Context::set('my_menu', $this->my_menu);

            $this->setTemplateFile('friends_list');
        }

        /**
         * @brief 친구 추가
         **/
        function dispMemberAddFriend() {
            $this->setLayoutFile("popup_layout");

            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
            $logged_info = Context::get('logged_info');

            $target_srl = Context::get('target_srl');
            if(!$target_srl) return $this->stop('msg_invalid_request');

            // 대상 회원의 정보를 구함
            $oMemberModel = &getModel('member');
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($target_srl);
            if($member_info->member_srl != $target_srl) return $this->stop('msg_invalid_request');
            Context::set('target_info', $member_info);

            // 그룹의 목록을 구함
            $friend_group_list = $oMemberModel->getFriendGroups();
            Context::set('friend_group_list', $friend_group_list);

            $this->setTemplateFile('add_friend');
        }

        /**
         * @brief 친구 그룹 추가
         **/
        function dispMemberAddFriendGroup() {
            $this->setLayoutFile("popup_layout");

            // 로그인이 되어 있지 않으면 오류 표시
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 그룹 번호가 넘어오면 수정모드로..
            $friend_group_srl = Context::get('friend_group_srl');
            if($friend_group_srl) {
                $oMemberModel = &getModel('member');
                $friend_group = $oMemberModel->getFriendGroupInfo($friend_group_srl);
                if($friend_group->friend_group_srl == $friend_group_srl) Context::set('friend_group', $friend_group);
            }


            $this->setTemplateFile('add_friend_group');
        }

    }
?>
