<?php
/**
 * #example_delete_images
 *
 * This sample code demonstrate how to delete images through CoolSMS Rest API PHP
 * for more info, visit
 * www.coolsms.co.kr
 */

use Nurigo\Api\Image;
use Nurigo\Exceptions\CoolsmsException;

require_once __DIR__ . "/../../bootstrap.php";

// api_key and api_secret can be obtained from www.coolsms.co.kr/credentials
$api_key = '#ENTER_YOUR_OWN#';
$api_secret = '#ENTER_YOUR_OWN#';

try {
    // initiate rest api sdk object
    $rest = new Image($api_key, $api_secret);

    // image_ids are mandatory. must be filled
    $image_ids = ''; // image ids. ex)'IM34BWIDJ12','IMG2559GBB'

    $result = $rest->deleteImages($image_ids);
    print_r($result);
} catch(CoolsmsException $e) {
    echo $e->getMessage(); // get error message
    echo $e->getCode(); // get error code
}
