<?php

/**
 * Legacy function library for XE Compatibility
 *
 * Copyright (c) NAVER <http://www.navercorp.com>
 */

/**
 * Define a function to use {@see ModuleHandler::getModuleObject()} ($module_name, $type)
 *
 * @param string $module_name The module name to get a instance
 * @param string $type disp, proc, controller, class
 * @param string $kind admin, null
 * @return mixed Module instance
 */
function getModule($module_name, $type = 'view', $kind = '')
{
	return ModuleHandler::getModuleInstance($module_name, $type, $kind);
}

/**
 * Create a controller instance of the module
 *
 * @param string $module_name The module name to get a controller instance
 * @return mixed Module controller instance
 */
function getController($module_name)
{
	return getModule($module_name, 'controller');
}

/**
 * Create a admin controller instance of the module
 *
 * @param string $module_name The module name to get a admin controller instance
 * @return mixed Module admin controller instance
 */
function getAdminController($module_name)
{
	return getModule($module_name, 'controller', 'admin');
}

/**
 * Create a view instance of the module
 *
 * @param string $module_name The module name to get a view instance
 * @return mixed Module view instance
 */
function getView($module_name)
{
	return getModule($module_name, 'view');
}

/**
 * Create a admin view instance of the module
 *
 * @param string $module_name The module name to get a admin view instance
 * @return mixed Module admin view instance
 */
function getAdminView($module_name)
{
	return getModule($module_name, 'view', 'admin');
}

/**
 * Create a model instance of the module
 *
 * @param string $module_name The module name to get a model instance
 * @return mixed Module model instance
 */
function getModel($module_name)
{
	return getModule($module_name, 'model');
}

/**
 * Create an admin model instance of the module
 *
 * @param string $module_name The module name to get a admin model instance
 * @return mixed Module admin model instance
 */
function getAdminModel($module_name)
{
	return getModule($module_name, 'model', 'admin');
}

/**
 * Create an api instance of the module
 *
 * @param string $module_name The module name to get a api instance
 * @return mixed Module api class instance
 */
function getAPI($module_name)
{
	return getModule($module_name, 'api');
}

/**
 * Create a mobile instance of the module
 *
 * @param string $module_name The module name to get a mobile instance
 * @return mixed Module mobile instance
 */
function getMobile($module_name)
{
	return getModule($module_name, 'mobile');
}

/**
 * Create a wap instance of the module
 *
 * @param string $module_name The module name to get a wap instance
 * @return mixed Module wap class instance
 */
function getWAP($module_name)
{
	return getModule($module_name, 'wap');
}

/**
 * Create a class instance of the module
 *
 * @param string $module_name The module name to get a class instance
 * @return mixed Module class instance
 */
function getClass($module_name)
{
	return getModule($module_name, 'class');
}

/**
 * The alias of DB::executeQuery()
 *
 * @see DB::executeQuery()
 * @param string $query_id (module name.query XML file)
 * @param object $args values of args object
 * @param string[] $arg_columns Column list
 * @return object Query result data
 */
function executeQuery($query_id, $args = NULL, $arg_columns = NULL)
{
	$oDB = DB::getInstance();
	return $oDB->executeQuery($query_id, $args, $arg_columns);
}

/**
 * Function to handle the result of DB::executeQuery() as an array
 *
 * @see DB::executeQuery()
 * @see executeQuery()
 * @param string $query_id (module name.query XML file)
 * @param object $args values of args object
 * @param string[] $arg_columns Column list
 * @return object Query result data
 */
function executeQueryArray($query_id, $args = NULL, $arg_columns = NULL)
{
	$oDB = DB::getInstance();
	$output = $oDB->executeQuery($query_id, $args, $arg_columns);
	if(isset($output->data) && !is_array($output->data) && count($output->data) > 0)
	{
		$output->data = array($output->data);
	}
	return $output;
}

/**
 * Alias of DB::getNextSequence()
 *
 * @see DB::getNextSequence()
 * @return int
 */
function getNextSequence()
{
	$oDB = DB::getInstance();
	$seq = $oDB->getNextSequence();
	setUserSequence($seq);
	return $seq;
}

/**
 * Set Sequence number to session
 *
 * @param int $seq sequence number
 * @return void
 */
function setUserSequence($seq)
{
	if(!isset($_SESSION['seq']))
	{
		$_SESSION['seq'] = array();
	}
	$_SESSION['seq'][] = $seq;
}

/**
 * Check Sequence number grant
 *
 * @param int $seq sequence number
 * @return boolean
 */
function checkUserSequence($seq)
{
	return isset($_SESSION['seq']) && in_array($seq, $_SESSION['seq']);
}

/**
 * Get a encoded url. Define a function to use Context::getUrl()
 *
 * getUrl() returns the URL transformed from given arguments of RequestURI
 * <ol>
 *  <li>argument format follows as (key, value).
 * ex) getUrl('key1', 'val1', 'key2',''): transform key1 and key2 to val1 and '' respectively</li>
 * <li>returns URL without the argument if no argument is given.</li>
 * <li>URL made of args_list added to RequestUri if the first argument value is ''.</li>
 * </ol>
 *
 * @return string
 */
function getUrl()
{
	$num_args = func_num_args();
	$args_list = func_get_args();

	if($num_args)
	{
		$url = Context::getUrl($num_args, $args_list);
	}
	else
	{
		$url = Context::getRequestUri();
	}

	return preg_replace('@\berror_return_url=[^&]*|\w+=(?:&|$)@', '', $url);
}

/**
 * Get a not encoded(html entity) url
 *
 * @see getUrl()
 * @return string
 */
function getNotEncodedUrl()
{
	$num_args = func_num_args();
	$args_list = func_get_args();

	if($num_args)
	{
		$url = Context::getUrl($num_args, $args_list, NULL, FALSE);
	}
	else
	{
		$url = Context::getRequestUri();
	}

	return preg_replace('@\berror_return_url=[^&]*|\w+=(?:&|$)@', '', $url);
}

/**
 * Get a encoded url. If url is encoded, not encode. Otherwise html encode the url.
 *
 * @see getUrl()
 * @return string
 */
function getAutoEncodedUrl()
{
	$num_args = func_num_args();
	$args_list = func_get_args();

	if($num_args)
	{
		$url = Context::getUrl($num_args, $args_list, NULL, TRUE, TRUE);
	}
	else
	{
		$url = Context::getRequestUri();
	}

	return preg_replace('@\berror_return_url=[^&]*|\w+=(?:&|$)@', '', $url);
}

/**
 * Return the value adding request uri to getUrl() to get the full url
 *
 * @return string
 */
function getFullUrl()
{
	$num_args = func_num_args();
	$args_list = func_get_args();
	$request_uri = Context::getRequestUri();
	if(!$num_args)
	{
		return $request_uri;
	}

	$url = Context::getUrl($num_args, $args_list);
	if(strncasecmp('http', $url, 4) !== 0)
	{
		preg_match('/^(http|https):\/\/([^\/]+)\//', $request_uri, $match);
		return substr($match[0], 0, -1) . $url;
	}
	return $url;
}

/**
 * Return the value adding request uri to getUrl() to get the not encoded full url
 *
 * @return string
 */
function getNotEncodedFullUrl()
{
	$num_args = func_num_args();
	$args_list = func_get_args();
	$request_uri = Context::getRequestUri();
	if(!$num_args)
	{
		return $request_uri;
	}

	$url = Context::getUrl($num_args, $args_list, NULL, FALSE);
	if(strncasecmp('http', $url, 4) !== 0)
	{
		preg_match('/^(http|https):\/\/([^\/]+)\//', $request_uri, $match);
		$url = Context::getUrl($num_args, $args_list, NULL, FALSE);
		return substr($match[0], 0, -1) . $url;
	}
	return $url;
}

/**
 * getSiteUrl() returns the URL by transforming the given argument value of domain
 * The first argument should consist of domain("http://" not included) and path
 * 
 * @return string
 */
function getSiteUrl()
{
	$num_args = func_num_args();
	$args_list = func_get_args();

	if(!$num_args)
	{
		return Context::getRequestUri();
	}

	$domain = array_shift($args_list);
	$num_args = count($args_list);

	return Context::getUrl($num_args, $args_list, $domain);
}

/**
 * getSiteUrl() returns the not encoded URL by transforming the given argument value of domain
 * The first argument should consist of domain("http://" not included) and path
 * 
 * @return string
 */
function getNotEncodedSiteUrl()
{
	$num_args = func_num_args();
	$args_list = func_get_args();

	if(!$num_args)
	{
		return Context::getRequestUri();
	}

	$domain = array_shift($args_list);
	$num_args = count($args_list);

	return Context::getUrl($num_args, $args_list, $domain, FALSE);
}

/**
 * Return the value adding request uri to the getSiteUrl() To get the full url
 *
 * @return string
 */
function getFullSiteUrl()
{
	$num_args = func_num_args();
	$args_list = func_get_args();

	$request_uri = Context::getRequestUri();
	if(!$num_args)
	{
		return $request_uri;
	}

	$domain = array_shift($args_list);
	$num_args = count($args_list);

	$url = Context::getUrl($num_args, $args_list, $domain);
	if(strncasecmp('http', $url, 4) !== 0)
	{
		preg_match('/^(http|https):\/\/([^\/]+)\//', $request_uri, $match);
		return substr($match[0], 0, -1) . $url;
	}
	return $url;
}

/**
 * Return the exact url of the current page
 *
 * @return string
 */
function getCurrentPageUrl()
{
	return escape((RX_SSL ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
}

/**
 * Return if domain of the virtual site is url type or id type
 *
 * @param string $domain
 * @return bool
 */
function isSiteID($domain)
{
	return preg_match('/^([a-zA-Z0-9\_]+)$/', $domain);
}

/**
 * Put a given tail after trimming string to the specified size
 *
 * @param string $string The original string to trim
 * @param int $cut_size The size to be
 * @param string $tail Tail to put in the end of the string after trimming
 * @return string
 */
function cut_str($string, $cut_size = 0, $tail = '...')
{
	if($cut_size < 1 || !$string)
	{
		return $string;
	}

	if($GLOBALS['use_mb_strimwidth'] || function_exists('mb_strimwidth'))
	{
		$GLOBALS['use_mb_strimwidth'] = TRUE;
		return mb_strimwidth($string, 0, $cut_size + 4, $tail, 'utf-8');
	}

	$chars = array(12, 4, 3, 5, 7, 7, 11, 8, 4, 5, 5, 6, 6, 4, 6, 4, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 4, 4, 8, 6, 8, 6, 10, 8, 8, 9, 8, 8, 7, 9, 8, 3, 6, 7, 7, 11, 8, 9, 8, 9, 8, 8, 7, 8, 8, 10, 8, 8, 8, 6, 11, 6, 6, 6, 4, 7, 7, 7, 7, 7, 3, 7, 7, 3, 3, 6, 3, 9, 7, 7, 7, 7, 4, 7, 3, 7, 6, 10, 6, 6, 7, 6, 6, 6, 9);
	$max_width = $cut_size * $chars[0] / 2;
	$char_width = 0;

	$string_length = strlen($string);
	$char_count = 0;

	$idx = 0;
	while($idx < $string_length && $char_count < $cut_size && $char_width <= $max_width)
	{
		$c = ord(substr($string, $idx, 1));
		$char_count++;
		if($c < 128)
		{
			$char_width += (int)($chars[$c - 32]);
			$idx++;
		}
		else if(191 < $c && $c < 224)
		{
			$char_width += $chars[4];
			$idx += 2;
		}
		else
		{
			$char_width += $chars[0];
			$idx += 3;
		}
	}

	$output = substr($string, 0, $idx);
	if(strlen($output) < $string_length)
	{
		$output .= $tail;
	}

	return $output;
}

/**
 * Convert XE legacy time zone format into UTC offset.
 * 
 * @param string $time_zone Time zone in '+0900' format
 * @return int
 */
function get_time_zone_offset($timezone)
{
	return Rhymix\Framework\DateTime::getTimezoneOffsetByLegacyFormat($timezone);
}

/**
 * Get the offset between the current user's time zone and Rhymix's internal time zone.
 *
 * @return int
 */
function zgap($timestamp = null)
{
	$current_user_timezone = Rhymix\Framework\DateTime::getTimezoneForCurrentUser();
	return Rhymix\Framework\DateTime::getTimezoneOffsetFromInternal($current_user_timezone, $timestamp);
}

/**
 * Convert YYYYMMDDHHIISS format to Unix timestamp.
 * This function assumes the internal timezone.
 *
 * @param string $str Time in YYYYMMDDHHIISS format
 * @return int
 */
function ztime($str)
{
	if(!$str)
	{
		return null;
	}
	$year = (int)substr($str, 0, 4);
	$month = (int)substr($str, 4, 2) ?: 1;
	$day = (int)substr($str, 6, 2) ?: 1;
	if(strlen($str) >= 8)
	{
		$hour = (int)substr($str, 8, 2);
		$min = (int)substr($str, 10, 2);
		$sec = (int)substr($str, 12, 2);
	}
	else
	{
		$hour = $min = $sec = 0;
	}
	$timestamp = gmmktime($hour, $min, $sec, $month, $day, $year);
	$offset = Rhymix\Framework\Config::get('locale.internal_timezone') ?: date('Z', $timestamp);
	return $timestamp - $offset;
}

/**
 * Convert YYYYMMDDHHIISS format to user-defined format.
 * This function assumes the internal timezone.
 *
 * @param string $str Time in YYYYMMDDHHIISS format
 * @param string $format Time format for date() function
 * @param bool $conversion If true, convert automatically for the current language.
 * @return string
 */
function zdate($str, $format = 'Y-m-d H:i:s', $conversion = false)
{
	if(!$str)
	{
		return null;
	}
	
	// convert the date format according to the language
	if($conversion)
	{
		static $convtable = array(
			'en' => array(
				'Y-m-d' => 'M j, Y',
				'Y-m-d H:i:s' => 'M j, Y H:i:s',
				'Y-m-d H:i' => 'M j, Y H:i',
			),
			'es' => array(
				'Y-m-d' => 'j M Y',
				'Y-m-d H:i:s' => 'j M Y H:i:s',
				'Y-m-d H:i' => 'j M Y H:i',
			),
			'de' => 'es',
			'fr' => 'es',
			'vi' => array(
				'Y-m-d' => 'd-m-Y',
				'Y-m-d H:i:s' => 'H:i:s d-m-Y',
				'Y-m-d H:i' => 'H:i d-m-Y',
			),
		);
		
		$lang_type = Context::getLangType();
		if(isset($convtable[$lang_type]))
		{
			if(isset($convtable[$lang_type][$format]))
			{
				$format = $convtable[$lang_type][$format];
			}
			elseif(is_string($convtable[$lang_type]) && isset($convtable[$convtable[$lang_type]][$format]))
			{
				$format = $convtable[$convtable[$lang_type]][$format];
			}
		}
	}
	
	// get unixtime by using ztime() for date() function's argument.
	$result = Rhymix\Framework\DateTime::formatTimestampForCurrentUser($format, ztime($str));
	
	// change day and am/pm for each language
	if(preg_match('/[MFAa]/', $format))
	{
		$unit_week = (Array)Context::getLang('unit_week');
		$unit_meridiem = (Array)Context::getLang('unit_meridiem');
		$result = str_replace(array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), $unit_week, $result);
		$result = str_replace(array('am', 'pm', 'AM', 'PM'), $unit_meridiem, $result);
	}
	return $result;
}

/**
 * Convert a Unix timestamp to YYYYMMDDHHIISS format, using the internal time zone.
 * If the timestamp is not given, the current time is used.
 * 
 * @param int $timestamp Unix timestamp
 * @return string
 */
function getInternalDateTime($timestamp = null, $format = 'YmdHis')
{
	$timestamp = ($timestamp !== null) ? $timestamp : time();
	return Rhymix\Framework\DateTime::formatTimestamp($format, $timestamp);
}

/**
 * Convert a Unix timestamp to YYYYMMDDHHIISS format, using the internal time zone.
 * If the timestamp is not given, the current time is used.
 * 
 * @param int $timestamp Unix timestamp
 * @return string
 */
function getDisplayDateTime($timestamp = null, $format = 'YmdHis')
{
	$timestamp = ($timestamp !== null) ? $timestamp : time();
	return Rhymix\Framework\DateTime::formatTimestampForCurrentUser($format, $timestamp);
}

/**
 * If the recent post within a day, output format of YmdHis is "min/hours ago from now". If not within a day, it return format string.
 *
 * @param string $date Time value in format of YYYYMMDDHHIISS
 * @param string $format If gap is within a day, returns this format.
 * @return string
 */
function getTimeGap($date, $format = 'Y.m.d')
{
	$gap = RX_TIME - ztime($date);

	$lang_time_gap = Context::getLang('time_gap');
	if($gap < 60 * 1.5)
	{
		$buff = sprintf($lang_time_gap['min'], round($gap / 60));
	}
	elseif($gap < 60 * 60)
	{
		$buff = sprintf($lang_time_gap['mins'], round($gap / 60));
	}
	elseif($gap < 60 * 60 * 1.5)
	{
		$buff = sprintf($lang_time_gap['hour'], round($gap / 60 / 60));
	}
	elseif($gap < 60 * 60 * 24)
	{
		$buff = sprintf($lang_time_gap['hours'], round($gap / 60 / 60));
	}
	else
	{
		$buff = zdate($date, $format);
	}

	return $buff;
}

/**
 * Name of the month return
 *
 * @param int $month Month
 * @param boot $short If set, returns short string
 * @return string
 */
function getMonthName($month, $short = TRUE)
{
	$short_month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	$long_month = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	return $short ? $short_month[$month - 1] : $long_month[$month - 1];
}

/**
 * Returns encoded value of given email address for email scraping
 *
 * @param string $email The email
 * @return string
 */
function getEncodeEmailAddress($email)
{
	$return = '';
	for($i = 0, $c = strlen($email); $i < $c; $i++)
	{
		$return .= '&#' . (rand(0, 1) == 0 ? ord($email[$i]) : 'X' . dechex(ord($email[$i]))) . ';';
	}
	return $return;
}

/**
 * Add an entry to the debug log.
 *
 * @param mixed $entry Target object to be printed
 * @return void
 */
function debugPrint($entry = null)
{
	Rhymix\Framework\Debug::addEntry($entry);
}

/**
 * @param string $type query, trigger
 * @param float $elapsed_time
 * @param object $obj
 */
function writeSlowlog($type, $elapsed_time, $obj)
{
	if(!__LOG_SLOW_TRIGGER__ && !__LOG_SLOW_ADDON__ && !__LOG_SLOW_WIDGET__ && !__LOG_SLOW_QUERY__) return;
	if(__LOG_SLOW_PROTECT__ === 1 &&  __LOG_SLOW_PROTECT_IP__ != $_SERVER['REMOTE_ADDR']) return;

	static $log_filename = array(
		'query' => 'files/_slowlog_query.php',
		'trigger' => 'files/_slowlog_trigger.php',
		'addon' => 'files/_slowlog_addon.php',
		'widget' => 'files/_slowlog_widget.php'
	);
	$write_file = true;

	$log_file = _XE_PATH_ . $log_filename[$type];

	$buff = array();
	$buff[] = '<?php exit(); ?>';
	$buff[] = date('c');

	if($type == 'trigger' && __LOG_SLOW_TRIGGER__ > 0 && $elapsed_time > __LOG_SLOW_TRIGGER__)
	{
		$buff[] = "\tCaller : " . $obj->caller;
		$buff[] = "\tCalled : " . $obj->called;
	}
	else if($type == 'addon' && __LOG_SLOW_ADDON__ > 0 && $elapsed_time > __LOG_SLOW_ADDON__)
	{
		$buff[] = "\tAddon : " . $obj->called;
		$buff[] = "\tCalled position : " . $obj->caller;
	}
	else if($type == 'widget' && __LOG_SLOW_WIDGET__ > 0 && $elapsed_time > __LOG_SLOW_WIDGET__)
	{
		$buff[] = "\tWidget : " . $obj->called;
	}
	else if($type == 'query' && __LOG_SLOW_QUERY__ > 0 && $elapsed_time > __LOG_SLOW_QUERY__)
	{

		$buff[] = $obj->query;
		$buff[] = "\tQuery ID   : " . $obj->query_id;
		$buff[] = "\tCaller     : " . $obj->caller;
		$buff[] = "\tConnection : " . $obj->connection;
	}
	else
	{
		$write_file = false;
	}

	if($write_file)
	{
		$buff[] = sprintf("\t%0.6f sec", $elapsed_time);
		$buff[] = PHP_EOL . PHP_EOL;
		file_put_contents($log_file, implode(PHP_EOL, $buff), FILE_APPEND);
	}

	if($type != 'query')
	{
		$trigger_args = $obj;
		$trigger_args->_log_type = $type;
		$trigger_args->_elapsed_time = $elapsed_time;
		ModuleHandler::triggerCall('XE.writeSlowlog', 'after', $trigger_args);
	}
}

/**
 * @param void
 */
function flushSlowlog()
{
	$trigger_args = new stdClass();
	$trigger_args->_log_type = 'flush';
	$trigger_args->_elapsed_time = 0;
	ModuleHandler::triggerCall('XE.writeSlowlog', 'after', $trigger_args);
}

/**
 * microtime() return
 *
 * @return float
 */
function getMicroTime()
{
	return microtime(true);
}

/**
 * Delete the second object vars from the first argument
 *
 * @param object $target_obj An original object
 * @param object $del_obj Object vars to delete from the original object
 * @return object
 */
function delObjectVars($target_obj, $del_obj)
{
	if(!is_object($target_obj) || !is_object($del_obj))
	{
		return new stdClass;
	}
	$target_vars = get_object_vars($target_obj);
	$del_vars = get_object_vars($del_obj);
	foreach($del_vars as $key => $val)
	{
		unset($target_vars[$key]);
	}
	return (object)$target_vars;
}

function getDestroyXeVars($vars)
{
	foreach(array('error_return_url', 'success_return_url', 'ruleset', 'xe_validator_id') as $var)
	{
		if(is_array($vars))
		{
			unset($vars[$var]);
		}
		elseif(is_object($vars))
		{
			unset($vars->$var);
		}
	}
	return $vars;
}

/**
 * Legacy error handler
 *
 * @param int $errno
 * @param string $errstr
 * @param string $file
 * @param int $line
 * @return void
 */
function handleError($errno, $errstr, $file, $line, $context)
{
	Rhymix\Framework\Debug::addError($errno, $errstr, $file, $line, $context);
}

/**
 * Trim a given number to a fiven size recursively
 *
 * @param int $no A given number
 * @param int $size A given digits
 */
function getNumberingPath($no, $size = 3)
{
	$mod = pow(10, $size);
	$output = sprintf('%0' . $size . 'd/', $no % $mod);
	if($no >= $mod)
	{
		$output .= getNumberingPath((int)$no / $mod, $size);
	}
	return $output;
}

/**
 * Decode the URL in Korean
 *
 * @param string $str The url
 * @return string
 */
function url_decode($str)
{
	return htmlspecialchars(utf8RawUrlDecode($str), null, 'UTF-8');
}

function purifierHtml(&$content)
{
	$oPurifier = Purifier::getInstance();
	$oPurifier->purify($content);
}

/**
 * Pre-block the codes which may be hacking attempts
 *
 * @param string $content Taget content
 * @return string
 */
function removeHackTag($content)
{
	$oEmbedFilter = EmbedFilter::getInstance();
	$oEmbedFilter->check($content);

	purifierHtml($content);

	// change the specific tags to the common texts
	$content = preg_replace('@<(\/?(?:html|body|head|title|meta|base|link|script|style|applet)(/*).*?>)@i', '&lt;$1', $content);

	/**
	 * Remove codes to abuse the admin session in src by tags of imaages and video postings
	 * - Issue reported by Sangwon Kim
	 */
	$content = preg_replace_callback('@<(/?)([a-z]+[0-9]?)((?>"[^"]*"|\'[^\']*\'|[^>])*?\b(?:on[a-z]+|data|style|background|href|(?:dyn|low)?src)\s*=[\s\S]*?)(/?)($|>|<)@i', 'removeSrcHack', $content);

	$content = checkXmpTag($content);
	$content = blockWidgetCode($content);

	return $content;
}

/**
 * blocking widget code
 *
 * @param string $content Taget content
 * @return string
 **/
function blockWidgetCode($content)
{
	$content = preg_replace('/(<(?:img|div)(?:[^>]*))(widget)(?:(=([^>]*?)>))/is', '$1blocked-widget$3', $content);

	return $content;
}

/**
 * check uploaded file which may be hacking attempts
 *
 * @param string $file Taget file path
 * @return bool
 */
function checkUploadedFile($file)
{
	return UploadFileFilter::check($file);
}

/**
 * Check xmp tag, close it.
 *
 * @param string $content Target content
 * @return string
 */
function checkXmpTag($content)
{
	$content = preg_replace('@<(/?)xmp.*?>@i', '<\1xmp>', $content);

	if(($start_xmp = strrpos($content, '<xmp>')) !== FALSE)
	{
		if(($close_xmp = strrpos($content, '</xmp>')) === FALSE)
		{
			$content .= '</xmp>';
		}
		else if($close_xmp < $start_xmp)
		{
			$content .= '</xmp>';
		}
	}

	return $content;
}

/**
 * Remove src hack(preg_replace_callback)
 *
 * @param array $match
 * @return string
 */
function removeSrcHack($match)
{
	$tag = strtolower($match[2]);

	// xmp tag ?뺣━
	if($tag == 'xmp')
	{
		return "<{$match[1]}xmp>";
	}
	if($match[1])
	{
		return $match[0];
	}
	if($match[4])
	{
		$match[4] = ' ' . $match[4];
	}

	$attrs = array();
	if(preg_match_all('/([\w:-]+)\s*=(?:\s*(["\']))?(?(2)(.*?)\2|([^ ]+))/s', $match[3], $m))
	{
		foreach($m[1] as $idx => $name)
		{
			if(strlen($name) >= 2 && substr_compare($name, 'on', 0, 2) === 0)
			{
				continue;
			}

			$val = preg_replace_callback('/&#(?:x([a-fA-F0-9]+)|0*(\d+));/', function($n) {return chr($n[1] ? ('0x00' . $n[1]) : ($n[2] + 0)); }, $m[3][$idx] . $m[4][$idx]);
			$val = preg_replace('/^\s+|[\t\n\r]+/', '', $val);

			if(preg_match('/^[a-z]+script:/i', $val))
			{
				continue;
			}

			$attrs[$name] = $val;
		}
	}

	//Remove ACT URL (CSRF)
	$except_act = array('procFileDownload');
	$block_act = array('dispMemberLogout', 'dispLayoutPreview');

	$filter_arrts = array('style', 'src', 'href');
	if($tag === 'object') array_push($filter_arrts, 'data');
	if($tag === 'param') array_push($filter_arrts, 'value');

	foreach($filter_arrts as $attr)
	{
		if(!isset($attrs[$attr])) continue;

		$attr_value = rawurldecode($attrs[$attr]);
		$attr_value = htmlspecialchars_decode($attr_value, ENT_COMPAT);
		$attr_value = preg_replace('/\s+|[\t\n\r]+/', '', $attr_value);

		preg_match('@(\?|&|;)act=(disp|proc)([^&]*)@i', $attr_value, $actmatch);
		$url_action = $actmatch[2].$actmatch[3];

		if(!empty($url_action) && !in_array($url_action, $except_act))
		{
			if($actmatch[2] == 'proc' || in_array($url_action, $block_act))
			{
				unset($attrs[$attr]);
			}
		}
	}

	if(isset($attrs['style']) && preg_match('@(?:/\*|\*/|\n|:\s*expression\s*\()@i', $attrs['style']))
	{
		unset($attrs['style']);
	}

	$attr = array();
	foreach($attrs as $name => $val)
	{
		if($tag == 'object' || $tag == 'embed' || $tag == 'a')
		{
			$attribute = strtolower(trim($name));
			if($attribute == 'data' || $attribute == 'src' || $attribute == 'href')
			{
				if(stripos($val, 'data:') === 0)
				{
					continue;
				}
			}
		}

		if($tag == 'img')
		{
			$attribute = strtolower(trim($name));
			if(stripos($val, 'data:') === 0)
			{
				continue;
			}
		}
		$val = str_replace('"', '&quot;', $val);
		$attr[] = $name . "=\"{$val}\"";
	}
	$attr = count($attr) ? ' ' . implode(' ', $attr) : '';

	return "<{$match[1]}{$tag}{$attr}{$match[4]}>";
}

/**
 * Convert hexa value to RGB
 *
 * @param string $hexstr
 * @return array
 */
if(!function_exists('hexrgb'))
{
	function hexrgb($hex)
	{
		return hex2rgb($hex);
	}
}

/**
 * Php function for mysql old_password()
 * provides backward compatibility for zero board4 which uses old_password() of mysql 4.1 earlier versions. 
 * the function implemented by referring to the source codes of password.c file in mysql
 *
 * @param string $password
 * @return string
 */
function mysql_pre4_hash_password($password)
{
	return VendorPass::mysql_old_password($password);
}

/**
 * Return the requested script path
 *
 * @return string
 */
function getScriptPath()
{
	return RX_BASEURL;
}

/**
 * Return the requested script path
 *
 * @return string
 */
function getRequestUriByServerEnviroment()
{
	return str_replace('<', '&lt;', $_SERVER['REQUEST_URI']);
}

/**
 * PHP unescape function of javascript's escape
 * Function converts an Javascript escaped string back into a string with specified charset (default is UTF-8).
 * Modified function from http://pure-essence.net/stuff/code/utf8RawUrlDecode.phps
 *
 * @param string $source
 * @return string
 */
function utf8RawUrlDecode($source)
{
	return preg_replace_callback('/%u([0-9a-f]+)/i', function($m) {
		return html_entity_decode('&#x' . $m[1] . ';');
	}, rawurldecode($source));
}

/**
 * Returns utf-8 string of given code
 *
 * @param int $num
 * @return string
 */
function _code2utf($num)
{
	return html_entity_decode('&#' . $num . ';');
}

/**
 * Get whether utf8 or not given string
 *
 * @param string $string
 * @param bool $return_convert If set, returns converted string
 * @param bool $urldecode
 * @return bool|string
 */
function detectUTF8($string, $return_convert = FALSE, $urldecode = TRUE)
{
	if($urldecode)
	{
		$string = urldecode($string);
	}
	
	if(function_exists('mb_check_encoding'))
	{
		$is_utf8 = mb_check_encoding($string, 'UTF-8');
		return $return_convert ? mb_convert_encoding($string, 'UTF-8', 'CP949') : $is_utf8;
	}
	else
	{
		$is_utf8 = ($string === @iconv('UTF-8', 'UTF-8', $string));
		return $return_convert ? iconv('CP949', 'UTF-8', $string) : $is_utf8;
	}
}

/**
 * get json encoded string of data
 *
 * @param mixed $data
 * @return string
 */
function json_encode2($data)
{
	return json_encode($data);
}

/**
 * Get is current user crawler
 *
 * @param string $agent if set, use this value instead HTTP_USER_AGENT
 * @return bool
 */
function isCrawler($agent = NULL)
{
	$agent = $agent ?: $_SERVER['HTTP_USER_AGENT'];
	return (bool)preg_match('@bot|crawl|sp[iy]der|https?://|google|yahoo|slurp|yeti|daum|teoma|fish|hanrss|facebook|yandex|infoseek|askjeeves|stackrambler@i', $agent);
}

/**
 * Remove embed media for admin
 *
 * @param string $content
 * @param int $writer_member_srl
 * @return void
 */
function stripEmbedTagForAdmin(&$content, $writer_member_srl)
{
	if(!Context::get('is_logged'))
	{
		return;
	}

	$oModuleModel = getModel('module');
	$logged_info = Context::get('logged_info');

	if($writer_member_srl != $logged_info->member_srl && ($logged_info->is_admin == "Y" || $oModuleModel->isSiteAdmin($logged_info)))
	{
		if($writer_member_srl)
		{
			$oMemberModel = getModel('member');
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($writer_member_srl);
			if($member_info->is_admin == "Y")
			{
				return;
			}
		}
		$security_msg = "<div style='border: 1px solid #DDD; background: #FAFAFA; text-align:center; margin: 1em 0;'><p style='margin: 1em;'>" . Context::getLang('security_warning_embed') . "</p></div>";
		$content = preg_replace('/<object[^>]+>(.*?<\/object>)?/is', $security_msg, $content);
		$content = preg_replace('/<embed[^>]+>(\s*<\/embed>)?/is', $security_msg, $content);
		$content = preg_replace('/<img[^>]+editor_component="multimedia_link"[^>]*>(\s*<\/img>)?/is', $security_msg, $content);
	}

	return;
}

/**
 * Require pear
 *
 * @return void
 */
function requirePear()
{
	
}

/**
 * Check for CSRF attacks
 * 
 * @return bool
 */
function checkCSRF()
{
	if($_SERVER['REQUEST_METHOD'] != 'POST')
	{
		return FALSE;
	}

	$default_url = Context::getDefaultUrl();
	$referer = $_SERVER["HTTP_REFERER"];

	if(strpos($default_url, 'xn--') !== FALSE && strpos($referer, 'xn--') === FALSE)
	{
		$referer = Context::encodeIdna($referer);
	}

	$default_url = parse_url($default_url);
	$referer = parse_url($referer);

	$oModuleModel = getModel('module');
	$siteModuleInfo = $oModuleModel->getDefaultMid();

	if($siteModuleInfo->site_srl == 0)
	{
		if($default_url['host'] !== $referer['host'])
		{
			return FALSE;
		}
	}
	else
	{
		$virtualSiteInfo = $oModuleModel->getSiteInfo($siteModuleInfo->site_srl);
		if(strtolower($virtualSiteInfo->domain) != strtolower(Context::get('vid')) && !strstr(strtolower($virtualSiteInfo->domain), strtolower($referer['host'])))
		{
			return FALSE;
		}
	}

	return TRUE;
}

/**
 * menu exposure check by isShow column
 * @param array $menu
 * @return void
 */
function recurciveExposureCheck(&$menu)
{
	if(is_array($menu))
	{
		foreach($menu AS $key=>$value)
		{
			if(!$value['isShow'])
			{
				unset($menu[$key]);
			}
			if(is_array($value['list']) && count($value['list']) > 0)
			{
				recurciveExposureCheck($menu[$key]['list']);
			}
		}
	}
}

function changeValueInUrl($key, $requestKey, $dbKey, $urlName = 'success_return_url')
{
	if($requestKey != $dbKey)
	{
		$arrayUrl = parse_url(Context::get('success_return_url'));
		if($arrayUrl['query'])
		{
			parse_str($arrayUrl['query'], $parsedStr);

			if(isset($parsedStr[$key]))
			{
				$parsedStr[$key] = $requestKey;
				$successReturnUrl .= $arrayUrl['path'].'?'.http_build_query($parsedStr);
				Context::set($urlName, $successReturnUrl);
			}
		}
	}
}

/**
 * Polyfill for iconv()
 */
if(!function_exists('iconv'))
{
	function iconv($in_charset, $out_charset, $str)
	{
		if(function_exists('mb_convert_encoding'))
		{
			$out_charset = preg_replace('#//.+$#', '', $out_charset);
			return mb_convert_encoding($str, $out_charset, $in_charset);
		}
		else
		{
			return $str;
		}
	}
}

/**
 * Polyfill for iconv_strlen()
 */
if(!function_exists('iconv_strlen'))
{
	function iconv_strlen($str, $charset = null)
	{
		if(function_exists('mb_strlen'))
		{
			return mb_strlen($str, $charset);
		}
		else
		{
			return strlen($str);
		}
	}
}

/**
 * Polyfill for iconv_strpos()
 */
if(!function_exists('iconv_strpos'))
{
	function iconv_strpos($haystack, $needle, $offset, $charset = null)
	{
		if(function_exists('mb_strpos'))
		{
			return mb_strpos($haystack, $needle, $offset, $charset);
		}
		else
		{
			return strpos($haystack, $needle, $offset);
		}
	}
}

/**
 * Polyfill for iconv_substr()
 */
if(!function_exists('iconv_substr'))
{
	function iconv_substr($str, $offset, $length = null, $charset = null)
	{
		if(function_exists('mb_substr'))
		{
			return mb_substr($str, $offset, $length, $charset);
		}
		else
		{
			return $length ? substr($str, $offset, $length) : substr($str, $offset);
		}
	}
}

/**
 * Polyfill for mb_strlen()
 */
if(!function_exists('mb_strlen'))
{
	function mb_strlen($str, $charset = null)
	{
		if(function_exists('iconv_strlen'))
		{
			return iconv_strlen($str, $charset);
		}
		else
		{
			return strlen($str);
		}
	}
}

/**
 * Polyfill for mb_strpos()
 */
if(!function_exists('mb_strpos'))
{
	function mb_strpos($haystack, $needle, $offset, $charset = null)
	{
		if(function_exists('iconv_strpos'))
		{
			return iconv_strpos($haystack, $needle, $offset, $charset);
		}
		else
		{
			return strpos($haystack, $needle, $offset);
		}
	}
}

/**
 * Polyfill for mb_substr()
 */
if(!function_exists('mb_substr'))
{
	function mb_substr($str, $offset, $length = null, $charset = null)
	{
		if(function_exists('iconv_substr'))
		{
			return iconv_substr($str, $offset, $length, $charset);
		}
		else
		{
			return $length ? substr($str, $offset, $length) : substr($str, $offset);
		}
	}
}

/**
 * Polyfill for mb_substr_count()
 */
if(!function_exists('mb_substr_count'))
{
	function mb_substr_count($haystack, $needle, $charset = null)
	{
		return substr_count($haystack, $needle);
	}
}

/**
 * Polyfill for mb_strtoupper()
 */
if(!function_exists('mb_strtoupper'))
{
	function mb_strtoupper($str, $charset = null)
	{
		return strtoupper($str);
	}
}

/**
 * Polyfill for mb_strtolower()
 */
if(!function_exists('mb_strtolower'))
{
	function mb_strtolower($str, $charset = null)
	{
		return strtolower($str);
	}
}

/**
 * Print raw html header
 *
 * @return void
 */
function htmlHeader()
{
	echo implode("\n", array('<!DOCTYPE html>', '<html lang="ko">', '<head>', '<meta charset="UTF-8" />', '</head>', '<body>', ''));
}

/**
 * Print raw html footer
 *
 * @return void
 */
function htmlFooter()
{
	echo implode("\n", array('', '</body>', '</html>', ''));
}

/**
 * Print raw alert message script
 *
 * @param string $msg
 * @return void
 */
function alertScript($msg)
{
	if($msg)
	{
		echo sprintf('<script> alert(%s); </script>', json_encode(@strval($msg)));
	}
}

/**
 * Print raw close window script
 *
 * @return void
 */
function closePopupScript()
{
	echo '<script> window.open("", "_self", ""); window.close(); </script>';
}

/**
 * Print raw reload script
 *
 * @param bool $isOpener
 * @return void
 */
function reload($isOpener = FALSE)
{
	$reloadScript = $isOpener ? 'window.opener.location.reload();' : 'window.location.reload();';
	echo sprintf('<script> %s </script>', $raloadScript);
}

/* End of file func.inc.php */
/* Location: ./config/func.inc.php */
