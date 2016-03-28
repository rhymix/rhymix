<?php

namespace Rhymix\Framework;

/**
 * The user-agent class.
 */
class UA
{
	/**
	 * Cache to prevent multiple lookups.
	 */
	protected static $_mobile_cache = array();
	protected static $_tablet_cache = array();
	protected static $_robot_cache = array();
	
	/**
	 * Check whether the current visitor is using a mobile device.
	 * 
	 * @param string $ua (optional)
	 * @return bool
	 */
	public static function isMobile($ua = null)
	{
		// Get the User-Agent header if the caller did not specify $ua.
		if ($ua === null)
		{
			$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
			$using_header = true;
		}
		else
		{
			$using_header = false;
		}
		
		// If the User-Agent header is missing, it's probably not a mobile browser.
		if (is_null($ua))
		{
			return false;
		}
		
		// Look for headers that are only used in mobile browsers.
		if ($using_header && (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])))
		{
			return true;
		}
		
		// Look up the cache.
		if (isset(self::$_mobile_cache[$ua]))
		{
			return self::$_mobile_cache[$ua];
		}
		
		// Look for the 'mobile' keyword and common mobile platform names.
		if (preg_match('/android|ip(hone|ad|od)|blackberry|nokia|palm|mobile/i', $ua))
		{
			return self::$_mobile_cache[$ua] = true;
		}
		
		// Look for common non-mobile OS names.
		if (preg_match('/windows|linux|os [x9]|bsd/i', $ua))
		{
			return self::$_mobile_cache[$ua] = false;
		}
		
		// Look for other platform, manufacturer, and device names that are known to be mobile.
		if (preg_match('/kindle|opera (mini|mobi)|polaris|netfront|fennec|motorola|symbianos|webos/i', $ua))
		{
			return self::$_mobile_cache[$ua] = true;
		}
		if (preg_match('/s[pgc]h-|lgtelecom|sonyericsson|alcatel|vodafone|maemo|minimo|bada/i', $ua))
		{
			return self::$_mobile_cache[$ua] = true;
		}
		
		// If we're here, it's probably not a mobile device.
		return self::$_mobile_cache[$ua] = false;
	}

	/**
	 * Check whether the current visitor is using a tablet.
	 * 
	 * @param string $ua (optional)
	 * @return bool
	 */
	public static function isTablet($ua = null)
	{
		// Get the User-Agent header if the caller did not specify $ua.
		$ua = $ua ?: (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
		
		// If the User-Agent header is missing, it's probably not a tablet.
		if (is_null($ua))
		{
			return false;
		}
		
		// Look up the cache.
		if (isset(self::$_tablet_cache[$ua]))
		{
			return self::$_tablet_cache[$ua];
		}
		
		// Check if the user-agent is mobile.
		if (!self::isMobile($ua))
		{
			return self::$_tablet_cache[$ua] = false;
		}
		
		// Check for Android tablets without the 'mobile' keyword.
		if (stripos($ua, 'android') !== false && stripos($ua, 'mobile') === false)
		{
			return self::$_tablet_cache[$ua] = true;
		}
		
		// Check for common tablet identifiers.
		if (preg_match('/tablet|pad\b|tab\b|\bgt-\d+|kindle|nook|playbook|webos|xoom/i', $ua))
		{
			return self::$_tablet_cache[$ua] = true;
		}
		
		// If we're here, it's probably not a tablet.
		return self::$_tablet_cache[$ua] = false;
	}
	
	/**
	 * Check whether the current visitor is a robot.
	 * 
	 * @param string $ua (optional)
	 * @return bool
	 */
	public static function isRobot($ua = null)
	{
		// Get the User-Agent header if the caller did not specify $ua.
		$ua = $ua ?: (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
		
		// If the User-Agent header is missing, it's probably not a robot.
		if (is_null($ua))
		{
			return false;
		}
		
		// Look up the cache.
		if (isset(self::$_robot_cache[$ua]))
		{
			return self::$_robot_cache[$ua];
		}
		
		// Look for common search engine names and the 'bot' keyword.
		if (preg_match('/bot|slurp|facebook(externalhit|scraper)|ia_archiver|ask jeeves|teoma|baidu|daumoa|lycos|pingdom/i', $ua))
		{
			return self::$_robot_cache[$ua] = true;
		}
		
		// If we're here, it's probably not a robot.
		return self::$_robot_cache[$ua] = false;
	}
	
	/**
	 * This method parses the User-Agent string to guess what kind of browser it is.
	 * 
	 * @param string $ua (optional)
	 * @return object
	 */
	public static function getBrowserInfo($ua = null)
	{
		// Get the User-Agent header if the caller did not specify $ua.
		$ua = $ua ?: (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
		
		// Initialize the result.
		$result = (object)array(
			'browser' => null,
			'version' => null,
			'os' => null,
			'is_mobile' => null,
			'is_tablet' => null,
		);
		if (is_null($ua))
		{
			return $result;
		}
		
		// Try to guess the OS.
		if (preg_match('#(Windows|Android|Linux|iOS|OS X|Macintosh)#i', $ua, $matches))
		{
			if ($matches[1] === 'Linux' && strpos($ua, 'Android') !== false)
			{
				$matches[1] = 'Android';
			}
			if ($matches[1] === 'Macintosh' && strpos($ua, 'OS X') !== false)
			{
				$matches[1] = 'OS X';
			}
			$result->os = $matches[1];
		}
		
		// Fill in miscellaneous fields.
		$result->is_mobile = self::isMobile($ua);
		$result->is_tablet = self::isTablet($ua);
		
		// Try to match some of the most common browsers.
		if (preg_match('#Android ([0-9]+\\.[0-9]+)#', $ua, $matches) && strpos($ua, 'Chrome') === false)
		{
			$result->browser = 'Android';
			$result->version = $matches[1];
			return $result;
		}
		if (preg_match('#Edge/([0-9]+\\.)#', $ua, $matches))
		{
			$result->browser = 'Edge';
			$result->version = $matches[1] . '0';
			return $result;
		}
		if (preg_match('#Trident/([0-9]+)\\.[0-9]+#', $ua, $matches))
		{
			$result->browser = 'IE';
			$result->version = ($matches[1] + 4) . '.0';
			return $result;
		}
		if (preg_match('#(MSIE|Chrome|Firefox|Safari)[ /:]([0-9]+\\.[0-9]+)#', $ua, $matches))
		{
			$result->browser = $matches[1] === 'MSIE' ? 'IE' : $matches[1];
			$result->version = $matches[2];
			return $result;
		}
		if (preg_match('#^Opera/.+(?:Opera |Version/)([0-9]+\\.[0-9]+)$#', $ua, $matches))
		{
			$result->browser = 'Opera';
			$result->version = $matches[1];
			return $result;
		}
		if (preg_match('#(?:Konqueror|KHTML)/([0-9]+\\.[0-9]+)$#', $ua, $matches))
		{
			$result->browser = 'Konqueror';
			$result->version = $matches[1];
			return $result;
		}
		
		return $result;
	}
}
