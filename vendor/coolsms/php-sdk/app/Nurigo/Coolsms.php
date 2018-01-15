<?php
/* vi:set sw=4 ts=4 expandtab: */

/**
 * Copyright (C) 2008-2016 NURIGO \n
 * http://www.coolsms.co.kr
 */

/**
 * @mainpage PHP SDK
 * @section intro 소개
 *     - 소개 : Coolsms REST API
 *     - 버전 : 2.0
 *     - 설명 : Coolsms REST API 를 이용 보다 빠르고 안전하게 문자메시지를 보낼 수 있는 PHP로 만들어진 SDK 입니다.
 * @section CreateInfo 작성 정보
 *     - 작성자 : Nurigo
 *     - 작성일 : 2016/05/13
 * @section Caution 주의할 사항
 *     - PHP SDK 2.0 은 PSR4에 근거하여 만들어 졌습니다. autoloading 과 namingspace의 개념을 알고 사용 하시는게 더 좋습니다.
 * @section common 기타 정보
 *     - 저작권 GPL v2
 */

namespace Nurigo;

use Nurigo\Exceptions\CoolsmsServerException;
use Nurigo\Exceptions\CoolsmsSystemException;
use Nurigo\Exceptions\CoolsmsSDKException;

require_once __DIR__ . "/../../bootstrap.php";

// check php extension "curl_init, json_decode"
if (!function_exists('curl_init')) {
    throw new CoolsmsSystemException('Coolsms needs the CURL PHP extension.', 301);
}
if (!function_exists('json_decode')) {
    throw new CoolsmsSystemException('Coolsms needs the JSON PHP extension.', 301);
}

/**
 * @class Coolsms
 * @brief Coolsms Rest API core class, using the Rest API
 */
class Coolsms
{
    const HOST = "https://solapi.com";
    const SDK_VERSION = "3";

    private $api_name = "GroupMessage";
    private $api_version = "3";
    private $api_key;
    private $date;
    private $salt;
    private $api_secret;
    private $resource;
    private $is_post;
    private $result;
    private $basecamp;
    private $user_agent;
    private $content;
    private $signature;

    /**
     * @brief Construct
     */
    public function __construct($api_key, $api_secret, $basecamp = false)
    {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        if (isset($_SERVER['HTTP_USER_AGENT'])) $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
        if ($basecamp) $this->basecamp = true;
    }

    /**
     * @brief Process curl
     */
    public function curlProcess()
    {
        $ch = curl_init();
        if (!$ch) throw new CoolsmsSystemException(curl_error($ch), 399);
        $url = sprintf("%s/%s/%s/%s", self::HOST, $this->api_name, $this->api_version, $this->resource);
        // Set curl info
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // check SSL certificate
        // HACK : 특정 일부 환경에서 CURLOPT_SSLVERSION 을 사용하게 되면 실제 요청을 못하는 경우가 있어서 주석처리
        //curl_setopt($ch, CURLOPT_SSLVERSION, 3); // SSL protocol version (need for https connect, 3 -> SSLv3)
        curl_setopt($ch, CURLOPT_HEADER, 0); // include the header in the output (1 = true, 0 = false) 
        curl_setopt($ch, CURLOPT_POST, $this->is_post); // POST GET method
        $header = array(
            "Content-Type: application/json",
            "Authorization: HMAC-MD5 ApiKey=$this->api_key, Date=$this->date, Salt=$this->salt, Signature=$this->signature"
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->content);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // TimeOut value
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // curl_exec() result output (1 = true, 0 = false)
        $output = curl_exec($ch);
        $this->result = json_decode($output);
        // unless http status code is 200. throw exception.
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200) throw new CoolsmsServerException($this->result, $http_code);

        // check curl errors
        if (curl_errno($ch)) throw new CoolsmsSystemException(curl_error($ch), 399);

        curl_close($ch);
    }

    /**
     * @brief set http body content
     * @return void
     */
    private function setContent($options)
    {
        $smsArgs = new \stdClass;
        if($options->encoding_json_data) {
            if (isset($options->json_option)) {
                $this->setApiConfig($options->json_option, '3');
            }

            $this->content = $options->encoding_json_data;
            return;
        }

        if ($options->json_option) {
            $json_option = $options->json_option;
        } else {
            $json_option = 'groupOptions';
        }
        $smsArgs->{$json_option} = new \stdClass;

        foreach ($options as $key => $val) {
            $smsArgs->{$json_option}->{$key} = $val;
        }
        if ($options->json_option !== 'groupOptions') {
            $smsArgs->{$json_option} = array($smsArgs->$json_option);
        }


        $this->content = json_encode($smsArgs);
    }

    /**
     * @biref Make a signature with hash_hamac then return the signature
     */
    private function getSignature($date, $salt)
    {
        return hash_hmac('md5', $date . $salt, $this->api_secret);
    }

    /**
     * @brief Set authenticate information
     */
    protected function addInfos($options = null)
    {
        if (!isset($options)) $options = new \stdClass();
        if (!isset($options->appId)) $options->appId = null;
        if (!isset($options->devLanguage)) $options->devLanguage = sprintf("PHP REST API %s", $this->api_version);
        if (!isset($options->osPlatform)) $options->osPlatform = $this->getOS();
        if (!isset($options->sdkVersion)) $options->sdkVersion = sprintf("PHP SDK %s", phpversion());
        if (!isset($options->appVersion)) $options->appVersion = null;
        $options->siteUser = '__private__';
        $options->mode = 'real';
        $options->forceSms = 'false';
        $options->onlyAta = 'false';
        $options->country = '82';
        $options->subject = '';

        // set salt & timestamp
        $options->salt = uniqid();
        $options->date = date('Y-m-d H:i:s');
        $this->salt = $options->salt;
        $this->date = $options->date;
        // If basecamp is true '$coolsms_user' use
        isset($this->basecamp) ? $options->coolsms_user = $this->api_key : $options->api_key = $this->api_key;

        $options->signature = $this->getSignature($options->date, $options->salt);
        $this->signature = $options->signature;

        $this->setContent($options);
    }

    /**
     * @brief set api resource and http method type
     * @param string $resource [required] related information. http://www.coolsms.co.kr/REST_API
     * @param boolean $is_post [optional] GET = false, POST = true
     */
    protected function setResource($resource, $is_post = false)
    {
        $this->resource = $resource;
        $this->is_post = $is_post;
    }

    /**
     * @brief https request using rest api
     * @param string $resource [required]
     * @param object $options [optional]
     * @param boolean $is_post [optional] GET = false, POST = true
     * @return mixed
     */
    protected function request($resource, $options = null, $is_post = true)
    {
        if (!$resource) throw new CoolsmsSDKException('resource is required', 201);

        // set http method and rest api path

        $this->setResource($resource, $is_post);

        // set contents
        $this->addInfos($options);

        // https request
        $this->curlProcess();

        // return result
        return $this->getResult();
    }

    /**
     * @brief Return result
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @brief set api name and api version
     * @param string $api_name [required] 'sms', 'senderid', 'image'
     * @param integer $api_version [required]
     */
    public function setApiConfig($api_name, $api_version)
    {
        if (!isset($api_name) || !isset($api_version)) throw new CoolsmsSDKException('API name and version is requried', 201);
        $this->api_name = $api_name;
        $this->api_version = $api_version;
    }

    /**
     * @brief Return user's current OS
     */
    function getOS()
    {
        $user_agent = $this->user_agent;
        $os_platform = "Unknown OS Platform";
        $os_array = array(
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile'
        );

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
            }
        }
        return $os_platform;
    }

    /**
     * @brief Return user's current browser
     */
    function getBrowser()
    {
        $user_agent = $this->user_agent;
        $browser = "Unknown Browser";
        $browser_array = array(
            '/msie/i' => 'Internet Explorer',
            '/firefox/i' => 'Firefox',
            '/safari/i' => 'Safari',
            '/chrome/i' => 'Chrome',
            '/opera/i' => 'Opera',
            '/netscape/i' => 'Netscape',
            '/maxthon/i' => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i' => 'Handheld Browser'
        );
        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $browser = $value;
            }
        }
        return $browser;
    }
}
