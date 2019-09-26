<?php

namespace Rhymix\Framework;

/**
 * The image class.
 */
class Image
{
	/**
	 * Check Check if file is an image
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public static function isImage($filename)
	{
		return strtolower(array_shift(explode('/', @mime_content_type($filename)))) === 'image';
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
			'bits' => $image_info['bits'],
			'channels' => $image_info['channels'],
			'mime' => $image_info['mime'],
		];
	}
}
