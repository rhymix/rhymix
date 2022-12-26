<?php

namespace Rhymix\Modules\Admin\Models;

use Rhymix\Framework\Storage;

class Icon
{
	/**
	 * Get favicon URL for a domain.
	 * 
	 * @param int $domain_srl
	 * @return string|false
	 */
	public static function getFaviconUrl($domain_srl = 0)
	{
		return self::getIconUrl($domain_srl, 'favicon.ico');
	}

	/**
	 * Get mobile icon URL for a domain.
	 * 
	 * @param int $domain_srl
	 * @return string|false
	 */
	public static function getMobiconUrl($domain_srl = 0)
	{
		return self::getIconUrl($domain_srl, 'mobicon.png');
	}
	
	/**
	 * Get the default image for a domain.
	 * 
	 * @param int $domain_srl
	 * @param int &$width
	 * @param int &$height
	 * @return string|false
	 */
	public static function getDefaultImageUrl($domain_srl = 0, &$width = 0, &$height = 0)
	{
		$domain_srl = intval($domain_srl);
		if ($domain_srl)
		{
			$virtual_site = $domain_srl . '/';
		}
		else
		{
			$virtual_site = '';
		}
		
		$info = Storage::readPHPData(\RX_BASEDIR . 'files/attach/xeicon/' . $virtual_site . 'default_image.php');
		if ($info && Storage::exists(\RX_BASEDIR . $info['filename']))
		{
			$width = $info['width'];
			$height = $info['height'];
			return \RX_BASEURL . $info['filename'] . '?' . date('YmdHis', filemtime(\RX_BASEDIR . $info['filename']));
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check if an icon file exists, and if so, return its URL.
	 * 
	 * @param int $domain_srl
	 * @param string $filename
	 * @return string|false
	 */
	public static function getIconUrl($domain_srl, $filename)
	{
		$domain_srl = intval($domain_srl);
		$filename = 'files/attach/xeicon/' . ($domain_srl ? ($domain_srl . '/') : '') . $filename;
		if (Storage::exists(\RX_BASEDIR . $filename))
		{
			return \RX_BASEURL . $filename . '?' . date('YmdHis', filemtime(\RX_BASEDIR . $filename));
		}
		else
		{
			return false;
		}
	}
}
