<?php
/**
 * #example_delete_messages
 *
 * This sample code demonstrate how to delete messages through CoolSMS Rest API PHP
 * for more info, visit
 * www.coolsms.co.kr
 */

use Nurigo\Api\GroupMessage;
use Nurigo\Exceptions\CoolsmsException;

require_once __DIR__ . "/../../bootstrap.php";

// api_key and api_secret can be obtained from www.coolsms.co.kr/credentials
$api_key = '#ENTER_YOUR_OWN#';
$api_secret = '#ENTER_YOUR_OWN#';

try {
    // initiate rest api sdk object
    $rest = new GroupMessage($api_key, $api_secret);

    // group_id, message_ids are mandatory. must be filled
    $group_id = 'GID56CC00E21C4DC'; // group id
    $message_ids = '2838DFJFE02EI10TM'; // message ids. ex) '2838DFJFE02EI10TM','RGGBB11545'

    $result = $rest->deleteMessages($group_id, $message_ids);
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
