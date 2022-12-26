<?php
/**
 * #example_delete
 *
 * This sample code demonstrate how to delete sender number through CoolSMS Rest API PHP
 * for more info, visit
 * www.coolsms.co.kr
 */

use Nurigo\Api\SenderID;
use Nurigo\Exceptions\CoolsmsException;

require_once __DIR__ . "/../../bootstrap.php";

// api_key and api_secret can be obtained from www.coolsms.co.kr/credentials
$api_key = '#ENTER_YOUR_OWN#';
$api_secret = '#ENTER_YOUR_OWN#';

try {
    // initiate rest api sdk object
    $rest = new SenderID($api_key, $api_secret);

    // handle_key are mandatory. must be filled
    $handle_key = 'C29CE02IOE9'; // sender number handle key. check for 'example_list'

    $result = $rest->delete($handle_key);
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
