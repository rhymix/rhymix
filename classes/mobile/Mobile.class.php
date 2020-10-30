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
		if (!self::isMobileEnabled() || Context::get('full_browse') || ($_COOKIE['FullBrowse'] ?? 0))
		{
			return self::$_ismobile = false;
		}
		
		// Try to detect from URL arguments and cookies, and finally fall back to user-agent detection.
		$m = Context::get('m');
		$cookie = isset($_COOKIE['rx_uatype']) ? $_COOKIE['rx_uatype'] : null;
		$uahash = base64_encode_urlsafe(md5($_SERVER['HTTP_USER_AGENT'], true));
		if (strncmp($cookie, $uahash . ':', strlen($uahash) + 1) !== 0)
		{
			$cookie = null;
		}
		elseif ($m === null)
		{
			$m = substr($cookie, -1);
		}
		
		if ($m === '1')
		{
			self::$_ismobile = TRUE;
		}
		elseif ($m === '0')
		{
			self::$_ismobile = FALSE;
		}
		else
		{
			self::$_ismobile = Rhymix\Framework\UA::isMobile() && (config('mobile.tablets') || !Rhymix\Framework\UA::isTablet());
		}
		
		// Set cookie to prevent recalculation.
		$uatype = $uahash . ':' . (self::$_ismobile ? '1' : '0');
		if ($cookie !== $uatype)
		{
			setcookie('rx_uatype', $uatype, 0, \RX_BASEURL, null, !!config('session.use_ssl_cookies'));
			$_COOKIE['rx_uatype'] = $uatype;
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
