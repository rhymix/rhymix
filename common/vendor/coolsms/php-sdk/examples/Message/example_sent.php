<?php
/**
 * #example_sent
 *
 * This sample code demonstrate how to check sms result through CoolSMS Rest API PHP
 * for more info, visit
 * www.coolsms.co.kr
 */

use Nurigo\Api\Message;
use Nurigo\Exceptions\CoolsmsException;

require_once __DIR__ . "/../../bootstrap.php";

// api_key and api_secret can be obtained from www.coolsms.co.kr/credentials
$api_key = '#ENTER_YOUR_OWN#';
$api_secret = '#ENTER_YOUR_OWN#';

try {
    // initiate rest api sdk object
    $rest = new Message($api_key, $api_secret);

    // set necessary options
    $options = new stdClass();
    $options->message_id = 'M52CB443257C61';			//message id 
    // $options->group_id = 'G52CB4432576C8';		//group id
    // $options->count = '40';					//result return counts. default is 20
    // $options->page = '1';					//page 
    // $options->rcpt = '01012345678';			//search sent result by recipient number
    // $options->start = '201401070915';		//set search start date  ex) 201401070915
    // $options->end = '201401071230';			//set search end date	 ex) 201401071230

    $result = $rest->sent($options);
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
