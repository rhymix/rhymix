<?php
    /**
     * @class  memberController
     * @author zero (zero@nzeo.com)
     * @brief  member module의 Controller class
     **/

    class memberController extends member {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief user_id, password를 체크하여 로그인 시킴
         **/
        function procMemberLogin() {
            // 변수 정리
            if(!$user_id) $user_id = Context::get('user_id');
            $user_id = trim($user_id);

            if(!$password) $password = Context::get('password');
            $password = trim($password);

            // 아이디나 비밀번호가 없을때 오류 return
            if(!$user_id) return new Object(-1,'null_user_id');
            if(!$password) return new Object(-1,'null_password');

            return $this->doLogin($user_id, $password);
        }

        /**
         * @brief 로그아웃
         **/
        function procMemberLogout() {
            $_SESSION['is_logged'] = false;
            $_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['logged_info'] = NULL;
            $_SESSION['member_srl'] = NULL;
            $_SESSION['group_srls'] = array();
            $_SESSION['is_admin'] = NULL;
            return new Object();
        }

        /**
         * @brief 쪽지 발송
         **/
        function procMemberSendMessage() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 검사
            $receiver_srl = Context::get('receiver_srl');
            if(!$receiver_srl) return new Object(-1, 'msg_not_exists_member');
            $title = trim(Context::get('title'));
            if(!$title) return new Object(-1, 'msg_title_is_null');
            $content = trim(Context::get('content'));
            if(!$content) return new Object(-1, 'msg_content_is_null');

            // 받을 회원이 있는지에 대한 검사
            $oMemberModel = &getModel('member');
            $receiver_member_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
            if($receiver_member_info->member_srl != $receiver_srl) return new Object(-1, 'msg_not_exists_member');

            // 발송하는 회원의 쪽지함에 넣을 쪽지
            $sender_args->message_srl = getNextSequence();
            $sender_args->related_srl = getNextSequence();
            $sender_args->list_order = getNextSequence()*-1;
            $sender_args->sender_srl = $logged_info->member_srl;
            $sender_args->receiver_srl = $receiver_srl;
            $sender_args->message_type = 'S';
            $sender_args->title = $title;
            $sender_args->content = $content;
            $sender_args->readed = 'N';
            $sender_args->regdate = date("YmdHis");
            $output = executeQuery('member.sendMessage', $sender_args);
            if(!$output->toBool()) return $output;

            // 받는 회원의 쪽지함에 넣을 쪽지
            $receiver_args->message_srl = $sender_args->related_srl;
            $receiver_args->related_srl = 0;
            $receiver_args->list_order = $sender_args->related_srl*-1;
            $receiver_args->sender_srl = $logged_info->member_srl;
            $receiver_args->receiver_srl = $receiver_srl;
            $receiver_args->message_type = 'R';
            $receiver_args->title = $title;
            $receiver_args->content = $content;
            $receiver_args->readed = 'N';
            $receiver_args->regdate = date("YmdHis");
            $output = executeQuery('member.sendMessage', $receiver_args);
            if(!$output->toBool()) return $output;

            // 받는 회원의 쪽지 발송 플래그 생성 (파일로 생성)
            $flag_path = './files/member_extra_info/new_message_flags/'.getNumberingPath($receiver_srl);
            FileHandler::makeDir($flag_path);
            $flag_file = sprintf('%s%s', $flag_path, $receiver_srl);
            FileHandler::writeFile($flag_file,'1');

            $this->setMessage('success_sended');
        }

        /**
         * @brief 특정 쪽지를 보관함으로 보냄
         **/
        function procMemberStoreMessage() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 체크
            $message_srl = Context::get('message_srl');
            if(!$message_srl) return new Object(-1,'msg_invalid_request');

            // 쪽지를 가져옴
            $oMemberModel = &getModel('member');
            $message = $oMemberModel->getSelectedMessage($message_srl);
            if(!$message || $message->message_type != 'R') return new Object(-1,'msg_invalid_request');

            $args->message_srl = $message_srl;
            $args->receiver_srl = $logged_info->member_srl;
            $output = executeQuery('member.setMessageStored', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
        }

        /**
         * @brief 쪽지 삭제
         **/
        function procMemberDeleteMessage() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            // 변수 체크
            $message_srl = Context::get('message_srl');
            if(!$message_srl) return new Object(-1,'msg_invalid_request');

            // 쪽지를 가져옴
            $oMemberModel = &getModel('member');
            $message = $oMemberModel->getSelectedMessage($message_srl);
            if(!$message) return new Object(-1,'msg_invalid_request');

            // 발송인+type=S or 수신인+type=R 검사
            if($message->sender_srl == $member_srl && $message->message_type == 'S') {
                $args->message_srl = $message_srl;
            } elseif($message->receiver_srl == $member_srl && $message->message_type == 'R') {
                $args->message_srl = $message_srl;
            }
            if(!$args->message_srl) return new Object(-1, 'msg_invalid_request');

            // 삭제
            $output = executeQuery('member.deleteMessage', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 선택된 다수의 쪽지 삭제
         **/
        function procMemberDeleteMessages() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            // 변수 체크
            $message_srl_list = trim(Context::get('message_srl_list'));
            if(!$message_srl_list) return new Object(-1, 'msg_cart_is_null');

            $message_srl_list = explode('|@|', $message_srl_list);
            if(!count($message_srl_list)) return new Object(-1, 'msg_cart_is_null');

            $message_type = Context::get('message_type');
            if(!$message_type || !in_array($message_type, array('R','S','T'))) return new Object(-1, 'msg_invalid_request');

            $message_count = count($message_srl_list);
            $target = array();
            for($i=0;$i<$message_count;$i++) {
                $message_srl = (int)trim($message_srl_list[$i]);
                if(!$message_srl) continue;
                $target[] = $message_srl;
            }
            if(!count($target)) return new Object(-1,'msg_cart_is_null');

            // 삭제
            $args->message_srls = implode(',',$target);
            $args->message_type = $message_type;

            if($message_type == 'S') $args->sender_srl = $member_srl;
            else $args->receiver_srl = $member_srl;

            $output = executeQuery('member.deleteMessages', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 친구 추가
         **/
        function procMemberAddFriend() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            $target_srl = (int)trim(Context::get('target_srl'));
            if(!$target_srl) return new Object(-1,'msg_invalid_request');

            // 변수 정리
            $args->friend_srl = getNextSequence();
            $args->list_order = $args->friend_srl * -1;
            $args->friend_group_srl = Context::get('friend_group_srl');
            $args->member_srl = $logged_info->member_srl;
            $args->target_srl = $target_srl;
            $output = executeQuery('member.addFriend', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
        }

        /**
         * @brief 등록된 친구의 그룹 이동
         **/
        function procMemberMoveFriend() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 정리
            $args->friend_srl = Context::get('friend_srl');
            $args->member_srl = $logged_info->member_srl;
            $args->friend_group_srl = Context::get('friend_group_srl');

            $output = executeQuery('member.moveFriend', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_moved');
        }

        /**
         * @brief 친구 삭제
         **/
        function procMemberDeleteFriend() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 정리
            $args->friend_srl = Context::get('friend_srl');
            $args->member_srl = $logged_info->member_srl;
            $output = executeQuery('member.deleteFriend', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 친구 그룹 추가
         **/
        function procMemberAddFriendGroup() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 정리
            $args->member_srl = $logged_info->member_srl;
            $args->title = Context::get('title');
            if(!$args->title) return new Object(-1, 'msg_invalid_request');

            $output = executeQuery('member.addFriendGroup', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
        }

        /**
         * @brief 친구 그룹 이름 변경
         **/
        function procMemberRenameFriendGroup() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 정리
            $args->friend_group_srl= Context::get('friend_group_srl');
            $args->member_srl = $logged_info->member_srl;
            $args->title = Context::get('title');
            if(!$args->title) return new Object(-1, 'msg_invalid_request');

            $output = executeQuery('member.renameFriendGroup', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 친구 그룹 삭제
         **/
        function procMemberDeleteFriendGroup() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 정리
            $args->friend_group_srl = Context::get('friend_group_srl');
            $args->member_srl = $logged_info->member_srl;
            $output = executeQuery('member.deleteFriendGroup', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 특정 쪽지의 상태를 읽은 상태로 변경
         **/
        function setMessageReaded($message_srl) {
            $args->message_srl = $message_srl;
            $args->related_srl = $message_srl;
            return executeQuery('member.setMessageReaded', $args);
        }

        /**
         * @brief 사용자 추가 (관리자용)
         **/
        function procMemberAdminInsert() {
            // 필수 정보들을 미리 추출
            $args = Context::gets('member_srl','user_id','user_name','nick_name','email_address','password','allow_mailing','denied','is_admin','description','group_srl_list');

            // 넘어온 모든 변수중에서 몇가지 불필요한 것들 삭제
            $all_args = Context::getRequestVars();
            unset($all_args->module);
            unset($all_args->act);

            // 모든 request argument에서 필수 정보만 제외 한 후 추가 데이터로 입력
            $extra_vars = delObjectVars($all_args, $args);
            $args->extra_vars = serialize($extra_vars);

            // member_srl이 넘어오면 원 회원이 있는지 확인
            if($args->member_srl) {
                // 멤버 모델 객체 생성
                $oMemberModel = &getModel('member');

                // 회원 정보 구하기
                $member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);

                // 만약 원래 회원이 없으면 새로 입력하기 위한 처리
                if($member_info->member_srl != $args->member_srl) unset($args->member_srl);
            }

            // member_srl의 값에 따라 insert/update
            if(!$args->member_srl) {
                $output = $this->insertMember($args);
                $msg_code = 'success_registed';
            } else {
                $output = $this->updateMember($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            // 서명 저장
            $signature = Context::get('signature');
            $this->putSignature($args->member_srl, $signature);

            // 결과 리턴
            $this->add('member_srl', $args->member_srl);
            $this->setMessage($msg_code);
        }

        /**
         * @brief 사용자 삭제 (관리자용)
         **/
        function procMemberAdminDelete() {
            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            $member_srl = Context::get('member_srl');

            $output = $this->deleteMember($member_srl);
            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->setMessage("success_deleted");
        }

        /**
         * @brief 회원 관리용 기본 정보의 추가
         **/
        function procMemberAdminInsertConfig() {
            // 기본 정보를 받음
            $args = Context::gets('enable_join','redirect_url','agreement','image_name','image_mark', 'image_name_max_width', 'image_name_max_height','image_mark_max_width','image_mark_max_height');
            if($args->enable_join!='Y') $args->enable_join = 'N';
            if($args->image_name!='Y') $args->image_name = 'N';
            if($args->image_mark!='Y') $args->image_mark = 'N';

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('member',$args);
            return $output;
        }

        /**
         * @brief 사용자 그룹 추가
         **/
        function procMemberAdminInsertGroup() {
            $args = Context::gets('title','description','is_default');
            $output = $this->insertGroup($args);
            if(!$output->toBool()) return $output;

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_registed');
        }

        /**
         * @brief 사용자 그룹 정보 수정
         **/
        function procMemberAdminUpdateGroup() {
            $group_srl = Context::get('group_srl');
            $mode = Context::get('mode');

            switch($mode) {
                case 'delete' :
                        $output = $this->deleteGroup($group_srl);
                        if(!$output->toBool()) return $output;
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                        $args = Context::gets('group_srl','title','description','is_default');
                        $output = $this->updateGroup($args);
                        if(!$output->toBool()) return $output;
                        $msg_code = 'success_updated';
                    break;
            }

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 가입 항목 추가
         **/
        function procMemberAdminInsertJoinForm() {
            $args->member_join_form_srl = Context::get('member_join_form_srl');

            $args->column_type = Context::get('column_type');
            $args->column_name = Context::get('column_name');
            $args->column_title = Context::get('column_title');
            $args->default_value = explode('|@|', Context::get('default_value'));
            $args->is_active = Context::get('is_active');
            if(!in_array(strtoupper($args->is_active), array('Y','N'))) $args->is_active = 'N';
            $args->required = Context::get('required');
            if(!in_array(strtoupper($args->required), array('Y','N'))) $args->required = 'N';
            $args->description = Context::get('description');
            $args->list_order = getNextSequence();

            // 기본값의 정리
            if(in_array($args->column_type, array('checkbox','select')) && count($args->default_value) ) {
                $args->default_value = serialize($args->default_value);
            } else {
                $args->default_value = '';
            }

            // member_join_form_srl이 있으면 수정, 없으면 추가
            if(!$args->member_join_form_srl) $output = executeQuery('member.insertJoinForm', $args);
            else $output = executeQuery('member.updateJoinForm', $args);

            if(!$output->toBool()) return $output;

            $this->add('act','dispJoinForm');
            $this->setMessage('success_registed');
        }

        /**
         * @brief 가입 항목의 상/하 이동 및 내용 수정
         **/
        function procMemberAdminUpdateJoinForm() {
            $member_join_form_srl = Context::get('member_join_form_srl');
            $mode = Context::get('mode');

            switch($mode) {
                case 'up' :
                        $output = $this->moveJoinFormUp($member_join_form_srl);
                        $msg_code = 'success_moved';
                    break;
                case 'down' :
                        $output = $this->moveJoinFormDown($member_join_form_srl);
                        $msg_code = 'success_moved';
                    break;
                case 'delete' :
                        $output = $this->deleteJoinForm($member_join_form_srl);
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                    break;
            }
            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
        }

        /**
         * @brief 금지 아이디 추가
         **/
        function procMemberAdminInsertDeniedID() {
            $user_id = Context::get('user_id');
            $description = Context::get('description');

            $oMemberController = &getController('member');
            $output = $oMemberController->insertDeniedID($user_id, $description);
            if(!$output->toBool()) return $output;

            $this->add('group_srl','');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_registed');
        }

        /**
         * @brief 금지 아이디 업데이트
         **/
        function procMemberAdminUpdateDeniedID() {
            $user_id = Context::get('user_id');
            $mode = Context::get('mode');

            $oMemberController = &getController('member');

            switch($mode) {
                case 'delete' :
                        $output = $oMemberController->deleteDeniedID($user_id);
                        if(!$output->toBool()) return $output;
                        $msg_code = 'success_deleted';
                    break;
            }

            $this->add('page',Context::get('page'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 회원 가입 or 정보 수정
         **/
        function procMemberInsert() {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if($config->enable_join != 'Y') return $this->stop('msg_signup_disabled');

            // 필수 정보들을 미리 추출
            $args = Context::gets('user_id','user_name','nick_name','email_address','password','allow_mailing');
            $args->member_srl = getNextSequence();

            // 넘어온 모든 변수중에서 몇가지 불필요한 것들 삭제
            $all_args = Context::getRequestVars();
            unset($all_args->module);
            unset($all_args->act);
            unset($all_args->is_admin);
            unset($all_args->description);
            unset($all_args->group_srl_list);

            // 모든 request argument에서 필수 정보만 제외 한 후 추가 데이터로 입력
            $extra_vars = delObjectVars($all_args, $args);
            $args->extra_vars = serialize($extra_vars);

            // member_srl의 값에 따라 insert/update
            $output = $this->insertMember($args);
            if(!$output->toBool()) return $output;

            // 로그인 시킴
            $this->doLogin($args->user_id);

            $this->add('member_srl', $args->member_srl);
            if($config->redirect_url) $this->add('redirect_url', $config->redirect_url);
            $this->setMessage('success_registed');
        }

        /**
         * @brief 회원 가입 or 정보 수정
         **/
        function procMemberModifyInfo() {
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');

            // 필수 정보들을 미리 추출
            $args = Context::gets('nick_name','email_address','allow_mailing');

            // 로그인 정보
            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;

            // 넘어온 모든 변수중에서 몇가지 불필요한 것들 삭제
            $all_args = Context::getRequestVars();
            unset($all_args->module);
            unset($all_args->act);
            unset($all_args->is_admin);
            unset($all_args->description);
            unset($all_args->group_srl_list);

            // 모든 request argument에서 필수 정보만 제외 한 후 추가 데이터로 입력
            $extra_vars = delObjectVars($all_args, $args);
            $args->extra_vars = serialize($extra_vars);

            // 멤버 모델 객체 생성
            $oMemberModel = &getModel('member');

            // member_srl의 값에 따라 insert/update
            $output = $this->updateMember($args);
            if(!$output->toBool()) return $output;

            // 서명 저장
            $signature = Context::get('signature');
            $this->putSignature($args->member_srl, $signature);

            // 결과 리턴
            $this->add('member_srl', $args->member_srl);
            $this->setMessage('success_updated');
        }

        /**
         * @brief 회원 비밀번호 수정
         **/
        function procMemberModifyPassword() {
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');

            // 필수 정보들을 미리 추출
            $args->password = trim(Context::get('password'));

            // 로그인 정보
            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;

            // member_srl의 값에 따라 insert/update
            $output = $this->updateMemberPassword($args);
            if(!$output->toBool()) return $output;

            $this->add('member_srl', $args->member_srl);
            $this->setMessage('success_updated');
        }

        /**
         * @brief 이미지 이름을 추가 
         **/
        function procMemberInsertImageName() {
            // 정상적으로 업로드 된 파일인지 검사
            $file = $_FILES['image_name'];
            if(!is_uploaded_file($file['tmp_name'])) return $this->stop('msg_not_uploaded_image_name');

            // 회원 정보를 검사해서 회원번호가 없거나 관리자가 아니고 회원번호가 틀리면 무시
            $member_srl = Context::get('member_srl');
            if(!$member_srl) return $this->stop('msg_not_uploaded_image_name');

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin != 'Y' && $logged_info->member_srl != $member_srl) return $this->stop('msg_not_uploaded_image_name');

            // 회원 모듈 설정에서 이미지 이름 사용 금지를 하였을 경우 관리자가 아니면 return;
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if($logged_info->is_admin != 'Y' && $config->image_name != 'Y') return $this->stop('msg_not_uploaded_image_name');

            // 정해진 사이즈를 구함
            $max_width = $config->image_name_max_width;
            if(!$max_width) $max_width = "90";
            $max_height = $config->image_name_max_height;
            if(!$max_height) $max_height = "20";

            $target_filename = sprintf('files/attach/member_extra_info/image_name/%s%d.gif', getNumberingPath($member_srl), $member_srl);
            FileHandler::createImageFile($file['tmp_name'], $target_filename, $max_width, $max_height, 'gif');

            // 페이지 리프레쉬
            $this->setRefreshPage();
        }

        /**
         * @brief 이미지 이름을 삭제
         **/
        function procMemberDeleteImageName() {
            $member_srl = Context::get('member_srl');
            if(!$member_srl) return new Object(0,'success');

            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if($config->image_mark == 'N') return new Object(0,'success');

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y' || $logged_info->member_srl == $member_srl) {
                $oMemberModel = &getModel('member');
                $image_name = $oMemberModel->getImageName($member_srl);
                @unlink($image_name->file);
            } 
            return new Object(0,'success');
        }

        /**
         * @brief 이미지 마크를 추가 
         **/
        function procMemberInsertImageMark() {
            // 정상적으로 업로드 된 파일인지 검사
            $file = $_FILES['image_mark'];
            if(!is_uploaded_file($file['tmp_name'])) return $this->stop('msg_not_uploaded_image_mark');

            // 회원 정보를 검사해서 회원번호가 없거나 관리자가 아니고 회원번호가 틀리면 무시
            $member_srl = Context::get('member_srl');
            if(!$member_srl) return $this->stop('msg_not_uploaded_image_mark');

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin != 'Y' && $logged_info->member_srl != $member_srl) return $this->stop('msg_not_uploaded_image_mark');

            // 회원 모듈 설정에서 이미지 마크 사용 금지를 하였을 경우 관리자가 아니면 return;
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if($logged_info->is_admin != 'Y' && $config->image_mark != 'Y') return $this->stop('msg_not_uploaded_image_mark');

            // 정해진 사이즈를 구함
            $max_width = $config->image_mark_max_width;
            if(!$max_width) $max_width = "20";
            $max_height = $config->image_mark_max_height;
            if(!$max_height) $max_height = "20";
            
            $target_filename = sprintf('files/attach/member_extra_info/image_mark/%s%d.gif', getNumberingPath($member_srl), $member_srl);
            FileHandler::createImageFile($file['tmp_name'], $target_filename, $max_width, $max_height, 'gif');

            // 페이지 리프레쉬
            $this->setRefreshPage();
        }

        /**
         * @brief 이미지 마크를  삭제
         **/
        function procMemberDeleteImageMark() {
            $member_srl = Context::get('member_srl');
            if(!$member_srl) return new Object(0,'success');

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y' || $logged_info->member_srl == $member_srl) {
                $oMemberModel = &getModel('member');
                $image_mark = $oMemberModel->getImageMark($member_srl);
                @unlink($image_mark->file);
            } 
            return new Object(0,'success');
        }

        /**
         * @brief 서명을 파일로 저장
         **/
        function putSignature($member_srl, $signature) {
            $path = sprintf('files/attach/member_extra_info/signature/%s/', getNumberingPath($member_srl));
            $filename = sprintf('%s%d.signature.php', $path, $member_srl);
            if(!$signature) return @unlink($filename);

            $buff = sprintf('<?php if(!defined("__ZBXE__")) exit();?>%s', $signature);
            FileHandler::makeDir($path);
            FileHandler::writeFile($filename, $buff);
        }

        /**
         * @brief 서명 파일 삭제
         **/
        function delSignature($member_srl) {
            $filename = sprintf('files/attach/member_extra_info/signature/%s%d.gif', getNumberingPath($member_srl), $member_srl);
            @unlink($filename);
        }

        /**
         * @brief 관리자를 추가한다
         **/
        function insertAdmin($args) {
            $args->is_admin = 'Y';
            return $this->insertMember($args);
        }

        /**
         * @brief 로그인 시킴
         **/
        function doLogin($user_id, $password = '') {
            // member model 객체 생성
            $oMemberModel = &getModel('member');

            // user_id 에 따른 정보 가져옴
            $member_info = $oMemberModel->getMemberInfoByUserID($user_id);

            // return 값이 없거나 비밀번호가 틀릴 경우
            if($member_info->user_id != $user_id) return new Object(-1, 'invalid_user_id');
            if($password && $member_info->password != md5($password)) return new Object(-1, 'invalid_password');

            // 로그인 처리
            $_SESSION['is_logged'] = true;
            $_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];

            unset($member_info->password);

            // 세션에 로그인 사용자 정보 저장
            $_SESSION['member_srl'] = $member_info->member_srl;
            $_SESSION['logged_info'] = $member_info;
            $_SESSION['group_srls'] = array_keys($member_info->group_list);
            $_SESSION['is_admin'] = $member_info->is_admin=='Y'?true:false;

            // 사용자 정보의 최근 로그인 시간을 기록
            $args->member_srl = $member_info->member_srl;
            $output = executeQuery('member.updateLastLogin', $args);

            return $output;
        }


        /**
         * @brief member 테이블에 사용자 추가
         **/
        function insertMember($args) {
            // 멤버 설정 정보에서 가입약관 부분을 재확인
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if($config->agreement && Context::get('accept_agreement')!='Y') {
                return new Object(-1, 'msg_accept_agreement');
            }

            // 필수 변수들의 조절
            if($args->allow_mailing!='Y') $args->allow_mailing = 'N';
            if($args->denied!='Y') $args->denied = 'N';
            if($args->is_admin!='Y') $args->is_admin = 'N';
            list($args->email_id, $args->email_host) = explode('@', $args->email_address);

            // 모델 객체 생성
            $oMemberModel = &getModel('member');

            // 금지 아이디인지 체크
            if($oMemberModel->isDeniedID($args->user_id)) return new Object(-1,'denied_user_id');

            // 아이디, 닉네임, email address 의 중복 체크
            $member_srl = $oMemberModel->getMemberSrlByUserID($args->user_id);
            if($member_srl) return new Object(-1,'msg_exists_user_id');

            $member_srl = $oMemberModel->getMemberSrlByNickName($args->nick_name);
            if($member_srl) return new Object(-1,'msg_exists_nick_name');

            $member_srl = $oMemberModel->getMemberSrlByEmailAddress($args->email_address);
            if($member_srl) return new Object(-1,'msg_exists_email_address');

            // DB에 입력
            $args->member_srl = getNextSequence();
            if($args->password) $args->password = md5($args->password);
            else unset($args->password);

            $output = executeQuery('member.insertMember', $args);
            if(!$output->toBool()) return $output;

            // 입력된 그룹 값이 없으면 기본 그룹의 값을 등록
            if(!$args->group_srl_list) {
                $default_group = $oMemberModel->getDefaultGroup();

                // 기본 그룹에 추가
                $output = $this->addMemberToGroup($args->member_srl,$default_group->group_srl);
                if(!$output->toBool()) return $output;

            // 입력된 그룹 값이 있으면 해당 그룹의 값을 등록
            } else {
                $group_srl_list = explode('|@|', $args->group_srl_list);
                for($i=0;$i<count($group_srl_list);$i++) {
                    $output = $this->addMemberToGroup($args->member_srl,$group_srl_list[$i]);

                    if(!$output->toBool()) return $output;
                }
            }

            $output->add('member_srl', $args->member_srl);
            return $output;
        }

        /**
         * @brief member 정보 수정
         **/
        function updateMember($args) {
            // 모델 객체 생성
            $oMemberModel = &getModel('member');

            // 수정하려는 대상의 원래 정보 가져오기
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);

            // 필수 변수들의 조절
            if($args->allow_mailing!='Y') $args->allow_mailing = 'N';
            if(!$args->denied) unset($args->denied);
            if(!$args->is_admin) unset($args->is_admin);
            list($args->email_id, $args->email_host) = explode('@', $args->email_address);


            // 아이디, 닉네임, email address 의 중복 체크
            $member_srl = $oMemberModel->getMemberSrlByUserID($args->user_id);
            if($member_srl&&$args->member_srl!=$member_srl) return new Object(-1,'msg_exists_user_id');

            $member_srl = $oMemberModel->getMemberSrlByNickName($args->nick_name);
            if($member_srl&&$args->member_srl!=$member_srl) return new Object(-1,'msg_exists_nick_name');

            $member_srl = $oMemberModel->getMemberSrlByEmailAddress($args->email_address);
            if($member_srl&&$args->member_srl!=$member_srl) return new Object(-1,'msg_exists_email_address');

            // DB에 update
            if($args->password) $args->password = md5($args->password);
            else $args->password = $member_info->password;
            if(!$args->user_name) $args->user_name = $member_info->user_name;

            $output = executeQuery('member.updateMember', $args);
            if(!$output->toBool()) return $output;

            // 그룹 정보가 있으면 그룹 정보를 변경
            if($args->group_srl_list) {
                $group_srl_list = explode('|@|', $args->group_srl_list);

                // 일단 해당 회원의 모든 그룹 정보를 삭제
                $output = executeQuery('member.deleteMemberGroupMember', $args);
                if(!$output->toBool()) return $output;

                // 하나 하나 루프를 돌면서 입력
                for($i=0;$i<count($group_srl_list);$i++) {
                    $output = $this->addMemberToGroup($args->member_srl,$group_srl_list[$i]);
                    if(!$output->toBool()) return $output;
                }
            }

            $output->add('member_srl', $args->member_srl);
            return $output;
        }

        /**
         * @brief member 비밀번호 수정
         **/
        function updateMemberPassword($args) {
            $args->password = md5($args->password);
            return executeQuery('member.updateMemberPassword', $args);
        }

        /**
         * @brief 사용자 삭제
         **/
        function deleteMember($member_srl) {

            // 모델 객체 생성
            $oMemberModel = &getModel('member');

            // 해당 사용자의 정보를 가져옴
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            if(!$member_info) return new Object(-1, 'msg_not_exists_member');

            // 관리자의 경우 삭제 불가능
            if($member_info->is_admin == 'Y') return new Object(-1, 'msg_cannot_delete_admin');

            // member_group_member에서 해당 항목들 삭제
            $args->member_srl = $member_srl;
            $output = executeQuery('member.deleteMemberGroupMember', $args);
            if(!$output->toBool()) return $output;

            // 이름이미지, 이미지마크, 서명 삭제
            $this->procMemberDeleteImageName();
            $this->procMemberDeleteImageMark();
            $this->delSignature($member_srl);

            // member 테이블에서 삭제
            return executeQuery('member.deleteMember', $args);
        }

        /**
         * @brief member_srl에 group_srl을 추가
         **/
        function addMemberToGroup($member_srl,$group_srl) {
            $args->member_srl = $member_srl;
            $args->group_srl = $group_srl;

            // 추가
            return  executeQuery('member.addMemberToGroup',$args);
        }

        /**
         * @brief 회원의 그룹값을 변경
         **/
        function changeGroup($source_group_srl, $target_group_srl) {
            $args->source_group_srl = $source_group_srl;
            $args->target_group_srl = $target_group_srl;

            return executeQuery('member.changeGroup', $args);
        }

        /**
         * @brief 그룹 등록
         **/
        function insertGroup($args) {
            // is_default값을 체크, Y일 경우 일단 모든 is_default에 대해서 N 처리
            if($args->is_default!='Y') {
                $args->is_default = 'N';
            } else {
                $output = executeQuery('member.updateGroupDefaultClear');
                if(!$output->toBool()) return $output;
            }

            return executeQuery('member.insertGroup', $args);
        }

        /**
         * @brief 그룹 정보 수정
         **/
        function updateGroup($args) {
            // is_default값을 체크, Y일 경우 일단 모든 is_default에 대해서 N 처리
            if($args->is_default!='Y') $args->is_default = 'N';
            else {
                $output = executeQuery('member.updateGroupDefaultClear');
                if(!$output->toBool()) return $output;
            }

            return executeQuery('member.updateGroup', $args);
        }

        /**
         * 그룹 삭제
         **/
        function deleteGroup($group_srl) {
            // 멤버모델 객체 생성
            $oMemberModel = &getModel('member');

            // 삭제 대상 그룹을 가져와서 체크 (is_default == 'Y'일 경우 삭제 불가)
            $group_info = $oMemberModel->getGroup($group_srl);

            if(!$group_info) return new Object(-1, 'lang->msg_not_founded');
            if($group_info->is_default == 'Y') return new Object(-1, 'msg_not_delete_default');

            // is_default == 'Y'인 그룹을 가져옴
            $default_group = $oMemberModel->getDefaultGroup();
            $default_group_srl = $default_group->group_srl;

            // default_group_srl로 변경
            $this->changeGroup($group_srl, $default_group_srl);

            $args->group_srl = $group_srl;
            return executeQuery('member.deleteGroup', $args);
        }

        /**
         * @brief 금지아이디 등록
         **/
        function insertDeniedID($user_id, $desription = '') {
            $args->user_id = $user_id;
            $args->description = $description;
            $args->list_order = -1*getNextSequence();

            return executeQuery('member.insertDeniedID', $args);
        }

        /**
         * @brief 금지아이디 삭제
         **/
        function deleteDeniedID($user_id) {
            $args->user_id = $user_id;
            return executeQuery('member.deleteDeniedID', $args);
        }

        /**
         * @brief 가입폼 항목을 삭제
         **/
        function deleteJoinForm($member_join_form_srl) {
            $args->member_join_form_srl = $member_join_form_srl;
            $output = executeQuery('member.deleteJoinForm', $args);
            return $output;
        }

        /**
         * @brief 가입항목을 상단으로 이동
         **/
        function moveJoinFormUp($member_join_form_srl) {
            $oMemberModel = &getModel('member');

            // 선택된 가입항목의 정보를 구한다
            $args->member_join_form_srl = $member_join_form_srl;
            $output = executeQuery('member.getJoinForm', $args);

            $join_form = $output->data;
            $list_order = $join_form->list_order;

            // 전체 가입항목 목록을 구한다
            $join_form_list = $oMemberModel->getJoinFormList();
            $join_form_srl_list = array_keys($join_form_list);
            if(count($join_form_srl_list)<2) return new Object();

            $prev_member_join_form = NULL;
            foreach($join_form_list as $key => $val) {
                if($val->member_join_form_srl == $member_join_form_srl) break;
                $prev_member_join_form = $val;
            }

            // 이전 가입항목가 없으면 그냥 return
            if(!$prev_member_join_form) return new Object();

            // 선택한 가입항목의 정보
            $cur_args->member_join_form_srl = $member_join_form_srl;
            $cur_args->list_order = $prev_member_join_form->list_order;

            // 대상 가입항목의 정보
            $prev_args->member_join_form_srl = $prev_member_join_form->member_join_form_srl;
            $prev_args->list_order = $list_order;

            // DB 처리
            $output = executeQuery('member.updateMemberJoinFormListorder', $cur_args);
            if(!$output->toBool()) return $output;

            executeQuery('member.updateMemberJoinFormListorder', $prev_args);
            if(!$output->toBool()) return $output;

            return new Object();
        }

        /**
         * @brief 가입항목을 하단으로 이동
         **/
        function moveJoinFormDown($member_join_form_srl) {
            $oMemberModel = &getModel('member');

            // 선택된 가입항목의 정보를 구한다
            $args->member_join_form_srl = $member_join_form_srl;
            $output = executeQuery('member.getJoinForm', $args);

            $join_form = $output->data;
            $list_order = $join_form->list_order;

            // 전체 가입항목 목록을 구한다
            $join_form_list = $oMemberModel->getJoinFormList();
            $join_form_srl_list = array_keys($join_form_list);
            if(count($join_form_srl_list)<2) return new Object();

            for($i=0;$i<count($join_form_srl_list);$i++) {
                if($join_form_srl_list[$i]==$member_join_form_srl) break;
            }

            $next_member_join_form_srl = $join_form_srl_list[$i+1];

            // 이전 가입항목가 없으면 그냥 return
            if(!$next_member_join_form_srl) return new Object();
            $next_member_join_form = $join_form_list[$next_member_join_form_srl];

            // 선택한 가입항목의 정보
            $cur_args->member_join_form_srl = $member_join_form_srl;
            $cur_args->list_order = $next_member_join_form->list_order;

            // 대상 가입항목의 정보
            $next_args->member_join_form_srl = $next_member_join_form->member_join_form_srl;
            $next_args->list_order = $list_order;

            // DB 처리
            $output = executeQuery('member.updateMemberJoinFormListorder', $cur_args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('member.updateMemberJoinFormListorder', $next_args);
            if(!$output->toBool()) return $output;

            return new Object();
        }

        /**
         * @brief 최종 출력물에서 이미지 이름을 변경
         * member_extra_info 애드온에서 요청이 됨
         **/
        function transImageName($matches) {
            $member_srl = $matches[2];
            $text = $matches[4];
            if(!$member_srl) return $matches[0];

            // 전역변수에 미리 설정한 데이터가 있다면 그걸 return
            if(!$GLOBALS['_transImageNameList'][$member_srl]) {
                $oMemberModel = &getModel('member');

                $image_name = $oMemberModel->getImageName($member_srl);
                $image_mark = $oMemberModel->getImageMark($member_srl);

                // 이미지이름이나 마크가 없으면 원본 정보를 세팅
                if(!$image_name && !$image_mark) {
                    $GLOBALS['_transImageNameList'][$member_srl] = $matches[0];

                // 이름이나 마크가 하나라도 있으면 변경
                } else {

                    if($image_name->width) {
                        if($image_mark->height && $image_mark->height > $image_name->height) $top_margin = ($image_mark->height - $image_name->height)/2;
                        else $top_margin = 0;
                        $text = sprintf('<img src="%s" border="0" alt="image" width="%s" height="%s" style="margin-top:%dpx;"/>', $image_name->file, $image_name->width, $image_name->height, $top_margin);
                    }

                    if($image_mark->width) $buff = sprintf('<div style="background:url(%s) no-repeat left;padding-left:%dpx; height:%dpx">%s</div>', $image_mark->file, $image_mark->width+2, $image_mark->height, $text);
                    else $buff = $text;

                    $GLOBALS['_transImageNameList'][$member_srl] = str_replace($matches[4], $buff, $matches[0]);
                }
            }

            return $GLOBALS['_transImageNameList'][$member_srl];
        }

        /**
         * @brief 최종 출력물에서 서명을 변경
         * member_extra_info 애드온에서 요청이 됨
         **/
        function transSignature($matches) {
            $member_srl = $matches[2];
            $text = $matches[4];
            if(!$member_srl) return $matches[0];

            // 전역변수에 미리 설정한 데이터가 있다면 그걸 return
            if(!$GLOBALS['_transSignatureList'][$member_srl]) {
                $oMemberModel = &getModel('member');

                $signature = $oMemberModel->getSignature($member_srl);

                // 서명이 없으면 빈 내용을 등록
                if(!$signature) {
                    $GLOBALS['_transSignatureList'][$member_srl] = $matches[0];

                // 서명이 있으면 글의 내용 다음에 추가
                } else {

                    $document = $matches[0].'<div class="member_signature">'.$signature.'</div>';

                    $GLOBALS['_transSignatureList'][$member_srl] = $document;
                }
            }

            return $GLOBALS['_transSignatureList'][$member_srl];
        }

    }
?>
