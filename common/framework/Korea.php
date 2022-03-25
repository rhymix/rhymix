<?php

namespace Rhymix\Framework;

/**
 * Class for validating Korea-specific information.
 */
class Korea
{
	/**
	 * Format a phone number.
	 * 
	 * @param string $num
	 * @return string
	 */
	public static function formatPhoneNumber($num)
	{
		// Remove all non-numbers.
		$num = preg_replace('/[^0-9]/', '', $num);
		
		// Remove the country code.
		if (strncmp($num, '82', 2) === 0)
		{
			$num = substr($num, 2);
			if (strncmp($num, '0', 1) !== 0)
			{
				$num = '0' . $num;
			}
		}
		
		// Apply different format based on the number of digits.
		switch (strlen($num))
		{
			case 8:
				return substr($num, 0, 4) . '-' . substr($num, 4);
			case 9:
				return substr($num, 0, 2) . '-' . substr($num, 2, 3) . '-' . substr($num, 5);
			case 10:
				if (substr($num, 0, 2) === '02')
				{
					return substr($num, 0, 2) . '-' . substr($num, 2, 4) . '-' . substr($num, 6);
				}
				else
				{
					return substr($num, 0, 3) . '-' . substr($num, 3, 3) . '-' . substr($num, 6);
				}
			default:
				if (substr($num, 0, 4) === '0303' || substr($num, 0, 3) === '050')
				{
					if (strlen($num) === 12)
					{
						return substr($num, 0, 4) . '-' . substr($num, 4, 4) . '-' . substr($num, 8);
					}
					else
					{
						return substr($num, 0, 4) . '-' . substr($num, 4, 3) . '-' . substr($num, 7);
					}
				}
				else
				{
					return substr($num, 0, 3) . '-' . substr($num, 3, 4) . '-' . substr($num, 7);
				}
		}
	}
	
	/**
	 * Check if a Korean phone number contains a valid area code and the correct number of digits.
	 * 
	 * @param string $num
	 * @return bool
	 */
	public static function isValidPhoneNumber($num)
	{
		$num = str_replace('-', '', self::formatPhoneNumber($num));
		if (preg_match('/^1[0-9]{7}$/', $num))
		{
			return true;
		}
		if (preg_match('/^02(?:[2-9][0-9]{6,7}|1[0-9]{7})$/', $num))
		{
			return true;
		}
		if (preg_match('/^0[13-8][0-9][2-9][0-9]{6,7}$/', $num))
		{
			return true;
		}
		if (preg_match('/^0(?:303|505)[2-9][0-9]{6,7}$/', $num))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Check if a Korean phone number is a mobile phone number.
	 * 
	 * @param string $num
	 * @return bool
	 */
	public static function isValidMobilePhoneNumber($num)
	{
		$num = str_replace('-', '', self::formatPhoneNumber($num));
		$len = strlen($num);
		return preg_match('/^01[016789][2-9][0-9]{6,7}$/', $num) ? true : false;
	}
	
	/**
	 * Check if the given string is a valid resident registration number (주민등록번호)
	 * or foreigner registration number (외국인등록번호).
	 * 
	 * This method only checks the format.
	 * It does not check that the number is actually in use.
	 * 
	 * @param string $code
	 * @return bool
	 */
	public static function isValidJuminNumber($code)
	{
		// Return false if the format is obviously wrong.
		if (!preg_match('/^[0-9]{6}-?[0-9]{7}$/', $code))
		{
			return false;
		}
		
		// Remove hyphen.
		$code = str_replace('-', '', $code);
		
		// Return false if the date of birth is in the future.
		if (in_array((int)($code[6]), array(3, 4, 7, 8)) && intval(substr($code, 0, 6), 10) > date('ymd'))
		{
			return false;
		}
		
		// Calculate the checksum.
		$sum = 0;
		for ($i = 0; $i < 12; $i++)
		{
			$sum += $code[$i] * (($i % 8) + 2);
		}
		$checksum = (11 - ($sum % 11)) % 10;
		if (in_array((int)($code[6]), array(1, 2, 3, 4, 9, 0)))
		{
			return $checksum === (int)($code[12]);
		}
		else
		{
			if (substr($code, 7, 2) % 2 !== 0)
			{
				return false;
			}
			else
			{
				return (($checksum + 2) % 10) === (int)($code[12]);
			}
		}
	}
	
	/**
	 * Check if the given string is a valid corporation registration number (법인등록번호).
	 * 
	 * This method only checks the format.
	 * It does not check that the number is actually in use.
	 * 
	 * @param string $code
	 * @return bool
	 */
	public static function isValidCorporationNumber($code)
	{
		// Return false if the format is obviously wrong.
		if (!preg_match('/^[0-9]{6}-?[0-9]{7}$/', $code))
		{
			return false;
		}
		
		// Remove hyphen.
		$code = str_replace('-', '', $code);
		
		// Calculate the checksum.
		$sum = 0;
		for ($i = 0; $i < 12; $i++)
		{
			$sum += $code[$i] * (($i % 2) + 1);
		}
		$checksum = (10 - ($sum % 10)) % 10;
		
		// Check the 7th and 13th digits.
		if ($code[6] !== '0')
		{
			return false;
		}
		return $checksum === (int)($code[12]);
	}
	
	/**
	 * Check if the given string is a valid business registration number (사업자등록번호).
	 * 
	 * This method only checks the format.
	 * It does not check that the number is actually in use.
	 * 
	 * @param string $code
	 * @return bool
	 */
	public static function isValidBusinessNumber($code)
	{
		// Return false if the format is obviously wrong.
		if (!preg_match('/^[0-9]{3}-?[0-9]{2}-?[0-9]{5}$/', $code))
		{
			return false;
		}
		
		// Remove hyphen.
		$code = str_replace('-', '', $code);
		
		// Calculate the checksum.
		$sum = 0;
		$sum += $code[0] + ($code[1] * 3) + ($code[2] * 7);
		$sum += $code[3] + ($code[4] * 3) + ($code[5] * 7);
		$sum += $code[6] + ($code[7] * 3) + ($code[8] * 5);
		$sum += floor(($code[8] * 5) / 10);
		$checksum = (10 - ($sum % 10)) % 10;
		
		// Check the last digit.
		return $checksum === (int)($code[9]);
	}
	
	/**
	 * Check if the given IP address is Korean.
	 *
	 * This method may return incorrect results if the IP allocation databases
	 * (korea.ipv4.php, korea.ipv6.php) are out of date.
	 * 
	 * @param string $ip
	 * @return bool
	 */
	public static function isKoreanIP($ip)
	{
		// Extract the IPv4 address from an "IPv4-mapped IPv6" address.
		if (preg_match('/::ffff:(?:0+:)?([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)$/', $ip, $matches)) $ip = $matches[1];
		
		// Return false if the IP address is not in the right format.
		if (!filter_var($ip, \FILTER_VALIDATE_IP)) return false;
		
		// Check IPv4.
		if (filter_var($ip, \FILTER_VALIDATE_IP, array('flags' => \FILTER_FLAG_IPV4)))
		{
			// Convert to integer.
			$ipnum = sprintf('%u', ip2long($ip));
			
			// Treat local addresses as Korean.
			if ($ipnum >= 167772160 && $ipnum <= 184549375) return true;    // 10.0.0.0/8
			if ($ipnum >= 2130706432 && $ipnum <= 2147483647) return true;  // 127.0.0.0/8
			if ($ipnum >= 3232235520 && $ipnum <= 3232301055) return true;  // 192.168.0.0/16
			if ($ipnum >= 2886729728 && $ipnum <= 2887778303) return true;  // 172.16.0.0/20
			
			// Check the IPv4 allocation database.
			$ranges = (include \RX_BASEDIR . 'common/defaults/korea.ipv4.php');
			foreach ($ranges as $range)
			{
				if ($ipnum >= $range[0] && $ipnum <= $range[1]) return true;
			}
			return false;
		}
		
		// Check IPv6.
		elseif (function_exists('inet_pton'))
		{
			// Convert to hexadecimal format.
			$ipbin = strtolower(bin2hex(inet_pton($ip)));
			
			// Treat local addresses as Korean.
			if ($ipbin == '00000000000000000000000000000001') return true;  // ::1
			if (preg_match('/^f(?:[cd]|e80{13})/', $ipbin)) return true;    // fc00::/8, fd00::/8, fe80::/64
			
			// Check the IPv6 allocation database.
			$ranges = (include \RX_BASEDIR . 'common/defaults/korea.ipv6.php');
			foreach ($ranges as $range)
			{
				if (strncmp($ipbin, $range[0], 16) >= 0 && strncmp($ipbin, $range[1], 16) <= 0) return true;
			}
			return false;
		}
		
		return false;
	}
	
	/**
	 * Check if the given email address is hosted by a Korean portal site.
	 * 
	 * This can be used to tell which recipients may subscribe to the KISA RBL (kisarbl.or.kr).
	 * If the domain is not found, this method returns false.
	 * 
	 * @param string $domain
	 * @param bool $clear_cache (optional)
	 * @return bool
	 */
	public static function isKoreanEmailAddress($email_address, $clear_cache = false)
	{
		// Clear the cache if requested.
		if ($clear_cache)
		{
			self::$_domain_cache = array();
		}
		
		// Get the domain from the email address.
		if ($pos = strpos($email_address, '@'))
		{
			$domain = substr($email_address, $pos + 1);
		}
		else
		{
			$domain = $email_address;
		}
		$domain = rtrim(strtolower($domain), '.');
		
		// Return cached result if available.
		if (array_key_exists($domain, self::$_domain_cache))
		{
			return self::$_domain_cache[$domain];
		}
		
		// Shortcut for known domains.
		if (in_array($domain, self::$known_korean))
		{
			return self::$_domain_cache[$domain] = true;
		}
		if (in_array($domain, self::$known_foreign))
		{
			return self::$_domain_cache[$domain] = false;
		}
		
		// For unknown domains, check the MX record.
		$mx = self::_getDNSRecords($domain, \DNS_MX);
		
		$i = 0;
		foreach ($mx as $mx)
		{
			$mx = rtrim($mx, '.');
			foreach (self::$known_korean as $portal)
			{
				if ($mx === $portal || ends_with('.' . $portal, $mx))
				{
					return self::$_domain_cache[$domain] = true;
				}
			}
			foreach (self::$known_foreign as $portal)
			{
				if ($mx === $portal || ends_with('.' . $portal, $mx))
				{
					return self::$_domain_cache[$domain] = false;
				}
			}
			foreach (self::_getDNSRecords($domain, \DNS_A) as $mx_ip)
			{
				return self::$_domain_cache[$domain] = self::isKoreanIP($mx_ip);
			}
			if (++$i > 2)
			{
				break;
			}
		}
		
		return self::$_domain_cache[$domain] = false;
	}
	
	/**
	 * Get the DNS records of a domain.
	 * 
	 * @param string $domain
	 * @param int $type
	 * @return array
	 */
	protected static function _getDNSRecords($domain, $type)
	{
		$records = dns_get_record($domain, $type);
		if (!$records)
		{
			return array();
		}
		
		$result = array();
		foreach ($records as $record)
		{
			if (isset($record['pri']) && isset($record['target']))
			{
				$result[intval($record['pri'])] = $record['target'];
			}
			elseif (isset($record['target']))
			{
				$result[] = $record['target'];
			}
			elseif (isset($record['ip']) || isset($record['ipv6']))
			{
				$result[] = isset($record['ip']) ? $record['ip'] : $record['ipv6'];
			}
			elseif (isset($record['txt']))
			{
				$result[] = $record['txt'];
			}
		}
		
		ksort($result);
		return $result;
	}
	
	/**
	 * Prevent multiple lookups for the same domain.
	 */
	protected static $_domain_cache = array();
	
	/**
	 * Domains known to be Korean and subscribed to the KISA RBL.
	 */
	public static $known_korean = array(
		'hanmail.net',
		'hanmail2.net',
		'daum.net',
		'paran.com',
		'tistory.com',
		'naver.com',
		'navercorp.com',
		'nate.com',
		'cyworld.com',
		'dreamwiz.com',
		'korea.com',
		'dreamx.com',
		'chol.com',
		'chollian.net',
		'hanmir.com',
		'hitel.com',
		'freechal.com',
		'empas.com',
		'empal.com',
		'hanafos.com',
	);
	
	/**
	 * Domains known to be foreign.
	 */
	public static $known_foreign = array(
		'gmail.com',
		'googlemail.com',
		'google.com',
		'yahoo.com',
		'yahoo.co.kr',
		'hotmail.com',
		'hotmail.co.kr',
		'live.com',
		'outlook.com',
		'msn.com',
		'me.com',
		'mac.com',
		'icloud.com',
		'facebook.com',
		'aol.com',
		'gmx.com',
		'mail.com',
		'fastmail.com',
		'fastmail.fm',
		'runbox.com',
		'inbox.com',
		'lycos.com',
		'zoho.com',
	);
}
