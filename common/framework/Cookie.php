<?php

namespace Rhymix\Framework;

/**
 * The cookie class.
 */
class Cookie
{
	/**
	 * Get a cookie.
	 *
	 * @param string $name
	 * @return ?string
	 */
	public static function get(string $name): ?string
	{
		return isset($_COOKIE[$name]) ? strval($_COOKIE[$name]) : null;
	}

	/**
	 * Set a cookie.
	 *
	 * Options may contain the following keys:
	 *   - expires (days or Unix timestamp)
	 *   - path
	 *   - domain
	 *   - secure
	 *   - httponly
	 *   - samesite
	 *
	 * Missing options will be replaced with Rhymix security configuration
	 * where applicable, e.g. secure and samesite.
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $options
	 * @return bool
	 */
	public static function set(string $name, string $value, array $options = []): bool
	{
		// Normalize samesite and sameSite, httponly and httpOnly.
		$options = array_change_key_case($options, \CASE_LOWER);

		// Convert the expires timestamp.
		$options['expires'] = $options['expires'] ?? 0;
		if ($options['expires'] < 0)
		{
			$options['expires'] = time() - (366 * 86400);
		}
		elseif ($options['expires'] > 0 && $options['expires'] < 36500)
		{
			$options['expires'] = time() + ($options['expires'] * 86400);
		}
		else
		{
			// Session cookie or Unix timestamp, no change
		}

		// Set defaults.
		if (!array_key_exists('path', $options))
		{
			$options['path'] = config('cookie.path') ?? '/';
		}
		if (!array_key_exists('domain', $options) && ($default_domain = config('cookie.domain')))
		{
			$options['domain'] = $default_domain;
		}
		if (!isset($options['secure']))
		{
			$options['secure'] = \RX_SSL && !!config('session.use_ssl_cookies');
		}
		if (!isset($options['httponly']))
		{
			$options['httponly'] = config('cookie.httponly') ?? false;
		}
		if (!isset($options['samesite']))
		{
			$options['samesite'] = config('cookie.samesite') ?? 'Lax';
		}

		$result = setcookie($name, $value, $options);

		// Make the cookie immediately available server-side.
		if ($result && $options['expires'] >= 0)
		{
			$_COOKIE[$name] = $value;
		}
		return $result;
	}

	/**
	 * Delete a cookie.
	 *
	 * You must pass an options array with the same values that were used to
	 * create the cookie, except 'expires' which doesn't apply here.
	 *
	 * @param string $name
	 * @param array $options
	 * @return bool
	 */
	public static function remove(string $name, array $options = []): bool
	{
		// Setting the expiry date to a negative number will take care of it.
		$options['expires'] = -1;
		$result = self::set($name, '', $options);

		// Make the cookie immediately unavailable server-side.
		if ($result)
		{
			unset($_COOKIE[$name]);
		}
		return $result;
	}
}
