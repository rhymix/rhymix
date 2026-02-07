<?php

/**
 * DisplayHandler
 *
 * @author NAVER (developers@xpressengine.com)
 */
class DisplayHandler extends Handler
{
	public static $response_size = 0;
	public static $debug_printed = 0;
	public $handler = NULL;

	/**
	 * Print the response content.
	 *
	 * @param Rhymix\Framework\AbstractController $oModule
	 * @return void
	 */
	public function printContent(Rhymix\Framework\AbstractController $oModule)
	{
		// Set error location info.
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

		// Dispatch event: layout (before)
		Rhymix\Framework\Event::trigger('layout', 'before', $oModule);

		// Decide which class to use for display handling
		if (isset($oModule->response))
		{
			$handler = $oModule->response;
		}
		else
		{
			$response_method = Context::getResponseMethod();
			$response_vars = array_merge(['error' => $oModule->getError(), 'message' => $oModule->getMessage()], $oModule->getVariables());
			if (Context::get('xeVirtualRequestMethod') == 'xml')
			{
				$handler = new Rhymix\Framework\Responses\LegacyRedirectResponse(200, $response_vars);
			}
			elseif ($response_method == 'JSON' || isset($_SERVER['HTTP_X_AJAX_COMPAT']) || isset($_POST['_rx_ajax_compat']))
			{
				$handler = new Rhymix\Framework\Responses\LegacyJSONResponse(200, $response_vars);
			}
			elseif ($response_method == 'XMLRPC')
			{
				$handler = new Rhymix\Framework\Responses\LegacyXMLResponse(200, $response_vars);
			}
			elseif ($response_method == 'JS_CALLBACK')
			{
				$handler = new Rhymix\Framework\Responses\LegacyCallbackResponse(200, $response_vars);
			}
			elseif ($response_method == 'RAW')
			{
				$handler = new Rhymix\Framework\Responses\RawTemplateResponse($oModule->getHttpStatusCode());
				$handler->setTemplate($oModule->getTemplatePath(), $oModule->getTemplateFile());
				$content_type = Context::get('response_content_type');
				if ($content_type)
				{
					$handler->setContentType($content_type);
				}
			}
			else
			{
				$handler = new Rhymix\Framework\Responses\HTMLResponse($oModule->getHttpStatusCode());
				$handler->setLayout($oModule->getLayoutPath() ?? '', $oModule->getLayoutFile() ?? '');
				$handler->setTemplate($oModule->getTemplatePath() ?? '', $oModule->getTemplateFile() ?? '');
				$handler->edited_layout_file = $oModule->getEditedLayoutFile();
			}
		}

		// Apply layout.
		if ($handler instanceof Rhymix\Framework\Responses\LateRenderingResponse)
		{
			Context::setResponseMethod('RAW');
			$output = '';
		}
		else
		{
			$output = implode('', iterator_to_array($handler->render()));
		}

		// Dispatch event: display (before)
		Rhymix\Framework\Event::trigger('display', 'before', $output);
		$original_output = $output;

		// Addon execution point: 'before_display_content'.
		$called_position = 'before_display_content';
		$oAddonController = AddonController::getInstance();
		$addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone() ? 'mobile' : 'pc');
		if (file_exists($addon_file))
		{
			include $addon_file;
		}
		if ($output === false || $output === null || $output instanceof BaseObject)
		{
			$output = $original_output;
		}

		// Update the HTTP status code in case it was changed by an addon.
		$handler->setStatusCode($oModule->getHttpStatusCode());

		// Finalize the output.
		$output = $handler->finalize($output);

		// If $_SESSION was touched, start the session so that changes will be saved.
		Context::checkSessionStatus();

		// Print headers.
		$headers = $handler->getHeaders();
		foreach ($headers as $header)
		{
			header($header);
		}

		// Add security-related headers.
		if ($header_value = config('security.x_frame_options'))
		{
			header('X-Frame-Options: ' . $header_value);
		}
		if ($header_value = config('security.x_content_type_options'))
		{
			header('X-Content-Type-Options: ' . $header_value);
		}

		// Add robot headers.
		if (isset($oModule->module_info->robots_tag) && $oModule->module_info->robots_tag === 'noindex')
		{
			header('X-Robots-Tag: noindex');
		}

		// Dispatch event: display (after)
		Rhymix\Framework\Event::trigger('display', 'after', $output);

		// Measure the response size.
		self::$response_size = strlen((string)$output);

		// Clean the output buffer.
		// Existing output will be prepended to the response only if the response type is HTML.
		$buff = '';
		while (ob_get_level())
		{
			$buff .= ob_get_clean();
		}
		$buff = ltrim($buff, "\n\r\t\v\x00\x20\u{FEFF}");
		if ($handler instanceof Rhymix\Framework\Responses\HTMLResponse)
		{
			self::$response_size += strlen($buff);
			echo $buff;
		}

		// Finalize the debug data.
		$debug = self::getDebugInfo($output);

		// Output the page content.
		if ($handler instanceof Rhymix\Framework\Responses\LateRenderingResponse)
		{
			foreach ($handler->render() as $chunk)
			{
				echo $chunk;
				flush();
			}
		}
		else
		{
			echo $output;
			echo $debug;
		}
	}

	/**
	 * Get debug information.
	 *
	 * @return string
	 */
	public static function getDebugInfo(&$output = null): string
	{
		// Check if debugging information has already been printed.

		if (self::$debug_printed)
		{
			return '';
		}
		else
		{
			self::$debug_printed = 1;
		}

		// Check if debugging is enabled for this request.
		if (!Rhymix\Framework\Debug::isEnabledForCurrentUser())
		{
			return '';
		}

		// Do not display debugging information if there is no output.
		$display_types = config('debug.display_type') ?: [];
		if ($display_types && !is_array($display_types))
		{
			$display_types = array($display_types);
		}
		if ($output === null && !in_array('file', $display_types))
		{
			return '';
		}
		if ($output === null)
		{
			$output = '';
		}

		// Print debug information.
		$debug_output = '';
		$response_type = Context::getResponseMethod();
		foreach ($display_types as $display_type)
		{
			if ($display_type === 'panel')
			{
				$data = Rhymix\Framework\Debug::getDebugData();
				$display_content = array_fill_keys(config('debug.display_content'), true);
				if (!isset($display_content['request_info']))
				{
					unset($data->timestamp, $data->url, $data->request, $data->response, $data->memory, $data->timing);
				}
				if (!isset($display_content['entries']))
				{
					$data->entries = null;
				}
				if (!isset($display_content['errors']))
				{
					unset($data->errors);
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

				switch ($response_type)
				{
					case 'HTML':
						$json_options = defined('JSON_PRETTY_PRINT') ? (JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 0;
						$panel_script = sprintf('<script src="%s%s?t=%d"></script>', RX_BASEURL, 'common/js/debug.js', filemtime(RX_BASEDIR . 'common/js/debug.js'));
						$panel_script .= "\n<script>\nRhymix.currentDebugData = " . json_encode($data, $json_options) . ";\n</script>";
						$body_end_position = strrpos($output, '</body>') ?: strlen($output);
						$output = substr($output, 0, $body_end_position) . "\n$panel_script\n" . substr($output, $body_end_position);
						break;
					case 'JSON':
						unset($_SESSION['_rx_debug_previous']);
						if (preg_match('/^(.+)\}\n?$/', $output, $matches))
						{
							$data = json_encode($data);
							if (json_last_error() === JSON_ERROR_NONE)
							{
								$output = $matches[1] . ',"_rx_debug":' . $data . "}\n";
							}
						}
						break;
					default:
						break;
				}
			}
			else
			{
				if ($display_type === 'comment' && $response_type !== 'HTML')
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
	 * @deprecated
	 */
	public static function _printHttpStatusCode($code)
	{
		$statusMessage = Context::get('http_status_message');
		header("HTTP/1.1 $code $statusMessage");
	}
}
