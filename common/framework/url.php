<?php

namespace Rhymix\Framework;

/**
 * The URL class.
 */
class URL
{
	/**
	 * Get the current URL.
	 * 
	 * If $changes are given, they will be appended to the current URL as a query string.
	 * To delete an existing query string, set its value to null.
	 * 
	 * @param array $changes
	 * @return string
	 */
	public static function getCurrentURL(array $changes = array())
	{
		$proto = \RX_SSL ? 'https://' : 'http://';
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
		$local = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		$url = $proto . $host . $local;
		if (count($changes))
		{
			return self::modifyURL($url, $changes);
		}
		else
		{
			return self::getCanonicalURL($url);
		}
	}
	
	/**
	 * Convert a URL to its canonical format.
	 * 
	 * @param string $url
	 * @return string
	 */
	public static function getCanonicalURL($url)
	{
		if (preg_match('#^\.?/([^/]|$)#', $url) || !preg_match('#^(https?:|/)#', $url))
		{
			$proto = \RX_SSL ? 'https://' : 'http://';
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
			$url = $proto . $host . \RX_BASEURL . ltrim($url, './');
		}
		return preg_replace_callback('#^(https?:|)//([^/]+)#i', function($matches) {
			if ($matches[1] === '') $matches[1] = \RX_SSL ? 'https:' : 'http:';
			return $matches[1] . '//' . self::decodeIdna($matches[2]);
		}, $url);
	}
	
	/**
	 * Get the domain from a URL.
	 * 
	 * @param string $url
	 * @return string|false
	 */
	public static function getDomainFromURL($url)
	{
		$domain = @parse_url($url, \PHP_URL_HOST);
		if ($domain === false || $domain === null)
		{
			return false;
		}
		else
		{
			return self::decodeIdna($domain);
		}
	}
	
	/**
	 * Check if a URL is internal to this site.
	 * 
	 * @param string $url
	 * @return bool
	 */
	public static function isInternalURL($url)
	{
		$domain = self::getDomainFromURL($url);
		if ($domain === false)
		{
			return true;
		}
		
		if ($domain === self::getDomainFromURL('http://' . $_SERVER['HTTP_HOST']))
		{
			return true;
		}
		
		if ($domain === self::getDomainFromURL(\Context::getDefaultUrl()))
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Modify a URL.
	 * 
	 * If $changes are given, they will be appended to the current URL as a query string.
	 * To delete an existing query string, set its value to null.
	 * 
	 * @param string $url
	 * @param array $changes
	 * @return string
	 */
	public static function modifyURL($url, array $changes = array())
	{
		$url = parse_url(self::getCanonicalURL($url));
		$prefix = sprintf('%s://%s%s%s', $url['scheme'], $url['host'], ($url['port'] ? (':' . $url['port']) : ''), $url['path']);
		parse_str($url['query'], $args);
		$changes = array_merge($args, $changes);
		$changes = array_filter($changes, function($val) { return $val !== null; });
		if (count($changes))
		{
			return $prefix . '?' . http_build_query($changes);
		}
		else
		{
			return $prefix;
		}
	}
	
	/**
	 * Encode UTF-8 domain into IDNA (punycode)
	 * 
	 * @param string $domain
	 * @return string
	 */
	public static function encodeIdna($domain)
	{
		if (function_exists('idn_to_ascii'))
		{
			return idn_to_ascii($domain);
		}
		else
		{
			$encoder = new \TrueBV\Punycode();
			return $encoder->encode($domain);
		}
	}

	/**
	 * Convert IDNA (punycode) domain into UTF-8
	 * 
	 * @param string $domain
	 * @return string
	 */
	public static function decodeIdna($domain)
	{
		if (function_exists('idn_to_utf8'))
		{
			return idn_to_utf8($domain);
		}
		else
		{
			$decoder = new \TrueBV\Punycode();
			return $decoder->decode($domain);
		}
	}
}
