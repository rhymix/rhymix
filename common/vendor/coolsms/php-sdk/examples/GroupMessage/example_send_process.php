<?php
/**
 * #example_send_process
 *
 * This sample code demonstrate how to send group message through CoolSMS Rest API PHP
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
	$options = new stdClass();

    // initiate rest api sdk object
	$rest = new GroupMessage($api_key, $api_secret);

    // create group
	$result = $rest->createGroup($options);
	$group_id = $result->group_id;
	print_r($result);

	// add messages
	$options->to = '01000000000';
    $options->from = '01000000000';
    $options->text = '안녕하세요. 10000건을 20초안에 발송하는 빠르고 저렴한 CoolSMS의 테스팅 문자입니다. ';
	$options->group_id = $group_id;	// group id
	$result = $rest->addMessages($options);
	print_r($result);

	// send messages
	$result = $rest->sendGroupMessage($group_id);
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
