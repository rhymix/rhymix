<?php
/* vi:set sw=4 ts=4 expandtab: */

namespace Nurigo\Api;

use Nurigo\Coolsms;
use Nurigo\Exceptions\CoolsmsSDKException;

require_once __DIR__ . "/../../../bootstrap.php";

/**
 * @class Message
 * @brief management message, using Rest API
 */
class Message extends Coolsms
{
    /**
     * @brief send message ( HTTP Method POST )
     * @param object $options {
     *   @param string  to             [required]
     *   @param string  from           [required]
     *   @param string  text           [required]
     *   @param string  type           [optional]
     *   @param mixed   image          [optional]
     *   @param string  image_encoding [optional]
     *   @param string  refname        [optional]
     *   @param mixed   country        [optional]
     *   @param string  datetime       [optional]
     *   @param string  subject        [optional]
     *   @param string  charset        [optional]
     *   @param string  srk            [optional]
     *   @param string  mode           [optional]
     *   @param string  extension      [optional]
     *   @param integer delay          [optional]
     *   @param boolean force_sms      [optional]
     *   @param string  app_version    [optional] }
     * @return object(recipient_number, group_id, message_id, result_code, result_message)
     */
    public function send($options) 
    {
        // check require fields. ( 'to, from, 'text' )
        if (!isset($options->to) || !isset($options->from) || !isset($options->text)) throw new CoolsmsSDKException('"to, from, text" must be entered', 202);

        return $this->request('send', $options, true);
    }
    
    /**
     * @brief sent message list ( HTTP Method GET )
     * @param object $options {
     *   @param integer offset           [optional]
     *   @param integer limit            [optional]
     *   @param string  rcpt             [optional]
     *   @param string  start            [optional]
     *   @param string  end              [optional]
     *   @param string  status           [optional]
     *   @param string  status           [optional]
     *   @param string  resultcode       [optional]
     *   @param string  notin_resultcode [optional]
     *   @param string  message_id       [optional]
     *   @param string  group_id         [optional] }
     * @return object(total count, list_count, page, data['type', 'accepted_time', 'recipient_number', 'group_id', 'message_id', 'status', 'result_code', 'result_message', 'sent_time', 'text'])
     */
    public function sent($options = null)
    {
        return $this->request('sent', $options);
    }

    /**
     * @brief cancel reserve message. mid or gid either one must be entered. ( HTTP Method POST )
     * @param string $mid [optional]
     * @param string $gid [optional]
     * @return None
     */
    public function cancel($mid = null, $gid = null) 
    {
        // mid or gid is empty. throw exception
        if (!$mid && !$gid) throw new CoolsmsSDKException('mid or gid either one must be entered', 202);

        $options = new \stdClass();
        if ($mid) $options->mid = $mid;
        if ($gid) $options->gid = $gid;
        return $this->request('cancel', $options, true);
    }

    /**
     * @brief get remaining balance ( HTTP Method GET )
     * @param None
     * @return object(cash, point)
     */
    public function getBalance() 
    {
        return $this->request('balance');
    }

    /**
     * @brief get status ( HTTP Method GET )
     * @param object $options {
     *   @param integer count   [optional]
     *   @param string  unit    [optional]
     *   @param string  date    [optional]
     *   @param integer channel [optional] }
     * @return object(registdate, sms_average, sms_sk_average, sms_kt_average, sms_lg_average, mms_average, mms_sk_average, mms_kt_average, mms_lg_average)
     */
    public function getStatus($options = null) 
    {
        return $this->request('status', $options);
    }
}
