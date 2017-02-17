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
	protected static $_domain = false;
	protected static $_started = false;
	protected static $_autologin_key = false;
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
			trigger_error('Session has already started', \E_USER_WARNING);
			return false;
		}
		
		// Set session parameters.
		list($lifetime, $refresh_interval, $domain, $path) = self::_getParams();
		$ssl_only = (\RX_SSL && config('session.use_ssl')) ? true : false;
		ini_set('session.gc_maxlifetime', $lifetime + 28800);
		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.use_strict_mode', 1);
		session_set_cookie_params($lifetime, $path, null, $ssl_only, false);
		session_name($session_name = Config::get('session.name') ?: session_name());
		
		// Get session ID from POST parameter if using relaxed key checks.
		if ($relax_key_checks && isset($_POST[$session_name]))
		{
			session_id($_POST[$session_name]);
		}
		
		// Abort if using delayed session.
		if(Config::get('session.delay') && !$force && !isset($_COOKIE[$session_name]))
		{
			$_SESSION = array();
			return false;
		}
		
		// Start the PHP native session.
		if (!session_start())
		{
			trigger_error('Session cannot be started', \E_USER_WARNING);
			return false;
		}
		
		// Mark the session as started.
		self::$_started = true;
		
		// Fetch session keys.
		list($key1, $key2, self::$_autologin_key) = self::_getKeys();
		$must_create = $must_refresh = $must_resend_keys = false;
		if (config('session.use_keys') === false)
		{
			$relax_key_checks = true;
		}
		
		// Check whether the visitor uses Android webview.
		if (!isset($_SESSION['is_webview']))
		{
			$_SESSION['is_webview'] = self::_isBuggyUserAgent();
		}
		
		// Validate the HTTP key.
		if (isset($_SESSION['RHYMIX']) && $_SESSION['RHYMIX'])
		{
			if (!isset($_SESSION['RHYMIX']['keys'][$domain]) && config('use_sso'))
			{
				$must_refresh = true;
			}
			elseif ($_SESSION['RHYMIX']['keys'][$domain]['key1'] === $key1 && $key1 !== null)
			{
				// OK
			}
			elseif ($_SESSION['RHYMIX']['keys'][$domain]['key1_prev'] === $key1 && $key1 !== null)
			{
				$must_resend_keys = true;
			}
			elseif (!$relax_key_checks && !$_SESSION['is_webview'])
			{
				// Hacked session! Destroy everything.
				trigger_error('Session is invalid (missing key 1)', \E_USER_WARNING);
				$_SESSION = array();
				$must_create = true;
				self::destroyAutologinKeys();
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
			elseif (!$relax_key_checks && !$_SESSION['is_webview'])
			{
				// Hacked session! Destroy everything.
				trigger_error('Session is invalid (missing key 2)', \E_USER_WARNING);
				$_SESSION = array();
				$must_create = true;
				self::destroyAutologinKeys();
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
		
		// If a member is logged in, check if the current session is valid for the member_srl.
		if (isset($_SESSION['RHYMIX']['login']) && $_SESSION['RHYMIX']['login'] && !self::isValid($_SESSION['RHYMIX']['login']))
		{
			trigger_error('Session failed validation checks for member_srl=' . intval($_SESSION['RHYMIX']['login']), \E_USER_WARNING);
			$_SESSION['RHYMIX']['login'] = $_SESSION['member_srl'] = false;
			$must_create = true;
		}
		
		// If this is not a GET request, do not refresh now.
		if ($_SERVER['REQUEST_METHOD'] !== 'GET')
		{
			$must_refresh = false;
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
			$_SESSION['is_new_session'] = false;
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
		if (!Config::get('session.delay'))
		{
			return false;
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
	 * Check if this session needs to be shared with another site with SSO.
	 * 
	 * This method uses more or less the same logic as XE's SSO mechanism.
	 * It may need to be changed to a more secure mechanism later.
	 * 
	 * @return bool
	 */
	public static function checkSSO()
	{
		// Abort if SSO is disabled, the visitor is a robot, or this is not a typical GET request.
		if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !config('use_sso') || UA::isRobot() || in_array(\Context::get('act'), array('rss', 'atom')))
		{
			return false;
		}
		
		// Abort of the default URL is not set.
		$default_url = \Context::getDefaultUrl();
		if (!$default_url)
		{
			return false;
		}
		
		// Get the current site information.
		$current_url = URL::getCurrentURL();
		$current_host = parse_url($current_url, \PHP_URL_HOST);
		$default_host = parse_url($default_url, \PHP_URL_HOST);
		
		// Step 1: if the current site is not the default site, send SSO validation request to the default site.
		if($default_host !== $current_host && !\Context::get('sso_response') && $_COOKIE['sso'] !== md5($current_host))
		{
			// Set sso cookie to prevent multiple simultaneous SSO validation requests.
			setcookie('sso', md5($current_host), 0, '/');
			
			// Redirect to the default site.
			$sso_request = Security::encrypt($current_url);
			header('Location:' . URL::modifyURL($default_url, array('sso_request' => $sso_request)));
			return true;
		}
		
		// Step 2: receive and process SSO validation request at the default site.
		if($default_host === $current_host && \Context::get('sso_request'))
		{
			// Get the URL of the origin site
			$sso_request = Security::decrypt(\Context::get('sso_request'));
			if (!$sso_request || !preg_match('!^https?://!', $sso_request))
			{
				\Context::displayErrorPage('SSO Error', 'Invalid SSO Request', 400);
				return true;
			}
			
			// Redirect back to the origin site.
			$sso_response = Security::encrypt(session_id());
			header('Location: ' . URL::modifyURL($sso_request, array('sso_response' => $sso_response)));
			return true;
		}
		
		// Step 3: back at the origin site, set session ID to be the same as the default site.
		if($default_host !== $current_host && \Context::get('sso_response'))
		{
			// Check SSO response
			$sso_response = Security::decrypt(\Context::get('sso_response'));
			if ($sso_response === false)
			{
				\Context::displayErrorPage('SSO Error', 'Invalid SSO Response', 400);
				return true;
			}
			
			// Check that the response was given by the default site (to prevent session fixation CSRF).
			if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $default_url) !== 0)
			{
				\Context::displayErrorPage('SSO Error', 'Invalid SSO Response', 400);
				return true;
			}
			
			// Set session ID.
			self::close();
			session_id($sso_response);
			self::start();
			
			// Finally, redirect to the originally requested URL.
			header('Location: ' . URL::getCurrentURL(array('sso_response' => null)));
			return true;
		}
		
		// If none of the conditions above apply, proceed normally.
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
		// Create the data structure for a new Rhymix session.
		$_SESSION['RHYMIX'] = array();
		$_SESSION['RHYMIX']['login'] = false;
		$_SESSION['RHYMIX']['last_login'] = false;
		$_SESSION['RHYMIX']['autologin_key'] = false;
		$_SESSION['RHYMIX']['ipaddress'] = $_SESSION['ipaddress'] = \RX_CLIENT_IP;
		$_SESSION['RHYMIX']['useragent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$_SESSION['RHYMIX']['language'] = \Context::getLangType();
		$_SESSION['RHYMIX']['timezone'] = DateTime::getTimezoneForCurrentUser();
		$_SESSION['RHYMIX']['secret'] = Security::getRandom(32, 'alnum');
		$_SESSION['RHYMIX']['tokens'] = array();
		$_SESSION['is_webview'] = self::_isBuggyUserAgent();
		$_SESSION['is_new_session'] = true;
		$_SESSION['is_logged'] = false;
		$_SESSION['is_admin'] = '';
		
		// Ensure backward compatibility with XE session.
		$member_srl = isset($_SESSION['member_srl']) ? ($_SESSION['member_srl'] ?: false) : false;
		if ($member_srl && self::isValid($member_srl))
		{
			self::login($member_srl, false);
		}
		else
		{
			$_SESSION['member_srl'] = false;
		}
		
		// Try autologin.
		if (!$member_srl && self::$_autologin_key)
		{
			$member_srl = getController('member')->doAutologin(self::$_autologin_key);
			if ($member_srl && self::isValid($member_srl))
			{
				self::login($member_srl, false);
				$_SESSION['RHYMIX']['autologin_key'] = substr(self::$_autologin_key, 0, 24);
			}
			else
			{
				self::destroyAutologinKeys();
			}
		}
		
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
		
		// Set the domain initialization timestamp.
		if (!isset($_SESSION['RHYMIX']['keys'][$domain]['started']))
		{
			$_SESSION['RHYMIX']['keys'][$domain]['started'] = time();
		}
		
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
		// Restore member_srl from XE-compatible variable if it has changed.
		if ($_SESSION['RHYMIX'] && $_SESSION['RHYMIX']['login'] !== intval($_SESSION['member_srl']))
		{
			$_SESSION['RHYMIX']['login'] = intval($_SESSION['member_srl']);
			$_SESSION['RHYMIX']['last_login'] = time();
			$_SESSION['is_logged'] = (bool)$member_srl;
		}
		
		// Close the session and write it to disk.
		self::$_started = false;
		self::$_member_info = false;
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
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path) = self::_getParams();
		
		// Delete all cookies.
		self::_setKeys();
		self::destroyAutologinKeys();
		setcookie(session_name(), 'deleted', time() - 86400, $path, null, false, false);
		setcookie('xe_logged', 'deleted', time() - 86400, $path, null, false, false);
		setcookie('xeak', 'deleted', time() - 86400, $path, null, false, false);
		setcookie('sso', 'deleted', time() - 86400, $path, null, false, false);
		unset($_COOKIE[session_name()]);
		unset($_COOKIE['rx_autologin']);
		unset($_COOKIE['rx_sesskey1']);
		unset($_COOKIE['rx_sesskey2']);
		unset($_COOKIE['xe_logged']);
		unset($_COOKIE['xeak']);
		unset($_COOKIE['sso']);
		
		// Clear session data.
		$_SESSION = array();
		
		// Close and delete the session.
		@session_write_close();
		$result = @session_destroy();
		
		// Clear local state.
		self::$_started = false;
		self::$_autologin_key = false;
		self::$_member_info = false;
		$_SESSION = array();
		
		return $result;
	}
	
	/**
	 * Log in.
	 *
	 * This method accepts either an integer or a member object.
	 * It returns true on success and false on failure.
	 * 
	 * @param int $member_srl
	 * @param bool $refresh (optional)
	 * @return bool
	 */
	public static function login($member_srl, $refresh = true)
	{
		// Check the validity of member_srl.
		if (is_object($member_srl) && isset($member_srl->member_srl))
		{
			$member_srl = $member_srl->member_srl;
		}
		if ($member_srl < 1)
		{
			return false;
		}
		
		// Set member_srl to session.
		$_SESSION['RHYMIX']['login'] = $_SESSION['member_srl'] = $member_srl;
		$_SESSION['RHYMIX']['last_login'] = time();
		$_SESSION['is_logged'] = (bool)$member_srl;
		self::$_member_info = false;
		
		// Refresh the session keys.
		if ($refresh)
		{
			return self::refresh();
		}
		else
		{
			return true;
		}
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
		$_SESSION['RHYMIX']['last_login'] = false;
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
	 * Check if the current session is valid for a given member_srl.
	 * 
	 * The session can be invalidated by password changes and other user action.
	 * 
	 * @param int $member_srl (optional)
	 * @return bool
	 */
	public static function isValid($member_srl = 0)
	{
		// If no member_srl is given, the session is always valid.
		$member_srl = intval($member_srl) ?: (isset($_SESSION['RHYMIX']['login']) ? $_SESSION['RHYMIX']['login'] : 0);
		if (!$member_srl)
		{
			return false;
		}
		
		// Check the invalidation timestamp against the current session.
		$validity_info = self::getValidityInfo($member_srl);
		if ($validity_info->invalid_before && self::isStarted() && $_SESSION['RHYMIX']['last_login'] && $_SESSION['RHYMIX']['last_login'] < $validity_info->invalid_before)
		{
			trigger_error('Session is invalid for member_srl=' . intval($_SESSION['RHYMIX']['login']) . ' (expired timestamp)', \E_USER_WARNING);
			return false;
		}
		
		// Check member information to see if denied or limited.
		$member_info = getModel('member')->getMemberInfoByMemberSrl($member_srl);
		if ($member_info->denied === 'Y')
		{
			trigger_error('Session is invalid for member_srl=' . intval($_SESSION['RHYMIX']['login']) . ' (denied)', \E_USER_WARNING);
			return false;
		}
		if ($member_info->limit_date && substr($member_info->limit_date, 0, 8) >= date('Ymd'))
		{
			trigger_error('Session is invalid for member_srl=' . intval($_SESSION['RHYMIX']['login']) . ' (limited)', \E_USER_WARNING);
			return false;
		}
		
		// Return true if all checks have passed.
		return true;
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
			self::$_member_info = new Helpers\SessionHelper($member_srl);
		}
		
		// Return the member info object.
		return self::$_member_info->member_srl ? self::$_member_info : false;
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
	 * Get session domain.
	 * 
	 * @return string
	 */
	public static function getDomain()
	{
		if (self::$_domain || (self::$_domain = ltrim(Config::get('session.domain'), '.')))
		{
			return self::$_domain;
		}
		else
		{
			self::$_domain = ini_get('session.cookie_domain') ?: preg_replace('/:\\d+$/', '', strtolower($_SERVER['HTTP_HOST']));
			return self::$_domain;
		}
	}
	
	/**
	 * Set session domain.
	 * 
	 * @return bool
	 */
	public static function setDomain($domain)
	{
		if (self::$_started)
		{
			return false;
		}
		else
		{
			self::$_domain = $domain;
			return true;
		}
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
	 * Get validity information.
	 * 
	 * @param int $member_srl
	 * @return object
	 */
	public static function getValidityInfo($member_srl)
	{
		$member_srl = intval($member_srl);
		$validity_info = Cache::get(sprintf('session:validity_info:%d', $member_srl), $invalid_before);
		if ($validity_info)
		{
			return $validity_info;
		}
		
		$filename = \RX_BASEDIR . sprintf('files/member_extra_info/session_validity/%s%d.php', getNumberingPath($member_srl), $member_srl);
		$validity_info = Storage::readPHPData($filename);
		if (!$validity_info)
		{
			$validity_info = (object)array(
				'invalid_before' => 0,
				'invalid_autologin_keys' => array(),
				'invalid_session_keys' => array(),
			);
		}
		
		Cache::set(sprintf('session:validity_info:%d', $member_srl), $validity_info);
		return $validity_info;
	}
	
	/**
	 * Set validity information.
	 * 
	 * @param int $member_srl
	 * @param object $validity_info
	 * @return bool
	 */
	public static function setValidityInfo($member_srl, $validity_info)
	{
		$member_srl = intval($member_srl);
		if (!$member_srl)
		{
			return false;
		}
		
		$filename = \RX_BASEDIR . sprintf('files/member_extra_info/session_validity/%s%d.php', getNumberingPath($member_srl), $member_srl);
		$result = Storage::writePHPData($filename, $validity_info);
		Cache::set(sprintf('session:validity_info:%d', $member_srl), $validity_info);
		return $result;
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
	 * Check if the user-agent is known to have a problem with security keys.
	 * 
	 * @return bool
	 */
	protected static function _isBuggyUserAgent()
	{
		$browser = UA::getBrowserInfo();
		if ($browser->browser === 'Android' || ($browser->os === 'Android' && $browser->browser === 'Chrome'))
		{
			return true;
		}
		else
		{
			return false;
		}
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
		$domain = self::getDomain();
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
		$key1 = $key2 = $key3 = null;
		
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
		
		// Fetch and validate the autologin key.
		if (isset($_COOKIE['rx_autologin']) && ctype_alnum($_COOKIE['rx_autologin']) && strlen($_COOKIE['rx_autologin']) === 48)
		{
			$key3 = $_COOKIE['rx_autologin'];
		}
		
		return array($key1, $key1 === null ? null : $key2, $key3);
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
		$ssl_only = (\RX_SSL && config('session.use_ssl')) ? true : false;
		
		// Set or destroy the HTTP-only key.
		if (isset($_SESSION['RHYMIX']['keys'][$domain]['key1']))
		{
			setcookie('rx_sesskey1', $_SESSION['RHYMIX']['keys'][$domain]['key1'], $lifetime, $path, null, $ssl_only, true);
			$_COOKIE['rx_sesskey1'] = $_SESSION['RHYMIX']['keys'][$domain]['key1'];
		}
		else
		{
			setcookie('rx_sesskey1', 'deleted', time() - 86400, $path, $domain, false, true);
			unset($_COOKIE['rx_sesskey1']);
		}
		
		// Set the HTTPS-only key.
		if (\RX_SSL && isset($_SESSION['RHYMIX']['keys'][$domain]['key2']))
		{
			setcookie('rx_sesskey2', $_SESSION['RHYMIX']['keys'][$domain]['key2'], $lifetime, $path, null, true, true);
			$_COOKIE['rx_sesskey2'] = $_SESSION['RHYMIX']['keys'][$domain]['key2'];
		}
		
		return true;
	}
	
	/**
	 * Set autologin key.
	 * 
	 * @param string $autologin_key
	 * @param string $security_key
	 * @return bool
	 */
	public static function setAutologinKeys($autologin_key, $security_key)
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path) = self::_getParams();
		$lifetime = time() + (86400 * 365);
		$ssl_only = (\RX_SSL && config('session.use_ssl')) ? true : false;
		
		// Set or destroy the HTTP-only key.
		if ($autologin_key && $security_key)
		{
			setcookie('rx_autologin', $autologin_key . $security_key, $lifetime, $path, null, $ssl_only, true);
			$_COOKIE['rx_autologin'] = $autologin_key . $security_key;
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Destroy autologin keys.
	 * 
	 * @return bool
	 */
	public static function destroyAutologinKeys()
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path) = self::_getParams();
		
		// Delete the autologin keys from the database.
		if (self::$_autologin_key)
		{
			executeQuery('member.deleteAutologin', (object)array('autologin_key' => substr(self::$_autologin_key, 0, 24)));
			self::$_autologin_key = false;
			$result = true;
		}
		else
		{
			$result = false;
		}
		
		// Delete the autologin cookie.
		setcookie('rx_autologin', 'deleted', time() - 86400, $path, null, false, false);
		unset($_COOKIE['rx_autologin']);
		return $result;
	}
	
	/**
	 * Destroy all other autologin keys (except the current session).
	 * 
	 * @param int $member_srl
	 * @return bool
	 */
	public static function destroyOtherSessions($member_srl)
	{
		// Check the validity of member_srl.
		$member_srl = intval($member_srl);
		if (!$member_srl)
		{
			return false;
		}
		
		// Invalidate all sessions that were logged in before the current timestamp.
		if (self::isStarted())
		{
			$validity_info = self::getValidityInfo($member_srl);
			$validity_info->invalid_before = time();
			self::setValidityInfo($member_srl, $validity_info);
			$_SESSION['RHYMIX']['last_login'] = $validity_info->invalid_before;
		}
		else
		{
			return false;
		}
		
		// Destroy all other autologin keys.
		if (self::$_autologin_key)
		{
			executeQuery('member.deleteAutologin', (object)array('member_srl' => $member_srl, 'not_autologin_key' => substr(self::$_autologin_key, 0, 24)));
		}
		else
		{
			executeQuery('member.deleteAutologin', (object)array('member_srl' => $member_srl));
		}
		
		return true;
	}
}
