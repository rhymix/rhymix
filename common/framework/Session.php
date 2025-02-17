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
	public static function get(string $key)
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
	public static function set(string $key, $value): void
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
	 * @return bool
	 */
	public static function start(bool $force = false): bool
	{
		// Do not start the session if it is already started.
		if (self::$_started)
		{
			trigger_error('Session has already started', \E_USER_WARNING);
			return false;
		}

		// Set session parameters.
		list($lifetime, $refresh_interval, $domain, $path, $secure, $httponly, $samesite) = self::_getParams();
		$alt_domain = $domain ?: preg_replace('/:\\d+$/', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
		ini_set('session.gc_maxlifetime', $lifetime > 0 ? $lifetime : max(28800, intval(ini_get('session.gc_maxlifetime'))));
		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.use_strict_mode', 1);
		ini_set('session.cookie_samesite', $samesite ? 1 : 0);
		session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
		session_name($session_name = Config::get('session.name') ?: session_name());

		// Check if the session cookie already exists.
		$cookie_exists = isset($_COOKIE[$session_name]);

		// Abort if using delayed session.
		if(!$cookie_exists && !$force && Config::get('session.delay'))
		{
			$_SESSION = array();
			return false;
		}

		// Start the PHP native session.
		$session_start_time = microtime(true);
		if (!session_start())
		{
			trigger_error('Session cannot be started', \E_USER_WARNING);
			return false;
		}
		Debug::addSessionStartTime(microtime(true) - $session_start_time);

		// Mark the session as started.
		self::$_started = true;
		$must_create = $must_refresh = false;

		// Check if the session has been initialized for Rhymix.
		if (!isset($_SESSION['RHYMIX']))
		{
			$must_create = true;
		}

		// Check if the session needs to be refreshed.
		if (!$must_create && !isset($_SESSION['RHYMIX']['domains'][$alt_domain]['started']) || $_SESSION['RHYMIX']['domains'][$alt_domain]['started'] < time() - $refresh_interval)
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
		if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET')
		{
			$must_refresh = false;
		}

		// Resend the autologin key if the client has not recognized its change.
		if (isset($_SESSION['RHYMIX']['autologin_key']) && strlen($_SESSION['RHYMIX']['autologin_key']) === 48)
		{
			if ($_SESSION['RHYMIX']['autologin_key'] !== self::_getAutologinKey())
			{
				self::setAutologinKeys(substr($_SESSION['RHYMIX']['autologin_key'], 0, 24), substr($_SESSION['RHYMIX']['autologin_key'], 24, 24));
			}
			else
			{
				$_SESSION['RHYMIX']['autologin_key'] = false;
			}
		}

		// Create or refresh the session if needed.
		if ($must_create)
		{
			$result = self::create();
		}
		elseif ($must_refresh)
		{
			$result = self::refresh(true);
		}
		else
		{
			$_SESSION['is_new_session'] = false;
			$result = true;
		}

		// Check the login status cookie.
		self::checkLoginStatusCookie();
		return $result;
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
	public static function checkStart(bool $force = false): bool
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
		if ($force || (@count($_SESSION) && !headers_sent()))
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
	 * Check the login status cookie.
	 *
	 * This cookie encodes information about whether the user is logged in,
	 * and helps client software distinguish between different users.
	 *
	 * @return void
	 */
	public static function checkLoginStatusCookie(): void
	{
		// If the cookie value is different from the current login status, overwrite it.
		$value = self::getLoginStatus();
		if (!isset($_COOKIE['rx_login_status']) || $_COOKIE['rx_login_status'] !== $value)
		{
			list($lifetime, $refresh_interval, $domain, $path, $secure, $httponly, $samesite) = self::_getParams();
			Cookie::set('rx_login_status', $value, array(
				'expires' => 0,
				'path' => $path,
				'domain' => $domain,
				'secure' => $secure,
				'httponly' => $httponly,
				'samesite' => $samesite,
			));
		}
	}

	/**
	 * Check if this session needs to be shared with another site with SSO.
	 *
	 * This method uses more or less the same logic as XE's SSO mechanism.
	 * It may need to be changed to a more secure mechanism later.
	 *
	 * @param object $site_module_info
	 * @return void
	 */
	public static function checkSSO(object $site_module_info): void
	{
		// Abort if SSO is disabled, the visitor is a robot, or this is not a typical GET request.
		if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET' || !config('use_sso') || UA::isRobot() || in_array(\Context::get('act'), array('rss', 'atom')))
		{
			return;
		}

		// Get the current site information.
		$is_default_domain = ($site_module_info->domain_srl == 0);
		if (!$is_default_domain)
		{
			$current_domain = $site_module_info->domain;
			$current_url = URL::getCurrentUrl();
			$default_domain = \ModuleModel::getDefaultDomainInfo();
			$default_url = \Context::getDefaultUrl($default_domain);
		}

		// Step 1: if the current site is not the default site, send SSO validation request to the default site.
		if(!$is_default_domain && !\Context::get('sso_response') && $_COOKIE['sso'] !== md5($current_domain))
		{
			// Set sso cookie to prevent multiple simultaneous SSO validation requests.
			Cookie::set('sso', md5($current_domain), array(
				'expires' => 0,
				'path' => '/',
				'domain' => null,
				'secure' => !!config('session.use_ssl'),
				'httponly' => true,
				'samesite' => config('session.samesite'),
			));

			// Redirect to the default site.
			$sso_request = Security::encrypt($current_url);
			header('Location:' . URL::modifyURL($default_url, array('sso_request' => $sso_request)));
			exit;
		}

		// Step 2: receive and process SSO validation request at the default site.
		if($is_default_domain && \Context::get('sso_request'))
		{
			// Get the URL of the origin site
			$sso_request = Security::decrypt(\Context::get('sso_request'));
			if (!$sso_request || !preg_match('!^https?://!', $sso_request))
			{
				\Context::displayErrorPage('SSO Error', 'ERR_INVALID_SSO_REQUEST', 400);
				exit;
			}
			if (!URL::isInternalUrl($sso_request) || !URL::isInternalURL($_SERVER['HTTP_REFERER'] ?? ''))
			{
				\Context::displayErrorPage('SSO Error', 'ERR_INVALID_SSO_REQUEST', 400);
				exit;
			}

			// Encrypt the session ID.
			self::start(true);
			$sso_response = Security::encrypt(session_id());

			// Redirect back to the origin site.
			header('Location: ' . URL::modifyURL($sso_request, array('sso_response' => $sso_response)));
			self::close();
			exit;
		}

		// Step 3: back at the origin site, set session ID to be the same as the default site.
		if(!$is_default_domain && \Context::get('sso_response'))
		{
			// Check SSO response
			$sso_response = Security::decrypt(\Context::get('sso_response'));
			if ($sso_response === false)
			{
				\Context::displayErrorPage('SSO Error', 'ERR_INVALID_SSO_RESPONSE', 400);
				exit;
			}

			// Check that the response was given by the default site (to prevent session fixation CSRF).
			if(isset($_SERVER['HTTP_REFERER']) && !URL::isInternalURL($_SERVER['HTTP_REFERER']))
			{
				\Context::displayErrorPage('SSO Error', 'ERR_INVALID_SSO_RESPONSE', 400);
				exit;
			}

			// Set the session ID.
			session_id($sso_response);
			self::start(true, false);

			// Finally, redirect to the originally requested URL.
			header('Location: ' . URL::getCurrentURL(array('sso_response' => null)));
			self::close();
			exit;
		}
	}

	/**
	 * Create the data structure for a new Rhymix session.
	 *
	 * This method is called automatically by start() when needed.
	 *
	 * @return bool
	 */
	public static function create(): bool
	{
		// Create the data structure for a new Rhymix session.
		$_SESSION['RHYMIX'] = array();
		$_SESSION['RHYMIX']['login'] = false;
		$_SESSION['RHYMIX']['last_login'] = false;
		$_SESSION['RHYMIX']['autologin_key'] = false;
		$_SESSION['RHYMIX']['ipaddress'] = $_SESSION['ipaddress'] = \RX_CLIENT_IP;
		$_SESSION['RHYMIX']['useragent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$_SESSION['RHYMIX']['language'] = \Context::getLangType();
		// $_SESSION['RHYMIX']['timezone'] = DateTime::getTimezoneForCurrentUser();
		$_SESSION['RHYMIX']['secret'] = Security::getRandom(32, 'alnum');
		$_SESSION['RHYMIX']['domains'] = array();
		$_SESSION['RHYMIX']['tokens'] = array();
		$_SESSION['RHYMIX']['token'] = false;
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
		self::$_autologin_key = self::_getAutologinKey();
		if (!$member_srl && self::$_autologin_key)
		{
			$member_srl = \MemberController::getInstance()->doAutologin(self::$_autologin_key);
			if ($member_srl && self::isValid($member_srl))
			{
				self::login($member_srl, false);
			}
			else
			{
				self::destroyAutologinKeys();
			}
		}

		// Pass control to refresh() to generate domain information.
		return self::refresh();
	}

	/**
	 * Refresh the session.
	 *
	 * This helps increase the lifetime for session cookies and autologin cookies
	 * while the user is active on the site.
	 *
	 * @param bool $refresh_cookie
	 * @return bool
	 */
	public static function refresh(bool $refresh_cookie = false): bool
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path, $secure, $httponly, $samesite) = self::_getParams();
		$alt_domain = $domain ?: preg_replace('/:\\d+$/', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
		$lifetime = $lifetime ? ($lifetime + time()) : 0;
		$options = array(
			'expires' => $lifetime,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'httponly' => $httponly,
			'samesite' => $samesite,
		);

		// Update the domain initialization timestamp.
		$_SESSION['RHYMIX']['domains'][$alt_domain]['started'] = time();
		if (!isset($_SESSION['RHYMIX']['domains'][$alt_domain]['trusted']))
		{
			$_SESSION['RHYMIX']['domains'][$alt_domain]['trusted'] = 0;
		}

		// Refresh the main session cookie and the autologin key.
		if ($refresh_cookie)
		{
			self::destroyCookiesFromConflictingDomains(array(session_name()));
			Cookie::set(session_name(), session_id(), $options);
			if (self::$_autologin_key = self::_getAutologinKey())
			{
				self::setAutologinKeys(substr(self::$_autologin_key, 0, 24), substr(self::$_autologin_key, 24, 24));
			}
		}

		return true;
	}

	/**
	 * Close the session and write its data.
	 *
	 * This method is called automatically at the end of a request, but you can
	 * call it sooner if you don't plan to write any more data to the session.
	 *
	 * @return void
	 */
	public static function close(): void
	{
		// Restore member_srl from XE-compatible variable if it has changed.
		if (isset($_SESSION['RHYMIX']) && $_SESSION['RHYMIX'] && $_SESSION['RHYMIX']['login'] !== intval($_SESSION['member_srl']))
		{
			$_SESSION['RHYMIX']['login'] = intval($_SESSION['member_srl'] ?? 0);
			$_SESSION['RHYMIX']['last_login'] = time();
			$_SESSION['is_logged'] = (bool)($_SESSION['member_srl'] ?? 0);
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
	 * @return void
	 */
	public static function destroy(): void
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path, $secure, $httponly, $samesite) = self::_getParams();

		// Delete all cookies.
		self::destroyAutologinKeys();
		self::destroyCookiesFromConflictingDomains(array('xe_logged', 'rx_login_status', 'xeak', 'sso'));
		self::_unsetCookie(session_name(), $path, $domain);
		self::_unsetCookie('xe_logged', $path, $domain);
		self::_unsetCookie('rx_login_status', $path, $domain);
		self::_unsetCookie('xeak', $path, $domain);
		self::_unsetCookie('sso', $path, $domain);

		// Clear session data.
		$_SESSION = array();

		// Close and delete the session.
		@session_write_close();
		@session_destroy();

		// Clear local state.
		self::$_started = false;
		self::$_autologin_key = false;
		self::$_member_info = false;
		$_SESSION = array();
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
	public static function login(int $member_srl, bool $refresh = true): bool
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
		$_SESSION['RHYMIX']['login'] = $member_srl;
		$_SESSION['RHYMIX']['last_login'] = time();

		// Set other session variables for backward compatibility.
		$_SESSION['member_srl'] = $member_srl;
		$_SESSION['is_logged'] = $member_srl > 0 ? true : false;
		self::$_member_info = false;

		// Refresh the session keys.
		if ($refresh)
		{
			self::checkLoginStatusCookie();
			return self::refresh(true);
		}
		else
		{
			return true;
		}
	}

	/**
	 * Log out and destroy the session.
	 *
	 * @return void
	 */
	public static function logout(): void
	{
		$_SESSION['RHYMIX']['login'] = false;
		$_SESSION['RHYMIX']['last_login'] = false;
		$_SESSION['is_logged'] = false;
		$_SESSION['member_srl'] = false;
		self::$_member_info = false;
		self::destroy();
	}

	/**
	 * Check if the session has been started.
	 *
	 * @return bool
	 */
	public static function isStarted(): bool
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
	public static function isMember(): bool
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
	public static function isAdmin(): bool
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
	public static function isTrusted(): bool
	{
		// Get session parameters.
		$domain = self::getDomain() ?: preg_replace('/:\\d+$/', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));

		// Check the 'trusted' parameter.
		if ($_SESSION['RHYMIX']['domains'][$domain]['trusted'] > time())
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
	public static function isValid(int $member_srl = 0): bool
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
		$member_info = \MemberModel::getMemberInfo($member_srl);
		if (!empty($member_info->denied) && $member_info->denied === 'Y')
		{
			trigger_error('Session is invalid for member_srl=' . intval($_SESSION['RHYMIX']['login']) . ' (denied)', \E_USER_WARNING);
			return false;
		}
		if (!empty($member_info->limit_date) && substr($member_info->limit_date, 0, 8) >= date('Ymd'))
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
	 * This method returns an integer, or zero if nobody is logged in.
	 *
	 * @return int
	 */
	public static function getMemberSrl(): int
	{
		return intval(($_SESSION['member_srl'] ?? 0) ?: (($_SESSION['RHYMIX']['login'] ?? 0) ?: 0));
	}

	/**
	 * Get information about the currently logged in member.
	 *
	 * This method returns an object, or false if nobody is logged in.
	 *
	 * @param bool $refresh
	 * @return Helpers\SessionHelper
	 */
	public static function getMemberInfo(bool $refresh = false): Helpers\SessionHelper
	{
		// Return false if the current user is not logged in.
		$member_srl = self::getMemberSrl();
		if (!$member_srl)
		{
			return new Helpers\SessionHelper(0);
		}

		// Create a member info object.
		if (!self::$_member_info || self::$_member_info->member_srl != $member_srl || $refresh)
		{
			self::$_member_info = new Helpers\SessionHelper($member_srl);
		}

		// Return the member info object.
		return self::$_member_info;
	}

	/**
	 * Set the member info.
	 *
	 * This method is for debugging and testing purposes only.
	 *
	 * @param Helpers\SessionHelper $member_info
	 * @return void
	 */
	public static function setMemberInfo(Helpers\SessionHelper $member_info): void
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
	public static function getLanguage(): string
	{
		return isset($_SESSION['RHYMIX']['language']) ? $_SESSION['RHYMIX']['language'] : \Context::getLangType();
	}

	/**
	 * Set the current user's preferred language.
	 *
	 * @param string $language
	 * @return void
	 */
	public static function setLanguage(string $language): void
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
	public static function getTimezone(): string
	{
		return DateTime::getTimezoneForCurrentUser();
	}

	/**
	 * Set the current user's preferred time zone.
	 *
	 * @param string $timezone
	 * @return void
	 */
	public static function setTimezone(string $timezone): void
	{
		$_SESSION['RHYMIX']['timezone'] = $timezone;
	}

	/**
	 * Get session domain.
	 *
	 * @return string
	 */
	public static function getDomain(): string
	{
		if (self::$_domain || (self::$_domain = ltrim(Config::get('session.domain') ?? '', '.')))
		{
			return self::$_domain ?: '';
		}
		else
		{
			return self::$_domain = ltrim(ini_get('session.cookie_domain'), '.') ?: '';
		}
	}

	/**
	 * Set session domain.
	 *
	 * @param string $domain
	 * @return bool
	 */
	public static function setDomain(string $domain): bool
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
	public static function setTrusted(int $duration = 300): bool
	{
		// Get session parameters.
		$domain = self::getDomain() ?: preg_replace('/:\\d+$/', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));

		// Update the 'trusted' parameter if the current user is logged in.
		if (isset($_SESSION['RHYMIX']['domains'][$domain]) && $_SESSION['RHYMIX']['login'])
		{
			$_SESSION['RHYMIX']['domains'][$domain]['trusted'] = time() + $duration;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get a generic token that is not restricted to any particular key.
	 *
	 * @return string|false
	 */
	public static function getGenericToken()
	{
		if (!self::isStarted())
		{
			return false;
		}

		if (!$_SESSION['RHYMIX']['token'])
		{
			$_SESSION['RHYMIX']['token'] = self::createToken('');
		}

		return $_SESSION['RHYMIX']['token'];
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
	public static function createToken(string $key = ''): string
	{
		$token = Security::getRandom(16, 'alnum');
		$_SESSION['RHYMIX']['tokens'][$token] = $key;
		return $token;
	}

	/**
	 * Verify a token.
	 *
	 * This method returns true if the token is valid, and false otherwise.
	 *
	 * Strict checking can be disabled if the user is not logged in
	 * and no tokens have been issued in the current session.
	 *
	 * @param string $token
	 * @param string $key (optional)
	 * @param bool $strict (optional)
	 * @return bool
	 */
	public static function verifyToken(string $token, string $key = '', bool $strict = true): bool
	{
		if (isset($_SESSION['RHYMIX']['tokens'][$token]) && $_SESSION['RHYMIX']['tokens'][$token] === strval($key))
		{
			return true;
		}
		elseif (!$strict && empty($_SESSION['RHYMIX']['tokens']) && !self::getMemberSrl())
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
	 * @return bool
	 */
	public static function invalidateToken(string $token): bool
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
	 * Get a string that identifies login status.
	 *
	 * Members are identified by a hash that is unique to each member.
	 * Guests are identified as 'none'.
	 *
	 * @return string
	 */
	public static function getLoginStatus(): string
	{
		if (isset($_SESSION['RHYMIX']) && $_SESSION['RHYMIX']['login'])
		{
			$data = sprintf('%s:%s:%d:%s', $_SERVER['HTTP_HOST'] ?? '', RX_BASEDIR, $_SESSION['RHYMIX']['login'], config('crypto.session_key'));
			return base64_encode_urlsafe(substr(hash('sha256', $data, true), 0, 18));
		}
		else
		{
			return 'none';
		}
	}

	/**
	 * Get the last login time.
	 *
	 * If the user is not logged in, this method returns 0.
	 *
	 * @return int
	 */
	public static function getLastLoginTime(): int
	{
		return $_SESSION['RHYMIX']['last_login'] ?? 0;
	}

	/**
	 * Get validity information.
	 *
	 * @param int $member_srl
	 * @return object
	 */
	public static function getValidityInfo(int $member_srl): object
	{
		$validity_info = Cache::get(sprintf('session:validity_info:%d', $member_srl));
		if (is_object($validity_info))
		{
			return $validity_info;
		}

		$filename = \RX_BASEDIR . sprintf('files/member_extra_info/session_validity/%s%d.php', getNumberingPath($member_srl), $member_srl);
		$validity_info = Storage::readPHPData($filename);
		if (!$validity_info || !is_object($validity_info))
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
	public static function setValidityInfo(int $member_srl, object $validity_info): bool
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
	 * @param string $plaintext
	 * @return string
	 */
	public static function encrypt(string $plaintext): string
	{
		$key = ($_SESSION['RHYMIX']['secret'] ?? '') . Config::get('crypto.encryption_key');
		return Security::encrypt($plaintext, $key);
	}

	/**
	 * Decrypt data that was encrypted in the same session.
	 *
	 * This method returns the decrypted data, or false on failure.
	 * All users of this method must be designed to handle failures safely.
	 *
	 * @param string $ciphertext
	 * @return string|false
	 */
	public static function decrypt(string $ciphertext)
	{
		$key = ($_SESSION['RHYMIX']['secret'] ?? '') . Config::get('crypto.encryption_key');
		return Security::decrypt($ciphertext, $key);
	}

	/**
	 * Check if the user-agent is known to have a problem with security keys.
	 *
	 * @return bool
	 */
	protected static function _isBuggyUserAgent(): bool
	{
		$browser = UA::getBrowserInfo();
		if ($browser->browser === 'Android')
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
	protected static function _getParams(): array
	{
		$lifetime = Config::get('session.lifetime');
		$refresh = Config::get('session.refresh') ?: 300;
		$domain = self::getDomain();
		$path = Config::get('session.path') ?: ini_get('session.cookie_path');
		$secure = (\RX_SSL && config('session.use_ssl')) ? true : false;
		$httponly = Config::get('session.httponly') ?? true;
		$samesite = config('session.samesite');
		return array($lifetime, $refresh, $domain, $path, $secure, $httponly, $samesite);
	}

	/**
	 * Get the autologin key from the rx_autologin cookie.
	 *
	 * @return string|null
	 */
	protected static function _getAutologinKey()
	{
		// Fetch and validate the autologin key.
		if (isset($_COOKIE['rx_autologin']) && ctype_alnum($_COOKIE['rx_autologin']) && strlen($_COOKIE['rx_autologin']) === 48)
		{
			return $_COOKIE['rx_autologin'];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Unset cookie.
	 *
	 * @param string $name
	 * @param string $path (optional)
	 * @param string $domain (optional)
	 * @return bool
	 */
	protected static function _unsetCookie(string $name, string $path = '', string $domain = ''): bool
	{
		$result = setcookie($name, 'deleted', time() - (86400 * 366), $path, $domain, false, false);
		if ($result)
		{
			unset($_COOKIE[$name]);
		}
		return $result;
	}

	/**
	 * Set autologin key.
	 *
	 * @param string $autologin_key
	 * @param string $security_key
	 * @return bool
	 */
	public static function setAutologinKeys(string $autologin_key, string $security_key): bool
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path, $secure, $httponly, $samesite) = self::_getParams();
		$lifetime_days = config('session.autologin_lifetime') ?: 365;
		$lifetime = time() + (86400 * $lifetime_days);

		// Set the autologin keys.
		if ($autologin_key && $security_key)
		{
			$_SESSION['RHYMIX']['autologin_key'] = $autologin_key . $security_key;
			self::destroyCookiesFromConflictingDomains(array('rx_autologin'));
			Cookie::set('rx_autologin', $autologin_key . $security_key, array(
				'expires' => $lifetime,
				'path' => $path,
				'domain' => $domain,
				'secure' => $secure,
				'httponly' => $httponly,
				'samesite' => $samesite,
			));

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
	public static function destroyAutologinKeys(): bool
	{
		// Get session parameters.
		list($lifetime, $refresh_interval, $domain, $path, $secure, $httponly, $samesite) = self::_getParams();

		// Delete the autologin keys from the database.
		if (self::$_autologin_key)
		{
			$output = executeQuery('member.deleteAutologin', (object)array('autologin_key' => substr(self::$_autologin_key, 0, 24)));
			self::$_autologin_key = false;
			$result = $output->toBool();
		}
		else
		{
			$result = false;
		}

		// Delete the autologin cookie.
		self::destroyCookiesFromConflictingDomains(array('rx_autologin'));
		self::_unsetCookie('rx_autologin', $path, $domain);
		unset($_COOKIE['rx_autologin']);
		return $result;
	}

	/**
	 * Destroy all other autologin keys (except the current session).
	 *
	 * @param int $member_srl
	 * @return bool
	 */
	public static function destroyOtherSessions(int $member_srl): bool
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
			$output = executeQuery('member.deleteAutologin', (object)array('member_srl' => $member_srl, 'not_autologin_key' => substr(self::$_autologin_key, 0, 24)));
		}
		else
		{
			$output = executeQuery('member.deleteAutologin', (object)array('member_srl' => $member_srl));
		}

		return $output->toBool();
	}

	/**
	 * Destroy cookies from potentially conflicting domains.
	 *
	 * @param array $cookies
	 * @param bool $include_current_host (optional)
	 * @return bool
	 */
	public static function destroyCookiesFromConflictingDomains(array $cookies, bool $include_current_host = false): bool
	{
		$conflict_domains = config('session.conflict_domains') ?: array();
		if ($include_current_host)
		{
			$conflict_domains[] = '.' . preg_replace('/:\\d+$/', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
		}
		if (!count($conflict_domains))
		{
			return false;
		}

		list($lifetime, $refresh_interval, $domain, $path, $secure, $httponly, $samesite) = self::_getParams();
		foreach ($cookies as $cookie)
		{
			foreach ($conflict_domains as $conflict_domain)
			{
				self::_unsetCookie($cookie, $path, $conflict_domain);
			}
		}

		return true;
	}
}
