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
	protected static $_enabled = null;
	protected static $_config = array();
	protected static $_aliases = array();
	protected static $_entries = array();
	protected static $_errors = array();
	protected static $_queries = array();
	protected static $_slow_queries = array();
	protected static $_triggers = array();
	protected static $_slow_triggers = array();
	protected static $_widgets = array();
	protected static $_slow_widgets = array();
	protected static $_remote_requests = array();
	protected static $_slow_remote_requests = array();
	protected static $_session_time = 0;
	protected static $_query_time = 0;

	/**
	 * Enable log collection.
	 *
	 * @return void
	 */
	public static function enable(): void
	{
		self::$_enabled = true;
	}

	/**
	 * Disable log collection.
	 *
	 * @return void
	 */
	public static function disable(): void
	{
		self::$_enabled = false;
	}

	/**
	 * Get all entries.
	 *
	 * @return array
	 */
	public static function getEntries(): array
	{
		return array_values(self::$_entries);
	}

	/**
	 * Clear all entries.
	 *
	 * @return void
	 */
	public static function clearEntries(): void
	{
		self::$_entries = array();
	}

	/**
	 * Get all errors.
	 *
	 * @return array
	 */
	public static function getErrors(): array
	{
		return array_values(self::$_errors);
	}

	/**
	 * Clear all errors.
	 *
	 * @return void
	 */
	public static function clearErrors(): void
	{
		self::$_errors = array();
	}

	/**
	 * Get all queries.
	 *
	 * @return array
	 */
	public static function getQueries(): array
	{
		return array_values(self::$_queries);
	}

	/**
	 * Get all slow queries.
	 *
	 * @return array
	 */
	public static function getSlowQueries(): array
	{
		return self::$_slow_queries;
	}

	/**
	 * Clear all queries.
	 *
	 * @return void
	 */
	public static function clearQueries(): void
	{
		self::$_queries = array();
		self::$_slow_queries = array();
	}

	/**
	 * Get all triggers.
	 *
	 * @return array
	 */
	public static function getTriggers(): array
	{
		return self::$_triggers;
	}

	/**
	 * Get all slow triggers.
	 *
	 * @return array
	 */
	public static function getSlowTriggers(): array
	{
		return self::$_slow_triggers;
	}

	/**
	 * Clear all triggers.
	 *
	 * @return void
	 */
	public static function clearTriggers(): void
	{
		self::$_triggers = array();
		self::$_slow_triggers = array();
	}

	/**
	 * Get all widgets.
	 *
	 * @return array
	 */
	public static function getWidgets(): array
	{
		return self::$_widgets;
	}

	/**
	 * Get all slow widgets.
	 *
	 * @return array
	 */
	public static function getSlowWidgets(): array
	{
		return self::$_slow_widgets;
	}

	/**
	 * Clear all widgets.
	 *
	 * @return void
	 */
	public static function clearWidgets(): void
	{
		self::$_widgets = array();
		self::$_slow_widgets = array();
	}

	/**
	 * Get all remote requests.
	 *
	 * @return array
	 */
	public static function getRemoteRequests(): array
	{
		return self::$_remote_requests;
	}

	/**
	 * Get all slow remote requests.
	 *
	 * @return array
	 */
	public static function getSlowRemoteRequests(): array
	{
		return self::$_slow_remote_requests;
	}

	/**
	 * Clear all remote requests.
	 *
	 * @return void
	 */
	public static function clearRemoteRequests(): void
	{
		self::$_remote_requests = array();
		self::$_slow_remote_requests = array();
	}

	/**
	 * Clear all records.
	 *
	 * @return void
	 */
	public static function clearAll(): void
	{
		self::$_entries = array();
		self::$_errors = array();
		self::$_queries = array();
		self::$_slow_queries = array();
		self::$_triggers = array();
		self::$_slow_triggers = array();
		self::$_widgets = array();
		self::$_slow_widgets = array();
		self::$_remote_requests = array();
		self::$_slow_remote_requests = array();
		self::$_session_time = 0;
		self::$_query_time = 0;
	}

	/**
	 * Add a filename alias.
	 *
	 * @param string $display_filename
	 * @param string $real_filename
	 * @return void
	 */
	public static function addFilenameAlias(string $display_filename, string $real_filename): void
	{
		self::$_aliases[$real_filename] = $display_filename;
	}

	/**
	 * Add session start time.
	 *
	 * @param float $session_start_time
	 * @return void
	 */
	public static function addSessionStartTime(float $session_start_time): void
	{
		self::$_session_time += $session_start_time;
	}

	/**
	 * Add an arbitrary entry to the log.
	 *
	 * @param mixed $message
	 * @return void
	 */
	public static function addEntry($message): void
	{
		// Do not store log if disabled.
		if (!self::$_enabled)
		{
			return;
		}

		// Get the backtrace.
		$backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
		if (count($backtrace) > 1 && $backtrace[1]['function'] === 'debugPrint' && empty($backtrace[1]['class']))
		{
			array_shift($backtrace);
		}

		// Create a log entry.
		$entry = (object)array(
			'message' => unserialize(serialize($message)),
			'file' => isset($backtrace[0]['file']) ? $backtrace[0]['file'] : null,
			'line' => isset($backtrace[0]['line']) ? $backtrace[0]['line'] : 0,
			'backtrace' => $backtrace,
			'count' => 1,
			'time' => microtime(true),
			'type' => 'Debug',
		);

		// Consolidate entries.
		if (!isset(self::$_config['consolidate']) || self::$_config['consolidate'] === true)
		{
			$key = hash_hmac('sha1', serialize([$entry->message, $entry->file, $entry->line]), config('crypto.authentication_key'));
			if (isset(self::$_entries[$key]))
			{
				self::$_entries[$key]->count++;
			}
			else
			{
				self::$_entries[$key] = $entry;
			}
		}
		else
		{
			$entry->count = 0;
			self::$_entries[] = $entry;
		}

		// Add the entry to the error log.
		if (isset(self::$_config['write_error_log']) && self::$_config['write_error_log'] === 'all')
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
	 * @return void
	 */
	public static function addError(int $errno, string $errstr, string $errfile, int $errline): void
	{
		// Do not store log if disabled.
		if (!self::$_enabled)
		{
			return;
		}

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
		$backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);

		// Prepare the error entry.
		$errinfo = (object)array(
			'message' => $message,
			'file' => $errfile,
			'line' => $errline,
			'backtrace' => $backtrace,
			'count' => 1,
			'time' => microtime(true),
			'type' => self::getErrorType($errno),
		);

		// If the same error is repeated, only increment the counter.
		if (!isset(self::$_config['consolidate']) || self::$_config['consolidate'] === true)
		{
			$key = hash_hmac('sha1', serialize([$errinfo->message, $errinfo->file, $errinfo->line]), config('crypto.authentication_key'));
			if (isset(self::$_errors[$key]))
			{
				self::$_errors[$key]->count++;
			}
			else
			{
				self::$_errors[$key] = $errinfo;
			}
		}
		else
		{
			$errinfo->count = 0;
			self::$_errors[] = $errinfo;
		}

		// Add the entry to the error log.
		if (isset(self::$_config['write_error_log']) && self::$_config['write_error_log'] === 'all')
		{
			$log_entry = strtr(sprintf('PHP %s: %s in %s on line %d', $errinfo->type, $errstr, $errfile, intval($errline)), "\0\r\n\t\v\e\f", '       ');
			error_log($log_entry . \PHP_EOL . self::formatBacktrace($backtrace));
		}
	}

	/**
	 * Add a query to the log.
	 *
	 * @param array $query
	 * @return void
	 */
	public static function addQuery(array $query): void
	{
		// Do not store log if disabled.
		if (!self::$_enabled)
		{
			return;
		}

		// Prepare the log entry.
		$query_object = (object)array(
			'query_id' => $query['query_id'],
			'query_time' => floatval($query['elapsed_time']),
			'query_string' => $query['query'],
			'query_connection' => $query['connection'],
			'message' => $query['result'] === 'success' ? 'success' : $query['errstr'],
			'error_code' => $query['result'] === 'success' ? 0 : $query['errno'],
			'file' => $query['called_file'],
			'line' => $query['called_line'],
			'method' => $query['called_method'],
			'backtrace' => $query['backtrace'] ?: array(),
			'count' => 1,
			'time' => microtime(true),
			'type' => 'Query',
		);

		// Consolidate repeated queries.
		if (!isset(self::$_config['consolidate']) || self::$_config['consolidate'] === true)
		{
			// Generate a unique key for this query.
			$key = hash_hmac('sha1', serialize([
				$query_object->query_id,
				$query_object->query_string,
				$query_object->query_connection,
				$query_object->file,
				$query_object->line,
				$query_object->method,
			]), config('crypto.authentication_key'));

			// If the same query is repeated, only increment the counter.
			if (isset(self::$_queries[$key]))
			{
				self::$_queries[$key]->query_time += $query_object->query_time;
				self::$_queries[$key]->count++;
			}
			else
			{
				self::$_queries[$key] = $query_object;
			}
		}
		else
		{
			$query_object->count = 0;
			self::$_queries[] = $query_object;
		}

		// Record query time.
		self::$_query_time += $query_object->query_time;

		// Add the query to the error log if the result wasn't successful.
		if ($query['result'] === 'error')
		{
			$error_object = (object)array(
				'message' => $query['errstr'] . ' (code ' . intval($query['errno']) . ')',
				'file' => $query_object->file,
				'line' => $query_object->line,
				'backtrace' => $query_object->backtrace ?: array(),
				'count' => 1,
				'time' => $query_object->time,
				'type' => 'Query Error',
			);

			// Consolidate repeated queries.
			if (!isset(self::$_config['consolidate']) || self::$_config['consolidate'] === true)
			{
				$key = hash_hmac('sha1', serialize(['QUERY ERROR', $error_object->message, $error_object->file, $error_object->line]), config('crypto.authentication_key'));
				if (isset(self::$_errors[$key]))
				{
					self::$_errors[$key]->count++;
				}
				else
				{
					self::$_errors[$key] = $error_object;
				}
			}
			else
			{
				$error_object->count = 0;
				self::$_errors[] = $error_object;
			}

			// Add the entry to the error log.
			if (self::$_config['write_error_log'] === 'all')
			{
				$log_entry = strtr(sprintf('Query Error: %s in %s on line %d', $error_object->message, $error_object->file, intval($error_object->line)), "\0\r\n\t\v\e\f", '       ');
				error_log($log_entry . \PHP_EOL . self::formatBacktrace($error_object->backtrace));
			}
		}

		// Add the query to the slow query log.
		if ($query_object->query_time && $query_object->query_time >= (self::$_config['log_slow_queries'] ?? 1))
		{
			self::$_slow_queries[] = $query_object;
		}
	}

	/**
	 * Add a trigger to the log.
	 *
	 * @param array $trigger
	 * @return bool
	 */
	public static function addTrigger(array $trigger): void
	{
		// Do not store log if disabled.
		if (!self::$_enabled)
		{
			return;
		}

		// Prepare the log entry.
		$trigger_object = (object)array(
			'trigger_name' => $trigger['name'],
			'trigger_target' => $trigger['target'],
			'trigger_plugin' => $trigger['target_plugin'],
			'trigger_time' => $trigger['elapsed_time'],
			'message' => null,
			'file' => null,
			'line' => null,
			'backtrace' => array(),
			'time' => microtime(true),
			'type' => 'Trigger',
		);

		self::$_triggers[] = $trigger_object;
		if ($trigger_object->trigger_time && $trigger_object->trigger_time >= (self::$_config['log_slow_triggers'] ?? 1))
		{
			self::$_slow_triggers[] = $trigger_object;
		}
	}

	/**
	 * Add a widget to the log.
	 *
	 * @param array $widget
	 * @return void
	 */
	public static function addWidget(array $widget): void
	{
		// Do not store log if disabled.
		if (!self::$_enabled)
		{
			return;
		}

		// Prepare the log entry.
		$widget_object = (object)array(
			'widget_name' => $widget['name'],
			'widget_time' => $widget['elapsed_time'],
			'message' => null,
			'file' => null,
			'line' => null,
			'backtrace' => array(),
			'time' => microtime(true),
			'type' => 'Widget',
		);

		self::$_widgets[] = $widget_object;
		if ($widget_object->widget_time && $widget_object->widget_time >= (self::$_config['log_slow_widgets'] ?? 1))
		{
			self::$_slow_widgets[] = $widget_object;
		}
	}

	/**
	 * Add a remote request to the log.
	 *
	 * @param array $request
	 * @return void
	 */
	public static function addRemoteRequest(array $request): void
	{
		// Do not store log if disabled.
		if (!self::$_enabled)
		{
			return;
		}

		// Prepare the log entry.
		$request_object = (object)array(
			'url' => $request['url'],
			'verb' => $request['verb'],
			'status' => $request['status'],
			'elapsed_time' => $request['elapsed_time'],
			'redirect_to' => $request['redirect_to'],
			'message' => null,
			'file' => $request['called_file'],
			'line' => $request['called_line'],
			'method' => $request['called_method'],
			'backtrace' => $request['backtrace'],
			'time' => microtime(true),
			'type' => $request['type'],
		);

		self::$_remote_requests[] = $request_object;

		if (!isset($GLOBALS['__remote_request_elapsed__']))
		{
			$GLOBALS['__remote_request_elapsed__'] = 0;
		}
		$GLOBALS['__remote_request_elapsed__'] += $request_object->elapsed_time;

		if ($request_object->elapsed_time && is_numeric($request_object->elapsed_time) && $request_object->elapsed_time >= (self::$_config['log_slow_remote_requests'] ?? 1))
		{
			self::$_slow_remote_requests[] = $request_object;
		}
	}

	/**
	 * The default handler for catching exceptions.
	 *
	 * @param \Throwable $e
	 * @return void
	 */
	public static function exceptionHandler(\Throwable $e): void
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
				$caller_errfile = self::translateFilename($trace['file'] ?? '');
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

		if (!isset(self::$_config['write_error_log']) || self::$_config['write_error_log'] !== 'none')
		{
			error_log('PHP Exception: ' . $log_entry . \PHP_EOL . self::formatBacktrace($e->getTrace()));
		}

		// Display the error screen.
		self::displayErrorScreen($log_entry, $errfile . ':' . $e->getLine());
		exit;
	}

	/**
	 * The default handler for catching fatal errors.
	 *
	 * @return void
	 */
	public static function shutdownHandler(): void
	{
		// Check if we are exiting because of a fatal error.
		$errinfo = error_get_last();
		if ($errinfo === null || ($errinfo['type'] !== 1 && $errinfo['type'] !== 4))
		{
			return;
		}

		// Find out the file where the error really occurred.
		$errinfo['file'] = self::translateFilename($errinfo['file'] ?? '');

		// Add the entry to the error log.
		$message = sprintf('%s in %s on line %d', $errinfo['message'], $errinfo['file'], intval($errinfo['line']));
		$log_entry = str_replace("\0", '', 'PHP ' . self::getErrorType($errinfo['type']) . ': ' . $message);
		if (!isset(self::$_config['write_error_log']) || self::$_config['write_error_log'] !== 'none')
		{
			error_log($log_entry);
		}

		// Display the error screen.
		self::displayErrorScreen($log_entry, $errinfo['file'] . ':' . intval($errinfo['line']));
	}

	/**
	 * Format a backtrace for error logging.
	 *
	 * @param array $backtrace
	 * @return string
	 */
	public static function formatBacktrace(array $backtrace): string
	{
		$result = array();
		foreach ($backtrace as $step)
		{
			$stepstr = '#' . count($result) . ' ';
			$stepstr .= ($step['file'] ?? 'unknown') . '(' . ($step['line'] ?? 0) . ')';
			if ($step['function'])
			{
				$stepstr .= ': ' . ((isset($step['type']) && $step['type']) ? ($step['class'] . $step['type'] . $step['function']) : $step['function']) . '()';
			}
			$result[] = strtr($stepstr, "\0\r\n\t\v\e\f", '       ');
		}
		return implode(\PHP_EOL, $result);
	}

	/**
	 * Translate filenames.
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function translateFilename(string $filename)
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
	 * @param int $error_types
	 * @return void
	 */
	public static function registerErrorHandlers(int $error_types): void
	{
		self::$_config = config('debug');
		set_error_handler('\\Rhymix\\Framework\\Debug::addError', $error_types);
		set_exception_handler('\\Rhymix\\Framework\\Debug::exceptionHandler');
		register_shutdown_function('\\Rhymix\\Framework\\Debug::shutdownHandler');
	}

	/**
	 * Display a fatal error screen.
	 *
	 * @param string $message
	 * @param string $location
	 * @return void
	 */
	public static function displayErrorScreen(string $message, string $location = ''): void
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
		$display_error_message = ini_get('display_errors') || !\Context::isInstalled() || Session::isAdmin() || self::isEnabledForCurrentUser();
		$message = $display_error_message ? $message : lang('msg_server_error_see_log');
		if ($message === 'msg_server_error_see_log')
		{
			$message = 'Your server is configured to hide error messages. Please see your server\'s error log for details.';
		}

		// Display a generic error page.
		try
		{
			\Context::displayErrorPage($title, $message, 500, $location);
		}
		catch (\Error $e)
		{
			self::displayError($message);
		}
	}

	/**
	 * Display a default error.
	 *
	 * @param string $message
	 * @return void
	 */
	public static function displayError(string $message): void
	{
		header('HTTP/1.1 500 Internal Server Error');
		if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SERVER['HTTP_X_REQUESTED_WITH']))
		{
			header('Content-Type: text/html; charset=UTF-8');
			echo sprintf('<html><head><meta charset="UTF-8" /><title>Server Error</title></head><body>%s</body></html>', escape($message, false));
		}
		else
		{
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('error' => -1, 'message' => escape($message, false)), \JSON_UNESCAPED_UNICODE);
		}
	}

	/**
	 * Check if debugging is enabled for the current user.
	 *
	 * @return bool
	 */
	public static function isEnabledForCurrentUser(): bool
	{
		if (self::$_enabled !== null)
		{
			return self::$_enabled;
		}
		if (!is_array(self::$_config) || !self::$_config['enabled'])
		{
			return self::$_enabled = false;
		}

		switch (self::$_config['display_to'])
		{
			case 'everyone':
				return self::$_enabled = true;

			case 'ip':
				if (Filters\IpFilter::inRanges(\RX_CLIENT_IP, self::$_config['allow']))
				{
					return self::$_enabled = true;
				}
				if (\RX_CLIENT_IP === '127.0.0.1' || \RX_CLIENT_IP === '::1')
				{
					return self::$_enabled = true;
				}
				if (\RX_CLIENT_IP === $_SERVER['SERVER_ADDR'] || \RX_CLIENT_IP === $_SERVER['LOCAL_ADDR'])
				{
					return self::$_enabled = true;
				}
				return self::$_enabled = false;

			case 'admin':
			default:
				$logged_info = \Context::get('logged_info');
				if (!isset($logged_info))
				{
					return true;
				}
				elseif (is_object($logged_info) && method_exists($logged_info, 'isAdmin'))
				{
					return self::$_enabled = $logged_info->isAdmin();
				}
				else
				{
					return self::$_enabled = false;
				}
		}
	}

	/**
	 * Get all debug information as an object.
	 *
	 * @return object
	 */
	public static function getDebugData(): object
	{
		// Collect debug information.
		$db = DB::getInstance();
		$data = (object)array(
			'timestamp' => DateTime::formatTimestamp('Y-m-d H:i:s', \RX_TIME),
			'url' => getCurrentPageUrl(),
			'request' => (object)array(
				'method' => $_SERVER['REQUEST_METHOD'] . ($_SERVER['REQUEST_METHOD'] !== \Context::getRequestMethod() ? (' (' . \Context::getRequestMethod() . ')') : ''),
				'size' => intval($_SERVER['CONTENT_LENGTH'] ?? 0),
			),
			'response' => (object)array(
				'method' => \Context::getResponseMethod(),
				'size' => \DisplayHandler::$response_size,
			),
			'memory' => memory_get_peak_usage(),
			'timing' => (object)array(
				'total' => sprintf('%0.4f sec', microtime(true) - \RX_MICROTIME),
				'db_query' => sprintf('%0.4f sec (count: %d)', self::$_query_time, count(self::$_queries)),
				'db_class' => sprintf('%0.4f sec', max(0, $db->getTotalElapsedTime() - self::$_query_time)),
				'layout' => sprintf('%0.4f sec', $GLOBALS['__layout_compile_elapsed__'] ?? 0),
				'widget' => sprintf('%0.4f sec', $GLOBALS['__widget_excute_elapsed__'] ?? 0),
				'remote' => sprintf('%0.4f sec', $GLOBALS['__remote_request_elapsed__'] ?? 0),
				'session' => sprintf('%0.4f sec', self::$_session_time),
				'xmlparse' => sprintf('%0.4f sec', $GLOBALS['__xmlparse_elapsed__'] ?? 0),
				'template' => sprintf('%0.4f sec (count: %d)', $GLOBALS['__template_elapsed__'] ?? 0, $GLOBALS['__TemplateHandlerCalled__'] ?? 0),
				'trans' => sprintf('%0.4f sec', $GLOBALS['__trans_content_elapsed__'] ?? 0),
			),
			'entries' => array_values(self::$_entries),
			'errors' => array_values(self::$_errors),
			'queries' => array_values(self::$_queries),
			'slow_queries' => self::$_slow_queries,
			'slow_triggers' => self::$_slow_triggers,
			'slow_widgets' => self::$_slow_widgets,
			'slow_remote_requests' => self::$_slow_remote_requests,
		);

		// Clean up the querystring.
		if (isset($data->queries))
		{
			foreach ($data->queries as $query)
			{
				$query->query_string = trim(utf8_normalize_spaces($query->query_string, true));
			}
		}

		// Clean up the backtrace.
		foreach (array('entries', 'errors', 'queries', 'slow_queries', 'remote_requests', 'slow_remote_requests') as $key)
		{
			if (!isset($data->$key) || !is_array($data->$key))
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
						if (isset($backtrace['file']))
						{
							$backtrace['file'] = self::translateFilename($backtrace['file']);
						}
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
	public static function getErrorType(int $errno): string
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
			case \E_USER_WARNING: return 'Warning';
			case \E_USER_NOTICE: return 'Notice';
			case \E_STRICT: return 'Strict Standards';
			case \E_PARSE: return 'Parse Error';
			case \E_DEPRECATED: return 'Deprecated';
			case \E_USER_DEPRECATED: return 'User Deprecated';
			case \E_RECOVERABLE_ERROR: return 'Catchable Fatal Error';
			default: return 'Error';
		}
	}
}
