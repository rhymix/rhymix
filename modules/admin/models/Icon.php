<?php

namespace Rhymix\Modules\Admin\Models;

use Rhymix\Framework\Storage;

class Icon
{
	/**
	 * Get favicon URL for a domain.
	 *
	 * @param int $domain_srl
	 * @return string
	 */
	public static function getFaviconUrl(int $domain_srl = 0): string
	{
		return self::getIconUrl($domain_srl, 'favicon.ico');
	}

	/**
	 * Get mobile icon URL for a domain.
	 *
	 * @param int $domain_srl
	 * @return string
	 */
	public static function getMobiconUrl(int $domain_srl = 0): string
	{
		return self::getIconUrl($domain_srl, 'mobicon.png');
	}

	/**
	 * Check if an icon file exists, and if so, return its URL.
	 *
	 * @param int $domain_srl
	 * @param string $icon_name
	 * @return string
	 */
	public static function getIconUrl(int $domain_srl, string $icon_name): string
	{
		$filename = 'files/attach/xeicon/' . ($domain_srl ? ($domain_srl . '/') : '') . $icon_name;
		if (Storage::exists(\RX_BASEDIR . $filename))
		{
			return \RX_BASEURL . $filename . '?t=' . filemtime(\RX_BASEDIR . $filename);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get the default image for a domain.
	 *
	 * @param int $domain_srl
	 * @param int &$width
	 * @param int &$height
	 * @return string
	 */
	public static function getDefaultImageUrl(int $domain_srl = 0, &$width = 0, &$height = 0): string
	{
		$dir = 'files/attach/xeicon/' . ($domain_srl ? ($domain_srl . '/') : '');
		$info = Storage::readPHPData(\RX_BASEDIR . $dir . 'default_image.php');
		if ($info && Storage::exists(\RX_BASEDIR . $info['filename']))
		{
			$width = $info['width'];
			$height = $info['height'];
			return \RX_BASEURL . $info['filename'] . '?t=' . filemtime(\RX_BASEDIR . $info['filename']);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Save an icon for a domain.
	 *
	 * @param int $domain_srl
	 * @param string $icon_name
	 * @param array $fileinfo
	 * @return bool
	 */
	public static function saveIcon(int $domain_srl, string $icon_name, array $file_info): bool
	{
		$filename = 'files/attach/xeicon/' . ($domain_srl ? ($domain_srl . '/') : '') . $icon_name;
		if (file_exists($file_info['tmp_name']) && is_uploaded_file($file_info['tmp_name']))
		{
			return Storage::move($file_info['tmp_name'], \RX_BASEDIR . $filename);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Delete an icon for a domain.
	 *
	 * @param int $domain_srl
	 * @param string $icon_name
	 * @return bool
	 */
	public static function deleteIcon(int $domain_srl, string $icon_name): bool
	{
		$filename = 'files/attach/xeicon/' . ($domain_srl ? ($domain_srl . '/') : '') . $icon_name;
		if (Storage::exists(\RX_BASEDIR . $filename))
		{
			return Storage::delete(\RX_BASEDIR . $filename);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Save the default image for a domain.
	 *
	 * @param int $domain_srl
	 * @param array $file_info
	 * @return bool
	 */
	public static function saveDefaultImage(int $domain_srl, array $file_info): bool
	{
		$dir = 'files/attach/xeicon/' . ($domain_srl ? ($domain_srl . '/') : '');
		if (file_exists($file_info['tmp_name']) && is_uploaded_file($file_info['tmp_name']))
		{
			list($width, $height, $type) = @getimagesize($file_info['tmp_name']);
			switch ($type)
			{
				case 'image/gif': $target_filename = $dir . 'default_image.gif'; break;
				case 'image/jpeg': $target_filename = $dir . 'default_image.jpg'; break;
				case 'image/png': default: $target_filename = $dir . 'default_image.png';
			}
			if (Storage::move($file_info['tmp_name'], \RX_BASEDIR . $target_filename))
			{
				Storage::writePHPData(\RX_BASEDIR . $dir . 'default_image.php', [
					'filename' => $target_filename,
					'width' => $width,
					'height' => $height,
				]);
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Delete the default image for a domain.
	 *
	 * @param int $domain_srl
	 * @return bool
	 */
	public static function deleteDefaultImage(int $domain_srl): bool
	{
		$dir = 'files/attach/xeicon/' . ($domain_srl ? ($domain_srl . '/') : '');
		$info = Storage::readPHPData(\RX_BASEDIR . $dir . 'default_image.php');
		if ($info && $info['filename'])
		{
			Storage::delete(\RX_BASEDIR . $dir . 'default_image.php');
			Storage::delete(\RX_BASEDIR . $info['filename']);
			return true;
		}
		else
		{
			return false;
		}
	}}
