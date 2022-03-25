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
    // $options->count = '1';					// 기본값 1이며 1개의 최신 레코드를 받을 수 있음. 10입력시 10분동안의 레코드 목록을 리턴
    // $options->unit = 'minute';				// minute(default), hour, day 중 하나 해당 단위의 평균
    // $options->date = '20161016230000';		// 데이터를 읽어오는 기준 시각 
    // $options->channel = '1';					// 1 : 1건 발송채널(default), 2 : 대량 발송 채널

    $result = $rest->getStatus($options);
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
