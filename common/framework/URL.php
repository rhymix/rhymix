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
	public static function getCurrentURL(array $changes = []): string
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
	 * @param bool $preserve_port
	 * @return string
	 */
	public static function getCurrentDomain(bool $preserve_port = false): string
	{
		// Get current domain.
		$domain = strtolower($_SERVER['HTTP_HOST'] ?? '');
		if (!$preserve_port)
		{
			$domain = preg_replace('/:\d+$/', '', $domain);
		}
		return self::decodeIdna($domain);
	}

	/**
	 * Get a URL using the current domain and the path.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getCurrentDomainURL(string $path = '/'): string
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
	public static function getCanonicalURL(string $url): string
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
	public static function getDomainFromURL(string $url)
	{
		$domain = @parse_url(str_replace('\\', '/', $url), \PHP_URL_HOST);
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
	public static function isInternalURL(string $url): bool
	{
		// Check the scheme.
		$url = str_replace('\\', '/', $url);
		if (preg_match('!^([a-zA-Z0-9_-]+):!', $url, $matches))
		{
			$scheme = strtolower($matches[1]);
			if (!in_array($scheme, ['http', 'https']))
			{
				return false;
			}
		}
		else
		{
			$scheme = \RX_SSL ? 'https' : 'http';
		}

		// Get the domain and port.
		$domain = parse_url($url, \PHP_URL_HOST);
		if ($domain === false || $domain === null)
		{
			return true;
		}
		$domain = self::decodeIdna(strtolower($domain));
		$port = intval(parse_url($url, \PHP_URL_PORT));
		$hostname = $domain . ($port ? ":$port" : '');

		// Check if the domain matches the current request.
		if ($hostname === self::decodeIdna($_SERVER['HTTP_HOST'] ?? ''))
		{
			return true;
		}

		// Check if the domain matches any other domain registered in the database.
		$domain_info = \ModuleModel::getSiteInfoByDomain($domain);
		if (!$domain_info)
		{
			return false;
		}
		if (isset($domain_info->security) && $domain_info->security === 'always')
		{
			$scheme = 'https';
		}
		if ($port && $port != ($domain_info->{$scheme . '_port'} ?? 0))
		{
			return false;
		}
		if ($scheme === 'http' && $domain_info->http_port && $domain_info->http_port != 80 && $port != $domain_info->http_port)
		{
			return false;
		}
		if ($scheme === 'https' && $domain_info->https_port && $domain_info->https_port != 443 && $port != $domain_info->https_port)
		{
			return false;
		}

		return true;
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
	public static function modifyURL(string $url, array $changes = []): string
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
	public static function fromServerPath(string $path)
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
	 * @return string|false
	 */
	public static function toServerPath(string $url)
	{
		$url = self::getCanonicalURL($url);
		if (!self::isInternalURL($url))
		{
			return false;
		}
		$root = $_SERVER['DOCUMENT_ROOT'] ?? rtrim(\RX_BASEDIR, '/');
		return Filters\FilenameFilter::cleanPath($root . parse_url($url, \PHP_URL_PATH));
	}

	/**
	 * Encode UTF-8 domain into IDNA (punycode)
	 *
	 * @param string $url
	 * @return string
	 */
	public static function encodeIdna(string $url): string
	{
		if (!preg_match('@^/(?!/)@', $url) && preg_match('@[:/#]@', $url))
		{
			$domain = parse_url($url, \PHP_URL_HOST);
			if (!$domain)
			{
				return $url;
			}
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
	public static function decodeIdna(string $url): string
	{
		if (!preg_match('@^/(?!/)@', $url) && preg_match('@[:/#]@', $url))
		{
			$domain = parse_url($url, \PHP_URL_HOST);
			if (!$domain)
			{
				return $url;
			}
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

		$domain = (string)$domain;
		if ($domain === '')
		{
			$new_domain = '';
		}
		elseif (function_exists('idn_to_utf8'))
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
