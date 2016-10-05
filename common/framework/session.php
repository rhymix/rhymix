<?php

namespace Rhymix\Framework;

/**
 * The session class.
 */
class Session
{
	/**
	 * Properties for internal use only.
	 */
	protected static $_started = false;
	protected static $_member_info = false;
	
	/**
	 * Get a session variable.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key)
	{
		$data = $_SESSION;
		$key = explode('.', $key);
		foreach ($key as $step)
		{
			if ($key === '' || !isset($data[$step]))
			{
				return null;
			}
			$data = $data[$step];
		}
		return $data;
	}
	
	/**
	 * Set a session variable.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public static function set($key, $value)
	{
		$data = &$_SESSION;
		$key = explode('.', $key);
		foreach ($key as $step)
		{
			$data = &$data[$step];
		}
		$data = $value;
	}
	
	/**
	 * Start the session.
	 * 
	 * This method is called automatically at Rhymix startup.
	 * There is usually no need to call it manually.
	 * 
	 * @param bool $force (optional)
	 * @param bool $relax_key_checks (optional)
	 * @return bool
	 */
	public static function start($force = false, $relax_key_checks = false)
	{
		// Do not start the session if it is already started.
		if (self::$_started)
		{
			return false;
		}
		
		// Set session parameters.
        list($lifetime, $refresh_interval, $domain, $path) = self::_getParams();
        ini_set('session.gc_maxlifetime', $lifetime + 28800);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        session_set_cookie_params($lifetime, $path, $domain, false, false);
		session_name(Config::get('session.name') ?: session_name());
		
		// Get session ID from POST parameter if using relaxed key checks.
		if ($relax_key_checks && isset($_POST[session_name()]))
		{
			session_id($_POST[session_name()]);
		}
		
		// Abort if using delayed session.
		if(Config::get('session.delay') && !$force && !isset($_COOKIE[session_name()]))
		{
			$_SESSION = array();
			return false;
		}
		
		// Start the PHP native session.
		if (!session_start())
		{
			return false;
		}
		
		// Mark the session as started.
		self::$_started = true;
		
		// Fetch session keys.
		list($key1, $key2) = self::_getKeys();
		$must_create = $must_refresh = $must_resend_keys = false;
		
		// Validate the HTTP key.
		if (isset($_SESSION['RHYMIX']) && $_SESSION['RHYMIX'])
		{
			if ($_SESSION['RHYMIX']['keys'][$domain]['key1'] === $key1 && $key1 !== null)
			{
				// OK
			}
			elseif ($_SESSION['RHYMIX']['keys'][$domain]['key1_prev'] === $key1 && $key1 !== null)
			{
				return 2;
				$must_resend_keys = true;
			}
			elseif (!$relax_key_checks)
			{
				$_SESSION = array();
				$must_create = true;
			}
		}
		else
		{
			$must_create = true;
		}
		
		// Validate the SSL key.
		if (!$must_create && \RX_SSL)
		{
			if (!isset($_SESSION['RHYMIX']['keys'][$domain]['key2']))
			{
				$must_refresh = true;
			}
			elseif ($_SESSION['RHYMIX']['keys'][$domain]['key2'] === $key2 && $key2 !== null)
			{
				// OK
			}
			elseif ($_SESSION['RHYMIX']['keys'][$domain]['key2_prev'] === $key2 && $key2 !== null)
			{
				$must_resend_keys = true;
			}
			elseif (!$relax_key_checks)
			{
				$_SESSION = array();
				$must_create = true;
			}
		}
		
		// Check the refresh interval.
		if (!$must_create && $_SESSION['RHYMIX']['keys'][$domain]['key1_time'] < time() - $refresh_interval && !$relax_key_checks)
		{
			$must_refresh = true;
		}
		elseif (!$must_create && \RX_SSL && $_SESSION['RHYMIX']['keys'][$domain]['key2_time'] < time() - $refresh_interval && !$relax_key_checks)
		{
			$must_refresh = true;
		}
		
		// Create or refresh the session if needed.
		if ($must_create)
		{
			return self::create();
		}
		elseif ($must_refresh)
		{
			return self::refresh();
		}
		elseif ($must_resend_keys)
		{
			return self::_setKeys();
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Check if the session needs to be started.
	 * 
	 * This method is called automatically at Rhymix shutdown.
	 * It is only necessary if the session is delayed.
	 * 
	 * @param bool $force (optional)
	 * @return bool
	 */
	public static function checkStart($force = false)
	{
		// Return if the session is already started.
		if (self::$_started)
		{
			return true;
		}
		
		// Start the session if it contains data.
		if ($force || (count($_SESSION) && !headers_sent()))
		{
			// Copy session data to a temporary array.
			$temp = $_SESSION;
			unset($_SESSION);
			
			// Start the session.
			self::start(true);
			
			// Copy session data back to $_SESSION.
			foreach ($temp as $key => $val)
			{
				if ($key !== 'RHYMIX')
				{
					$_SESSION[$key] = $val;
				}
			}
			return true;
		}
		
		// Return false if nothing needed to be done.
		return false;
	}
	
	/**
	 * Create the data structure for a new Rhymix session.
	 * 
	 * This method is called automatically by start() when needed.
	 * 
	 * @return bool
	 */
	public static function create()
	{
		// Ensure backward compatibility with XE session.
		$member_srl = $_SESSION['member_srl'] ?: false;
		$_SESSION['is_logged'] = (bool)$member_srl;
		$_SESSION['is_admin'] = '';
		
		// Create the data structure for a new Rhymix session.
		$_SESSION['RHYMIX'] = array();
		$_SESSION['RHYMIX']['login'] = $_SESSION['member_srl'] = $member_srl;
		$_SESSION['RHYMIX']['ipaddress'] = $_SESSION['ipaddress'] = \RX_CLIENT_IP;
		$_SESSION['RHYMIX']['useragent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$_SESSION['RHYMIX']['language'] = \Context::getLangType();
		$_SESSION['RHYMIX']['timezone'] = DateTime::getTimezoneForCurrentUser();
		$_SESSION['RHYMIX']['secret'] = Security::getRandom(32, 'alnum');
		$_SESSION['RHYMIX']['tokens'] = array();
		
		// Pass control to refresh() to generate security keys.
		return self::refresh();
	}
	
	/**
	 * Refresh the session.
	 * 
	 * This method can be used to invalidate old session cookies.
	 * It is called automatically when someone logs in or out.
	 *
	 * @return bool
	 */
	public static function refresh()
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path) = self::_getParams();
		
		// Reset the trusted information.
		if (!isset($_SESSION['RHYMIX']['keys'][$domain]['trusted']))
		{
			$_SESSION['RHYMIX']['keys'][$domain]['trusted'] = 0;
		}
		
		// Create or refresh the HTTP-only key.
		if (isset($_SESSION['RHYMIX']['keys'][$domain]['key1']))
		{
			$_SESSION['RHYMIX']['keys'][$domain]['key1_prev'] = $_SESSION['RHYMIX']['keys'][$domain]['key1'];
		}
		$_SESSION['RHYMIX']['keys'][$domain]['key1'] = Security::getRandom(24, 'alnum');
		$_SESSION['RHYMIX']['keys'][$domain]['key1_time'] = time();
		
		// Create or refresh the HTTPS-only key.
		if (\RX_SSL)
		{
			if (isset($_SESSION['RHYMIX']['keys'][$domain]['key2']))
			{
				$_SESSION['RHYMIX']['keys'][$domain]['key2_prev'] = $_SESSION['RHYMIX']['keys'][$domain]['key2'];
			}
			$_SESSION['RHYMIX']['keys'][$domain]['key2'] = Security::getRandom(24, 'alnum');
			$_SESSION['RHYMIX']['keys'][$domain]['key2_time'] = time();
		}
		
		// Pass control to _setKeys() to send the keys to the client.
		return self::_setKeys();
	}
	
	/**
	 * Close the session and write its data.
	 * 
	 * This method is called automatically at the end of a request, but you can
	 * call it sooner if you don't plan to write any more data to the session.
	 * 
	 * @return bool
	 */
	public static function close()
	{
		self::$_started = false;
		session_write_close();
	}
	
	/**
	 * Destroy the session.
	 * 
	 * This method deletes all data associated with the current session.
	 * 
	 * @return bool
	 */
	public static function destroy()
	{
		unset($_SESSION['RHYMIX']);
		self::$_started = false;
		self::$_member_info = false;
		self::_setKeys();
		session_destroy();
		return true;
	}
	
	/**
	 * Log in.
	 *
	 * This method accepts either an integer or a member object.
	 * It returns true on success and false on failure.
	 * 
	 * @param int $member_srl
	 * @return bool
	 */
	public static function login($member_srl)
	{
		if (is_object($member_srl) && isset($member_srl->member_srl))
		{
			$member_srl = $member_srl->member_srl;
		}
		if ($member_srl < 1)
		{
			return false;
		}
		
		$_SESSION['RHYMIX']['login'] = $_SESSION['member_srl'] = $member_srl;
		$_SESSION['is_logged'] = (bool)$member_srl;
		self::$_member_info = false;
		return self::refresh();
	}
	
	/**
	 * Log out.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @return bool
	 */
	public static function logout()
	{
		$_SESSION['RHYMIX']['login'] = $_SESSION['member_srl'] = false;
		$_SESSION['is_logged'] = false;
		self::$_member_info = false;
		return self::destroy();
	}
	
	/**
	 * Check if the session has been started.
	 * 
	 * @return bool
	 */
	public static function isStarted()
	{
		return self::$_started;
	}
	
	/**
	 * Check if a member has logged in with this session.
	 * 
	 * This method returns true or false, not 'Y' or 'N'.
	 *
	 * @return bool
	 */
	public static function isMember()
	{
		return ($_SESSION['member_srl'] > 0 && $_SESSION['RHYMIX']['login'] > 0);
	}
	
	/**
	 * Check if an administrator is logged in with this session.
	 * 
	 * This method returns true or false, not 'Y' or 'N'.
	 *
	 * @return bool
	 */
	public static function isAdmin()
	{
		$member_info = self::getMemberInfo();
		return ($member_info && $member_info->is_admin === 'Y');
	}
	
	/**
	 * Check if the current session is trusted.
	 *
	 * This can be useful if you want to force a password check before granting
	 * access to certain pages. The duration of trust can be set by calling
	 * the Session::setTrusted() method.
	 * 
	 * @return bool
	 */
	public static function isTrusted()
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path) = self::_getParams();
		
		// Check the 'trusted' parameter.
		if ($_SESSION['RHYMIX']['keys'][$domain]['trusted'] > time())
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Get the member_srl of the currently logged in member.
	 * 
	 * This method returns an integer, or false if nobody is logged in.
	 *
	 * @return int|false
	 */
	public static function getMemberSrl()
	{
		return $_SESSION['member_srl'] ?: ($_SESSION['RHYMIX']['login'] ?: false);
	}
	
	/**
	 * Get information about the currently logged in member.
	 * 
	 * This method returns an object, or false if nobody is logged in.
	 *
	 * @return object|false
	 */
	public static function getMemberInfo()
	{
		// Return false if the current user is not logged in.
		$member_srl = self::getMemberSrl();
		if (!$member_srl)
		{
			return false;
		}
		
		// Create a member info object.
		if (!self::$_member_info || self::$_member_info->member_srl != $member_srl)
		{
			self::$_member_info = getModel('member')->getMemberInfoByMemberSrl($member_srl);
		}
		
		// Return the member info object.
		if (self::$_member_info == new \stdClass)
		{
			return false;
		}
		else
		{
			return self::$_member_info;
		}
	}
	
	/**
	 * Set the member info.
	 * 
	 * This method is for debugging and testing purposes only.
	 * 
	 * @param object $member_info
	 * @return void
	 */
	public static function setMemberInfo($member_info)
	{
		self::$_member_info = $member_info;
	}
	
	/**
	 * Get the current user's preferred language.
	 * 
	 * If the current user does not have a preferred language, this method
	 * will return the default language.
	 *
	 * @return string
	 */
	public static function getLanguage()
	{
		return isset($_SESSION['RHYMIX']['language']) ? $_SESSION['RHYMIX']['language'] : \Context::getLangType();
	}
	
	/**
	 * Set the current user's preferred language.
	 * 
	 * @param string $language
	 * @return bool
	 */
	public static function setLanguage($language)
	{
		$_SESSION['RHYMIX']['language'] = $language;
	}
	
	/**
	 * Get the current user's preferred time zone.
	 * 
	 * If the current user does not have a preferred time zone, this method
	 * will return the default time zone for display.
	 *
	 * @return string
	 */
	public static function getTimezone()
	{
		return DateTime::getTimezoneForCurrentUser();
	}
	
	/**
	 * Set the current user's preferred time zone.
	 * 
	 * @param string $timezone
	 * @return bool
	 */
	public static function setTimezone($timezone)
	{
		$_SESSION['RHYMIX']['timezone'] = $timezone;
	}
	
	/**
	 * Mark the current session as trusted for a given duration.
	 * 
	 * See isTrusted() for description.
	 * 
	 * @param int $duration (optional, default is 300 seconds)
	 * @return bool
	 */
	public static function setTrusted($duration = 300)
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path) = self::_getParams();
		
		// Update the 'trusted' parameter if the current user is logged in.
		if (isset($_SESSION['RHYMIX']['keys'][$domain]) && $_SESSION['RHYMIX']['login'])
		{
			$_SESSION['RHYMIX']['keys'][$domain]['trusted'] = time() + $duration;
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Create a token that can only be verified in the same session.
	 * 
	 * This can be used to create CSRF tokens, etc.
	 * If you specify a key, the same key must be used to verify the token.
	 * 
	 * @param string $key (optional)
	 * @return string
	 */
	public static function createToken($key = null)
	{
		$token = Security::getRandom(16, 'alnum');
		$_SESSION['RHYMIX']['tokens'][$token] = strval($key);
		return $token;
	}
	
	/**
	 * Verify a token.
	 * 
	 * This method returns true if the token is valid, and false otherwise.
	 * 
	 * @param string $token
	 * @param string $key (optional)
	 * @return bool
	 */
	public static function verifyToken($token, $key = null)
	{
		if (isset($_SESSION['RHYMIX']['tokens'][$token]) && $_SESSION['RHYMIX']['tokens'][$token] === strval($key))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Invalidate a token so that it cannot be verified.
	 * 
	 * @param string $token
	 * @param string $key (optional)
	 * @return bool
	 */
	public static function invalidateToken($token)
	{
		if (isset($_SESSION['RHYMIX']['tokens'][$token]))
		{
			unset($_SESSION['RHYMIX']['tokens'][$token]);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Encrypt data so that it can only be decrypted in the same session.
	 * 
	 * Arrays and objects can also be encrypted. (They will be serialized.)
	 * Resources and the boolean false value will not be preserved.
	 * 
	 * @param mixed $plaintext
	 * @return string
	 */
	public static function encrypt($plaintext)
	{
		$key = $_SESSION['RHYMIX']['secret'] . Config::get('crypto.encryption_key');
		return Security::encrypt($plaintext, $key);
	}
	
	/**
	 * Decrypt data that was encrypted in the same session.
	 * 
	 * This method returns the decrypted data, or false on failure.
	 * All users of this method must be designed to handle failures safely.
	 * 
	 * @param string $ciphertext
	 * @return mixed
	 */
	public static function decrypt($ciphertext)
	{
		$key = $_SESSION['RHYMIX']['secret'] . Config::get('crypto.encryption_key');
		return Security::decrypt($ciphertext, $key);
	}
	
	/**
	 * Get session parameters.
	 * 
	 * @return array
	 */
	protected static function _getParams()
	{
        $lifetime = Config::get('session.lifetime');
		$refresh = Config::get('session.refresh') ?: 300;
        $domain = Config::get('session.domain') ?: (ini_get('session.cookie_domain') ?: preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST']));
        $path = Config::get('session.path') ?: ini_get('session.cookie_path');
        return array($lifetime, $refresh, $domain, $path);
	}
	
	/**
	 * Get session keys.
	 * 
	 * @return array
	 */
	protected static function _getKeys()
	{
		// Initialize keys.
		$key1 = $key2 = null;
		
		// Fetch and validate the HTTP-only key.
		if (isset($_COOKIE['rx_sesskey1']) && ctype_alnum($_COOKIE['rx_sesskey1']) && strlen($_COOKIE['rx_sesskey1']) === 24)
		{
			$key1 = $_COOKIE['rx_sesskey1'];
		}
		
		// Fetch and validate the HTTPS-only key.
		if (isset($_COOKIE['rx_sesskey2']) && ctype_alnum($_COOKIE['rx_sesskey2']) && strlen($_COOKIE['rx_sesskey2']) === 24)
		{
			$key2 = $_COOKIE['rx_sesskey2'];
		}
		
		return array($key1, $key1 === null ? null : $key2);
	}
	
	/**
	 * Set session keys.
	 * 
	 * @return bool
	 */
	protected static function _setKeys()
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path) = self::_getParams();
		$lifetime = $lifetime ? ($lifetime + time()) : 0;
		
		// Set or destroy the HTTP-only key.
		if (isset($_SESSION['RHYMIX']['keys'][$domain]['key1']))
		{
			setcookie('rx_sesskey1', $_SESSION['RHYMIX']['keys'][$domain]['key1'], $lifetime, $path, $domain, false, true);
			$_COOKIE['rx_sesskey1'] = $_SESSION['RHYMIX']['keys'][$domain]['key1'];
		}
		else
		{
			setcookie('rx_sesskey1', 'deleted', time() - 86400, $path, $domain, false, true);
			unset($_COOKIE['rx_sesskey1']);
		}
		
		// Set or delete the HTTPS-only key.
		if (\RX_SSL && isset($_SESSION['RHYMIX']['keys'][$domain]['key2']))
		{
			setcookie('rx_sesskey2', $_SESSION['RHYMIX']['keys'][$domain]['key2'], $lifetime, $path, $domain, true, true);
			$_COOKIE['rx_sesskey2'] = $_SESSION['RHYMIX']['keys'][$domain]['key2'];
		}
		
		return true;
	}
}
