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
			'is_robot' => null,
		);
		if (is_null($ua))
		{
			return $result;
		}
		
		// Try to guess the OS.
		if (preg_match('#(Windows|Android|Linux|i(?:Phone|P[ao]d)|OS X|Macintosh)#i', $ua, $matches))
		{
			if ($matches[1] === 'Linux' && strpos($ua, 'Android') !== false)
			{
				$result->os = 'Android';
			}
			elseif ($matches[1] === 'iPhone' || $matches[1] === 'iPad' || $matches[1] === 'iPod')
			{
				$result->os = 'iOS';
			}
			elseif ($matches[1] === 'Macintosh' || $matches[1] === 'OS X')
			{
				$result->os = 'macOS';
			}
			else
			{
				$result->os = $matches[1];
			}
		}
		
		// Fill in miscellaneous fields.
		$result->is_mobile = self::isMobile($ua);
		$result->is_tablet = self::isTablet($ua);
		$result->is_robot = self::isRobot($ua);
		
		// Try to match some of the most common browsers.
		if ($result->os === 'Android' && preg_match('#Android ([0-9]+\\.[0-9]+)#', $ua, $matches))
		{
			if (strpos($ua, 'Chrome') === false || preg_match('#(?:Version|Browser)/[0-9]+#', $ua) || preg_match('#\\bwv\\b#', $ua))
			{
				$result->browser = 'Android';
				$result->version = $matches[1];
				return $result;
			}
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
		if (preg_match('#(MSIE|OPR|CriOS|Firefox|FxiOS|Iceweasel|Yeti|[a-z]+(?:bot|spider)(?:-Image)?|wget|curl)[ /:]([0-9]+\\.[0-9]+)#i', $ua, $matches))
		{
			if ($matches[1] === 'MSIE')
			{
				$result->browser = 'IE';
			}
			elseif ($matches[1] === 'CriOS')
			{
				$result->browser = 'Chrome';
			}
			elseif ($matches[1] === 'FxiOS' || $matches[1] === 'Iceweasel')
			{
				$result->browser = 'Firefox';
			}
			elseif ($matches[1] === 'OPR')
			{
				$result->browser = 'Opera';
			}
			else
			{
				$result->browser = ucfirst($matches[1]);
			}
			$result->version = $matches[2];
			return $result;
		}
		if (preg_match('#(?:Opera|OPR)[/ ]([0-9]+\\.[0-9]+)#', $ua, $matches))
		{
			$result->browser = 'Opera';
			if ($matches[1] >= 9.79 && preg_match('#Version/([0-9]+\\.[0-9]+)#', $ua, $operamatches))
			{
				$result->version = $operamatches[1];
			}
			else
			{
				$result->version = $matches[1];
			}
			return $result;
		}
		if (preg_match('#(?:Konqueror|KHTML)/([0-9]+\\.[0-9]+)$#', $ua, $matches))
		{
			$result->browser = 'Konqueror';
			$result->version = $matches[1];
			return $result;
		}
		if (preg_match('#Chrome/([0-9]+\\.[0-9]+)#', $ua, $matches))
		{
			$result->browser = 'Chrome';
			$result->version = $matches[1];
			return $result;
		}
		if (preg_match('#Safari/[0-9]+#', $ua) && preg_match('#Version/([0-9]+\\.[0-9]+)#', $ua, $matches) && $matches[1] < 500)
		{
			$result->browser = 'Safari';
			$result->version = $matches[1];
			return $result;
		}
		if (preg_match('#\\bPHP(/[0-9]+\\.[0-9]+)?#', $ua, $matches))
		{
			$result->browser = 'PHP';
			$result->version = (isset($matches[1]) && $matches[1]) ? substr($matches[1], 1) : null;
			return $result;
		}
		if (preg_match('#^Mozilla/([0-9]+\\.[0-9]+)#', $ua, $matches))
		{
			$result->browser = 'Mozilla';
			$result->version = $matches[1];
			return $result;
		}
		if (preg_match('#^([a-zA-Z0-9_-]+)/([0-9]+\\.[0-9]+)#', $ua, $matches))
		{
			$result->browser = ucfirst($matches[1]);
			$result->version = $matches[2];
			return $result;
		}
		
		return $result;
	}
	
	/**
	 * This method encodes a UTF-8 filename for downloading in the current visitor's browser.
	 * 
	 * @param string $filename
	 * @param string $ua (optional)
	 * @return string
	 */
	public static function encodeFilenameForDownload($filename, $ua = null)
	{
		// Get the User-Agent header if the caller did not specify $ua.
		$ua = $ua ?: (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
		
		// Get the browser name and version.
		$browser = self::getBrowserInfo($ua);
		
		// Find the best format that this browser supports.
		if ($browser->browser === 'Chrome' && $browser->version >= 11)
		{
			$output_format = 'rfc5987';
		}
		elseif ($browser->browser === 'Firefox' && $browser->version >= 6)
		{
			$output_format = 'rfc5987';
		}
		elseif ($browser->browser === 'Safari' && $browser->version >= 6)
		{
			$output_format = 'rfc5987';
		}
		elseif ($browser->browser === 'IE' && $browser->version >= 10)
		{
			$output_format = 'rfc5987';
		}
		elseif ($browser->browser === 'Edge')
		{
			$output_format = 'rfc5987';
		}
		elseif ($browser->browser === 'IE')
		{
			$output_format = 'old_ie';
		}
		elseif ($browser->browser === 'Android' || $browser->browser === 'Chrome' || $browser->browser === 'Safari')
		{
			$output_format = 'raw';
		}
		else
		{
			$output_format = 'old_ie';
		}
		
		// Clean the filename.
		$filename = Filters\FilenameFilter::clean($filename);
		
		// Apply the format and return.
		switch ($output_format)
		{
			case 'raw':
				return 'filename="' . $filename . '"';
				
			case 'rfc5987':
				$filename = rawurlencode($filename);
				return "filename*=UTF-8''" . $filename . '; filename="' . $filename . '"';
				
			case 'old_ie':
			default:
				$filename = rawurlencode($filename);
				return 'filename="' . preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1) . '"';
		}
	}
}
