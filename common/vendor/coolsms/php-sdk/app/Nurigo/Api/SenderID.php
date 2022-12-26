<?php
/* vi:set sw=4 ts=4 expandtab: */

namespace Nurigo\Api;

use Nurigo\Coolsms;
use Nurigo\Exceptions\CoolsmsSDKException;

require_once __DIR__ . "/../../../bootstrap.php";

/**
 * @class SenderID 
 * @brief management sender id, using Rest API
 */
class SenderID extends Coolsms
{
    /**
     * @brief change api name and api version
     * @param string  $api_key    [required]
     * @param string  $api_secret [required] 
     * @param boolean $basecamp   [optional]
     * @return object(group_id)
     */
    function __construct($api_key, $api_secret, $basecamp = false)
    {
        // set api_key and api_secret
        parent::__construct($api_key, $api_secret, $basecamp);

        // set API and version
        $this->setApiConfig("senderid", "1.1");
    }

    /**
     * @brief sender id registration request ( HTTP Method POST )
     * @param string $phone     [required]
     * @param string $site_user [optional]
     * @return object(handle_key, ars_number)
     */
    public function register($phone, $site_user = null)
    {
        if (!$phone) throw new CoolsmsSDKException('phone number is required', 202);

        $options = new \stdClass();
        $options->phone = $phone;
        $options->site_user = $site_user;
        return $this->request('register', $options, true);
    }

    /**
     * @brief verify sender id ( HTTP Method POST )
     * @param string $handle_key [required]
     * @return none 
     */
    public function verify($handle_key)
    {
        if (!$handle_key) throw new CoolsmsSDKException('handle_key is required', 202);

        $options = new \stdClass();
        $options->handle_key = $handle_key;
        return $this->request('verify', $options, true);
    }

    /**
     * @brief delete sender id ( HTTP Method POST )
     * @param string $handle_key [required]
     * @return none
     */
    public function delete($handle_key)
    {
        if (!$handle_key) throw new CoolsmsSDKException('handle_key is required', 202);

        $options = new \stdClass();
        $options->handle_key = $handle_key;
        return $this->request('delete', $options, true);
    }

    /**
     * @brief get sender id list ( HTTP Method GET )
     * @param string $site_user [optional]
     * @return object(site_user, idno, phone_number, flag_default, updatetime, regdate)
     */
    public function getSenderidList($site_user = null)
    {
        $options = new \stdClass();
        $options->site_user = $site_user;
        return $this->request('list', $options);
    }

    /**
     * @brief set default sender id ( HTTP Method POST )
     * @param string $handle_key [required]
     * @param string $site_user  [optional]
     * @return none 
     */
    public function setDefault($handle_key, $site_user = null)
    {
        if (!$handle_key) throw new CoolsmsSDKException('handle_key is required', 202);

        $options = new \stdClass();
        $options->handle_key = $handle_key;
        $options->site_user = $site_user;
        return $this->request('set_default', $options, true);
    }

    /**
     * @brief get default sender id ( HTTP Method GET )
     * @param string $site_user [optional]
     * @return object(handle_key, phone_number)
     */
    public function getDefault($site_user = null)
    {
        $options = new \stdClass();
        $options->site_user = $site_user;
        return $this->request('get_default', $options);
    }
}
