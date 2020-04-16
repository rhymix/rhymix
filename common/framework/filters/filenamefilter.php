<?php

namespace Rhymix\Framework\Filters;

/**
 * The filename filter class.
 */
class FilenameFilter
{
	/**
	 * Remove illegal and dangerous characters from a filename.
	 * 
	 * @param string $filename
	 * @return string
	 */
	public static function clean($filename)
	{
		// Replace dangerous characters with safe alternatives, maintaining meaning as much as possible.
		$illegal = array('\\', '/', '<', '>', '{', '}', ':', ';', '|', '"', '~', '`', '$', '%', '^', '*', '?');
		$replace = array('', '', '(', ')', '(', ')', '_', ',', '_', '', '_', '\'', '_', '_', '_', '', '');
		$filename = str_replace($illegal, $replace, $filename);
		
		// Remove control characters.
		$filename = preg_replace('/([\\x00-\\x1f\\x7f\\xff]+)/u', '', $filename);
		
		// Standardize whitespace characters.
		$filename = trim(preg_replace('/[\\pZ\\pC]+/u', ' ', $filename));
		
		// Remove excess spaces and replacement characters.
		$filename = trim($filename, ' .-_');
		$filename = preg_replace('/__+/', '_', $filename);
		$filename = preg_replace('/\.\.+/', '.', $filename);
		
		// Clean up unnecessary encodings.
		$filename = strtr($filename, array('&amp;' => '&'));
		
		// Change .php files to .phps to make them non-executable.
		if (strtolower(substr($filename, strlen($filename) - 4)) === '.php')
		{
			$filename = substr($filename, 0, strlen($filename) - 4) . '.phps';
		}
		
		// Truncate filenames over 127 chars long, or extensions over 16 chars long.
		if (mb_strlen($filename, 'UTF-8') > 127)
		{
			$extension = strrchr($filename, '.');
			if (mb_strlen($extension, 'UTF-8') > 16) $extension = mb_substr($extension, 0, 16);
			$filename = mb_substr($filename, 0, 127 - mb_strlen($extension)) . $extension;
		}
		
		return $filename;
	}
	
	/**
	 * Clean a path to remove ./, ../, trailing slashes, etc.
	 * 
	 * @param string $path
	 * @return string
	 */
	public static function cleanPath($path)
	{
		// Convert relative paths to absolute paths.
		if (!preg_match('@^(?:/|[a-z]:[\\\\/]|\\\\|https?:)@i', $path))
		{
			$path = \RX_BASEDIR . $path;
		}
		
		// Convert backslashes to forward slashes.
		$path = str_replace('\\', '/', $path);
		
		// Remove querystrings and URL fragments.
		if (($querystring = strpbrk($path, '?#')) !== false)
		{
			$path = substr($path, 0, -1 * strlen($querystring));
		}
		
		// Remove single dots, three or more dots, and duplicate slashes.
		$path = preg_replace(array(
			'@(?<!^|^http:|^https:)/{2,}@',
			'@/(?:(?:\.|\.{3,})/)+@',
		), '/', $path);
		
		// Remove double dots and the preceding directory.
		while (preg_match('@/(?!\.\.)[^/]+/\.\.(?:/|$)@', $path, $matches))
		{
			$path = str_replace($matches[0], '/', $path);
		}
		
		// Trim trailing slashes.
		return rtrim($path, '/');
	}
	
	/**
	 * Check if a file has an extension that would allow direct download.
	 * 
	 * @param string $filename
	 * @param bool $include_multimedia (optional)
	 * @return bool
	 */
	public static function isDirectDownload($filename, $include_multimedia = true)
	{
		$images = 'gif|jpe?g|png|webp';
		$audios = 'mp3|wav|ogg|flac|aac';
		$videos = 'mp4|webm|ogv';
		$legacy = 'avi|as[fx]|flv|m4[av]|midi?|mkv|moo?v|mpe?g|qt|r[am]m?|wm[av]';
		
		if ($include_multimedia)
		{
			$pattern = sprintf('/\.(?:%s|%s|%s|%s)$/i', $images, $audios, $videos, $legacy);
		}
		else
		{
			$pattern = sprintf('/\.(?:%s)$/i', $images);
		}
		
		return preg_match($pattern, $filename) ? true : false;
	}
}
