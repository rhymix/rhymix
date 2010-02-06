<?php

require_once 'Auth/OpenID.php';
require_once 'Auth/OpenID/Interface.php';
require_once 'Auth/OpenID/HMACSHA1.php';

class Auth_OpenID_XEStore extends Auth_OpenID_OpenIDStore {

    function Auth_OpenID_XEStore()
    {
        $this->max_nonce_age = 6 * 60 * 60;
    }

    function destory()
    {
    }

    function createAuthKey()
    {
        $auth_key = Auth_OpenID_CryptUtil::randomString($this->AUTH_KEY_LEN);
        $oModuleModel = &getModel('module');
        $memberConfig = $oModuleModel->getModuleConfig('member');
        $memberConfig->openid_authkey = $auth_key;

        $oModuleController = &getController('module');
        $oModuleController->inesrtModuleConfig("module", $memberConfig);
        return $auth_key;
    }

    function _readAuthKey()
    {
        $oModuleModel = &getModel('module');
        $memberConfig = $oModuleModel->getModuleConfig('member');
        return $memberConfig->openid_authkey;
    }

    function getAuthKey()
    {
        $auth_key = $this->_readAuthKey();
        if ($auth_key === null) {
            $auth_key = $this->createAuthKey();

            if (strlen($auth_key) != $this->AUTH_KEY_LEN) {
                $fmt = 'Got an invalid auth key from %s. Expected '.
                    '%d-byte string. Got: %s';
                $msg = sprintf($fmt, $this->auth_key_name, $this->AUTH_KEY_LEN,
                               $auth_key);
                trigger_error($msg, E_USER_WARNING);
                return null;
            }
        }
    }

    function storeNonce($nonce)
    {
        $args->nonce = $nonce;
        $args->expires = time();
        $output = executeQuery("member.insertOpenIdNonce", $args);
    }

    function useNonce($nonce)
    {
        $args->nonce = $nonce;
        $output = executeQuery("member.getOpenIdNonce", $args);
        if($output->data)
        {
            $timestamp = $output->data->expires;
            $nonce_age = time() - $timestamp;

            if ($nonce_age > $this->max_nonce_age) {
                $present = 0;
            } else {
                $present = 1;
            }
            $output = executeQuery("member.deleteOpenIdNonce", $args);
        }
        else
        {
            $present = 0;
        }

        return $present;
    }

    function storeAssociation($server_url, $association)
    {
        $args->server_url = $server_url;
        $args->handle = $association->handle;
        $args->secret = bin2hex($association->secret);
        $args->issued = $association->issued;
        $args->lifetime = $association->lifetime;
        $args->assoc_type = $association->assoc_type;
        executeQuery("member.insertOpenIdAssociation", $args);
    }

    function _check_expire(&$assoc)
    {
        $assoc_o = new Auth_OpenID_Association($assoc->handle, $assoc->secret, $assoc->issued, $assoc->lifetime, $assoc->assoc_type);
        if ($assoc_o->getExpiresIn() == 0) {
            $this->removeAssociation($server_url, $assoc_o->handle);
            return null;
        }
        return $assoc_o;
    }

    function hex2bin($h)
    {
        if (!is_string($h)) return null;
        $r='';
        for ($a=0; $a<strlen($h); $a+=2) { $r.=chr(hexdec($h{$a}.$h{($a+1)})); }
        return $r;
    }

    function _get_assoc($server_url, $handle, $getOrig = false)
    {
        $args->server_url = $server_url;
        $args->handle = $handle;
        $output = executeQueryArray("member.getOpenIdAssociation", $args);
        if(!$output->data) return null;

        if(count($output->data) == 1) {
            $assoc = array_shift($output->data);
            $assoc->secret = $this->hex2bin($assoc->secret);
            if($getOrig) return $assoc;
            return $this->_check_expire($assoc);
        }
        
        $res = null;
        foreach($output->data as $assoc)
        {
            if($res == null)
            {
                $res = $assoc;
                continue;
            }

            $assoc->secret = $this->hex2bin($assoc->secret);
            $assoc_o = $this->_check_expire($assoc);
            if(!$assoc_o) continue;

            if($res->issued < $assoc->issued)
            {
                $res = $assoc_o;
            }
        }
        return $res;
    }

    function getAssociation($server_url, $handle = null)
    {
        $assoc = $this->_get_assoc($server_url, $handle);
        if(!$assoc) return null;
        $assoc_o = new Auth_OpenID_Association($assoc->handle, $assoc->secret, $assoc->issued, $assoc->lifetime, $assoc->assoc_type);
        return $assoc_o;
    }


    function removeAssociation($server_url, $handle)
    {
        if ($this->_get_assoc($server_url, $handle, true) == null) {
            return false;
        }

        $args->server_url = $server_url;
        $args->handle = $handle;
        $output = executeQuery("member.deleteOpenIdAssociation", $args);
        return true;
    }

    function reset()
    {
        $output = executeQuery("member.deleteOpenIdNonce");
        $output = executeQuery("member.deleteOpenIdAssociation");
    }
}
?>
