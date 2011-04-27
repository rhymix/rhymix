<?php
    /**
     * @class  communicationModel
     * @author NHN (developers@xpressengine.com)
     * @brief communication module of the Model class
     **/

    class communicationModel extends communication {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief get the configuration
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
         * @brief get the message contents
         **/
        function getSelectedMessage($message_srl, $columnList = array()) {
            $logged_info = Context::get('logged_info');

            $args->message_srl = $message_srl;
            $output = executeQuery('communication.getMessage',$args, $columnList);
            $message = $output->data;
            if(!$message) return ;
            // get recipient's information if it is a sent message
            $oMemberModel = &getModel('member');
            if($message->sender_srl == $logged_info->member_srl && $message->message_type == 'S') $member_info = $oMemberModel->getMemberInfoByMemberSrl($message->receiver_srl);
            // get sendor's information if it is a received/archived message
            else $member_info = $oMemberModel->getMemberInfoByMemberSrl($message->sender_srl);

            if($member_info) {
                foreach($member_info as $key => $val) {
                  if($key != 'regdate') $message->{$key} = $val;
                }
            }
            // change the status if is a received and not yet read message
            if($message->message_type == 'R' && $message->readed != 'Y') {
                $oCommunicationController = &getController('communication');
                $oCommunicationController->setMessageReaded($message_srl);
            }


            return $message;
        }

        /**
         * @brief get a new message
         **/
        function getNewMessage($columnList = array()) {
            $logged_info = Context::get('logged_info');
            $args->receiver_srl = $logged_info->member_srl;
            $args->readed = 'N';

            $output = executeQuery('communication.getNewMessage', $args, $columnList);
            if(!count($output->data)) return;
            $message = array_pop($output->data);

            $oCommunicationController = &getController('communication');
            $oCommunicationController->setMessageReaded($message->message_srl);

            return $message;
        }

        /**
         * @brief get a message list
         * type = R: Received Message 
         * type = S: Sent Message
         * type = T: Archive
         **/
        function getMessages($message_type = "R", $columnList = array()) {
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
            // Other variables
            $args->sort_index = 'message.list_order';
            $args->page = Context::get('page');
            $args->list_count = 20;
            $args->page_count = 10;
            return executeQuery($query_id, $args, $columnList);
        }

        /**
         * @brief Get a list of friends
         **/
        function getFriends($friend_group_srl = 0, $columnList = array()) {
            $logged_info = Context::get('logged_info');

            $args->friend_group_srl = $friend_group_srl;
            $args->member_srl = $logged_info->member_srl;
            // Other variables
            $args->page = Context::get('page');
            $args->sort_index = 'friend.list_order';
            $args->list_count = 10;
            $args->page_count = 10;
            $output = executeQuery('communication.getFriends', $args, $columnList);
            return $output;
        }

        /**
         * @brief check if a friend is already added
         **/
        function isAddedFriend($member_srl) {
            $logged_info = Context::get('logged_info');

            $args->member_srl = $logged_info->member_srl;
            $args->target_srl = $member_srl;
            $output = executeQuery('communication.isAddedFriend', $args);
            return $output->data->count;
        }

        /**
         * @brief Get a group of friends
         **/
        function getFriendGroupInfo($friend_group_srl) {
            $logged_info = Context::get('logged_info');

            $args->member_srl = $logged_info->member_srl;
            $args->friend_group_srl = $friend_group_srl;

            $output = executeQuery('communication.getFriendGroup', $args);
            return $output->data;
        }

        /**
         * @brief Get a list of groups
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
         * @brief check whether to be added in the friend list
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
