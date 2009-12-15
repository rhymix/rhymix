<?php


require_once 'Auth/OpenID.php';
require_once 'Auth/OpenID/Interface.php';
require_once 'Auth/OpenID/HMACSHA1.php';

class Auth_OpenID_SessionStore extends Auth_OpenID_OpenIDStore {


    function Auth_OpenID_SessionStore()
    {
        if(!$_SESSION["sessionStore"]) $_SESSION["sessionStore"] = array();

        $this->max_nonce_age = 6 * 60 * 60;
    }

    function destory()
    {
    }

    function createAuthKey()
    {
        $auth_key = Auth_OpenID_CryptUtil::randomString($this->AUTH_KEY_LEN);
        $_SESSION["sessionStore"]["authkey"] = $auth_key;
        return $auth_key;
    }

    function _readAuthKey()
    {
        return $_SESSION["sessionStore"]["authkey"];
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
        return $auth_key;
    }

    function storeNonce($nonce)
    {
        $_SESSION["sessionStore"]["nonce"][$nonce] = time(); 
    }

    function useNonce($nonce)
    {
        $timestamp = $_SESSION["sessionStore"]["nonce"][$nonce];
        if($timestamp)
        {
            $nonce_age = time() - $timestamp;

            if ($nonce_age > $this->max_nonce_age) {
                $present = 0;
            } else {
                $present = 1;
            }

            $this->_remove_nonce($nonce);
        }
        else
        {
            $present = 0;
        }

        return $present;
    }

    function _remove_nonce($nonce)
    {
        if($_SESSION["sessionStore"]["nonce"][$nonce])
        {
            unset($_SESSION["sessionStore"]["nonce"][$nonce]);
        }
    }

    function storeAssociation($server_url, $association)
    {
        $_SESSION["sessionStore"]["association"][$server_url][$association->handle] = $association->serialize();
    }

    function getAssociation($server_url, $handle = null)
    {
        $assoc = null;
        if ($handle != null) {
            $assoc = $_SESSION["sessionStore"]["association"][$server_url][$handle];
        }
        else if($_SESSION["sessionStore"]["association"][$server_url]){
            foreach($_SESSION["sessionStore"]["association"][$server_url] as $handle => $asso)
            {
                $assoc = $asso;
                break;
            }
        }
        if($assoc)
        {
            $assoc = Auth_OpenID_Association::deserialize('Auth_OpenID_Association', $assoc);
        }
        return $assoc;
    }

    function removeAssociation($server_url, $handle)
    {
        if($handle == null) return false;
        if($_SESSION["sessionStore"]["association"][$server_url][$handle])
        {
            unset($_SESSION["sessionStore"]["association"][$server_url][$handle]);
            return true;
        }
        return false;
    }

    function reset()
    {
        unset($_SESSION["sessionStore"]);
        $_SESSION["sessionStore"] = array();
    }
}

?>
