<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Mobile class
 *
 * @author NAVER (developers@xpressengine.com)
 */
class Mobile
{
	/**
	 * Whether mobile or not mobile mode
	 * @var bool
	 */
	protected static $_ismobile = null;
	
	/**
	 * Get instance of Mobile class
	 * 
	 * @return Mobile
	 */
	public function getInstance()
	{
		return new self();
	}
	
	/**
	 * Get current mobile mode
	 * 
	 * @return bool
	 */
	public static function isFromMobilePhone()
	{
		// Return cached result.
		if (self::$_ismobile !== null)
		{
			return self::$_ismobile;
		}
		
		// Not mobile if disabled explicitly.
		if (!self::isMobileEnabled() || Context::get('full_browse') || $_COOKIE["FullBrowse"])
		{
			return self::$_ismobile = false;
		}
		
		// Try to detect from URL arguments and cookies, and finally fall back to user-agent detection.
		$m = Context::get('m');
		$cookie = (isset($_COOKIE['mobile']) && $_SESSION['user_agent'] === md5($_SERVER['HTTP_USER_AGENT'])) ? $_COOKIE['mobile'] : null;
		if ($m === '1' || $cookie === 'true')
		{
			self::$_ismobile = TRUE;
		}
		elseif ($m === '0' || $cookie === 'false')
		{
			self::$_ismobile = FALSE;
		}
		else
		{
			self::$_ismobile = Rhymix\Framework\UA::isMobile() && (config('mobile.tablets') || !Rhymix\Framework\UA::isTablet());
		}
		
		// Set cookie to prevent recalculation.
		if (!$cookie)
		{
			$_SESSION['user_agent'] = md5($_SERVER['HTTP_USER_AGENT']);
			$_COOKIE['mobile'] = self::$_ismobile ? 'true' : 'false';
			setcookie('mobile', $_COOKIE['mobile'], 0, RX_BASEURL);
		}
		
		return self::$_ismobile;
	}
	
	/**
	 * Get current mobile mode
	 *
	 * @return bool
	 */
	public static function _isFromMobilePhone()
	{
		return self::isFromMobilePhone();
	}
	
	/**
	 * Detect mobile device by user agent
	 *
	 * @return bool Returns true on mobile device or false.
	 */
	public static function isMobileCheckByAgent()
	{
		return Rhymix\Framework\UA::isMobile();
	}

	/**
	 * Check if user-agent is a tablet PC as iPad or Andoid tablet.
	 *
	 * @return bool TRUE for tablet, and FALSE for else.
	 */
	public static function isMobilePadCheckByAgent()
	{
		return Rhymix\Framework\UA::isTablet();
	}
	
	/**
	 * Set mobile mode
	 *
	 * @param bool $ismobile
	 * @return void
	 */
	public static function setMobile($ismobile)
	{
		self::$_ismobile = (bool)$ismobile;
	}

	/**
	 * Check if mobile view is enabled
	 * 
	 * @raturn bool
	 */
	public static function isMobileEnabled()
	{
		$mobile_enabled = config('mobile.enabled');
		if ($mobile_enabled === null)
		{
			$mobile_enabled = config('use_mobile_view') ? true : false;
		}
		return $mobile_enabled;
	}
}
