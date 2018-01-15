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
    // $options->type = 'SMS';                       // Message type ( SMS, LMS, MMS, ATA, CTA )
    // $options->image_id = 'IM289E9CISNWIC'	       // image_id. type must be set as 'MMS'
    // $options->country = 82;		                   // Korea(82) Japan(81) America(1) China(86) Default is Korea
    // $options->subject = 'Hello World';		         // set msg title for LMS and MMS
    // $options->kakaoOptions = new stdClass(); // 알림톡 혹은 친구톡을 전송할때 한번 초기화 필요.
    // $options->kakaoOptions->senderKey = '55540253a3e61072...'; // 발급받은 snederKey
    // $options->kakaoOptions->templateCode = 'C001'; // 알림톡 발송시 해당 템플릿검사를 위한 템플릿 코드
    // $options->kakaoOptions->buttonName = '바로가기'; // 알림톡과 친구톡에서 바로가기 링크버튼의 이름
    // $options->kakaoOptions->buttonUrl = 'https://www.coolsms.co.kr/'; // 알림톡 바로가기 링크 버튼클릭시 이동할 링크주소

    /**
     * 문자전송을 여러명에게 하는 기능.
     * 여러명에게 문자를 전송하기위해 오브젝트 생성하여 문자 전송을 해야함.
     * 필요한 갯수만큼의 오브젝트를 만들어서 $options->extension array을 넘겨주면 됩니다.
     * 자동으로 PHP에서 처리가능할 경우 foreach 문을 통해서 정보를 가져와 각자의 데이터에 따로 사용할 수 있도록 하는것이 편합니다.
     */
    /* e.g) 소스코드
    $args = new stdClass();
    $args->to = '01000000000'; // 수신번호
    $args->text = '안녕하세요. 여러명 문자 전송기능입니다.'; // 문자내용
    $sendArrays = array(
        $args, // $args2, $args3 도 동일하게 오브젝트 만들어 생성.
    );
    $options->extension = array();
    foreach ($sendArrays as $value) {
        $sendObject = new stdClass();
        $sendObject->to = $value->to;
        $sendObject->text = $value->text;
        $options->extension[] = $sendObject;
    }
    */

    $result = $rest->addMessages($options);
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
