<?php

namespace Rhymix\Framework\Security;

/**
 * The media filter class.
 */
class MediaFilter
{
	/**
	 * Whitelists are cached here.
	 */
	protected static $_iframe_whitelist;
	protected static $_object_whitelist;
	
	/**
	 * Get the iframe whitelist.
	 * 
	 * @return string
	 */
	public static function getIframeWhitelist()
	{
		if (!count(self::$_iframe_whitelist))
		{
			self::_loadWhitelists();
		}
		return self::$_iframe_whitelist;
	}
	
	/**
	 * Get the iframe whitelist as a regular expression.
	 * 
	 * @return string
	 */
	public static function getIframeWhitelistRegex()
	{
		if (!count(self::$_iframe_whitelist))
		{
			self::_loadWhitelists();
		}
		$result = array();
		foreach(self::$_iframe_whitelist as $domain)
		{
			$result[] = preg_quote($domain, '%');
		}
		return '%^https?://(' . implode('|', $result) . ')%';
	}
	
	/**
	 * Get the object whitelist.
	 * 
	 * @return string
	 */
	public static function getObjectWhitelist()
	{
		if (!count(self::$_object_whitelist))
		{
			self::_loadWhitelists();
		}
		return self::$_object_whitelist;
	}
	
	/**
	 * Get the object whitelist as a regular expression.
	 * 
	 * @return string
	 */
	public static function getObjectWhitelistRegex()
	{
		if (!count(self::$_object_whitelist))
		{
			self::_loadWhitelists();
		}
		$result = array();
		foreach(self::$_object_whitelist as $domain)
		{
			$result[] = preg_quote($domain, '%');
		}
		return '%^https?://(' . implode('|', $result) . ')%';
	}
	
	/**
	 * Check if a URL matches the iframe whitelist.
	 * 
	 * @param string $url
	 * @return bool
	 */
	public static function matchIframeWhitelist($url)
	{
		return preg_match(self::getIframeWhitelistRegex(), $url) ? true : false;
	}
	
	/**
	 * Check if a URL matches the iframe whitelist.
	 * 
	 * @param string $url
	 * @return bool
	 */
	public static function matchObjectWhitelist($url)
	{
		return preg_match(self::getObjectWhitelistRegex(), $url) ? true : false;
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
		$default_whitelist = (include RX_BASEDIR . 'common/defaults/whitelist.php');
		self::$_object_whitelist = array();
		self::$_iframe_whitelist = array();
		
		if(count($custom_whitelist))
		{
			if(!is_array($custom_whitelist) || !isset($custom_whitelist['iframe']) || !isset($custom_whitelist['object']))
			{
				$whitelist = array(
					'iframe' => isset($whitelist->iframe) ? $whitelist->iframe : array(),
					'object' => isset($whitelist->object) ? $whitelist->object : array(),
				);
			}
			foreach ($custom_whitelist['iframe'] as $prefix)
			{
				self::$_iframe_whitelist[] = preg_match('@^https?://(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
			}
			foreach ($custom_whitelist['object'] as $prefix)
			{
				self::$_object_whitelist[] = preg_match('@^https?://(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
			}
		}
		else
		{
			foreach ($default_whitelist['iframe'] as $prefix)
			{
				self::$_iframe_whitelist[] = $prefix;
			}
			foreach ($default_whitelist['object'] as $prefix)
			{
				self::$_object_whitelist[] = $prefix;
			}
			if ($iframe_whitelist = config('mediafilter.iframe') ?: config('embedfilter.iframe'))
			{
				foreach ($iframe_whitelist as $prefix)
				{
					self::$_iframe_whitelist[] = preg_match('@^https?://(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
				}
			}
			if ($object_whitelist = config('mediafilter.object') ?: config('embedfilter.object'))
			{
				foreach ($object_whitelist as $prefix)
				{
					self::$_object_whitelist[] = preg_match('@^https?://(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
				}
			}
		}
		
		self::$_object_whitelist = array_unique(self::$_object_whitelist);
		self::$_iframe_whitelist = array_unique(self::$_iframe_whitelist);
		natcasesort(self::$_object_whitelist);
		natcasesort(self::$_iframe_whitelist);
	}
}
