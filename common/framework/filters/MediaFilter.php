<?php

namespace Rhymix\Framework\Filters;

use Rhymix\Framework\Config;

/**
 * The media filter class.
 */
class MediaFilter
{
	/**
	 * Whitelists are cached here.
	 */
	protected static $_whitelist = [];

	/**
	 * Add a prefix to the iframe whitelist.
	 *
	 * @param string $prefix
	 * @param bool $permanently
	 * @return void
	 */
	public static function addPrefix(string $prefix, bool $permanently = false): void
	{
		if (!self::$_whitelist)
		{
			self::_loadWhitelists();
		}

		$prefix = self::formatPrefix($prefix);
		if (!in_array($prefix, self::$_whitelist))
		{
			self::$_whitelist[] = $prefix;
			natcasesort(self::$_whitelist);

			if ($permanently)
			{
				Config::set('mediafilter.whitelist', self::$_whitelist);
				Config::set('mediafilter.iframe', []);
				Config::set('mediafilter.object', []);
				Config::save();
			}
		}
	}

	/**
	 * Add a prefix to the object whitelist.
	 *
	 * @deprecated
	 * @param string $prefix
	 * @param bool $permanently
	 * @return void
	 */
	public static function addIframePrefix(string $prefix, bool $permanently = false): void
	{
		self::addPrefix($prefix, $permanently);
	}

	/**
	 * Add a prefix to the object whitelist.
	 *
	 * @deprecated
	 * @return void
	 */
	public static function addObjectPrefix(): void
	{

	}

	/**
	 * Format a prefix for standardization.
	 *
	 * @param string $prefix
	 * @return string
	 */
	public static function formatPrefix(string $prefix): string
	{
		$prefix = preg_match('@^(?:https?:)?//(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
		if (strpos($prefix, '/') === false)
		{
			$prefix .= '/';
		}
		return $prefix;
	}

	/**
	 * Get the iframe whitelist.
	 *
	 * @return array
	 */
	public static function getWhitelist(): array
	{
		if (!self::$_whitelist)
		{
			self::_loadWhitelists();
		}
		return self::$_whitelist;
	}

	/**
	 * Get the iframe whitelist as a regular expression.
	 *
	 * @return string
	 */
	public static function getWhitelistRegex(): string
	{
		if (!self::$_whitelist)
		{
			self::_loadWhitelists();
		}
		$result = array();
		foreach(self::$_whitelist as $domain)
		{
			$result[] = str_replace('\*\.', '[a-z0-9-]+\.', preg_quote($domain, '%'));
		}
		return '%^(?:https?:)?//(' . implode('|', $result) . ')%';
	}

	/**
	 * Check if a URL matches the iframe whitelist.
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function matchWhitelist(string $url): bool
	{
		return preg_match(self::getWhitelistRegex(), $url) ? true : false;
	}

	/**
	 * Remove embedded media from HTML content.
	 *
	 * @param string $input
	 * @param string $replacement
	 * @return string
	 */
	public static function removeEmbeddedMedia(string $input, string $replacement = ''): string
	{
		$input = preg_replace('!<object[^>]*>(.*?</object>)?!is', $replacement, $input);
		$input = preg_replace('!<embed[^>]*>(.*?</embed>)?!is', $replacement, $input);
		$input = preg_replace('!<img[^>]*editor_component="multimedia_link"[^>]*>(.*?</img>)?!is', $replacement, $input);
		return (string)$input;
	}

	/**
	 * Load whitelists.
	 *
	 * @param array $custom_whitelist
	 * @return void
	 */
	protected static function _loadWhitelists(array $custom_whitelist = []): void
	{
		$default_whitelist = (include \RX_BASEDIR . 'common/defaults/whitelist.php');
		self::$_whitelist = [];

		if($custom_whitelist)
		{
			if(!isset($custom_whitelist['iframe']) || !isset($custom_whitelist['object']))
			{
				$custom_whitelist = array(
					'iframe' => isset($custom_whitelist->iframe) ? $custom_whitelist->iframe : array(),
					'object' => isset($custom_whitelist->object) ? $custom_whitelist->object : array(),
				);
			}
			foreach ($custom_whitelist['iframe'] as $prefix)
			{
				self::$_whitelist[] = self::formatPrefix($prefix);
			}
			foreach ($custom_whitelist['object'] as $prefix)
			{
				self::$_whitelist[] = self::formatPrefix($prefix);
			}
		}
		else
		{
			foreach ($default_whitelist as $prefix)
			{
				self::$_whitelist[] = $prefix;
			}
			if ($whitelist = config('mediafilter.whitelist'))
			{
				foreach ($whitelist as $prefix)
				{
					self::$_whitelist[] = self::formatPrefix($prefix);
				}
			}
			else
			{
				if ($whitelist = config('mediafilter.iframe') ?: config('embedfilter.iframe'))
				{
					foreach ($whitelist as $prefix)
					{
						self::$_whitelist[] = self::formatPrefix($prefix);
					}
				}
				if ($whitelist = config('mediafilter.object') ?: config('embedfilter.object'))
				{
					foreach ($whitelist as $prefix)
					{
						self::$_whitelist[] = self::formatPrefix($prefix);
					}
				}
			}
		}

		self::$_whitelist = array_unique(self::$_whitelist);
		natcasesort(self::$_whitelist);
	}

	/**
	 * ========================== DEPRECATED METHODS ==========================
	 * ============== KEPT FOR COMPATIBILITY WITH OLDER VERSIONS ==============
	 */

	/**
	 * Get the iframe whitelist.
	 *
	 * @deprecated
	 * @return array
	 */
	public static function getIframeWhitelist(): array
	{
		return self::getWhitelist();
	}

	/**
	 * Get the iframe whitelist as a regular expression.
	 *
	 * @deprecated
	 * @return string
	 */
	public static function getIframeWhitelistRegex(): string
	{
		return self::getWhitelistRegex();
	}

	/**
	 * Check if a URL matches the iframe whitelist.
	 *
	 * @deprecated
	 * @param string $url
	 * @return bool
	 */
	public static function matchIframeWhitelist(string $url): bool
	{
		return self::matchWhitelist($url);
	}

	/**
	 * Get the object whitelist.
	 *
	 * @deprecated
	 * @return array
	 */
	public static function getObjectWhitelist(): array
	{
		return self::getWhitelist();
	}

	/**
	 * Get the object whitelist as a regular expression.
	 *
	 * @deprecated
	 * @return string
	 */
	public static function getObjectWhitelistRegex(): string
	{
		return self::getWhitelistRegex();
	}

	/**
	 * Check if a URL matches the iframe whitelist.
	 *
	 * @deprecated
	 * @param string $url
	 * @return bool
	 */
	public static function matchObjectWhitelist(string $url): bool
	{
		return self::matchWhitelist($url);
	}
}
