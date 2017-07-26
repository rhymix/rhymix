<?php
/* vi:set sw=4 ts=4 expandtab: */

namespace Nurigo\Api;

use Nurigo\Coolsms;
use Nurigo\Exceptions\CoolsmsSDKException;

require_once __DIR__ . "/../../../bootstrap.php";

/**
 * @class GroupMessage
 * @brief management group message, using Rest API
 */
class GroupMessage extends Coolsms
{
    /**
     * @brief create create group ( HTTP Method GET )
     * @param object $options {
     *   @param string  charset     [optional]
     *   @param string  srk         [optional]
     *   @param string  mode        [optional]
     *   @param string  delay       [optional]
     *   @param boolean force_sms   [optional]
     *   @param string  os_platform [optional]
     *   @param string  dev_lang    [optional]
     *   @param string  sdk_version [optional]
     *   @param string  app_version [optional] }
     * @return object(group_id)
     */
    public function createGroup($options) 
    {
        return $this->request('createGroup', $options, true);
    }

    /**
     * @brief get group list ( HTTP Method GET )
     * @param None
     * @return object(groupList)
     */
    public function getGroupList()
    {
        return $this->request('getGroupList');
    }

    /**
     * @brief delete groups ( HTTP Method POST )
     * @param array $group_ids [required]
     * @return object(count)
     */
    public function deleteGroups($group_ids)
    {
        if (!$group_ids) throw new CoolsmsSDKException('group_ids is required', 202);

        $args = new \stdClass();
        $args->groups = array();
        if (is_array($group_ids)) {
            foreach ($group_ids as $key => $group_id) {
                $args->groups[$key] = new \stdClass();
                $args->groups[$key]->groupId = $group_id;
            }
        } else {
            $args->groups[0] = new \stdClass();
            $args->groups[0]->groupId = $group_ids;
        }

        $encoding_json_data = json_encode($args);

        $options = new \stdClass();
        $options->encoding_json_data = $encoding_json_data;
        return $this->request('deleteGroups', $options, true);
    }

    /**
     * @brief get group info ( HTTP Method GET )
     * @param string $group_id [required]
     * @return object(group_id, message_count)
     */
    public function getGroupInfo($group_id) 
    {
        if (!$group_id) throw new CoolsmsSDKException('group_id is required', 202);

        $options = new \stdClass();
        $options->group_id = $group_id;
        return $this->request(sprintf('group/%s/getGroupInfo', $group_id), $options);
    }


    /**
     * @brief add messages to group ( HTTP Method POST )
     * @param $options
     * @return mixed
     * @throws CoolsmsSDKException
     */
    public function addMessages($options) 
    {
        if (!isset($options->group_id) || !isset($options->to) || !isset($options->text) || !isset($options->from)) {
            throw new CoolsmsSDKException('group_id, to, text, from is required', 202);
        }
        $args = new \stdClass();
        $args->messages = array();
        $args->messages[0] = new \stdClass();
        $sendNumber = explode(',', $options->to);
        $args->messages[0]->to = new \stdClass();
        $args->messages[0]->to->recipients = $sendNumber;
        $args->messages[0]->from = $options->from;
        $args->messages[0]->text = $options->text;
        if ($options->type) {
            $args->messages[0]->type = $options->type;
        } else {
            $args->messages[0]->type = 'SMS';
        }
        if ($options->country) {
            $args->messages[0]->country = $options->country;
        }
        if ($options->subject) {
            $args->messages[0]->subject = $options->subject;
        }
        if ($options->imageId) {
            $args->messages[0]->imageId = $options->imageId;
        }
        if ($options->kakaoOptions) {
            $args->messages[0]->kakaoOptions = new \stdClass();
            if($options->kakaoOptions->senderKey) $args->messages[0]->kakaoOptions->senderKey = $options->kakaoOptions->senderKey;
            if($options->kakaoOptions->templateCode) $args->messages[0]->kakaoOptions->templateCode = $options->kakaoOptions->templateCode;
            if($options->kakaoOptions->buttonName) $args->messages[0]->kakaoOptions->buttonName = $options->kakaoOptions->buttonName;
            if($options->kakaoOptions->buttonUrl) $args->messages[0]->kakaoOptions->buttonUrl = $options->kakaoOptions->buttonUrl;
        }

        $encoding_json_data = json_encode($args);
        $obj = new \stdClass();
        $obj->encoding_json_data = $encoding_json_data;
        return $this->request(sprintf('group/%s/addMessages', $options->group_id), $obj, true);
    }

    /**
     * @brief get message list ( HTTP Method GET )
     * @param string  $group_id [required]
     * @param integer $offset   [optional]
     * @param integer $limit    [optional]
     * @return object(total_count, offset, limit, list['message_id', 'message_id' ...])
     */
    public function getMessageList($group_id, $offset = 0, $limit = 20)
    {
        if (!$group_id) throw new CoolsmsSDKException('group_id is required', 202);

        $options = new \stdClass();
        $options->offset = $offset;
        $options->limit = $limit;

        $encoding_json_data = json_encode($options);
        $obj = new \stdClass();
        $obj->encoding_json_data = $encoding_json_data;
        return $this->request(sprintf('group/%s/getMessageList', $group_id), $obj, true);
    }

    /**
     * @brief delete message from group ( HTTP Method POST )
     * @param string $group_id    [required]
     * @param string $message_ids [required]
     * @return object(success_count)
     */
    public function deleteMessages($group_id, $message_ids) 
    {
        if (!$group_id || !$message_ids) throw new CoolsmsSDKException('group_id and message_ids are required', 202);

        $options = new \stdClass();
        $options->messages = array();
        $options->messages[0] = new \stdClass();
        $options->messages[0]->messageId = $message_ids;

        $encoding_json_data = json_encode($options);
        $obj = new \stdClass();
        $obj->encoding_json_data = $encoding_json_data;
        return $this->request(sprintf('groups/%s/deleteMessages', $group_id), $obj, true);
    }

    /**
     * @brief send group message ( HTTP Method POST )
     * @param string $group_id [required]
     * @return object(group_id)
     */
    public function sendGroupMessage($group_id) 
    {
        if (!$group_id) throw new CoolsmsSDKException('group_id is required', 202);

        $obj = new \stdClass();
        $obj->encoding_json_data = null;
        return $this->request(sprintf('group/%s/sendMessages', $group_id), $obj, true);
    }
}
