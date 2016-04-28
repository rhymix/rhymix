<?php

namespace Rhymix\Framework;

/**
 * The debug class.
 */
class Debug
{
	/**
	 * Store log entries here.
	 */
	protected static $_aliases = array();
	protected static $_entries = array();
	protected static $_errors = array();
	protected static $_queries = array();
	protected static $_slow_queries = array();
	protected static $_triggers = array();
	protected static $_slow_triggers = array();
	protected static $_widgets = array();
	protected static $_slow_widgets = array();
	
	/**
	 * Also write to error log.
	 */
	public static $write_to_error_log = true;
	
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
	 * Get all triggers.
	 * 
	 * @return array
	 */
	public static function getTriggers()
	{
		return self::$_triggers;
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
	 * Get all widgets.
	 * 
	 * @return array
	 */
	public static function getWidgets()
	{
		return self::$_widgets;
	}
	
	/**
	 * Get all slow widgets.
	 * 
	 * @return array
	 */
	public static function getSlowWidgets()
	{
		return self::$_slow_widgets;
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
	 * @return void
	 */
	public static function addEntry($message)
	{
		// Get the backtrace.
		$backtrace_args = defined('\DEBUG_BACKTRACE_IGNORE_ARGS') ? \DEBUG_BACKTRACE_IGNORE_ARGS : 0;
		$backtrace = debug_backtrace($backtrace_args);
		if (count($backtrace) > 1 && $backtrace[1]['function'] === 'debugPrint' && !$backtrace[1]['class'])
		{
			array_shift($backtrace);
		}
		
		// Create a log entry.
		$entry = (object)array(
			'type' => 'Debug',
			'time' => microtime(true),
			'message' => unserialize(serialize($message)),
			'file' => isset($backtrace[0]['file']) ? $backtrace[0]['file'] : null,
			'line' => isset($backtrace[0]['line']) ? $backtrace[0]['line'] : 0,
			'backtrace' => $backtrace,
		);
		self::$_entries[] = $entry;
		
		// Add the entry to the error log.
		if (self::$write_to_error_log && self::isEnabledForCurrentUser())
		{
			$log_entry = str_replace("\0", '', sprintf('Rhymix Debug: %s in %s on line %d',
				var_export($message, true), $entry->file, $entry->line));
			error_log($log_entry);
		}
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
		
		// Rewrite the error message with relative paths.
		$message = str_replace(array(
			' called in ' . \RX_BASEDIR,
			' defined in ' . \RX_BASEDIR,
		), array(
			' called in ',
			' defined in ',
		), $errstr);
		
		// Get the backtrace.
		$backtrace_args = defined('\DEBUG_BACKTRACE_IGNORE_ARGS') ? \DEBUG_BACKTRACE_IGNORE_ARGS : 0;
		$backtrace = debug_backtrace($backtrace_args);
		
		// Prepare the error entry.
		self::$_errors[] = $errinfo = (object)array(
			'type' => self::getErrorType($errno),
			'time' => microtime(true),
			'message' => $message,
			'file' => $errfile,
			'line' => $errline,
			'backtrace' => $backtrace,
		);
		
		// Add the entry to the error log.
		if (self::$write_to_error_log)
		{
			$log_entry = str_replace("\0", '', sprintf('PHP %s: %s in %s on line %d',
				$errinfo->type, $errstr, $errfile, intval($errline)));
			error_log($log_entry);
		}
	}
	
	/**
	 * Add a query to the log.
	 * 
	 * @return void
	 */
	public static function addQuery($query)
	{
		$query_object = (object)array(
			'type' => 'Query',
			'time' => microtime(true),
			'message' => $query['result'] === 'success' ? 'success' : $query['errstr'],
			'error_code' => $query['result'] === 'success' ? 0 : $query['errno'],
			'query_id' => $query['query_id'],
			'query_connection' => $query['connection'],
			'query_string' => $query['query'],
			'query_time' => $query['elapsed_time'],
			'file' => $query['called_file'],
			'line' => $query['called_line'],
			'method' => $query['called_method'],
			'backtrace' => $query['backtrace'],
		);
		
		self::$_queries[] = $query_object;
		if ($query_object->query_time && $query_object->query_time >= config('debug.log_slow_queries'))
		{
			self::$_slow_queries[] = $query_object;
		}
	}
	
	/**
	 * Add a trigger to the log.
	 * 
	 * @return bool
	 */
	public static function addTrigger($trigger)
	{
		$trigger_object = (object)array(
			'type' => 'Trigger',
			'time' => microtime(true),
			'message' => null,
			'file' => null,
			'line' => null,
			'backtrace' => array(),
			'trigger_name' => $trigger['name'],
			'trigger_target' => $trigger['target'],
			'trigger_plugin' => $trigger['target_plugin'],
			'trigger_time' => $trigger['elapsed_time'],
		);
		
		self::$_triggers[] = $trigger_object;
		if ($trigger_object->trigger_time && $trigger_object->trigger_time >= config('debug.log_slow_triggers'))
		{
			self::$_slow_triggers[] = $trigger_object;
		}
	}
	
	/**
	 * Add a widget to the log.
	 * 
	 * @return bool
	 */
	public static function addWidget($widget)
	{
		$widget_object = (object)array(
			'type' => 'Widget',
			'time' => microtime(true),
			'message' => null,
			'file' => null,
			'line' => null,
			'backtrace' => array(),
			'widget_name' => $widget['name'],
			'widget_time' => $widget['elapsed_time'],
		);
		
		self::$_widgets[] = $widget_object;
		if ($widget_object->widget_time && $widget_object->widget_time >= config('debug.log_slow_widgets'))
		{
			self::$_slow_widgets[] = $widget_object;
		}
	}
	
	/**
	 * The default handler for catching exceptions.
	 * 
	 * @param Exception $e
	 * @return void
	 */
	public static function exceptionHandler($e)
	{
		// Find out the file where the error really occurred.
		$errfile = self::translateFilename($e->getFile());
		
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
				$caller_errfile = self::translateFilename($trace['file']);
				$caller_errline = $trace['line'];
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
		exit;
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
		
		// Find out the file where the error really occurred.
		$errinfo['file'] = self::translateFilename($errinfo['file']);
		
		// Add the entry to the error log.
		$message = sprintf('%s in %s on line %d', $errinfo['message'], $errinfo['file'], intval($errinfo['line']));
		$log_entry = str_replace("\0", '', 'PHP ' . self::getErrorType($errinfo['type']) . ': ' . $message);
		error_log($log_entry);
		
		// Display the error screen.
		self::displayErrorScreen($log_entry);
	}
	
	/**
	 * Translate filenames.
	 * 
	 * @param string $filename
	 * @return string
	 */
	public static function translateFilename($filename)
	{
		if (isset(self::$_aliases[$filename]))
		{
			$filename = self::$_aliases[$filename];
		}
		if (!strncmp($filename, \RX_BASEDIR, strlen(\RX_BASEDIR)))
		{
			$filename = substr($filename, strlen(\RX_BASEDIR));
		}
		return $filename;
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
		// Do not display error screen in CLI.
		if (php_sapi_name() === 'cli')
		{
			return;
		}
		
		// Disable output buffering.
		while (ob_get_level())
		{
			ob_end_clean();
		}
		
		// Localize the error title.
		$title = lang('msg_server_error');
		if ($title === 'msg_server_error')
		{
			$message = 'Server Error';
		}
		
		// Localize the error message.
		$display_error_message = ini_get('display_errors') || (\Context::get('logged_info') && toBool(\Context::get('logged_info')->is_admin));
		$message = $display_error_message ? $message : lang('msg_server_error_see_log');
		if ($message === 'msg_server_error_see_log')
		{
			$message = 'Your server is configured to hide error messages. Please see your server\'s error log for details.';
		}
		
		// Display a generic error page.
		\Context::displayErrorPage($title, $message, 500);
	}
	
	/**
	 * Check if debugging is enabled for the current user.
	 * 
	 * @return bool
	 */
	public static function isEnabledForCurrentUser()
	{
		static $cache = null;
		if ($cache !== null)
		{
			return $cache;
		}
		if (!Config::get('debug.enabled'))
		{
			return $cache = false;
		}
		
		$display_to = Config::get('debug.display_to');
		switch ($display_to)
		{
			case 'everyone':
				return $cache = true;
			
			case 'ip':
				if (Filters\IpFilter::inRanges(\RX_CLIENT_IP, Config::get('debug.allow')))
				{
					return $cache = true;
				}
				if (\RX_CLIENT_IP === '127.0.0.1' || \RX_CLIENT_IP === '::1')
				{
					return $cache = true;
				}
				if (\RX_CLIENT_IP === $_SERVER['SERVER_ADDR'] || \RX_CLIENT_IP === $_SERVER['LOCAL_ADDR'])
				{
					return $cache = true;
				}
				return $cache = false;
			
			case 'admin':
			default:
				$logged_info = \Context::get('logged_info');
				if ($logged_info && $logged_info->is_admin === 'Y')
				{
					return $cache = true;
				}
				return $cache = false;
		}
	}
	
	/**
	 * Get all debug information as an object.
	 * 
	 * @return object
	 */
	public static function getDebugData()
	{
		// Collect debug information.
		$data = (object)array(
			'timestamp' => DateTime::formatTimestamp('Y-m-d H:i:s', \RX_TIME),
			'url' => getCurrentPageUrl(),
			'request' => (object)array(
				'method' => $_SERVER['REQUEST_METHOD'] . ($_SERVER['REQUEST_METHOD'] !== \Context::getRequestMethod() ? (' (' . \Context::getRequestMethod() . ')') : ''),
				'size' => intval($_SERVER['CONTENT_LENGTH']),
			),
			'response' => (object)array(
				'method' => \Context::getResponseMethod(),
				'size' => \DisplayHandler::$response_size,
			),
			'timing' => (object)array(
				'total' => sprintf('%0.4f sec', microtime(true) - \RX_MICROTIME),
				'template' => sprintf('%0.4f sec (count: %d)', $GLOBALS['__template_elapsed__'], $GLOBALS['__TemplateHandlerCalled__']),
				'xmlparse' => sprintf('%0.4f sec', $GLOBALS['__xmlparse_elapsed__']),
				'db_query' => sprintf('%0.4f sec (count: %d)', $GLOBALS['__db_elapsed_time__'], count(self::$_queries)),
				'db_class' => sprintf('%0.4f sec', $GLOBALS['__dbclass_elapsed_time__'] - $GLOBALS['__db_elapsed_time__']),
				'layout' => sprintf('%0.4f sec', $GLOBALS['__layout_compile_elapsed__']),
				'widget' => sprintf('%0.4f sec', $GLOBALS['__widget_excute_elapsed__']),
				'trans' => sprintf('%0.4f sec', $GLOBALS['__trans_content_elapsed__']),
			),
			'entries' => self::$_entries,
			'errors' => self::$_errors,
			'queries' => self::$_queries,
			'slow_queries' => self::$_slow_queries,
			'slow_triggers' => self::$_slow_triggers,
			'slow_widgets' => self::$_slow_widgets,
		);
		
		// Clean up the backtrace.
		foreach (array('entries', 'errors', 'queries', 'slow_queries') as $key)
		{
			if (!$data->$key)
			{
				continue;
			}
			foreach ($data->$key as &$entry)
			{
				if (isset($entry->file))
				{
					$entry->file = self::translateFilename($entry->file);
				}
				if (isset($entry->backtrace) && is_array($entry->backtrace))
				{
					foreach ($entry->backtrace as &$backtrace)
					{
						$backtrace['file'] = self::translateFilename($backtrace['file']);
						unset($backtrace['object'], $backtrace['args']);
					}
				}
			}
		}
		
		return $data;
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
