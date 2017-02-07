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
        return $this->request('new_group', $options);
    }

    /**
     * @brief get group list ( HTTP Method GET )
     * @param None
     * @return array['groupid', 'groupid'...]
     */
    public function getGroupList() 
    {
        return $this->request('group_list');
    }

    /**
     * @brief delete groups ( HTTP Method POST )
     * @param string $group_ids [required]
     * @return object(count)
     */
    public function deleteGroups($group_ids) 
    {
        if (!$group_ids) throw new CoolsmsSDKException('group_ids is required', 202);

        $options = new \stdClass();
        $options->group_ids = $group_ids;
        return $this->request('delete_groups', $options, true);
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
        return $this->request(sprintf('groups/%s', $group_id), $options);
    }
    
    /**
     * @brief add messages to group ( HTTP Method POST )
     * @param object $options {
     *   @param string  group_id [required]
     *   @param string  to       [required]
     *   @param string  from     [required]
     *   @param string  text     [required]
     *   @param string  image_id [optional]
     *   @param string  refname  [optional]
     *   @param string  country  [optional]
     *   @param string  datetime [optional]
     *   @param string  subject  [optional]
     *   @param integer delay    [optional] }
     * @return object(success_count, error_count, error_list['index':'code', 'index', 'code'])
     */
    public function addMessages($options) 
    {
        if (!isset($options->group_id) || !isset($options->to) || !isset($options->text) || !isset($options->from)) {
            throw new CoolsmsSDKException('group_id, to, text, from is required', 202);
        }

        return $this->request(sprintf('groups/%s/add_messages', $options->group_id), $options, true);
    }

    /**
     * @brief add json type messages to group ( HTTP Method POST )
     * @param object $options {
     *   @param string  group_id [required]
     *   @param string  messages [required] [{
     *     @param string  to       [required]
     *     @param string  from     [required]
     *     @param string  text     [required]
     *     @param string  image_id [optional]
     *     @param string  refname  [optional]
     *     @param string  country  [optional]
     *     @param string  datetime [optional]
     *     @param string  subject  [optional]
     *     @param integer delay    [optional] }] }
     * @return array[object(success_count, error_count, error_list['index':'code', 'index', 'code']), ...]
     */
    public function addMessagesJSON($options) 
    {
        if (!isset($options->group_id) || !isset($options->messages)) throw new CoolsmsSDKException('group_id and messages is required', 202);
        foreach ($options->messages as $val) {
            if (!isset($val->to) || !isset($val->text) || !isset($val->from)) {
                throw new CoolsmsSDKException('to, text, from is required', 202);
            }
        }

        $options->messages = json_encode($options->messages);
        return $this->request(sprintf('groups/%s/add_messages.json', $options->group_id), $options, true);
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
        $options->group_id = $group_id;
        $options->offset = $offset;
        $options->limit = $limit;
        return $this->request(sprintf('groups/%s/message_list', $options->group_id), $options);
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
        $options->group_id = $group_id;
        $options->message_ids = $message_ids;
        return $this->request(sprintf('groups/%s/delete_messages', $options->group_id), $options, true);
    }

    /**
     * @brief send group message ( HTTP Method POST )
     * @param string $group_id [required]
     * @return object(group_id)
     */
    public function sendGroupMessage($group_id) 
    {
        if (!$group_id) throw new CoolsmsSDKException('group_id is required', 202);

        $options = new \stdClass();
        $options->group_id = $group_id;
        return $this->request(sprintf('groups/%s/send', $group_id), $options, true);
    }
}
