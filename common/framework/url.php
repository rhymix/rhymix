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
		$request_uri = preg_replace('/[<>"]/', '', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
		$url = self::getCurrentDomainURL($request_uri);
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
	 * Get the current domain.
	 * 
	 * @param string $path
	 * @return string
	 */
	public static function getCurrentDomainURL($path = '/')
	{
		$proto = \RX_SSL ? 'https://' : 'http://';
		$host = isset($_SERVER['HTTP_HOST']) ? self::decodeIdna($_SERVER['HTTP_HOST']) : 'localhost';
		return $proto . $host . '/' . ltrim($path, '/');
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
			$url = self::getCurrentDomainURL(\RX_BASEURL . ltrim($url, './'));
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
		
		if (\ModuleModel::getInstance()->getSiteInfoByDomain($domain))
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
		$prefix = sprintf('%s://%s%s%s', $url['scheme'], $url['host'], (($url['port'] ?? '') ? (':' . $url['port']) : ''), $url['path']);
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
	 * Convert a server-side path to a URL.
	 * 
	 * This method returns false if the path cannot be converted to a URL,
	 * e.g. if the path is outside of the document root.
	 * 
	 * @param string $path
	 * @return string|false
	 */
	public static function fromServerPath($path)
	{
		$cleanpath = Filters\FilenameFilter::cleanPath($path);
		if (substr($path, -1) === '/')
		{
			$cleanpath .= '/';
		}
		$root = Filters\FilenameFilter::cleanPath($_SERVER['DOCUMENT_ROOT']);
		if ($cleanpath === $root)
		{
			return self::getCurrentDomainURL('/');
		}
		if (starts_with($root . '/', $cleanpath))
		{
			return self::getCurrentDomainURL(substr($cleanpath, strlen($root)));
		}
		return false;
	}
	
	/**
	 * Convert a URL to a server-side path.
	 * 
	 * This method returns false if the URL cannot be converted to a server-side path,
	 * e.g. if the URL belongs to an external domain.
	 * 
	 * @param string $url
	 * @return string
	 */
	public static function toServerPath($url)
	{
		$url = self::getCanonicalURL($url);
		if (!self::isInternalURL($url))
		{
			return false;
		}
		return Filters\FilenameFilter::cleanPath($_SERVER['DOCUMENT_ROOT'] . parse_url($url, \PHP_URL_PATH));
	}
	
	/**
	 * Encode UTF-8 domain into IDNA (punycode)
	 * 
	 * @param string $url
	 * @return string
	 */
	public static function encodeIdna($url)
	{
		if (preg_match('@[:/#]@', $url))
		{
			$domain = parse_url($url, \PHP_URL_HOST);
			$position = strpos($url, $domain);
			if ($position === false)
			{
				return $url;
			}
		}
		else
		{
			$domain = $url;
			$position = 0;
		}
		
		if (function_exists('idn_to_ascii'))
		{
			$new_domain = idn_to_ascii($domain);
		}
		else
		{
			$encoder = new \TrueBV\Punycode();
			$new_domain = $encoder->encode($domain);
		}
		
		return substr_replace($url, $new_domain, $position, strlen($domain));
	}

	/**
	 * Convert IDNA (punycode) domain into UTF-8
	 * 
	 * @param string $url
	 * @return string
	 */
	public static function decodeIdna($url)
	{
		if (preg_match('@[:/#]@', $url))
		{
			$domain = parse_url($url, \PHP_URL_HOST);
			$position = strpos($url, $domain);
			if ($position === false)
			{
				return $url;
			}
		}
		else
		{
			$domain = $url;
			$position = 0;
		}
		
		if (function_exists('idn_to_utf8'))
		{
			$new_domain = idn_to_utf8($domain);
		}
		else
		{
			$decoder = new \TrueBV\Punycode();
			$new_domain = $decoder->decode($domain);
		}
		
		return substr_replace($url, $new_domain, $position, strlen($domain));
	}
}
