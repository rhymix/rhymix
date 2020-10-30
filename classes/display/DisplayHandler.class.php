<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class DisplayHandler
 * @author NAVER (developers@xpressengine.com)
 *  DisplayHandler is responsible for displaying the execution result. \n
 *  Depending on the request type, it can display either HTML or XML content.\n
 *  Xml content is simple xml presentation of variables in oModule while html content
 *   is the combination of the variables of oModue and template files/.
 */
class DisplayHandler extends Handler
{
	public static $response_size = 0;
	public static $debug_printed = 0;
	var $content_size = 0; // /< The size of displaying contents
	var $gz_enabled = FALSE; // / <a flog variable whether to call contents after compressing by gzip
	var $handler = NULL;

	/**
	 * print either html or xml content given oModule object
	 * @remark addon execution and the trigger execution are included within this method, which might create inflexibility for the fine grained caching
	 * @param ModuleObject $oModule the module object
	 * @return void
	 */
	public function printContent(&$oModule)
	{
		// Check if the gzip encoding supported
		if(config('view.use_gzip') && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && extension_loaded('zlib') && $oModule->gzhandler_enable)
		{
			$this->gz_enabled = TRUE;
		}

		// Extract contents to display by the request method
		if(Context::get('xeVirtualRequestMethod') == 'xml')
		{
			$handler = new VirtualXMLDisplayHandler();
		}
		elseif(Context::getResponseMethod() == 'JSON' || isset($_POST['_rx_ajax_compat']))
		{
			$handler = new JSONDisplayHandler();
		}
		elseif(Context::getResponseMethod() == 'JS_CALLBACK')
		{
			$handler = new JSCallbackDisplayHandler();
		}
		elseif(Context::getResponseMethod() == 'XMLRPC')
		{
			$handler = new XMLDisplayHandler();
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
			{
				$this->gz_enabled = FALSE;
			}
		}
		else
		{
			$handler = new HTMLDisplayHandler();
		}
		
		// Handle error location info
		if ($location = $oModule->get('rx_error_location'))
		{
			if (!Rhymix\Framework\Debug::isEnabledForCurrentUser())
			{
				$oModule->unset('rx_error_location');
			}
			elseif (starts_with(\RX_BASEDIR, $location))
			{
				$oModule->add('rx_error_location', $location = substr($location, strlen(\RX_BASEDIR)));
			}
		}

		// call a trigger before layout
		ModuleHandler::triggerCall('layout', 'before', $oModule);

		// apply layout
		$output = $handler->toDoc($oModule);

		// call a trigger before display
		ModuleHandler::triggerCall('display', 'before', $output);
		$original_output = $output;

		// execute add-on
		$called_position = 'before_display_content';
		$oAddonController = AddonController::getInstance();
		$addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone() ? "mobile" : "pc");
		if(file_exists($addon_file)) include($addon_file);
		if($output === false || $output === null || $output instanceof BaseObject)
		{
			$output = $original_output;
		}

		if(method_exists($handler, "prepareToPrint"))
		{
			$handler->prepareToPrint($output);
		}

		// Start the session if $_SESSION was touched
		Context::checkSessionStatus();

		// header output
		$httpStatusCode = $oModule->getHttpStatusCode();
		if($httpStatusCode !== 200 && !in_array(Context::getResponseMethod(), array('XMLRPC', 'JSON', 'JS_CALLBACK')))
		{
			self::_printHttpStatusCode($httpStatusCode);
		}
		else
		{
			if(Context::getResponseMethod() == 'JSON' || Context::getResponseMethod() == 'JS_CALLBACK' || isset($_POST['_rx_ajax_compat']))
			{
				self::_printJSONHeader();
			}
			elseif(Context::getResponseMethod() == 'XMLRPC')
			{
				self::_printXMLHeader();
			}
			else
			{
				self::_printHTMLHeader();
			}
		}

		// disable gzip if output already exists
		while (ob_get_level())
		{
			ob_end_flush();
		}
		if(headers_sent())
		{
			$this->gz_enabled = FALSE;
		}

		// enable gzip using zlib extension
		if($this->gz_enabled)
		{
			ini_set('zlib.output_compression', true);
		}

		// call a trigger after display
		self::$response_size = $this->content_size = strlen($output);
		ModuleHandler::triggerCall('display', 'after', $output);

		// Output the page content and debug data.
		$debug = self::getDebugInfo($output);
		print $output;
		print $debug;
	}
	
	/**
	 * Get debug information.
	 * 
	 * @return string
	 */
	public static function getDebugInfo(&$output = null)
	{
		// Check if debugging information has already been printed.
		
		if (self::$debug_printed)
		{
			return;
		}
		else
		{
			self::$debug_printed = 1;
		}
		
		// Check if debugging is enabled for this request.
		if (!config('debug.enabled') || !Rhymix\Framework\Debug::isEnabledForCurrentUser())
		{
			return;
		}
		
		// Do not display debugging information if there is no output.
		$display_types = config('debug.display_type') ?: [];
		if ($display_types && !is_array($display_types))
		{
			$display_types = array($display_types);
		}
		if ($output === null && !in_array('file', $display_types))
		{
			return;
		}
		
		// Print debug information.
		$debug_output = '';
		foreach ($display_types as $display_type)
		{
			if ($display_type === 'panel')
			{
				$data = Rhymix\Framework\Debug::getDebugData();
				$display_content = array_fill_keys(config('debug.display_content'), true);
				if (!isset($display_content['entries']))
				{
					$data->entries = null;
				}
				if (!isset($display_content['queries']))
				{
					unset($data->queries);
				}
				if (!isset($display_content['slow_queries']))
				{
					unset($data->slow_queries);
				}
				if (!isset($display_content['slow_triggers']))
				{
					unset($data->slow_triggers);
				}
				if (!isset($display_content['slow_widgets']))
				{
					unset($data->slow_widgets);
				}
				if (!isset($display_content['slow_remote_requests']))
				{
					unset($data->slow_remote_requests);
				}
				if ($data->entries)
				{
					foreach ($data->entries as &$entry)
					{
						if (is_scalar($entry->message))
						{
							$entry->message = var_export($entry->message, true);
						}
						else
						{
							$entry->message = trim(print_r($entry->message, true));
						}
					}
				}
				switch (Context::getResponseMethod())
				{
					case 'HTML':
						$json_options = defined('JSON_PRETTY_PRINT') ? (JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 0;
						$panel_script = sprintf('<script src="%s%s?%s"></script>', RX_BASEURL, 'common/js/debug.js', filemtime(RX_BASEDIR . 'common/js/debug.js'));
						if (isset($_SESSION['_rx_debug_previous']))
						{
							$panel_script .= "\n<script>\nvar rhymix_debug_previous = " . json_encode($_SESSION['_rx_debug_previous'], $json_options) . ";\n</script>";
							unset($_SESSION['_rx_debug_previous']);
						}
						$panel_script .= "\n<script>\nvar rhymix_debug_content = " . json_encode($data, $json_options) . ";\n</script>";
						$body_end_position = strrpos($output, '</body>') ?: strlen($output);
						$output = substr($output, 0, $body_end_position) . "\n$panel_script\n" . substr($output, $body_end_position);
						break;
					case 'JSON':
						if (RX_POST && preg_match('/^proc/', Context::get('act')))
						{
							$data->ajax_module = Context::get('module');
							$data->ajax_act = Context::get('act');
							$_SESSION['_rx_debug_previous'] = $data;
						}
						else
						{
							unset($_SESSION['_rx_debug_previous']);
						}
						if (preg_match('/^(.+)\}$/', $output, $matches))
						{
							$output = $matches[1] . ',"_rx_debug":' . json_encode($data) . '}';
						}
						break;
					default:
						break;
				}
			}
			else
			{
				if ($display_type === 'comment' && Context::getResponseMethod() !== 'HTML')
				{
					continue;
				}
				ob_start();
				$data = Rhymix\Framework\Debug::getDebugData();
				$display_content = array_fill_keys(config('debug.display_content'), true);
				include RX_BASEDIR . 'common/tpl/debug_comment.html';
				$content = preg_replace('/\n{2,}/', "\n\n", trim(ob_get_clean())) . "\n";
				if ($display_type === 'file')
				{
					$log_filename = config('debug.log_filename') ?: 'files/debug/YYYYMMDD.php';
					$log_filename = str_replace(array('YYYY', 'YY', 'MM', 'DD'), array(
						getInternalDateTime(RX_TIME, 'Y'),
						getInternalDateTime(RX_TIME, 'y'),
						getInternalDateTime(RX_TIME, 'm'),
						getInternalDateTime(RX_TIME, 'd'),
					), $log_filename);
					$log_filename = RX_BASEDIR . $log_filename;
					if (!file_exists($log_filename) || !filesize($log_filename))
					{
						$phpheader = '<?php exit; ?>' . "\n";
					}
					else
					{
						$phpheader = '';
					}
					FileHandler::writeFile($log_filename, $phpheader . $content . "\n", 'a');
					$debug_output .= '';
				}
				else
				{
					$debug_output .= '<!--' . "\n" . $content . "\n" . '-->' . "\n";
				}
			}
		}
		
		return $debug_output;
	}

	/**
	 * print a HTTP HEADER for XML, which is encoded in UTF-8
	 * @return void
	 */
	public static function _printXMLHeader()
	{
		header("Content-Type: text/xml; charset=UTF-8");
	}

	/**
	 * print a HTTP HEADER for HTML, which is encoded in UTF-8
	 * @return void
	 */
	public static function _printHTMLHeader()
	{
		header("Content-Type: text/html; charset=UTF-8");
	}

	/**
	 * print a HTTP HEADER for JSON, which is encoded in UTF-8
	 * @return void
	 */
	public static function _printJSONHeader()
	{
		header("Content-Type: application/json; charset=UTF-8");
	}

	/**
	 * print a HTTP HEADER for HTML, which is encoded in UTF-8
	 * @return void
	 */
	public static function _printHttpStatusCode($code)
	{
		$statusMessage = Context::get('http_status_message');
		header("HTTP/1.0 $code $statusMessage");
	}

}
/* End of file DisplayHandler.class.php */
/* Location: ./classes/display/DisplayHandler.class.php */
