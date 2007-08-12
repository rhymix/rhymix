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
         * @brief openid로그인
         **/
        function procMemberOpenIDLogin() {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if($config->enable_openid != 'Y') $this->stop('msg_invalid_request');

            ob_start();
            require('./modules/member/openid_lib/class.openid.php');
            require_once('./modules/member/openid_lib/libcurlemu.inc.php');

            $user_id = Context::get('user_id');

            $openid = new SimpleOpenID();

            $openid->SetIdentity($user_id);
            $openid->SetTrustRoot('http://' . $_SERVER["HTTP_HOST"]);

            $openid->SetRequiredFields(array('email'));
            $openid->SetOptionalFields(array('dob'));

            if (!$openid->GetOpenIDServer()) {
                $error = $openid->GetError();
                $this->setError(-1);
                $this->setMessage($error['description']);
            } else {
                $openid->SetApprovedURL( sprintf('%s?module=member&act=procMemberOpenIDValidate', Context::getRequestUri()) );
                $url = $openid->GetRedirectURL();
                $this->add('redirect_url', $url);
            }
            ob_clean();
        }

        /** 
         * @brief openid 인증 체크
         **/
        function procMemberOpenIDValidate() {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if($config->enable_openid != 'Y') $this->stop('msg_invalid_request');

            ob_start();
            require('./modules/member/openid_lib/class.openid.php');
            require_once('./modules/member/openid_lib/libcurlemu.inc.php');

            $openid = new SimpleOpenID;
            $openid->SetIdentity($_GET['openid_identity']);
            $openid_validation_result = $openid->ValidateWithServer();
            ob_clean();

            // 인증 성공
            if ($openid_validation_result == true) {
                // 기본 정보들을 받음
                $args->user_id = $args->nick_name = preg_replace('/^http:\/\//i','',Context::get('openid_identity'));
                $args->email_address = Context::get('openid_sreg_email');
                $args->user_name = Context::get('openid_sreg_fullname');
                if(!$args->user_name) list($args->user_name) = explode('@', $args->email_address);
                $args->birthday = Context::get('openid_sreg_dob');

                // 자체 인증 시도
                $output = $this->doLogin($args->user_id);

                // 자체 인증 실패시 회원 가입시킴
                if(!$output->toBool()) {
                    $args->password = md5(getmicrotime());
                    $output = $this->insertMember($args);
                    if(!$output->toBool()) return $this->stop($output->getMessage());
                    $output = $this->doLogin($args->user_id);
                    if(!$output->toBool()) return $this->stop($output->getMessage());
                }

                // 페이지 이동
                header("location:./");
                exit();


            // 인증 실패
            } else if($openid->IsError() == true) {
                $error = $openid->GetError();
                return $this->stop($error['description']);
            } else {
                return $this->stop('invalid_authorization');
            }
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

            // 받을 회원의 쪽지 수신여부 검사
            if($receiver_member_info->allow_message == 'F') {
                if(!$oMemberModel->isFriend($receiver_member_info->member_srl)) return new object(-1, 'msg_allow_message_to_friend');
            } elseif($receiver_member_info->allow_messge == 'N') {
                return new object(-1, 'msg_disallow_message');
            }

            // 쪽지 발송
            return $this->sendMessage($logged_info->member_srl, $receiver_srl, $title, $content);
        }

        function sendMessage($sender_srl, $receiver_srl, $title, $content, $sender_log = true) {
            $content = removeHackTag($content);

            // 보내는 사용자의 쪽지함에 넣을 쪽지
            $sender_args->sender_srl = $sender_srl;
            $sender_args->receiver_srl = $receiver_srl;
            $sender_args->message_type = 'S';
            $sender_args->title = $title;
            $sender_args->content = $content;
            $sender_args->readed = 'N';
            $sender_args->regdate = date("YmdHis");
            $sender_args->related_srl = getNextSequence();
            $sender_args->message_srl = getNextSequence();
            $sender_args->list_order = getNextSequence()*-1;

            // 받는 회원의 쪽지함에 넣을 쪽지
            $receiver_args->message_srl = $sender_args->related_srl;
            $receiver_args->related_srl = 0;
            $receiver_args->list_order = $sender_args->related_srl*-1;
            $receiver_args->sender_srl = $sender_srl;
            if(!$receiver_args->sender_srl) $receiver_args->sender_srl = $receiver_srl;
            $receiver_args->receiver_srl = $receiver_srl;
            $receiver_args->message_type = 'R';
            $receiver_args->title = $title;
            $receiver_args->content = $content;
            $receiver_args->readed = 'N';
            $receiver_args->regdate = date("YmdHis");

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 발송하는 회원의 쪽지함에 넣을 쪽지
            if($sender_srl && $sender_log) {
                $output = executeQuery('member.sendMessage', $sender_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }
            }

            // 받을 회원의 쪽지함에 넣을 쪽지
            $output = executeQuery('member.sendMessage', $receiver_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 받는 회원의 쪽지 발송 플래그 생성 (파일로 생성)
            $flag_path = './files/member_extra_info/new_message_flags/'.getNumberingPath($receiver_srl);
            FileHandler::makeDir($flag_path);
            $flag_file = sprintf('%s%s', $flag_path, $receiver_srl);
            FileHandler::writeFile($flag_file,'1');

            $oDB->commit();

            return new Object(0,'success_sended');
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
         * @brief 스크랩 기능
         **/
        function procMemberScrapDocument() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            $document_srl = (int)Context::get('document_srl');
            if(!$document_srl) return new Object(-1,'msg_invalid_request');

            // 문서 가져오기
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);

            // 변수 정리
            $args->document_srl = $document_srl;
            $args->member_srl = $logged_info->member_srl;
            $args->user_id = $oDocument->get('user_id');
            $args->user_name = $oDocument->get('user_name');
            $args->nick_name = $oDocument->get('nick_name');
            $args->target_member_srl = $oDocument->get('member_srl');
            $args->title = $oDocument->get('title');

            // 있는지 조사
            $output = executeQuery('member.getScrapDocument', $args);
            if($output->data->count) return new Object(-1, 'msg_alreay_scrapped');

            // 입력
            $output = executeQuery('member.addScrapDocument', $args);
            if(!$output->toBool()) return $output;

            $this->setError(-1);
            $this->setMessage('success_registed');
        }

        /**
         * @brief 스크랩 삭제
         **/
        function procMemberDeleteScrap() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            $document_srl = (int)Context::get('document_srl');
            if(!$document_srl) return new Object(-1,'msg_invalid_request');

            // 변수 정리
            $args->member_srl = $logged_info->member_srl;
            $args->document_srl = $document_srl;
            return executeQuery('member.deleteScrapDocument', $args);
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

            $this->add('member_srl', $target_srl);
            $this->setMessage('success_registed');
        }

        /**
         * @brief 등록된 친구의 그룹 이동
         **/
        function procMemberMoveFriend() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 체크
            $friend_srl_list = trim(Context::get('friend_srl_list'));
            if(!$friend_srl_list) return new Object(-1, 'msg_cart_is_null');

            $friend_srl_list = explode('|@|', $friend_srl_list);
            if(!count($friend_srl_list)) return new Object(-1, 'msg_cart_is_null');

            $friend_count = count($friend_srl_list);
            $target = array();
            for($i=0;$i<$friend_count;$i++) {
                $friend_srl = (int)trim($friend_srl_list[$i]);
                if(!$friend_srl) continue;
                $target[] = $friend_srl;
            }
            if(!count($target)) return new Object(-1,'msg_cart_is_null');

            // 변수 정리
            $args->friend_srls = implode(',',$target);
            $args->member_srl = $logged_info->member_srl;
            $args->friend_group_srl = Context::get('target_friend_group_srl');

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
            $member_srl = $logged_info->member_srl;

            // 변수 체크
            $friend_srl_list = trim(Context::get('friend_srl_list'));
            if(!$friend_srl_list) return new Object(-1, 'msg_cart_is_null');

            $friend_srl_list = explode('|@|', $friend_srl_list);
            if(!count($friend_srl_list)) return new Object(-1, 'msg_cart_is_null');

            $friend_count = count($friend_srl_list);
            $target = array();
            for($i=0;$i<$friend_count;$i++) {
                $friend_srl = (int)trim($friend_srl_list[$i]);
                if(!$friend_srl) continue;
                $target[] = $friend_srl;
            }
            if(!count($target)) return new Object(-1,'msg_cart_is_null');

            // 삭제
            $args->friend_srls = implode(',',$target);
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
            $args->friend_group_srl = trim(Context::get('friend_group_srl'));
            $args->member_srl = $logged_info->member_srl;
            $args->title = Context::get('title');
            if(!$args->title) return new Object(-1, 'msg_invalid_request');

            // friend_group_srl이 있으면 수정
            if($args->friend_group_srl) {
                $output = executeQuery('member.renameFriendGroup', $args);
                $msg_code = 'success_updated';

            // 아니면 입력
            } else {
                $output = executeQuery('member.addFriendGroup', $args);
                $msg_code = 'success_registed';
            }

            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
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
         * @brief 회원 가입
         **/
        function procMemberInsert() {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');

            // 관리자가 회원가입을 허락하였는지 검사
            if($config->enable_join != 'Y') return $this->stop('msg_signup_disabled');

            // 약관에 동의하였는지 검사 (약관이 있을 경우만) 
            if($config->agreement && Context::get('accept_agreement')!='Y') return $this->stop('msg_accept_agreement');

            // 필수 정보들을 미리 추출
            $args = Context::gets('user_id','user_name','nick_name','homepage','blog','birthday','email_address','password','allow_mailing','allow_message');
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
         * @brief 회원 정보 수정
         **/
        function procMemberModifyInfo() {
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');

            // 필수 정보들을 미리 추출
            $args = Context::gets('user_name','nick_name','homepage','blog','birthday','email_address','allow_mailing','allow_message');

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

            $this->setSessionInfo($args);

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
            $current_password = trim(Context::get('current_password'));
            $password = trim(Context::get('password'));

            // 로그인한 유저의 정보를 가져옴
            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            // member model 객체 생성
            $oMemberModel = &getModel('member');

            // member_srl 에 따른 정보 가져옴
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);

            // 현재 비밀번호가 맞는지 확인
            if(!$current_password || ($member_info->password != md5($current_password) && $this->mysql_pre4_hash_password($current_password) != $member_info->password)) {
                return new Object(-1, 'invalid_password');
            }

            // member_srl의 값에 따라 insert/update
            $args->member_srl = $member_srl;
            $args->password = $password;
            $output = $this->updateMemberPassword($args);
            if(!$output->toBool()) return $output;

            $this->add('member_srl', $args->member_srl);
            $this->setMessage('success_updated');
        }

        /**
         * @brief 탈퇴
         **/
        function procMemberLeave() {
            if(!Context::get('is_logged')) return $this->stop('msg_not_logged');

            // 필수 정보들을 미리 추출
            $password = trim(Context::get('password'));

            // 로그인한 유저의 정보를 가져옴
            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            // member model 객체 생성
            $oMemberModel = &getModel('member');

            // member_srl 에 따른 정보 가져옴
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);

            // 현재 비밀번호가 맞는지 확인
            if(!$password || ($member_info->password != md5($password) && $this->mysql_pre4_hash_password($password) != $member_info->password)) {
                return new Object(-1, 'invalid_password');
            }

            $output = $this->deleteMember($member_srl);
            if(!$output->toBool()) return $output;

            $_SESSION['is_logged'] = false;
            $_SESSION['logged_info'] = null;

            $this->setMessage('success_leaved');
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

            $this->insertImageName($member_srl, $file['tmp_name']);

            // 페이지 리프레쉬
            $this->setRefreshPage();
        }

        function insertImageName($member_srl, $target_file) {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');

            // 정해진 사이즈를 구함
            $max_width = $config->image_name_max_width;
            if(!$max_width) $max_width = "90";
            $max_height = $config->image_name_max_height;
            if(!$max_height) $max_height = "20";

            // 저장할 위치 구함
            $target_path = sprintf('files/member_extra_info/image_name/%s/', getNumberingPath($member_srl));
            FileHandler::makeDir($target_path);

            $target_filename = sprintf('%s%d.gif', $target_path, $member_srl);

            // 파일 정보 구함
            list($width, $height, $type, $attrs) = @getimagesize($target_file);

            // 지정된 사이즈보다 크거나 gif가 아니면 변환
            if($width > $max_width || $height > $max_height || $type!=1) FileHandler::createImageFile($target_file, $target_filename, $max_width, $max_height, 'gif');
            else @copy($target_file, $target_filename);
        }

        /**
         * @brief 이미지 이름을 삭제
         **/
        function procMemberDeleteImageName() {
            $member_srl = Context::get('member_srl');
            if(!$member_srl) return new Object(0,'success');

            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');
            if($config->image_name == 'N') return new Object(0,'success');

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

            $this->insertImageMark($member_srl, $file['tmp_name']);

            // 페이지 리프레쉬
            $this->setRefreshPage();
        }

        function insertImageMark($member_srl, $target_file) {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');

            // 정해진 사이즈를 구함
            $max_width = $config->image_mark_max_width;
            if(!$max_width) $max_width = "20";
            $max_height = $config->image_mark_max_height;
            if(!$max_height) $max_height = "20";
            
            $target_path = sprintf('files/member_extra_info/image_mark/%s/', getNumberingPath($member_srl));
            FileHandler::makeDir($target_path);

            $target_filename = sprintf('%s%d.gif', $target_path, $member_srl);
            FileHandler::createImageFile($target_file, $target_filename, $max_width, $max_height, 'gif');

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
            $signature = removeHackTag($signature);
            $path = sprintf('files/member_extra_info/signature/%s/', getNumberingPath($member_srl));
            $filename = sprintf('%s%d.signature.php', $path, $member_srl);
            if(!trim($signature) || trim(strtolower($signature))=='<br>') return @unlink($filename);

            $buff = sprintf('<?php if(!defined("__ZBXE__")) exit();?>%s', $signature);
            FileHandler::makeDir($path);
            FileHandler::writeFile($filename, $buff);
        }

        /**
         * @brief 서명 파일 삭제
         **/
        function delSignature($member_srl) {
            $filename = sprintf('files/member_extra_info/signature/%s%d.gif', getNumberingPath($member_srl), $member_srl);
            @unlink($filename);
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
         * @brief 로그인 시킴
         **/
        function doLogin($user_id, $password = '') {
            // member model 객체 생성
            $oMemberModel = &getModel('member');

            // user_id 에 따른 정보 가져옴
            $member_info = $oMemberModel->getMemberInfoByUserID($user_id);

            // return 값이 없으면 존재하지 않는 사용자로 지정
            if(!$user_id || $member_info->user_id != $user_id) return new Object(-1, 'invalid_user_id');

            // 비밀번호 검사 : 우선 md5() hash값으로 비굥
            if($password && $member_info->password != md5($password)) {
                // 혹시나 하여.. -_-;; mysql old_password로 검사하여 맞으면 db의 비밀번호 교체
                if($this->mysql_pre4_hash_password($password) == $member_info->password) {

                    // 비밀번호 교체
                    $password_args->member_srl = $member_info->member_srl;
                    $password_args->password = md5($password);
                    $output = executeQuery('member.updateMemberPassword', $password_args);
                    if(!$output->toBool()) return $output;

                // md5(), mysql old_password와도 다르면 잘못된 비빌번호 오류 메세지 리턴
                } else {
                    return new Object(-1, 'invalid_password');
                }
            }

            // denied == 'Y' 이면 알림
            if($member_info->denied == 'Y') return new Object(-1,'msg_user_denied');

            // denied_date가 현 시간보다 적으면 알림
            if($member_info->limit_date && $member_info->limit_date >= date("YmdHis")) return new Object(-1,sprintf(Context::getLang('msg_user_limited'),zdate($member_info->limit_date,"Y-m-d H:i")));

            // 사용자 정보의 최근 로그인 시간을 기록
            $args->member_srl = $member_info->member_srl;
            $output = executeQuery('member.updateLastLogin', $args);

            $this->setSessionInfo($member_info);

            return $output;
        }

        /**
         * @brief 세션 정보 갱싱 또는 생성
         **/
        function setSessionInfo($member_info) {
            if(!$member_info->member_srl) return;

            // 로그인 처리
            $_SESSION['is_logged'] = true;
            $_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['member_srl'] = $member_info->member_srl;
            $_SESSION['is_admin'] = false;

            unset($member_info->password);

            // 사용자 그룹 설정
            if($member_info->group_list) {
                $group_srl_list = array_keys($member_info->group_list);
                $_SESSION['group_srls'] = $group_srl_list;

                // 관리자 그룹일 경우 관리자로 지정
                $oMemberModel = &getModel('member');
                $admin_group = $oMemberModel->getAdminGroup();
                if($admin_group->group_srl && in_array($admin_group->group_srl, $group_srl_list)) $_SESSION['is_admin'] = true;
            }
            
            // 세션에 로그인 사용자 정보 저장
            foreach($member_info as $key => $val) {
                $_SESSION['logged_info']->{$key} = $val;
            }

            Context::set('is_logged', true);
            Context::set('logged_info', $member_info);
        }


        /**
         * @brief member 테이블에 사용자 추가
         **/
        function insertMember($args, $password_is_hashed = false) {
            // 멤버 설정 정보에서 가입약관 부분을 재확인
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('member');

            $logged_info = Context::get('logged_info');

            // 임시 제한 일자가 있을 경우 제한 일자에 내용 추가
            if($config->limit_day) $args->limit_date = date("YmdHis", time()+$config->limit_day*60*60*24);

            // 입력할 사용자의 아이디를 소문자로 변경
            $args->user_id = strtolower($args->user_id);

            // 필수 변수들의 조절
            if($args->allow_mailing!='Y') $args->allow_mailing = 'N';
            if(!in_array($args->allow_message, array('Y','N','F'))) $args->allow_message= 'Y';

            if($logged_info->is_admin == 'Y') {
                if($args->denied!='Y') $args->denied = 'N';
                if($args->is_admin!='Y') $args->is_admin = 'N';
            } else {
                unset($args->is_admin);
                unset($args->denied);
            }

            list($args->email_id, $args->email_host) = explode('@', $args->email_address);

            // 홈페이지, 블로그의 주소 검사
            if($args->homepage && !eregi("^http:\/\/",$args->homepage)) $args->homepage = 'http://'.$args->homepage;
            if($args->blog && !eregi("^http:\/\/",$args->blog)) $args->blog = 'http://'.$args->blog;

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

            $oDB = &DB::getInstance();
            $oDB->begin();

            // DB에 입력
            $args->member_srl = getNextSequence();
            if($args->password && !$password_is_hashed) $args->password = md5($args->password);
            elseif(!$args->password) unset($args->password);

            $output = executeQuery('member.insertMember', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 입력된 그룹 값이 없으면 기본 그룹의 값을 등록
            if(!$args->group_srl_list) {
                $default_group = $oMemberModel->getDefaultGroup();

                // 기본 그룹에 추가
                $output = $this->addMemberToGroup($args->member_srl,$default_group->group_srl);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

            // 입력된 그룹 값이 있으면 해당 그룹의 값을 등록
            } else {
                $group_srl_list = explode('|@|', $args->group_srl_list);
                for($i=0;$i<count($group_srl_list);$i++) {
                    $output = $this->addMemberToGroup($args->member_srl,$group_srl_list[$i]);

                    if(!$output->toBool()) {
                        $oDB->rollback();
                        return $output;
                    }
                }
            }

            $oDB->commit(true);

            $output->add('member_srl', $args->member_srl);
            return $output;
        }

        /**
         * @brief member 정보 수정
         **/
        function updateMember($args) {
            // 모델 객체 생성
            $oMemberModel = &getModel('member');

            $logged_info = Context::get('logged_info');

            // 수정하려는 대상의 원래 정보 가져오기
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);

            // 필수 변수들의 조절
            if($args->allow_mailing!='Y') $args->allow_mailing = 'N';
            if(!in_array($args->allow_message, array('Y','N','F'))) $args->allow_message = 'Y';

            if($logged_info->is_admin == 'Y') {
                if($args->denied!='Y') $args->denied = 'N';
                if($args->is_admin!='Y' && $logged_info->member_srl != $args->member_srl) $args->is_admin = 'N';
            } else {
                unset($args->is_admin);
                unset($args->denied);
            }

            list($args->email_id, $args->email_host) = explode('@', $args->email_address);

            // 홈페이지, 블로그의 주소 검사
            if($args->homepage && !eregi("^http:\/\/",$args->homepage)) $args->homepage = 'http://'.$args->homepage;
            if($args->blog && !eregi("^http:\/\/",$args->blog)) $args->blog = 'http://'.$args->blog;

            // 아이디, 닉네임, email address 의 중복 체크
            $member_srl = $oMemberModel->getMemberSrlByUserID($args->user_id);
            if($member_srl&&$args->member_srl!=$member_srl) return new Object(-1,'msg_exists_user_id');

            $member_srl = $oMemberModel->getMemberSrlByNickName($args->nick_name);
            if($member_srl&&$args->member_srl!=$member_srl) return new Object(-1,'msg_exists_nick_name');

            $member_srl = $oMemberModel->getMemberSrlByEmailAddress($args->email_address);
            if($member_srl&&$args->member_srl!=$member_srl) return new Object(-1,'msg_exists_email_address');

            $oDB = &DB::getInstance();
            $oDB->begin();

            // DB에 update
            if($args->password) $args->password = md5($args->password);
            else $args->password = $member_info->password;
            if(!$args->user_name) $args->user_name = $member_info->user_name;

            $output = executeQuery('member.updateMember', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 그룹 정보가 있으면 그룹 정보를 변경
            if($args->group_srl_list) {
                $group_srl_list = explode('|@|', $args->group_srl_list);

                // 일단 해당 회원의 모든 그룹 정보를 삭제
                $output = executeQuery('member.deleteMemberGroupMember', $args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 하나 하나 루프를 돌면서 입력
                for($i=0;$i<count($group_srl_list);$i++) {
                    $output = $this->addMemberToGroup($args->member_srl,$group_srl_list[$i]);
                    if(!$output->toBool()) {
                        $oDB->rollback();
                        return $output;
                    }
                }
            }

            $oDB->commit();

            // 세션에 저장
            $member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);

            $logged_info = Context::get('logged_info');
            if($logged_info->member_srl == $member_srl) {
                $_SESSION['logged_info'] = $member_info;
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
         * @brief 최종 출력물에서 이미지 이름을 변경
         * member_extra_info 애드온에서 요청이 됨
         **/
        function transImageName($matches) {
            $member_srl = $matches[3];
            if($member_srl<0) return $matches[5];

            $text = $matches[5];
            if(!$member_srl) return $matches[0];

            // 전역변수에 미리 설정한 데이터가 있다면 그걸 return
            if(!$GLOBALS['_transImageNameList'][$member_srl]) {
                $oMemberModel = &getModel('member');

                $GLOBALS['_transImageNameList'][$member_srl]['image_name'] = $oMemberModel->getImageName($member_srl);
                $GLOBALS['_transImageNameList'][$member_srl]['image_mark'] = $oMemberModel->getImageMark($member_srl);
            } 
            $image_name = $GLOBALS['_transImageNameList'][$member_srl]['image_name'];
            $image_mark = $GLOBALS['_transImageNameList'][$member_srl]['image_mark'];

            // 이미지이름이나 마크가 없으면 원본 정보를 세팅
            if(!$image_name && !$image_mark) return $matches[0];

            if($image_name->width) {
                if($image_mark->height && $image_mark->height > $image_name->height) $top_margin = ($image_mark->height - $image_name->height)/2;
                else $top_margin = 0;
                $text = sprintf('<img src="%s" border="0" alt="id: %s" title="id: %s" width="%s" height="%s" style="margin-top:%dpx;"/>', Context::getRequestUri().$image_name->file, htmlspecialchars(strip_tags($matches[5])), htmlspecialchars(strip_tags($matches[5])), $image_name->width, $image_name->height, $top_margin);
            }

            if($image_mark->width) {
                $matches[0] = str_replace('<'.$matches[6], sprintf('<%s style="cursor:pointer;background:url(%s) no-repeat left;padding-left:%dpx; height:%dpx" ', $matches[1],Context::getRequestUri().$image_mark->file, $image_mark->width+2, $image_mark->height), $matches[0] );
            }
            $output = str_replace('>'.$matches[5].'<', '>'.$text.'<', $matches[0]);
            return $output;
        }

        /**
         * @brief 최종 출력물에서 서명을 변경
         * member_extra_info 애드온에서 요청이 됨
         **/
        function transSignature($matches) {
            $member_srl = $matches[2];
            if(!$member_srl) return $matches[0];

            // 전역변수에 미리 설정한 데이터가 있다면 그걸 return
            if(!isset($GLOBALS['_transSignatureList'][$member_srl])) {
                $oMemberModel = &getModel('member');

                $signature = $oMemberModel->getSignature($member_srl);

                // 서명이 없으면 빈 내용을 등록
                if(!$signature) {
                    $GLOBALS['_transSignatureList'][$member_srl] = null;

                // 서명이 있으면 글의 내용 다음에 추가
                } else {
                    $document = $matches[0].'<div class="member_signature">'.$signature.'</div>';
                    $GLOBALS['_transSignatureList'][$member_srl] = $document;
                }
            }

            return $GLOBALS['_transSignatureList'][$member_srl].$matches[0];
        }

        /**
         * @brief mysql old_password 의 php 구현 함수
         * 제로보드4나 기타 mysql4.1 이전의 old_password()함수를 쓴 데이터의 사용을 위해서
         * mysql의 password.c 소스 참조해서 구현함
         **/
        function mysql_pre4_hash_password($password) {
            $nr = 1345345333;
            $add = 7;
            $nr2 = 0x12345671;

            settype($password, "string");

            for ($i=0; $i<strlen($password); $i++) {
                if ($password[$i] == ' ' || $password[$i] == '\t') continue;
                $tmp = ord($password[$i]);
                $nr ^= ((($nr & 63) + $add) * $tmp) + ($nr << 8);
                $nr2 += ($nr2 << 8) ^ $nr;
                $add += $tmp;
            }
            $result1 = sprintf("%08lx", $nr & ((1 << 31) -1));
            $result2 = sprintf("%08lx", $nr2 & ((1 << 31) -1));

            if($result1 == '80000000') $nr += 0x80000000;
            if($result2 == '80000000') $nr2 += 0x80000000;

            return sprintf("%08lx%08lx", $nr, $nr2);
        }
    }
?>
