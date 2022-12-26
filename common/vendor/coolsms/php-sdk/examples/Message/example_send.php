<?php
/**
 * #example_send
 *
 * This sample code demonstrate how to send sms through CoolSMS Rest API PHP
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

    // 4 options(to, from, type, text) are mandatory. must be filled
    $options = new stdClass();
    $options->to = '01000000000'; // 수신번호
    $options->from = '01000000000'; // 발신번호
    $options->type = 'SMS'; // Message type ( SMS, LMS, MMS, ATA )
    $options->text = '안녕하세요. 10000건을 20초안에 발송하는 빠르고 저렴한 CoolSMS의 테스팅 문자입니다. '; // 문자내용

    // Optional parameters for your own needs
    // $options->image = '../Image/images/test.jpg'; // image for MMS. type must be set as 'MMS'
    // $options->image_encoding = 'binary';          // image encoding binary(default), base64 
    // $options->mode = 'test';                      // 'test' 모드. 실제로 발송되지 않으며 전송내역에 60 오류코드로 뜹니다. 차감된 캐쉬는 다음날 새벽에 충전 됩니다.
    // $options->delay = 10;                         // 0~20사이의 값으로 전송지연 시간을 줄 수 있습니다.
    // $options->force_sms = true;                   // 푸시 및 알림톡 이용시에도 강제로 SMS로 발송되도록 할 수 있습니다.
    // $options->refname = '';                       // Reference name 
    // $options->country = 'KR';                     // Korea(KR) Japan(JP) America(USA) China(CN) Default is Korea
    // $options->datetime = '20140106153000';        // Format must be(YYYYMMDDHHMISS) 2014 01 06 15 30 00 (2014 Jan 06th 3pm 30 00)
    // $options->mid = 'mymsgid01';                  // set message id. Server creates automatically if empty
    // $options->gid = 'mymsg_group_id01';           // set group id. Server creates automatically if empty
    // $options->subject = 'Hello World';            // set msg title for LMS and MMS
    // $options->charset = 'euckr';                  // For Korean language, set euckr or utf-8
    // $options->sender_key = '55540253a3e61072...'; // 알림톡 사용을 위해 필요합니다. 신청방법 : http://www.coolsms.co.kr/AboutAlimTalk
    // $options->template_code = 'C004';             // 알림톡 template code 입니다. 자세한 설명은 http://www.coolsms.co.kr/AboutAlimTalk을 참조해주세요.
    // $options->app_version = 'Purplebook 4.1'      // 어플리케이션 버전

    $result = $rest->send($options);            
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
