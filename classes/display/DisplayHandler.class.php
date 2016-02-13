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
		elseif(Context::getRequestMethod() == 'JSON' || isset($_POST['_rx_ajax_compat']))
		{
			$handler = new JSONDisplayHandler();
		}
		elseif(Context::getRequestMethod() == 'JS_CALLBACK')
		{
			$handler = new JSCallbackDisplayHandler();
		}
		elseif(Context::getRequestMethod() == 'XMLRPC')
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

		$output = $handler->toDoc($oModule);

		// call a trigger before display
		ModuleHandler::triggerCall('display', 'before', $output);
		$original_output = $output;

		// execute add-on
		$called_position = 'before_display_content';
		$oAddonController = getController('addon');
		$addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone() ? "mobile" : "pc");
		if(file_exists($addon_file)) include($addon_file);
		if($output === false || $output === null || $output instanceof Object)
		{
			$output = $original_output;
		}

		if(method_exists($handler, "prepareToPrint"))
		{
			$handler->prepareToPrint($output);
		}

		// Start the session if $_SESSION was touched
		Context::getInstance()->checkSessionStatus();

		// header output
		$httpStatusCode = $oModule->getHttpStatusCode();
		if($httpStatusCode && $httpStatusCode != 200)
		{
			self::_printHttpStatusCode($httpStatusCode);
		}
		else
		{
			if(Context::getResponseMethod() == 'JSON' || Context::getResponseMethod() == 'JS_CALLBACK')
			{
				if(strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false)
				{
					self::_printJSONHeader();
				}
			}
			else if(Context::getResponseMethod() != 'HTML')
			{
				self::_printXMLHeader();
			}
			else
			{
				self::_printHTMLHeader();
			}
		}

		// disable gzip if output already exists
		ob_flush();
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

		// debugOutput output
		$debug = $this->getDebugInfo($output);
		print $output;
		print $debug;

		flushSlowlog();
	}
	
	/**
	 * Get debug information.
	 * 
	 * @return string
	 */
	public function getDebugInfo(&$output)
	{
		// Check if debugging is enabled for this request.
		if (!config('debug.enabled'))
		{
			return;
		}
		
		// Check if debugging info should be visible to the current user.
		$display_to = config('debug.display_to');
		switch ($display_to)
		{
			case 'everyone':
				break;
			
			case 'ip':
				$allowed_ip = config('debug.allow');
				foreach ($allowed_ip as $range)
				{
					if (Rhymix\Framework\IpFilter::inRange(RX_CLIENT_IP, $range))
					{
						break 2;
					}
				}
				return;
			
			case 'admin':
			default:
				$logged_info = Context::get('logged_info');
				if ($logged_info && $logged_info->is_admin === 'Y')
				{
					break;
				}
				return;
		}
		
		// Print debug information.
		switch ($display_type = config('debug.display_type'))
		{
			case 'panel':
				$data = Rhymix\Framework\Debug::getDebugData();
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
						return;
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
						return;
					default:
						return;
				}
			
			case 'comment':
			case 'file':
			default:
				if ($display_type === 'comment' && Context::getResponseMethod() !== 'HTML')
				{
					return;
				}
				ob_start();
				$data = Rhymix\Framework\Debug::getDebugData();
				include RX_BASEDIR . 'common/tpl/debug_comment.html';
				$content = ob_get_clean();
				if ($display_type === 'file')
				{
					$debug_file = RX_BASEDIR . 'files/_debug_message.php';
					FileHandler::writeFile($debug_file, $content, 'a');
					return '';
				}
				else
				{
					return '<!--' . PHP_EOL . $content . PHP_EOL . '-->';
				}
		}
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
		header("Content-Type: text/javascript; charset=UTF-8");
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
