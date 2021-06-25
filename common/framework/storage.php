<?php

namespace Rhymix\Framework;

/**
 * The storage class.
 */
class Storage
{
	/**
	 * Use atomic rename to overwrite files.
	 */
	public static $safe_overwrite = true;
	
	/**
	 * Cache the umask here.
	 */
	protected static $_umask;
	
	/**
	 * Cache the opcache status here.
	 */
	protected static $_opcache;
	
	/**
	 * Cache locks here.
	 */
	protected static $_locks = array();
	
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
	 * Check if the given path is executable.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isExecutable($path)
	{
		$path = rtrim($path, '/\\');
		if (function_exists('exec') && !\RX_WINDOWS)
		{
			@exec('/bin/ls -l ' . escapeshellarg($path), $output, $return_var);
			if ($return_var === 0)
			{
				return preg_match('@^[a-z-]{9}x@', array_pop($output)) === 1;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return @self::exists($path) && @is_executable($path);
		}
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
				$result = @fopen($filename, 'r');
			}
			else
			{
				$result = @file_get_contents($filename);
			}
			
			if ($result === false)
			{
				trigger_error('Cannot read file: ' . $filename, \E_USER_WARNING);
			}
			return $result;
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
			if (!$mkdir_success && !self::exists($destination_dir))
			{
				trigger_error('Cannot create directory to write file: ' . $filename, \E_USER_WARNING);
				return false;
			}
		}
		
		if (self::$safe_overwrite && strncasecmp($mode, 'a', 1) && @is_writable($destination_dir))
		{
			$use_atomic_rename = true;
			$original_filename = $filename;
			$filename = $filename . '.tmp.' . microtime(true);
		}
		else
		{
			$use_atomic_rename = false;
		}
		
		if ($fp = @fopen($filename, $mode))
		{
			flock($fp, \LOCK_EX);
			if (is_resource($content))
			{
				$result = stream_copy_to_stream($content, $fp);
			}
			else
			{
				$result = fwrite($fp, $content);
			}
			fflush($fp);
			flock($fp, \LOCK_UN);
			fclose($fp);
			
			if ($result === false || (is_string($content) && strlen($content) !== $result))
			{
				trigger_error('Cannot write file: ' . (isset($original_filename) ? $original_filename : $filename), \E_USER_WARNING);
				return false;
			}
		}
		else
		{
			trigger_error('Cannot write file: ' . (isset($original_filename) ? $original_filename : $filename), \E_USER_WARNING);
			return false;
		}
		
		@chmod($filename, ($perms === null ? (0666 & ~self::getUmask()) : $perms));
		
		if ($use_atomic_rename)
		{
			$rename_success = @rename($filename, $original_filename);
			if (!$rename_success)
			{
				@unlink($original_filename);
				$rename_success = @rename($filename, $original_filename);
				if (!$rename_success)
				{
					@unlink($filename);
					trigger_error('Cannot write file: ' . (isset($original_filename) ? $original_filename : $filename), \E_USER_WARNING);
					return false;
				}
			}
			$filename = $original_filename;
		}
		
		if (self::$_opcache === null)
		{
			self::$_opcache = function_exists('opcache_invalidate');
		}
		if (self::$_opcache && substr($filename, -4) === '.php')
		{
			@opcache_invalidate($filename, true);
		}
		
		clearstatcache(true, $filename);
		return true;
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
	 * @param bool $serialize (optional)
	 * @return string|false
	 */
	public static function writePHPData($filename, $data, $comment = null, $serialize = true)
	{
		if ($comment !== null)
		{
			$comment = "/* $comment */\n";
		}
		if ($serialize)
		{
			$content = '<' . '?php ' . $comment . 'return unserialize(' . var_export(serialize($data), true) . ');';
		}
		else
		{
			$content = '<' . '?php ' . $comment . 'return ' . var_export($data, true) . ';';
		}
		return self::write($filename, $content);
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
			trigger_error('Cannot copy because the source does not exist: ' . $source, \E_USER_WARNING);
			return false;
		}
		
		$destination_dir = dirname($destination);
		if (!self::exists($destination_dir) && !self::createDirectory($destination_dir))
		{
			trigger_error('Cannot create directory to copy into: ' . $destination_dir, \E_USER_WARNING);
			return false;
		}
		elseif (self::isDirectory($destination))
		{
			$destination_dir = $destination;
			$destination = $destination . '/' . basename($source);
		}
		
		if (self::$safe_overwrite && @is_writable($destination_dir))
		{
			$use_atomic_rename = true;
			$original_destination = $destination;
			$destination = $destination . '.tmp.' . microtime(true);
		}
		else
		{
			$use_atomic_rename = false;
		}
		
		$copy_success = @copy($source, $destination);
		if (!$copy_success)
		{
			trigger_error('Cannot copy ' . $source . ' to ' . (isset($original_destination) ? $original_destination : $destination), \E_USER_WARNING);
			return false;
		}
		
		if ($destination_perms === null)
		{
			if (is_uploaded_file($source))
			{
				@chmod($destination, 0666 & ~self::getUmask());
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
		
		if ($use_atomic_rename)
		{
			$rename_success = @rename($destination, $original_destination);
			if (!$rename_success)
			{
				@unlink($original_destination);
				$rename_success = @rename($destination, $original_destination);
				if (!$rename_success)
				{
					@unlink($destination);
					trigger_error('Cannot copy ' . $source . ' to ' . (isset($original_destination) ? $original_destination : $destination), \E_USER_WARNING);
					return false;
				}
			}
			$destination = $original_destination;
		}
		
		if (self::$_opcache === null)
		{
			self::$_opcache = function_exists('opcache_invalidate');
		}
		if (self::$_opcache && substr($destination, -4) === '.php')
		{
			@opcache_invalidate($destination, true);
		}
		
		clearstatcache(true, $destination);
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
			trigger_error('Cannot move because the source does not exist: ' . $source, \E_USER_WARNING);
			return false;
		}
		
		$destination_dir = dirname($destination);
		if (!self::exists($destination_dir) && !self::createDirectory($destination_dir))
		{
			trigger_error('Cannot create directory to move into: ' . $destination_dir, \E_USER_WARNING);
			return false;
		}
		elseif (self::isDirectory($destination))
		{
			$destination = $destination . '/' . basename($source);
		}
		
		$result = @rename($source, $destination);
		if (!$result)
		{
			trigger_error('Cannot move ' . $source . ' to ' . $destination, \E_USER_WARNING);
			return false;
		}
		
		if (is_uploaded_file($source))
		{
			@chmod($destination, 0666 & ~self::getUmask());
		}
		
		if (self::$_opcache === null)
		{
			self::$_opcache = function_exists('opcache_invalidate');
		}
		if (self::$_opcache)
		{
			if (substr($source, -4) === '.php')
			{
				@opcache_invalidate($source, true);
			}
			if (substr($destination, -4) === '.php')
			{
				@opcache_invalidate($destination, true);
			}
		}
		
		clearstatcache(true, $destination);
		return true;
	}
	
	/**
	 * Move uploaded $source to $destination.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param string $source
	 * @param string $destination
	 * @param string $type
	 * @return bool
	 */
	public static function moveUploadedFile($source, $destination, $type = null)
	{
		if ($type === 'copy')
		{
			if (!self::copy($source, $destination))
			{
				if (!self::copy($source, $destination))
				{
					return false;
				}
			}
		}
		elseif ($type === 'move')
		{
			if (!self::move($source, $destination))
			{
				if (!self::move($source, $destination))
				{
					return false;
				}
			}
		}
		else
		{
			if (!@move_uploaded_file($source, $destination))
			{
				if (!@move_uploaded_file($source, $destination))
				{
					return false;
				}
			}
		}
		return true;
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
		if (!self::exists($filename))
		{
			return false;
		}
		
		$result = @is_file($filename) && @unlink($filename);
		if (!$result)
		{
			trigger_error('Cannot delete file: ' . $filename, \E_USER_WARNING);
		}
		
		if (self::$_opcache === null)
		{
			self::$_opcache = function_exists('opcache_invalidate');
		}
		if (self::$_opcache && substr($filename, -4) === '.php')
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
			$mode = 0777 & ~self::getUmask();
		}
		
		$result = @mkdir($dirname, $mode, true);
		if (!$result)
		{
			if (!is_dir($dirname))
			{
				trigger_error('Cannot create directory: ' . $dirname, \E_USER_WARNING);
			}
			else
			{
				@chmod($dirname, $mode);
			}
			return false;
		}
		else
		{
			return true;
		}
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
			trigger_error('Cannot read directory: ' . $dirname, \E_USER_WARNING);
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
			trigger_error('Cannot copy because the source does not exist: ' . $source, \E_USER_WARNING);
			return false;
		}
		if (!self::isDirectory($destination) && !self::createDirectory($destination))
		{
			trigger_error('Cannot create directory to copy into: ' . $destination, \E_USER_WARNING);
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
		if (!self::exists($dirname))
		{
			return false;
		}
		if (!self::isDirectory($dirname))
		{
			trigger_error('Delete target is not a directory: ' . $dirname, \E_USER_WARNING);
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
					trigger_error('Cannot delete directory: ' . $path->getPathname(), \E_USER_WARNING);
					return false;
				}
			}
			else
			{
				if (!@unlink($path->getPathname()))
				{
					trigger_error('Cannot delete file: ' . $path->getPathname(), \E_USER_WARNING);
					return false;
				}
			}
		}
		
		if ($delete_self)
		{
			$result = @rmdir($dirname);
			if (!$result)
			{
				trigger_error('Cannot delete directory: ' . $dirname, \E_USER_WARNING);
				return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Delete a directory only if it is empty.
	 * 
	 * @param string $dirname
	 * @param bool $delete_empty_parents (optional)
	 * @return bool
	 */
	public static function deleteEmptyDirectory($dirname, $delete_empty_parents = false)
	{
		$dirname = rtrim($dirname, '/\\');
		if (!self::isDirectory($dirname) || !self::isEmptyDirectory($dirname))
		{
			return false;
		}
		
		$result = @rmdir($dirname);
		if (!$result)
		{
			return false;
		}
		else
		{
			if ($delete_empty_parents)
			{
				self::deleteEmptyDirectory(dirname($dirname), true);
			}
			return true;
		}
	}
	
	/**
	 * Get the current umask.
	 * 
	 * @return int
	 */
	public static function getUmask()
	{
		if (self::$_umask === null)
		{
			self::$_umask = intval(config('file.umask'), 8) ?: 0;
		}
		return self::$_umask;
	}
	
	/**
	 * Set the current umask.
	 * 
	 * @param int $umask
	 * @return void
	 */
	public static function setUmask($umask)
	{
		self::$_umask = intval($umask);
	}
	
	/**
	 * Determine the best umask for this installation of Rhymix.
	 * 
	 * @return string
	 */
	public static function recommendUmask()
	{
		// On Windows, set the umask to 0000.
		if (\RX_WINDOWS)
		{
			return '0000';
		}
		
		// Get the UID of the owner of the current file.
		$file_uid = fileowner(__FILE__);
		
		// Get the UID of the current PHP process.
		$php_uid = self::getServerUID();
		
		// If both UIDs are the same, set the umask to 0022.
		if ($file_uid == $php_uid)
		{
			return '0022';
		}
		
		// Otherwise, set the umask to 0000.
		else
		{
			return '0000';
		}
	}
	
	/**
	 * Get the UID of the server process.
	 * 
	 * @return int|false
	 */
	public static function getServerUID()
	{
		if (function_exists('posix_geteuid'))
		{
			return posix_geteuid();
		}
		else
		{
			$testfile = \RX_BASEDIR . 'files/cache/uidcheck_' . time();
			if (self::exists($testfile))
			{
				self::delete($testfile);
			}
			
			if (self::write($testfile, 'TEST'))
			{
				$uid = fileowner($testfile);
				self::delete($testfile);
				return $uid;
			}
			else
			{
				return false;
			}
		}
	}
	
	/**
	 * Obtain an exclusive lock.
	 * 
	 * @return bool
	 */
	public static function getLock($name)
	{
		$name = str_replace('.', '%2E', rawurlencode($name));
		if (isset(self::$_locks[$name]))
		{
			return false;
		}
		
		$lockdir = \RX_BASEDIR . 'files/locks';
		if (!self::isDirectory($lockdir) && !self::createDirectory($lockdir))
		{
			return false;
		}
		
		self::$_locks[$name] = @fopen($lockdir . '/' . $name . '.lock', 'w');
		if (!self::$_locks[$name])
		{
			unset(self::$_locks[$name]);
			return false;
		}
		
		$result = @flock(self::$_locks[$name], \LOCK_EX | \LOCK_NB);
		if (!$result)
		{
			@fclose(self::$_locks[$name]);
			unset(self::$_locks[$name]);
			return false;
		}
		
		register_shutdown_function('\\Rhymix\\Framework\\Storage::clearLocks');
		return true;
	}
	
	/**
	 * Clear all locks.
	 * 
	 * @return void
	 */
	public static function clearLocks()
	{
		foreach (self::$_locks as $name => $lock)
		{
			@flock($lock, \LOCK_UN);
			@fclose($lock);
			@unlink(\RX_BASEDIR . 'files/locks/' . $name . '.lock');
			unset(self::$_locks[$name]);
		}
	}
}
