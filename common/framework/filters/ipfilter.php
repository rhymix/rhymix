<?php

namespace Rhymix\Framework\Filters;

/**
 * The IP filter class.
 */
class IpFilter
{
	/**
	 * Check whether the given IP address belongs to a range.
	 * 
	 * @param string $ip
	 * @param string $range
	 * @return bool
	 */
	public static function inRange($ip, $range)
	{
		// Determine the type of the IP address.
		if (preg_match('/[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/', $ip, $matches))
		{
			$ip = $matches[0];
			$ip_type = 4;
		}
		elseif (preg_match('/^[0-9a-f:]+$/i', $ip))
		{
			$ip = strtolower($ip);
			$ip_type = 6;
		}
		
		// Determine the type of the range.
		if ($ip_type === 6 && strpos($range, ':') !== false)
		{
			$range_type = 'ipv6_cidr';
		}
		elseif ($ip_type === 4 && strpos($range, '*') !== false)
		{
			$range_type = 'ipv4_wildcard';
		}
		elseif ($ip_type === 4 && strpos($range, '-') !== false)
		{
			$range_type = 'ipv4_hyphen';
		}
		elseif ($ip_type === 4 && strpos($range, '.') !== false)
		{
			$range_type = 'ipv4_cidr';
		}
		else
		{
			$range_type = 'unknown';
		}
		
		// Check!
		switch ($range_type)
		{
			case 'ipv4_cidr':
				return self::_checkIPv4CIDR($ip, $range);
			case 'ipv6_cidr':
				return self::_checkIPv6CIDR($ip, $range);
			case 'ipv4_wildcard':
				return self::_checkIPv4Wildcard($ip, $range);
			case 'ipv4_hyphen':
				return self::_checkIPv4Hyphen($ip, $range);
			default:
				return false;
		}
	}
	
	/**
	 * Check whether the given IP address belongs to a set of ranges.
	 * 
	 * @param string $ip
	 * @param array $ranges
	 * @return bool
	 */
	public static function inRanges($ip, array $ranges)
	{
		foreach ($ranges as $range)
		{
			if (self::inRange($ip, $range))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Check whether a range definition is valid.
	 * 
	 * @param string $range
	 * @return bool
	 */
	public static function validateRange($range)
	{
		$regexes = array(
			'/^\d+\.\d+\.\d+\.\d+(\/\d+)?$/',
			'/^\d+\.(\d+|\*)(\.(\d+|\*)(\.(\d+|\*))?)?$/',
			'/^\d+\.\d+\.\d+\.\d+-\d+\.\d+\.\d+\.\d+$/',
			'/^[0-9a-f:]+(\/\d+)?$/i',
		);
		
		foreach ($regexes as $regex)
		{
			if (preg_match($regex, $range))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Check whether a set of range definitions is valid.
	 * 
	 * @param array $ranges
	 * @return bool
	 */
	public static function validateRanges(array $ranges)
	{
		foreach ($ranges as $range)
		{
			if (!self::validateRange($range))
			{
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Get real IP from CloudFlare headers.
	 * 
	 * @return string|false
	 */
	public static function getCloudFlareRealIP()
	{
		if (!isset($_SERVER['HTTP_CF_CONNECTING_IP']))
		{
			return false;
		}
		
		$cloudflare_ranges = (include \RX_BASEDIR . 'common/defaults/cloudflare.php');
		foreach ($cloudflare_ranges as $cloudflare_range)
		{
			if (self::inRange($_SERVER['REMOTE_ADDR'], $cloudflare_range))
			{
				return $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
			}
		}
		return false;
	}
	
	/**
	 * Check whether the given IPv4 address belongs to a IPv4 CIDR range with mask.
	 * 
	 * Example: 172.16.0.0/12
	 * 
	 * @param string $ip
	 * @param string $range
	 * @return bool
	 */
	protected static function _checkIPv4CIDR($ip, $range)
	{
		if (strpos($range, '/') === false) $range .= '/32';
		list($range, $mask) = explode('/', $range);
		$ip = ip2long($ip) & (0xffffffff << (32 - $mask));
		$range = ip2long($range) & (0xffffffff << (32 - $mask));
		return $ip === $range;
	}
	
	/**
	 * Check whether the given IPv4 address belongs to a IPv6 CIDR range with mask.
	 * 
	 * Example: 2400:cb00::/32
	 * 
	 * @param string $ip
	 * @param string $range
	 * @return bool
	 */
	protected static function _checkIPv6CIDR($ip, $range)
	{
		if (function_exists('inet_pton'))
		{
			if (strpos($range, '/') === false) $range .= '/128';
			list($range, $mask) = explode('/', $range);
			$ip = substr(bin2hex(inet_pton($ip)), 0, intval($mask / 4));
			$range = substr(bin2hex(inet_pton($range)), 0, intval($mask / 4));
			return $ip === $range;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Check whether the given IPv4 address belongs to a IPv4 wildcard range.
	 * 
	 * Example: 192.168.*.*
	 * 
	 * @param string $ip
	 * @param string $range
	 * @return bool
	 */
	protected static function _checkIPv4Wildcard($ip, $range)
	{
		$count = count(explode('.', $range));
		if ($count < 4)
		{
			$range .= str_repeat('.*', 4 - $count);
		}
		$range = str_replace(array('.', '*'), array('\\.', '\\d+'), trim($range));
		return preg_match("/^$range$/", $ip) ? true : false;
	}
	
	/**
	 * Check whether the given IPv4 address belongs to a IPv4 hyphen range.
	 * 
	 * Example: 192.168.0.0-192.168.255.255
	 * 
	 * @param string $ip
	 * @param string $range
	 * @return bool
	 */
	protected static function _checkIPv4Hyphen($ip, $range)
	{
		$ip = sprintf('%u', ip2long($ip));
		list($range_start, $range_end) = explode('-', $range);
		$range_start = sprintf('%u', ip2long(trim($range_start)));
		$range_end = sprintf('%u', ip2long(trim($range_end)));
		return ($ip >= $range_start && $ip <= $range_end);
	}
}
