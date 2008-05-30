<?php
    /**
     * @class  communicationModel
     * @author zero (zero@nzeo.com)
     * @brief  communication module의 Model class
     **/

    class communicationModel extends communication {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설정된 내용을 구함
         **/
        function getConfig() {
            $oModuleModel = &getModel('module');
            $communication_config = $oModuleModel->getModuleConfig('communication');

            if(!$communication_config->skin) $communication_config->skin = 'default';
            if(!$communication_config->colorset) $communication_config->colorset = 'white';
            if(!$communication_config->editor_skin) $communication_config->editor_skin = 'default';

            return $communication_config;
        }

        /**
         * @brief 쪽지 내용을 가져옴
         **/
        function getSelectedMessage($message_srl) {
            $logged_info = Context::get('logged_info');

            $args->message_srl = $message_srl;
            $output = executeQuery('communication.getMessage',$args);
            $message = $output->data;
            if(!$message) return ;

            // 보낸 쪽지일 경우 받는 사람 정보를 구함 
            $oMemberModel = &getModel('member');
            if($message->sender_srl == $logged_info->member_srl && $message->message_type == 'S') $member_info = $oMemberModel->getMemberInfoByMemberSrl($message->receiver_srl);

            // 보관/받은 쪽지일 경우 보낸 사람 정보를 구함
            else $member_info = $oMemberModel->getMemberInfoByMemberSrl($message->sender_srl);

            if($member_info) {
                foreach($member_info as $key => $val) {
                  if($key != 'regdate') $message->{$key} = $val;
                }
            }

            // 받은 쪽지이고 아직 읽지 않았을 경우 읽은 상태로 변경
            if($message->message_type == 'R' && $message->readed != 'Y') {
                $oCommunicationController = &getController('communication');
                $oCommunicationController->setMessageReaded($message_srl);
            }


            return $message;
        }

        /**
         * @brief 새 쪽지를 가져옴
         **/
        function getNewMessage() {
            $logged_info = Context::get('logged_info');
            $args->receiver_srl = $logged_info->member_srl;
            $args->readed = 'N';

            $output = executeQuery('communication.getNewMessage', $args);
            if(!count($output->data)) return;
            $message = array_pop($output->data);

            $oCommunicationController = &getController('communication');
            $oCommunicationController->setMessageReaded($message->message_srl);

            return $message;
        }

        /**
         * @brief 쪽지 목록 가져오기
         * type = R : 받은 쪽지
         * type = S : 보낸 쪽지 
         * type = T : 보관함
         **/
        function getMessages($message_type = "R") {
            $logged_info = Context::get('logged_info');

            switch($message_type) {
                case 'R' :
                        $args->member_srl = $logged_info->member_srl;
                        $args->message_type = 'R';
                        $query_id = 'communication.getReceivedMessages';
                    break;
                case 'T' :
                        $args->member_srl = $logged_info->member_srl;
                        $args->message_type = 'T';
                        $query_id = 'communication.getStoredMessages';
                    break;
                default :
                        $args->member_srl = $logged_info->member_srl;
                        $args->message_type = 'S';
                        $query_id = 'communication.getSendedMessages';
                    break;
    
            }

            // 기타 변수들 정리
            $args->sort_index = 'message.list_order';
            $args->page = Context::get('page');
            $args->list_count = 20;
            $args->page_count = 10;
            return executeQuery($query_id, $args);
        }

        /**
         * @brief 친구 목록 가져오기
         **/
        function getFriends($friend_group_srl = 0) {
            $logged_info = Context::get('logged_info');

            $args->friend_group_srl = $friend_group_srl;
            $args->member_srl = $logged_info->member_srl;

            // 기타 변수들 정리
            $args->page = Context::get('page');
            $args->sort_index = 'friend.list_order';
            $args->list_count = 10;
            $args->page_count = 10;
            $output = executeQuery('communication.getFriends', $args);
            return $output;
        }

        /**
         * @brief 이미 친구로 등록되었는지 검사
         **/
        function isAddedFriend($member_srl) {
            $logged_info = Context::get('logged_info');

            $args->member_srl = $logged_info->member_srl;
            $args->target_srl = $member_srl;
            $output = executeQuery('communication.isAddedFriend', $args);
            return $output->data->count;
        }

        /**
         * @brief 특정 친구 그룹 가져오기 
         **/
        function getFriendGroupInfo($friend_group_srl) {
            $logged_info = Context::get('logged_info');

            $args->member_srl = $logged_info->member_srl;
            $args->friend_group_srl = $friend_group_srl;

            $output = executeQuery('communication.getFriendGroup', $args);
            return $output->data;
        }

        /**
         * @brief 그룹 목록 가져오기
         **/
        function getFriendGroups() {
            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;

            $output = executeQuery('communication.getFriendGroups', $args);
            $group_list = $output->data;
            if(!$group_list) return;

            if(!is_array($group_list)) $group_list = array($group_list);
            return $group_list;
        }

        /**
         * @brief 특정 회원의 친구 목록에 포함되어 있는지를 확인
         **/
        function isFriend($target_srl) {
            $logged_info = Context::get('logged_info');

            $args->member_srl = $target_srl;
            $args->target_srl = $logged_info->member_srl;
            $output = executeQuery('communication.isAddedFriend', $args);
            if($output->data->count) return true;
            return false;
        }
    }
?>
