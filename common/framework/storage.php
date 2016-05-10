<?php

namespace Rhymix\Framework;

/**
 * The storage class.
 */
class Storage
{
	/**
	 * Check if a path really exists.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function exists($path)
	{
		$path = rtrim($path, '/\\');
		clearstatcache(true, $path);
		return @file_exists($path);
	}
	
	/**
	 * Check if the given path is a file.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isFile($path)
	{
		$path = rtrim($path, '/\\');
		return @self::exists($path) && @is_file($path);
	}
	
	/**
	 * Check if the given path is an empty file.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isEmptyFile($path)
	{
		$path = rtrim($path, '/\\');
		return @self::exists($path) && @is_file($path) && (@filesize($path) == 0);
	}
	
	
	/**
	 * Check if the given path is a directory.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isDirectory($path)
	{
		$path = rtrim($path, '/\\');
		return @self::exists($path) && @is_dir($path);
	}
	
	/**
	 * Check if the given path is an empty directory.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isEmptyDirectory($path)
	{
		$path = rtrim($path, '/\\');
		if (!self::isDirectory($path))
		{
			return false;
		}
		
		$iterator = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);
		return (iterator_count($iterator)) === 0 ? true : false;
	}
	
	/**
	 * Check if the given path is a symbolic link.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isSymlink($path)
	{
		$path = rtrim($path, '/\\');
		return @is_link($path);
	}
	
	/**
	 * Check if the given path is a valid symbolic link.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isValidSymlink($path)
	{
		$path = rtrim($path, '/\\');
		return @is_link($path) && ($target = @readlink($path)) !== false && self::exists($target);
	}
	
	/**
	 * Check if the given path is readable.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isReadable($path)
	{
		$path = rtrim($path, '/\\');
		return @self::exists($path) && @is_readable($path);
	}
	
	/**
	 * Check if the given path is writable.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isWritable($path)
	{
		$path = rtrim($path, '/\\');
		return @self::exists($path) && @is_writable($path);
	}
	
	/**
	 * Get the size of a file.
	 * 
	 * This method returns the size of a file, or false on error.
	 * 
	 * @param string $filename
	 * @return int|false
	 */
	public static function getSize($filename)
	{
		$filename = rtrim($filename, '/\\');
		if (self::exists($filename) && @is_file($filename) && @is_readable($filename))
		{
			return @filesize($filename);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Get the content of a file.
	 * 
	 * This method returns the content if it exists and is readable.
	 * If $stream is true, it will return the content as a stream instead of
	 * loading the entire content in memory. This may be useful for large files.
	 * If the file cannot be opened, this method returns false.
	 * 
	 * @param string $filename
	 * @param bool $stream (optional)
	 * @return string|resource|false
	 */
	public static function read($filename, $stream = false)
	{
		$filename = rtrim($filename, '/\\');
		if (self::exists($filename) && @is_file($filename) && @is_readable($filename))
		{
			if ($stream)
			{
				return @fopen($filename, 'r');
			}
			else
			{
				return @file_get_contents($filename);
			}
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Read PHP data from a file, formatted for easy retrieval.
	 *
	 * This method returns the data on success and false on failure.
	 * 
	 * @param string $filename
	 * @return mixed
	 */
	public static function readPHPData($filename)
	{
		$filename = rtrim($filename, '/\\');
		if (@is_file($filename) && @is_readable($filename))
		{
			return @include $filename;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Write $content to a file.
	 *
	 * If $content is a stream, this method will copy it to the target file
	 * without loading the entire content in memory. This may be useful for large files.
	 * This method returns true on success and false on failure.
	 * 
	 * @param string $filename
	 * @param string|resource $content
	 * @param string $mode (optional)
	 * @param int $perms (optional)
	 * @return string|false
	 */
	public static function write($filename, $content, $mode = 'w', $perms = null)
	{
		$filename = rtrim($filename, '/\\');
		$destination_dir = dirname($filename);
		if (!self::exists($destination_dir))
		{
			$mkdir_success = self::createDirectory($destination_dir);
			if (!$mkdir_success)
			{
				return false;
			}
		}
		
		if ($fp = fopen($filename, $mode))
		{
			flock($fp, \LOCK_EX);
			if (is_resource($content))
			{
				$result = stream_copy_to_stream($content, $fp) ? true : false;
			}
			else
			{
				$result = fwrite($fp, $content) ? true : false;
			}
			fflush($fp);
			flock($fp, \LOCK_UN);
			fclose($fp);
		}
		else
		{
			return false;
		}
		
		@chmod($filename, ($perms === null ? (0666 & ~umask()) : $perms));
		if (function_exists('opcache_invalidate') && substr($filename, -4) === '.php')
		{
			@opcache_invalidate($filename, true);
		}
		return $result;
	}
	
	/**
	 * Write PHP data to a file, formatted for easy retrieval.
	 *
	 * This method returns true on success and false on failure.
	 * Resources and anonymous functions cannot be saved.
	 * 
	 * @param string $filename
	 * @param mixed $data
	 * @param string $comment (optional)
	 * @return string|false
	 */
	public static function writePHPData($filename, $data, $comment = null)
	{
		if ($comment !== null)
		{
			$comment = "/* $comment */\n";
		}
		return self::write($filename, '<' . '?php ' . $comment . 'return unserialize(' . var_export(serialize($data), true) . ');');
	}
	
	/**
	 * Copy $source to $destination.
	 * 
	 * This method returns true on success and false on failure.
	 * If the destination permissions are not given, they will be copied from the source.
	 * 
	 * @param string $source
	 * @param string $destination
	 * @param int $destination_perms
	 * @return bool
	 */
	public static function copy($source, $destination, $destination_perms = null)
	{
		$source = rtrim($source, '/\\');
		$destination = rtrim($destination, '/\\');
		if (!self::exists($source))
		{
			return false;
		}
		
		$destination_dir = dirname($destination);
		if (!self::exists($destination_dir) && !self::createDirectory($destination_dir))
		{
			return false;
		}
		elseif (self::isDirectory($destination))
		{
			$destination = $destination . '/' . basename($source);
		}
		
		$copy_success = @copy($source, $destination);
		if (!$copy_success)
		{
			return false;
		}
		
		if ($destination_perms === null)
		{
			if (is_uploaded_file($source))
			{
				@chmod($destination, 0666 ^ intval(config('file.umask'), 8));
			}
			else
			{
				@chmod($destination, 0777 & @fileperms($source));
			}
		}
		else
		{
			@chmod($destination, $destination_perms);
		}
		return true;
	}
	
	/**
	 * Move $source to $destination.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param string $source
	 * @param string $destination
	 * @return bool
	 */
	public static function move($source, $destination)
	{
		$source = rtrim($source, '/\\');
		$destination = rtrim($destination, '/\\');
		if (!self::exists($source))
		{
			return false;
		}
		
		$destination_dir = dirname($destination);
		if (!self::exists($destination_dir) && !self::createDirectory($destination_dir))
		{
			return false;
		}
		elseif (self::isDirectory($destination))
		{
			$destination = $destination . '/' . basename($source);
		}
		
		$result = @rename($source, $destination);
		if (function_exists('opcache_invalidate') && substr($source, -4) === '.php')
		{
			@opcache_invalidate($source, true);
		}
		return $result;
	}
	
	/**
	 * Delete a file.
	 * 
	 * This method returns true if the file exists and has been successfully
	 * deleted, and false on any kind of failure.
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public static function delete($filename)
	{
		$filename = rtrim($filename, '/\\');
		$result = @self::exists($filename) && @is_file($filename) && @unlink($filename);
		if (function_exists('opcache_invalidate') && substr($filename, -4) === '.php')
		{
			@opcache_invalidate($filename, true);
		}
		return $result;
	}
	
	/**
	 * Create a directory.
	 * 
	 * @param string $dirname
	 * @return bool
	 */
	public static function createDirectory($dirname, $mode = null)
	{
		$dirname = rtrim($dirname, '/\\');
		if ($mode === null)
		{
			$mode = 0777 & ~umask();
		}
		return @mkdir($dirname, $mode, true);
	}
	
	/**
	 * Read the list of files in a directory.
	 * 
	 * @param string $dirname
	 * @param bool $full_path (optional)
	 * @param bool $skip_dotfiles (optional)
	 * @param bool $skip_subdirs (optional)
	 * @return array|false
	 */
	public static function readDirectory($dirname, $full_path = true, $skip_dotfiles = true, $skip_subdirs = true)
	{
		$dirname = rtrim($dirname, '/\\');
		if (!self::isDirectory($dirname))
		{
			return false;
		}
		
		try
		{
			$iterator = new \FilesystemIterator($dirname, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);
		}
		catch (\UnexpectedValueException $e)
		{
			return false;
		}
		
		$result = array();
		foreach ($iterator as $fileinfo)
		{
			if (!$skip_subdirs || !is_dir($fileinfo))
			{
				$basename = basename($fileinfo);
				if (!$skip_dotfiles || $basename[0] !== '.')
				{
					$result[] = $full_path ? $fileinfo : $basename;
				}
			}
		}
		sort($result);
		return $result;
	}
	
	/**
	 * Copy a directory recursively.
	 *
	 * @param string $source
	 * @param string $destination
	 * @param string $exclude_regexp (optional)
	 * @return bool
	 */
	public static function copyDirectory($source, $destination, $exclude_regexp = null)
	{
		$source = rtrim($source, '/\\');
		$destination = rtrim($destination, '/\\');
		if (!self::isDirectory($source))
		{
			return false;
		}
		if (!self::isDirectory($destination) && !self::createDirectory($destination))
		{
			return false;
		}
		
		$rdi_options = \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS;
		$rii_options = \RecursiveIteratorIterator::CHILD_FIRST;
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, $rdi_options), $rii_options);
		
		foreach ($iterator as $path)
		{
			$path_source = $path->getPathname();
			if (strpos($path_source, $source) !== 0)
			{
				continue;
			}
			if ($exclude_regexp && preg_match($exclude_regexp, $path_source))
			{
				continue;
			}
			
			$path_destination = $destination . substr($path_source, strlen($source));
			if ($path->isDir())
			{
				$status = self::isDirectory($path_destination) || self::createDirectory($path_destination, $path->getPerms());
				if (!$status)
				{
					return false;
				}
			}
			else
			{
				$status = self::copy($path_source, $path_destination, $path->getPerms());
				if (!$status)
				{
					return false;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Move a directory.
	 * 
	 * @param string $source
	 * @param string $destination
	 * @return bool
	 */
	public static function moveDirectory($source, $destination)
	{
		return self::move($source, $destination);
	}
	
	/**
	 * Delete a directory recursively.
	 * 
	 * @param string $dirname
	 * @param bool $delete_self (optional)
	 * @return bool
	 */
	public static function deleteDirectory($dirname, $delete_self = true)
	{
		$dirname = rtrim($dirname, '/\\');
		if (!self::isDirectory($dirname))
		{
			return false;
		}
		
		$rdi_options = \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS;
		$rii_options = \RecursiveIteratorIterator::CHILD_FIRST;
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirname, $rdi_options), $rii_options);
		
		foreach ($iterator as $path)
		{
			if ($path->isDir())
			{
				if (!@rmdir($path->getPathname()))
				{
					return false;
				}
			}
			else
			{
				if (!@unlink($path->getPathname()))
				{
					return false;
				}
			}
		}
		
		if ($delete_self)
		{
			return @rmdir($dirname);
		}
		else
		{
			return true;
		}
	}
}
