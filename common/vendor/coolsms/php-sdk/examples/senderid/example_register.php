<?php
/**
 * #example_register
 *
 * This sample code demonstrate how to request sender number register through CoolSMS Rest API PHP
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

    // phone are mandatory. must be filled
    $phone = '01000000000'; // sender number to register 

    // Optional parameters for your own needs
    // $site_user = 'admin'; // site user_id. '__private__' is default value

    $result = $rest->register($phone); // or $rest->register($phone, $site_user);
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
