<?php

namespace Rhymix\Framework;

/**
 * The debug class.
 */
class Debug
{
	/**
	 * Cache the debug.enabled flag here.
	 */
	protected static $_enabled;
	
	/**
	 * Store log entries here.
	 */
	protected static $_aliases = array();
	protected static $_entries = array();
	protected static $_errors = array();
	protected static $_queries = array();
	protected static $_slow_queries = array();
	protected static $_slow_triggers = array();
	protected static $_slow_widgets = array();
	
	/**
	 * Get all entries.
	 * 
	 * @return array
	 */
	public static function getEntries()
	{
		return self::$_entries;
	}
	
	/**
	 * Get all errors.
	 * 
	 * @return array
	 */
	public static function getErrors()
	{
		return self::$_errors;
	}
	
	/**
	 * Get all queries.
	 * 
	 * @return array
	 */
	public static function getQueries()
	{
		return self::$_queries;
	}
	
	/**
	 * Get all slow queries.
	 * 
	 * @return array
	 */
	public static function getSlowQueries()
	{
		return self::$_slow_queries;
	}
	
	/**
	 * Get all slow triggers.
	 * 
	 * @return array
	 */
	public static function getSlowTriggers()
	{
		return self::$_slow_triggers;
	}
	
	/**
	 * Get all slow widgets.
	 * 
	 * @return array
	 */
	public static function getSlowWidgets()
	{
		return self::$_slow_triggers;
	}
	
	/**
	 * Add a filename alias.
	 * 
	 * @param string $display_filename
	 * @param string $real_filename
	 * @return void
	 */
	public static function addFilenameAlias($display_filename, $real_filename)
	{
		self::$_aliases[$real_filename] = $display_filename;
	}
	
	/**
	 * Add an arbitrary entry to the log.
	 * 
	 * @param string $message
	 * @return bool
	 */
	public static function addEntry($message)
	{
		// If debugging is disabled, stop here.
		if (!(self::$_enabled = (self::$_enabled !== null) ? self::$_enabled : Config::get('debug.enabled')))
		{
			return false;
		}
		
		// Get the backtrace.
		$backtrace_args = defined('\DEBUG_BACKTRACE_IGNORE_ARGS') ? \DEBUG_BACKTRACE_IGNORE_ARGS : 0;
		$backtrace = debug_backtrace($backtrace_args);
		
		// Create a log entry.
		$entry = (object)array(
			'type' => 'Debug',
			'time' => microtime(true),
			'message' => $message,
			'file' => isset($backtrace[0]['file']) ? $backtrace[0]['file'] : null,
			'line' => isset($backtrace[0]['line']) ? $backtrace[0]['line'] : 0,
			'backtrace' => $backtrace,
		);
		self::$_entries[] = $entry;
		return true;
	}
	
	/**
	 * Add a PHP error to the log.
	 * 
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @param array $errcontext
	 * @return void
	 */
	public static function addError($errno, $errstr, $errfile, $errline, $errcontext)
	{
		// Do not handle error types that we were told to ignore.
		if (!($errno & error_reporting()))
		{
			return;
		}
		
		// Find out the file where the error really occurred.
		if (isset(self::$_aliases[$errfile]))
		{
			$errfile = self::$_aliases[$errfile];
		}
		if (!strncmp($errfile, \RX_BASEDIR, strlen(\RX_BASEDIR)))
		{
			$errfile = substr($errfile, strlen(\RX_BASEDIR));
		}
		
		// Get the backtrace.
		$backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
		
		// Prepare the error entry.
		self::$_errors[] = $errinfo = (object)array(
			'type' => self::getErrorType($errno),
			'time' => microtime(true),
			'message' => $errstr,
			'file' => $errfile,
			'line' => $errline,
			'backtrace' => $backtrace,
		);
		
		// Add the entry to the error log.
		$log_entry = str_replace("\0", '', sprintf('PHP %s: %s in %s on line %d',
			$errinfo->type, $errinfo->message, $errinfo->file, intval($errinfo->line)));
		error_log($log_entry);
	}
	
	/**
	 * Add a query to the log.
	 * 
	 * @return bool
	 */
	public static function addQuery()
	{
		
	}
	
	/**
	 * Add a slow query to the log.
	 * 
	 * @return bool
	 */
	public static function addSlowQuery()
	{
		
	}
	
	/**
	 * Add a slow trigger to the log.
	 * 
	 * @return bool
	 */
	public static function addSlowTrigger()
	{
		
	}
	
	/**
	 * Add a slow widget to the log.
	 * 
	 * @return bool
	 */
	public static function addSlowWidget()
	{
		
	}
	
	/**
	 * The default handler for catching exceptions.
	 * 
	 * @param Exception $e
	 * @return void
	 */
	public static function exceptionHandler(\Exception $e)
	{
		// Find out the file where the exception really occurred.
		$errfile = $e->getFile();
		if (isset(self::$_aliases[$errfile]))
		{
			$errfile = self::$_aliases[$errfile];
		}
		if (!strncmp($errfile, \RX_BASEDIR, strlen(\RX_BASEDIR)))
		{
			$errfile = substr($errfile, strlen(\RX_BASEDIR));
		}
		
		// If the exception was thrown in a Rhymix Framework class, find out where that class was called.
		$backtrace = $e->getTrace();
		$caller_errfile = $errfile;
		$caller_errline = $e->getLine();
		while (preg_match('#^(classes|common)/#i', $caller_errfile))
		{
			$trace = array_shift($backtrace);
			if (!$trace)
			{
				$caller_errfile = $caller_errline = null;
			}
			else
			{
				$caller_errfile = $trace['file'];
				$caller_errline = $trace['line'];
				if (isset(self::$_aliases[$caller_errfile]))
				{
					$caller_errfile = self::$_aliases[$caller_errfile];
				}
				if (!strncmp($caller_errfile, \RX_BASEDIR, strlen(\RX_BASEDIR)))
				{
					$caller_errfile = substr($caller_errfile, strlen(\RX_BASEDIR));
				}
			}
		}
		
		// Add the exception to the error log.
		
		if ($caller_errfile && $caller_errfile !== $errfile)
		{
			$log_entry = str_replace("\0", '', sprintf('%s #%d "%s" in %s on line %d (via %s on line %d)',
				get_class($e), $e->getCode(), $e->getMessage(), $caller_errfile, $caller_errline, $errfile, $e->getLine()));
		}
		else
		{
			$log_entry = str_replace("\0", '', sprintf('%s #%d "%s" in %s on line %d',
				get_class($e), $e->getCode(), $e->getMessage(), $errfile, $e->getLine()));
		}
		error_log('PHP Exception: ' . $log_entry . "\n" . str_replace("\0", '',  $e->getTraceAsString()));
		
		// Display the error screen.
		self::displayErrorScreen($log_entry);
	}
	
	/**
	 * The default handler for catching fatal errors.
	 * 
	 * @return void
	 */
	public static function shutdownHandler()
	{
		// Check if we are exiting because of a fatal error.
		$errinfo = error_get_last();
		if ($errinfo === null || ($errinfo['type'] !== 1 && $errinfo['type'] !== 4))
		{
			return;
		}
		
		// Find out the file where the fatal error really occurred.
		if (isset(self::$_aliases[$errinfo['file']]))
		{
			$errinfo['file'] = self::$_aliases[$errinfo['file']];
		}
		if (!strncmp($errinfo['file'], \RX_BASEDIR, strlen(\RX_BASEDIR)))
		{
			$errinfo['file'] = substr($errinfo['file'], strlen(\RX_BASEDIR));
		}
		
		// Add the entry to the error log.
		$message = sprintf('%s in %s on line %d', $errinfo['message'], $errinfo['file'], intval($errinfo['line']));
		$log_entry = str_replace("\0", '', 'PHP ' . self::getErrorType($errinfo['type']) . ': ' . $message);
		error_log($log_entry);
		
		// Display the error screen.
		self::displayErrorScreen($log_entry);
	}
	
	/**
	 * Register all error handlers.
	 * 
	 * @return void
	 */
	public static function registerErrorHandlers($error_types)
	{
		set_error_handler('\\Rhymix\\Framework\\Debug::addError', $error_types);
		set_exception_handler('\\Rhymix\\Framework\\Debug::exceptionHandler');
		register_shutdown_function('\\Rhymix\\Framework\\Debug::shutdownHandler');
	}
	
	/**
	 * Display a fatal error screen.
	 * 
	 * @param string $message
	 * @return void
	 */
	public static function displayErrorScreen($message)
	{
		// Disable output buffering.
		while (ob_get_level())
		{
			ob_end_clean();
		}
		
		// Localize the error title.
		$title = \Context::getLang('msg_server_error');
		if ($title === 'msg_server_error')
		{
			$message = 'Server Error';
		}
		
		// Localize the error message.
		$message = ini_get('display_errors') ? $message : \Context::getLang('msg_server_error_see_log');
		if ($message === 'msg_server_error_see_log')
		{
			$message = 'Your server is configured to hide error messages. Please see your server\'s error log for details.';
		}
		
		// Display a generic error page.
		\Context::displayErrorPage($title, $message, 500);
		exit;
	}
	
	/**
	 * Convert a PHP error number to the corresponding error name.
	 * 
	 * @param int $errno
	 * @return string
	 */
	public static function getErrorType($errno)
	{
		switch ($errno)
		{
			case \E_ERROR: return 'Fatal Error';
			case \E_WARNING: return 'Warning';
			case \E_NOTICE: return 'Notice';
			case \E_CORE_ERROR: return 'Core Error';
			case \E_CORE_WARNING: return 'Core Warning';
			case \E_COMPILE_ERROR: return 'Compile-time Error';
			case \E_COMPILE_WARNING: return 'Compile-time Warning';
			case \E_USER_ERROR: return 'User Error';
			case \E_USER_WARNING: return 'User Warning';
			case \E_USER_NOTICE: return 'User Notice';
			case \E_STRICT: return 'Strict Standards';
			case \E_PARSE: return 'Parse Error';
			case \E_DEPRECATED: return 'Deprecated';
			case \E_USER_DEPRECATED: return 'User Deprecated';
			case \E_RECOVERABLE_ERROR: return 'Catchable Fatal Error';
			default: return 'Error';
		}
	}
}
