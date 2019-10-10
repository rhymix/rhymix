<?php

namespace Rhymix\Framework;

/**
 * The image class.
 */
class Image
{
	/**
	 * Check if a file is an image
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public static function isImage($filename)
	{
		return array_shift(explode('/', MIME::getContentType($filename))) === 'image';
	}
	
	/**
	 * Check if a file is an animated GIF.
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public static function isAnimatedGIF($filename)
	{
		if (MIME::getContentType($filename) !== 'image/gif')
		{
			return false;
		}
		if (!$fp = @fopen($filename, 'rb'))
		{
			return false;
		}
		$frames = 0;
		while (!feof($fp) && $frames < 2)
		{
			$frames += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', fread($fp, 1024 * 16) ?: '');
			if (!feof($fp))
			{
				fseek($fp, -9, SEEK_CUR);
			}
		}
		fclose($fp);
		return $frames > 1;
	}
	
	/**
	 * Get image information
	 * 
	 * @param string $filename
	 * @return array|false
	 */
	public static function getImageInfo($filename)
	{
		if (!self::isImage($filename))
		{
			return false;
		}
		if (!$image_info = @getimagesize($filename))
		{
			return false;
		}
		$img_type = [
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_JPEG2000 => 'jp2',
			IMAGETYPE_PNG => 'png',
			(defined('IMAGETYPE_WEBP') ? IMAGETYPE_WEBP : 18) => 'webp',
			IMAGETYPE_BMP => 'bmp',
			IMAGETYPE_PSD => 'psd',
			IMAGETYPE_ICO => 'ico',
		];
		return [
			'width' => $image_info[0],
			'height' => $image_info[1],
			'type' => $img_type[$image_info[2]],
			'bits' => $image_info['bits'] ?? null,
			'channels' => $image_info['channels'] ?? null,
		];
	}
}
