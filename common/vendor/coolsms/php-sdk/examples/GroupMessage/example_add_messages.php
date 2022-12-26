<?php
/**
 * #example_add_messages
 *
 * This sample code demonstrate how to add messages into group through CoolSMS Rest API PHP
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

    // options(to, from, text) are mandatory. must be filled
    $options = new stdClass();
    $options->to = '01000000000'; // 수신번호
    $options->from = '01000000000'; // 발신번호
    $options->text = '안녕하세요. 10000건을 20초안에 발송하는 빠르고 저렴한 CoolSMS의 테스팅 문자입니다. '; // 문자내용
    $options->group_id = 'GID56CC00E21C4DC';	// group id

    // Optional parameters for your own needs
    // $options->type = 'SMS';                       // Message type ( SMS, LMS, MMS, ATA )
    // $options->image_id = 'IM289E9CISNWIC'	       // image_id. type must be set as 'MMS'
    // $options->refname = '';					             // Reference name 
    // $options->country = 82;		                   // Korea(82) Japan(81) America(1) China(86) Default is Korea
    // $options->datetime = '20140106153000';	       // Format must be(YYYYMMDDHHMISS) 2014 01 06 15 30 00 (2014 Jan 06th 3pm 30 00)
    // $options->subject = 'Hello World';		         // set msg title for LMS and MMS
    // $options->delay = 10;                  			 // '0~20' delay messages
    // $options->sender_key = '55540253a3e61072...'; // 알림톡 사용을 위해 필요합니다. 신청방법 : http://www.coolsms.co.kr/AboutAlimTalk
    // $options->template_code = 'C004';             // 알림톡 template code 입니다. 자세한 설명은 http://www.coolsms.co.kr/AboutAlimTalk을 참조해주세요.

    $result = $rest->addMessages($options);
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
