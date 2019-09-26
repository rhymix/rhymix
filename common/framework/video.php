<?php

namespace Rhymix\Framework;

/**
 * The video class.
 */
class Video
{
	/**
	 * Check Check if file is a video
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public static function isVideo($filename)
	{
		return strtolower(array_shift(explode('/', @mime_content_type($filename)))) === 'video';
	}
}
