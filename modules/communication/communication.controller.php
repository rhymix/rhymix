<?php
    /**
     * @class  communicationController
     * @author zero (zero@nzeo.com)
     * @brief  communication module의 Controller class
     **/

    class communicationController extends communication {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 쪽지함 설정 변경
         **/
        function procCommunicationUpdateAllowMessage() {
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');

            $args->allow_message = Context::get('allow_message');
            if(!in_array($args->allow_message, array('Y','N','F'))) $args->allow_message = 'Y';

            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;

            $output = executeQuery('communication.updateAllowMessage', $args);

            return $output;
        }

        /**
         * @brief 쪽지 발송
         **/
        function procCommunicationSendMessage() {
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

            $send_mail = Context::get('send_mail');
            if($send_mail != 'Y') $send_mail = 'N';

            // 받을 회원이 있는지에 대한 검사
            $oMemberModel = &getModel('member');
            $oCommunicationModel = &getModel('communication');
            $receiver_member_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
            if($receiver_member_info->member_srl != $receiver_srl) return new Object(-1, 'msg_not_exists_member');

            // 받을 회원의 쪽지 수신여부 검사 (최고관리자이면 패스)
            if($logged_info->is_admin != 'Y') {
                if($receiver_member_info->allow_message == 'F') {
                    if(!$oCommunicationModel->isFriend($receiver_member_info->member_srl)) return new object(-1, 'msg_allow_message_to_friend');
                } elseif($receiver_member_info->allow_messge == 'N') {
                    return new object(-1, 'msg_disallow_message');
                }
            }

            // 쪽지 발송
            $output = $this->sendMessage($logged_info->member_srl, $receiver_srl, $title, $content);

            // 메일로도 발송
            if($output->toBool() && $send_mail == 'Y') {
                $view_url = Context::getRequestUri();
                $content = sprintf("%s<br /><br />From : <a href=\"%s\" target=\"_blank\">%s</a>",$content, $view_url, $view_url);
                $oMail = new Mail();
                $oMail->setTitle($title);
                $oMail->setContent($content);
                $oMail->setSender($logged_info->user_name, $logged_info->email_address);
                $oMail->setReceiptor($receiver_member_info->user_name, $receiver_member_info->email_address);
                $oMail->send();
            }

            return $output;
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
                $output = executeQuery('communication.sendMessage', $sender_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }
            }

            // 받을 회원의 쪽지함에 넣을 쪽지
            $output = executeQuery('communication.sendMessage', $receiver_args);
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
        function procCommunicationStoreMessage() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 체크
            $message_srl = Context::get('message_srl');
            if(!$message_srl) return new Object(-1,'msg_invalid_request');

            // 쪽지를 가져옴
            $oCommunicationModel = &getModel('communication');
            $message = $oCommunicationModel->getSelectedMessage($message_srl);
            if(!$message || $message->message_type != 'R') return new Object(-1,'msg_invalid_request');

            $args->message_srl = $message_srl;
            $args->receiver_srl = $logged_info->member_srl;
            $output = executeQuery('communication.setMessageStored', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
        }

        /**
         * @brief 쪽지 삭제
         **/
        function procCommunicationDeleteMessage() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl;

            // 변수 체크
            $message_srl = Context::get('message_srl');
            if(!$message_srl) return new Object(-1,'msg_invalid_request');

            // 쪽지를 가져옴
            $oCommunicationModel = &getModel('communication');
            $message = $oCommunicationModel->getSelectedMessage($message_srl);
            if(!$message) return new Object(-1,'msg_invalid_request');

            // 발송인+type=S or 수신인+type=R 검사
            if($message->sender_srl == $member_srl && $message->message_type == 'S') {
                if(!$message_srl) return new Object(-1, 'msg_invalid_request');
            } elseif($message->receiver_srl == $member_srl && $message->message_type == 'R') {
                if(!$message_srl) return new Object(-1, 'msg_invalid_request');
            }

            // 삭제
            $args->message_srl = $message_srl;
            $output = executeQuery('communication.deleteMessage', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 선택된 다수의 쪽지 삭제
         **/
        function procCommunicationDeleteMessages() {
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

            $output = executeQuery('communication.deleteMessages', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 친구 추가
         **/
        function procCommunicationAddFriend() {
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
            $output = executeQuery('communication.addFriend', $args);
            if(!$output->toBool()) return $output;

            $this->add('member_srl', $target_srl);
            $this->setMessage('success_registed');
        }

        /**
         * @brief 등록된 친구의 그룹 이동
         **/
        function procCommunicationMoveFriend() {
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

            $output = executeQuery('communication.moveFriend', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_moved');
        }

        /**
         * @brief 친구 삭제
         **/
        function procCommunicationDeleteFriend() {
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
            $output = executeQuery('communication.deleteFriend', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 친구 그룹 추가
         **/
        function procCommunicationAddFriendGroup() {
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
                $output = executeQuery('communication.renameFriendGroup', $args);
                $msg_code = 'success_updated';

            // 아니면 입력
            } else {
                $output = executeQuery('communication.addFriendGroup', $args);
                $msg_code = 'success_registed';
            }

            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
        }

        /**
         * @brief 친구 그룹 이름 변경
         **/
        function procCommunicationRenameFriendGroup() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 정리
            $args->friend_group_srl= Context::get('friend_group_srl');
            $args->member_srl = $logged_info->member_srl;
            $args->title = Context::get('title');
            if(!$args->title) return new Object(-1, 'msg_invalid_request');

            $output = executeQuery('communication.renameFriendGroup', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 친구 그룹 삭제
         **/
        function procCommunicationDeleteFriendGroup() {
            // 로그인 정보 체크
            if(!Context::get('is_logged')) return new Object(-1, 'msg_not_logged');
            $logged_info = Context::get('logged_info');

            // 변수 정리
            $args->friend_group_srl = Context::get('friend_group_srl');
            $args->member_srl = $logged_info->member_srl;
            $output = executeQuery('communication.deleteFriendGroup', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 특정 쪽지의 상태를 읽은 상태로 변경
         **/
        function setMessageReaded($message_srl) {
            $args->message_srl = $message_srl;
            $args->related_srl = $message_srl;
            return executeQuery('communication.setMessageReaded', $args);
        }

    }
?>
