<?php

/**
 * Contains methods for accessing file system
 *
 * @author NAVER (developers@xpressengine.com)
 */
class FileHandler
{
	/**
	 * Changes path of target file, directory into absolute path
	 *
	 * @param string $source path to change into absolute path
	 * @return string Absolute path
	 */
	public static function getRealPath($source): string
	{
		if (strncmp($source, './', 2) === 0)
		{
			return \RX_BASEDIR . substr($source, 2);
		}
		elseif (preg_match('@^(?:/|[a-z]:[\\\\/]|\\\\|https?:)@i', $source))
		{
			return (string)$source;
		}
		else
		{
			return \RX_BASEDIR . $source;
		}
	}

	/**
	 * Copy a directory to target
	 *
	 * If target directory does not exist, this function creates it
	 *
	 * @param string $source_dir Path of source directory
	 * @param string $target_dir Path of target dir
	 * @param string $filter Regex to filter files. If file matches this regex, the file is not copied.
	 * @return void
	 */
	public static function copyDir($source_dir, $target_dir, $filter = '')
	{
		return Rhymix\Framework\Storage::copyDirectory(self::getRealPath($source_dir), self::getRealPath($target_dir), $filter ?: '');
	}

	/**
	 * Copy a file to target
	 *
	 * @param string $source Path of source file
	 * @param string $target Path of target file
	 * @param string $force Y: overwrite
	 * @return void
	 */
	public static function copyFile($source, $target, $force = 'Y')
	{
		setlocale(LC_CTYPE, 'en_US.UTF8', 'ko_KR.UTF8');
		return Rhymix\Framework\Storage::copy(self::getRealPath($source), self::getRealPath($target));
	}

	/**
	 * Returns the content of the file
	 *
	 * @param string $filename Path of target file
	 * @return string The content of the file. If target file does not exist, this function returns nothing.
	 */
	public static function readFile($filename)
	{
		return Rhymix\Framework\Storage::read(self::getRealPath($filename));
	}

	/**
	 * Write $buff into the specified file
	 *
	 * @param string $filename Path of target file
	 * @param string $buff Content to be written
	 * @param string $mode a(append) / w(write)
	 * @return false|string
	 */
	public static function writeFile($filename, $buff, $mode = "w")
	{
		if (!$filename)
		{
			return false;
		}

		return Rhymix\Framework\Storage::write(self::getRealPath($filename), (string)$buff, (string)$mode);
	}

	/**
	 * Remove a file
	 *
	 * @param string $filename path of target file
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public static function removeFile($filename)
	{
		if (!$filename)
		{
			return false;
		}

		return Rhymix\Framework\Storage::delete(self::getRealPath($filename));
	}

	/**
	 * Rename a file
	 *
	 * In order to move a file, use this function.
	 *
	 * @param string $source Path of source file
	 * @param string $target Path of target file
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public static function rename($source, $target)
	{
		if (!$source || !$target)
		{
			return false;
		}

		return Rhymix\Framework\Storage::move(self::getRealPath($source), self::getRealPath($target));
	}

	/**
	 * Move a file
	 *
	 * @param string $source Path of source file
	 * @param string $target Path of target file
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public static function moveFile($source, $target)
	{
		if (!$source || !$target)
		{
			return false;
		}

		return Rhymix\Framework\Storage::move(self::getRealPath($source), self::getRealPath($target));
	}

	/**
	 * Move a directory
	 *
	 * This function just wraps rename function.
	 *
	 * @param string $source_dir Path of source directory
	 * @param string $target_dir Path of target directory
	 * @return void
	 */
	public static function moveDir($source_dir, $target_dir)
	{
		if (!$source_dir || !$target_dir)
		{
			return false;
		}

		return Rhymix\Framework\Storage::move(self::getRealPath($source_dir), self::getRealPath($target_dir));
	}

	/**
	 * Return list of the files in the path
	 *
	 * The array does not contain files, such as '.', '..', and files starting with '.'
	 *
	 * @param string $path Path of target directory
	 * @param string $filter If specified, return only files matching with the filter
	 * @param bool $to_lower If TRUE, file names will be changed into lower case.
	 * @param bool $concat_prefix If TRUE, return file name as absolute path
	 * @return string[] Array of the filenames in the path
	 */
	public static function readDir($path, $filter = '', $to_lower = FALSE, $concat_prefix = FALSE)
	{
		$list = Rhymix\Framework\Storage::readDirectory(self::getRealPath($path), (bool)$concat_prefix, true, false);
		if (!$list)
		{
			return array();
		}

		$output = array();
		foreach ($list as $filename)
		{
			$filename = str_replace(array('\\', '//'), '/', $filename);
			$basename = $concat_prefix ? basename($filename) : $filename;
			if ($basename[0] === '.' || ($filter && !preg_match($filter, $basename)))
			{
				continue;
			}
			if ($to_lower)
			{
				$filename = strtolower($filename);
			}
			if($filter)
			{
				$filename = preg_replace($filter, '$1', $filename);
			}
			$output[] = $filename;
		}
		return $output;
	}

	/**
	 * Creates a directory
	 *
	 * This function creates directories recursively, which means that if ancestors of the target directory does not exist, they will be created too.
	 *
	 * @param string $path_string Path of target directory
	 * @return bool TRUE if success. It might return nothing when ftp is used and connection to the ftp address failed.
	 */
	public static function makeDir($path_string)
	{
		if (!$path_string)
		{
			return false;
		}

		$path = self::getRealPath($path_string);
		return Rhymix\Framework\Storage::isDirectory($path) || Rhymix\Framework\Storage::createDirectory($path);
	}

	/**
	 * Remove all files under the path
	 *
	 * @param string $path Path of the target directory
	 * @return void
	 */
	public static function removeDir($path)
	{
		if (!$path)
		{
			return false;
		}

		return Rhymix\Framework\Storage::deleteDirectory(self::getRealPath($path));
	}

	/**
	 * Remove a directory only if it is empty
	 *
	 * @param string $path Path of the target directory
	 * @return void
	 */
	public static function removeBlankDir($path)
	{
		if (!$path)
		{
			return false;
		}

		return Rhymix\Framework\Storage::deleteEmptyDirectory(self::getRealPath($path), false);
	}

	/**
	 * Remove files in the target directory
	 *
	 * This function keeps the directory structure.
	 *
	 * @param string $path Path of the target directory
	 * @return void
	 */
	public static function removeFilesInDir($path)
	{
		if (!$path)
		{
			return false;
		}

		return Rhymix\Framework\Storage::deleteDirectory(self::getRealPath($path), false);
	}

	/**
	 * Makes file size byte into KB, MB according to the size
	 *
	 * @see self::returnBytes()
	 * @param int $size Number of the size
	 * @return string File size string
	 */
	public static function filesize($size)
	{
		if(!$size)
		{
			return '0Byte';
		}

		if($size === 1)
		{
			return '1Byte';
		}

		if($size < 1024)
		{
			return $size . 'Bytes';
		}

		if($size >= 1024 && $size < (1024 * 1024))
		{
			return sprintf("%0.1fKB", $size / 1024);
		}

		if($size >= (1024 * 1024) && $size < (1024 * 1024 * 1024))
		{
			return sprintf("%0.2fMB", $size / (1024 * 1024));
		}

		if($size >= (1024 * 1024 * 1024) && $size < (1024 * 1024 * 1024 * 1024))
		{
			return sprintf("%0.2fGB", $size / (1024 * 1024 * 1024));
		}

		return sprintf("%0.2fTB", $size / (1024 * 1024 * 1024 * 1024));
	}

	/**
	 * Return remote file's content via HTTP
	 *
	 * @param string $url The address of the target file
	 * @param string $body HTTP request body
	 * @param int $timeout Connection timeout
	 * @param string $method GET/POST
	 * @param string $content_type Content type header of HTTP request
	 * @param string[] $headers Headers key value array.
	 * @param string[] $cookies Cookies key value array.
	 * @param string $post_data Request arguments array for POST method
	 * @param array $settings Any additional settings
	 * @return string If success, the content of the target file. Otherwise: none
	 */
	public static function getRemoteResource($url, $body = null, $timeout = 3, $method = 'GET', $content_type = null, $headers = array(), $cookies = array(), $post_data = array(), $settings = array())
	{
		// This method converts erroneously escaped ampersands. The HTTP class doesn't.
		$url = str_replace('&amp;', '&', $url);

		// Convert backward-compatible parameters to a format accepted by the HTTP class.
		$converted_headers = [];
		$converted_cookies = [];
		$converted_settings = ['timeout' => $timeout];

		// Add headers.
		foreach ($headers as $key => $val)
		{
			$converted_headers[strval($key)] = strval($val);
		}

		// Add cookies.
		$host = parse_url($url, PHP_URL_HOST);
		if (isset($cookies[$host]) && is_array($cookies[$host]))
		{
			foreach($cookies[$host] as $key => $val)
			{
				$converted_cookies[strval($key)] = strval($val);
			}
		}

		// Add the request body and its content type.
		if ($content_type)
		{
			$converted_headers['Content-Type'] = $content_type;
		}

		// Add other settings, converting old format to the new format as necessary.
		foreach ($settings as $key => $val)
		{
			if ($key === 'filename')
			{
				$converted_settings['sink'] = $val;
			}
			elseif ($key === 'follow_redirects')
			{
				$converted_settings['allow_redirects'] = true;
			}
			elseif ($key === 'redirects' && $val > 0)
			{
				$converted_settings['allow_redirects'] = ['max' => intval($val)];
			}
			elseif ($key === 'useragent')
			{
				$converted_headers['User-Agent'] = $val;
			}
			else
			{
				$converted_settings[$key] = $val;
			}
		}

		// Pass to HTTP::request(), using Guzzle behind the scenes.
		$response = \Rhymix\Framework\HTTP::request($url, strval($method),
			$body ?: $post_data,
			$converted_headers,
			$converted_cookies,
			$converted_settings
		);

		// Return the response body.
		if ($response->getStatusCode() === 200)
		{
			if (isset($converted_settings['sink']))
			{
				return true;
			}
			else
			{
				return $response->getBody()->getContents();
			}
		}
		else
		{
			return null;
		}
	}

	/**
	 * Retrieves remote file, then stores it into target path.
	 *
	 * @param string $url The address of the target file
	 * @param string $target_filename The location to store
	 * @param string $body HTTP request body
	 * @param string $timeout Connection timeout
	 * @param string $method GET/POST
	 * @param string $content_type Content type header of HTTP request
	 * @param string[] $headers Headers key value array.
	 * @return bool TRUE: success, FALSE: failed
	 */
	public static function getRemoteFile($url, $target_filename, $body = null, $timeout = 3, $method = 'GET', $content_type = null, $headers = array(), $cookies = array(), $post_data = array(), $request_config = array())
	{
		$target_dirname = dirname($target_filename);
		if (!Rhymix\Framework\Storage::isDirectory($target_dirname) && !Rhymix\Framework\Storage::createDirectory($target_dirname))
		{
			return false;
		}

		$request_config['sink'] = $target_filename;
		$success = self::getRemoteResource($url, $body, $timeout, $method, $content_type, $headers, $cookies, $post_data, $request_config);
		return $success ? true : false;
	}

	/**
	 * Convert size in string into numeric value
	 *
	 * @see self::filesize()
	 * @param $val Size in string (ex., 10, 10K, 10M, 10G )
	 * @return int converted size
	 */
	public static function returnBytes($val)
	{
		$val = preg_replace('/[^0-9\.PTGMK]/', '', $val);
		$unit = strtoupper(substr($val, -1));
		$val = (float)$val;

		switch ($unit)
		{
			case 'P': $val *= 1024;
			case 'T': $val *= 1024;
			case 'G': $val *= 1024;
			case 'M': $val *= 1024;
			case 'K': $val *= 1024;
		}

		return round($val);
	}

	/**
	 * Check available memory to load image file
	 *
	 * @param array $imageInfo Image info retrieved by getimagesize function
	 * @return bool TRUE: it's ok, FALSE: otherwise
	 */
	public static function checkMemoryLoadImage(&$imageInfo)
	{
		$K64 = 65536;
		$TWEAKFACTOR = 2.0;
		$channels = $imageInfo['channels'];
		if(!$channels)
		{
			$channels = 6; //for png
		}
		$memoryNeeded = round(($imageInfo[0] * $imageInfo[1] * $imageInfo['bits'] * $channels / 8 + $K64 ) * $TWEAKFACTOR);
		$memoryLimit = ini_get('memory_limit');
		if($memoryLimit <= 0)
		{
			return true;
		}
		$memoryLimit = self::returnBytes($memoryLimit);
		if($memoryLimit <= 0)
		{
			return true;
		}
		$availableMemory = $memoryLimit - memory_get_usage();
		if($availableMemory < $memoryNeeded)
		{
			return false;
		}
		return true;
	}

	/**
	 * Check if image needs rotation
	 *
	 * @param string $filename
	 * @return int|bool 0, 90, 180, 360, or false
	 */
	public static function checkImageRotation(string $filename)
	{
		if (function_exists('exif_read_data'))
		{
			$exif = @exif_read_data($filename);
			if($exif && isset($exif['Orientation']))
			{
				switch ($exif['Orientation'])
				{
					case 3: return 180;
					case 6: return 270;
					case 8: return 90;
					default: return 0;
				}
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
	 * Moves an image file (resizing is possible)
	 *
	 * @param string $source_file Path of the source file
	 * @param string $target_file Path of the target file
	 * @param int $resize_width Width to resize
	 * @param int $resize_height Height to resize
	 * @param string $target_type If $target_type is set (gif, jpg, png, bmp), result image will be saved as target type
	 * @param string $thumbnail_type Thumbnail type(crop, ratio, stretch, fill, center)
	 * @param int $quality Compression ratio (0~100)
	 * @param int $rotate Rotation degrees (0~360)
	 * @return bool TRUE: success, FALSE: failed
	 */
	public static function createImageFile($source_file, $target_file, $resize_width = 0, $resize_height = 0, $target_type = '', $thumbnail_type = 'fill', $quality = 100, $rotate = 0)
	{
		// check params
		if (($source_file = self::exists($source_file)) === FALSE)
		{
			return false;
		}

		// sanitize params
		$target_file = self::getRealPath($target_file);
		$resize_width = intval($resize_width);
		$resize_height = $resize_height === 'auto' ? 'auto' : intval($resize_height);
		if(!$resize_width)
		{
			$resize_width = 100;
		}
		if(!$resize_height)
		{
			$resize_height = $resize_width;
		}

		// retrieve source image's information
		$imageInfo = getimagesize($source_file);
		if(!self::checkMemoryLoadImage($imageInfo))
		{
			return false;
		}

		list($width, $height, $type) = $imageInfo;
		if($width < 1 || $height < 1)
		{
			return false;
		}

		$typemap = array(
			1 => 'gif',
			2 => 'jpg',
			3 => 'png',
			6 => 'bmp',
			18 => 'webp',
		);

		$type = isset($typemap[$type]) ? $typemap[$type] : null;
		if (!$type)
		{
			return false;
		}

		$target_type = strtolower($target_type ?: $type);
		if ($target_type === 'jpeg')
		{
			$target_type = 'jpg';
		}

		// Automatically set resize_height if it is 'auto'
		if ($resize_height === 'auto')
		{
			$resize_height = round($resize_width / ($width / $height));
		}

		// create temporary image having original type
		if ($type === 'gif' && function_exists('imagecreatefromgif'))
		{
			$source = @imagecreatefromgif($source_file);
		}
		elseif ($type === 'jpg' && function_exists('imagecreatefromjpeg'))
		{
			$source = @imagecreatefromjpeg($source_file);
		}
		elseif ($type === 'png' && function_exists('imagecreatefrompng'))
		{
			$source = @imagecreatefrompng($source_file);
		}
		elseif ($type === 'bmp' && function_exists('imagecreatefrombmp'))
		{
			$source = @imagecreatefrombmp($source_file);
		}
		elseif ($type === 'webp' && function_exists('imagecreatefromwebp') && !Rhymix\Framework\Image::isAnimatedWebP($source_file))
		{
			$source = @imagecreatefromwebp($source_file);
		}
		else
		{
			return false;
		}
		if (!$source)
		{
			return false;
		}

		// Rotate image
		if ($rotate)
		{
			$source = imagerotate($source, $rotate, 0);
			$width = imagesx($source);
			$height = imagesy($source);
		}

		// If resize not needed, skip thumbnail generation
		if ($width == $resize_width && $height == $resize_height)
		{
			$resize_needed = false;
			$thumb = &$source;
		}
		else
		{
			$resize_needed = true;
			$thumb = imagecreatetruecolor($resize_width, $resize_height);
			if (!$thumb)
			{
				return false;
			}

			if ($target_type == 'png')
			{
				imagefill($thumb, 0, 0, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
				imagealphablending($thumb, true);
				imagesavealpha($thumb, true);
			}
			else
			{
				imagefilledrectangle($thumb, 0, 0, $resize_width - 1, $resize_height - 1, imagecolorallocate($thumb, 255, 255, 255));
			}
		}

		// Resize the original image and copy it to a temporary image
		if ($resize_needed)
		{
			// Calculate the size ratio
			$ratio_x = ($resize_width > 0 && $width > 0) ? $resize_width / $width : 1;
			$ratio_y = ($resize_height > 0 && $height > 0) ? $resize_height / $height : 1;
			$ratio_max = max($ratio_x, $ratio_y);
			$ratio_min = min($ratio_x, $ratio_y);

			// Determine the X-Y coordinates to copy
			if ($thumbnail_type === 'stretch')
			{
				$dst_width = $resize_width;
				$dst_height = $resize_height;
			}
			elseif ($thumbnail_type === 'fill')
			{
				$dst_width = round($width * $ratio_max);
				$dst_height = round($height * $ratio_max);
			}
			elseif ($thumbnail_type === 'center')
			{
				$dst_width = $width;
				$dst_height = $height;
			}
			elseif ($thumbnail_type === 'ratio')
			{
				$dst_width = round($width * $ratio_min);
				$dst_height = round($height * $ratio_min);
			}
			else // crop
			{
				$dst_width = round($width * min(1, $ratio_max));
				$dst_height = round($height * min(1, $ratio_max));
			}

			// Adjust for small margins of error
			if (abs($resize_width - $dst_width) < ($resize_width * 0.02)) $dst_width = $resize_width;
			if (abs($resize_height - $dst_height) < ($resize_height * 0.02)) $dst_height = $resize_height;
			$dst_x = round(($resize_width - $dst_width) / 2);
			$dst_y = round(($resize_height - $dst_height) / 2);

			imagecopyresampled($thumb, $source, $dst_x, $dst_y, 0, 0, $dst_width, $dst_height, $width, $height);
		}

		// create directory
		self::makeDir(dirname($target_file));

		// write into the file
		if ($target_type === 'gif' && function_exists('imagegif'))
		{
			$output = imagegif($thumb, $target_file);
		}
		elseif ($target_type === 'jpg' && function_exists('imagejpeg'))
		{
			$output = imagejpeg($thumb, $target_file, $quality);
		}
		elseif ($target_type === 'png' && function_exists('imagepng'))
		{
			$output = imagepng($thumb, $target_file, 9);
		}
		elseif ($target_type === 'bmp' && function_exists('imagebmp'))
		{
			$output = imagebmp($thumb, $target_file);
		}
		elseif ($target_type === 'webp' && function_exists('imagewebp'))
		{
			$output = imagewebp($thumb, $target_file);
		}
		else
		{
			return false;
		}

		imagedestroy($thumb);
		imagedestroy($source);
		@chmod($target_file, 0666 & ~Rhymix\Framework\Storage::getUmask());
		return $output;
	}

	/**
	 * Reads ini file, and puts result into array
	 *
	 * @deprecated
	 * @see self::writeIniFile()
	 * @param string $filename Path of the ini file
	 * @return array ini array (if the target file does not exist, it returns FALSE)
	 */
	public static function readIniFile($filename)
	{
		$filename = (string)$filename;
		if(!Rhymix\Framework\Storage::isReadable($filename))
		{
			return false;
		}

		$arr = parse_ini_file($filename, true);
		return is_array($arr) ? $arr : array();
	}

	/**
	 * Write array into ini file
	 *
	 * 	$ini['key1'] = 'value1';<br/>
	 * 	$ini['key2'] = 'value2';<br/>
	 * 	$ini['section']['key1_in_section'] = 'value1_in_section';<br/>
	 * 	$ini['section']['key2_in_section'] = 'value2_in_section';<br/>
	 * 	self::writeIniFile('exmple.ini', $ini);
	 *
	 * @deprecated
	 * @see self::readIniFile()
	 * @param string $filename Target ini file name
	 * @param array $arr Array
	 * @return bool if array contains nothing it returns FALSE, otherwise TRUE
	 */
	public static function writeIniFile($filename, $arr)
	{
		if(!is_array($arr) || count($arr) == 0)
		{
			return FALSE;
		}
		self::writeFile($filename, self::_makeIniBuff($arr));
		return TRUE;
	}

	/**
	 * Make array to ini string
	 *
	 * @deprecated
	 * @param array $arr Array
	 * @return string
	 */
	public static function _makeIniBuff($arr)
	{
		$return = array();
		foreach($arr as $key => $val)
		{
			// section
			if(is_array($val))
			{
				$return[] = sprintf("[%s]", $key);
				foreach($val as $k => $v)
				{
					$return[] = sprintf("%s=\"%s\"", $k, $v);
				}
				// value
			}
			else if(is_object($val))
			{
				continue;
			}
			else
			{
				$return[] = sprintf("%s=\"%s\"", $key, $val);
			}
		}

		return join("\n", $return);
	}

	/**
	 * Returns a file object
	 *
	 * If the directory of the file does not exist, create it.
	 *
	 * @deprecated
	 * @param string $filename Target file name
	 * @param string $mode File mode for fopen
	 * @return FileObject File object
	 */
	public static function openFile($filename, $mode)
	{
		$filename = self::getRealPath($filename);
		Rhymix\Framework\Storage::createDirectory(dirname($filename));
		return new FileObject($filename, $mode);
	}

	/**
	 * Check whether the given file has the content.
	 *
	 * @param string $filename Target file name
	 * @return bool Returns TRUE if the file exists and contains something.
	 */
	public static function hasContent($filename)
	{
		return Rhymix\Framework\Storage::getSize(self::getRealPath($filename)) > 0;
	}

	/**
	 * Check file exists.
	 *
	 * @param string $filename Target file name
	 * @return bool Returns FALSE if the file does not exists, or Returns full path file(string).
	 */
	public static function exists($filename)
	{
		$filename = self::getRealPath($filename);
		return Rhymix\Framework\Storage::exists($filename) ? $filename : false;
	}

	/**
	 * Check it is dir
	 *
	 * @param string $dir Target dir path
	 * @return bool Returns FALSE if the dir is not dir, or Returns full path of dir(string).
	 */
	public static function isDir($path)
	{
		$path = self::getRealPath($path);
		return Rhymix\Framework\Storage::isDirectory($path) ? $path : false;
	}

	/**
	 * Check is writable dir
	 *
	 * @param string $path Target dir path
	 * @return bool
	 */
	public static function isWritableDir($path)
	{
		$path = self::getRealPath($path);
		return Rhymix\Framework\Storage::isDirectory($path) && Rhymix\Framework\Storage::isWritable($path);
	}

	/**
	 * Clears file status cache
	 *
	 * @deprecated
	 * @param string|array $target filename or directory
	 * @param boolean $include include files in the directory
	 */
	public static function clearStatCache($target, $include = false)
	{
		foreach(is_array($target) ? $target : array($target) as $target_item)
		{
			$target_item = self::getRealPath($target_item);
			if($include && self::isDir($target_item))
			{
				self::clearStatCache(self::readDir($target_item, '', false, true), $include);
			}
			else
			{
				clearstatcache(true, $target_item);
			}
		}
	}

	/**
	 * Invalidates a cached script of OPcache
	 *
	 * @deprecated
	 * @param string|array $target filename or directory
	 * @param boolean $force force
	 */
	public static function invalidateOpcache($target, $force = true)
	{
		static $opcache = null;

		if($opcache === null)
		{
			$opcache = function_exists('opcache_invalidate');
		}
		if($opcache === false)
		{
			return;
		}

		foreach(is_array($target) ? $target : array($target) as $target_item)
		{
			if(substr($target_item, -4) === '.php')
			{
				opcache_invalidate(self::getRealPath($target_item), $force);
			}
			elseif($path = self::isDir($target_item))
			{
				self::invalidateOpcache(self::readDir($path, '', false, true), $force);
			}
		}
	}
}
