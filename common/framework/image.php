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
		return array_shift(explode('/', Storage::getContentType($filename))) === 'image';
	}
	
	/**
	 * Check if a file is an animated GIF.
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public static function isAnimatedGIF($filename)
	{
		if (Storage::getContentType($filename) !== 'image/gif')
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
		return [
			'width' => $image_info[0],
			'height' => $image_info[1],
			'type' => image_type_to_extension($image_info[2], false),
			'mime' => $image_info['mime'],
			'bits' => $image_info['bits'] ?? null,
			'channels' => $image_info['channels'] ?? null,
		];
	}
}
