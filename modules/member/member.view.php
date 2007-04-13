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

        /**
         * @brief 초기화
         **/
        function init() {
            // 관리자 모듈에서 요청중이면 initAdmin(), 아니면 initNormal()
            if(Context::get('module')=='admin') $this->initAdmin();
        }

        /**
         * @brief 관리자 페이지의 초기화
         **/
        function initAdmin() {
            // 멤버모델 객체 생성
            $oMemberModel = &getModel('member');

            // member_srl이 있으면 미리 체크하여 member_info 세팅
            $member_srl = Context::get('member_srl');
            if($member_srl) {
                $this->member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
                if(!$this->member_info) Context::set('member_srl','');
                else Context::set('member_info',$this->member_info);
            }

            // group 목록 가져오기
            $this->group_list = $oMemberModel->getGroups();
            Context::set('group_list', $this->group_list);

            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 일반 페이지 초기화
         **/
        function initNormal() {
            // 회원 관리 정보를 받음
            $oModuleModel = &getModel('module');
            $this->member_config = $oModuleModel->getModuleConfig('member');
            Context::set('member_config', $this->member_config);
            $skin = $this->member_config->skin;
            if(!$skin) $skin = 'default';

            // template path 지정
            $tpl_path = sprintf('%sskins/%s', $this->module_path, $skin);
            $this->setTemplatePath($tpl_path);
        }

        /**
         * @brief 회원 정보 출력
         **/
        function dispMemberInfo() {
            $this->initNormal();

            $oMemberModel = &getModel('member');

            $member_srl = Context::get('member_srl');
            if(!$member_srl && Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
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

            $this->setTemplateFile('member_info');
        }

        /**
         * @brief 회원 가입 폼 출력
         **/
        function dispMemberSignUpForm() {
            $this->initNormal();

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
            $this->initNormal();

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
                $editor = $oEditorModel->getEditor($member_info->member_srl, false, false);
                Context::set('editor', $editor);
            }


            // 템플릿 파일 지정
            $this->setTemplateFile('modify_info');
        }

        /**
         * @brief 로그인 폼 출력
         **/
        function dispMemberLoginForm() {
            $this->initNormal();

            // 템플릿 파일 지정
            $this->setTemplateFile('login_form');
        }

        /**
         * @brief 회원 비밀번호 수정
         **/
        function dispMemberModifyPassword() {
            $this->initNormal();

            $oMemberModel = &getModel('member');

            // 로그인 되어 있지 않을 경우 로그인 되어 있지 않다는 메세지 출력
            if(!$oMemberModel->isLogged()) return $this->stop('msg_not_logged');

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            Context::set('member_info',$member_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('modify_password');
        }

        /**
         * @brief 로그아웃 출력
         **/
        function dispMemberLogout() {
            $this->initNormal();

            // 템플릿 파일 지정
            $this->setTemplateFile('logout');
        }

        /**
         * @brief 쪽지함 출력
         **/
        function dispMemberMessages() {
            $this->initNormal();

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
            $this->initNormal();
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
            $this->initNormal();
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
            $editor = $oEditorModel->getEditor($logged_info->member_srl, false, false);
            Context::set('editor', $editor);

            $this->setTemplateFile('send_message');
        }

        /**
         * @brief 친구 목록 보기
         **/
        function dispMemberFriend() {
            $this->initNormal();
            $this->setLayoutFile("popup_layout");

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

            $this->setTemplateFile('friends_list');
        }

        /**
         * @brief 친구 추가
         **/
        function dispMemberAddFriend() {
            $this->initNormal();
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
            $this->initNormal();
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

        /**
         * @brief 회원 목록 출력
         **/
        function dispMemberAdminList() {

            // member model 객체 생성후 목록을 구해옴
            $oMemberModel = &getModel('member');
            $output = $oMemberModel->getMemberList();

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('member_list');
        }

        /**
         * @brief 회원 관리에 필요한 기본 설정들
         **/
        function dispMemberAdminConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if(!$config->image_name_max_width) $config->image_name_max_width = 90;
            if(!$config->image_name_max_height) $config->image_name_max_height = 20;
            if(!$config->image_mark_max_width) $config->image_mark_max_width = 20;
            if(!$config->image_mark_max_height) $config->image_mark_max_height = 20;
            Context::set('config',$config);

            // 회원 관리 모듈의 스킨 목록을 구함
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list', $skin_list);

            // 에디터를 받음
            $oEditorModel = &getModel('editor');
            $editor = $oEditorModel->getEditor(0, false, true);
            Context::set('editor', $editor);

            // 템플릿 파일 지정
            $this->setTemplateFile('member_config');
        }

        /**
         * @brief 회원 정보 출력
         **/
        function dispMemberAdminInfo() {
            // 추가 가입폼 목록을 받음
            $oMemberModel = &getModel('member');
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->member_info));

            $this->setTemplateFile('member_info');
        }

        /**
         * @brief 회원 정보 입력 화면 출력
         **/
        function dispMemberAdminInsert() {
            // 추가 가입폼 목록을 받음
            $oMemberModel = &getModel('member');
            Context::set('extend_form_list', $oMemberModel->getCombineJoinForm($this->member_info));

            $member_info = Context::get('member_info');
            $member_info->signature = $oMemberModel->getSignature($this->member_info->member_srl);
            Context::set('member_info', $member_info);

            // 에디터 모듈의 getEditor를 호출하여 서명용으로 세팅
            if($this->member_info->member_srl) {
                $oEditorModel = &getModel('editor');
                $editor = $oEditorModel->getEditor($this->member_info->member_srl, false, false);
                Context::set('editor', $editor);
            }

            // 템플릿 파일 지정
            $this->setTemplateFile('insert_member');
        }

        /**
         * @brief 회원 삭제 화면 출력
         **/
        function dispMemberAdminDeleteForm() {
            if(!Context::get('member_srl')) return $this->dispContent();
            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief 그룹 목록 출력
         **/
        function dispMemberAdminGroupList() {
            $group_srl = Context::get('group_srl');

            if($group_srl && $this->group_list[$group_srl]) {
                Context::set('selected_group', $this->group_list[$group_srl]);
                $this->setTemplateFile('group_update_form');
            } else {
                $this->setTemplateFile('group_list');
            }
        }

        /**
         * @brief 회원 가입 폼 목록 출력
         **/
        function dispMemberAdminJoinFormList() {
            // 멤버모델 객체 생성
            $oMemberModel = &getModel('member');

            // 추가로 설정한 가입 항목 가져오기
            $form_list = $oMemberModel->getJoinFormList();
            Context::set('form_list', $form_list);

            $this->setTemplateFile('join_form_list');
        }

        /**
         * @brief 회원 가입 폼 관리 화면 출력
         **/
        function dispMemberAdminInsertJoinForm() {
            // 수정일 경우 대상 join_form의 값을 구함
            $member_join_form_srl = Context::get('member_join_form_srl');
            if($member_join_form_srl) {
                $oMemberModel = &getModel('member');
                $join_form = $oMemberModel->getJoinForm($member_join_form_srl);
                if(!$join_form) Context::set('member_join_form_srl','',true);
                else Context::set('join_form', $join_form);
            }
            $this->setTemplateFile('insert_join_form');
        }

        /**
         * @brief 금지 목록 아이디 출력
         **/
        function dispMemberAdminDeniedIDList() {
            // 멤버모델 객체 생성
            $oMemberModel = &getModel('member');

            // 사용금지 목록 가져오기
            $output = $oMemberModel->getDeniedIDList();

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('denied_id_list');
        }
    }
?>
