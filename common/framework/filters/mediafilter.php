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
	 * @parsm bool $permanently
	 * @return void
	 */
	public static function addPrefix($prefix, $permanently = false)
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
	 */
	public static function addIframePrefix($prefix, $permanently = false)
	{
		self::addPrefix($prefix, $permanently);
	}
	
	/**
	 * Add a prefix to the object whitelist.
	 * 
	 * @deprecated
	 */
	public static function addObjectPrefix()
	{
		
	}
	
	/**
	 * Format a prefix for standardization.
	 * 
	 * @param string $prefix
	 * @return string
	 */
	public static function formatPrefix($prefix)
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
	public static function getWhitelist()
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
	public static function getWhitelistRegex()
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
	public static function matchWhitelist($url)
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
	public static function removeEmbeddedMedia($input, $replacement = '')
	{
		$input = preg_replace('!<object[^>]*>(.*?</object>)?!is', $replacement, $input);
		$input = preg_replace('!<embed[^>]*>(.*?</embed>)?!is', $replacement, $input);
		$input = preg_replace('!<img[^>]*editor_component="multimedia_link"[^>]*>(.*?</img>)?!is', $replacement, $input);
		return $input;
	}
	
	/**
	 * Load whitelists.
	 * 
	 * @param array $custom_whitelist
	 * @return void
	 */
	protected static function _loadWhitelists($custom_whitelist = array())
	{
		$default_whitelist = (include \RX_BASEDIR . 'common/defaults/whitelist.php');
		self::$_whitelist = [];
		
		if($custom_whitelist)
		{
			if(!is_array($custom_whitelist) || !isset($custom_whitelist['iframe']) || !isset($custom_whitelist['object']))
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
	public static function getIframeWhitelist()
	{
		return self::getWhitelist();
	}
	
	/**
	 * Get the iframe whitelist as a regular expression.
	 * 
	 * @deprecated
	 * @return string
	 */
	public static function getIframeWhitelistRegex()
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
	public static function matchIframeWhitelist($url)
	{
		return self::matchWhitelist($url);
	}
	
	/**
	 * Get the object whitelist.
	 * 
	 * @deprecated
	 * @return array
	 */
	public static function getObjectWhitelist()
	{
		return self::getWhitelist();
	}
	
	/**
	 * Get the object whitelist as a regular expression.
	 * 
	 * @deprecated
	 * @return string
	 */
	public static function getObjectWhitelistRegex()
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
	public static function matchObjectWhitelist($url)
	{
		return self::matchWhitelist($url);
	}
}
