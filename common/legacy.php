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
	return ModuleHandler::getModuleInstance($module_name, 'controller');
}

/**
 * Create a admin controller instance of the module
 *
 * @param string $module_name The module name to get a admin controller instance
 * @return mixed Module admin controller instance
 */
function getAdminController($module_name)
{
	return ModuleHandler::getModuleInstance($module_name, 'controller', 'admin');
}

/**
 * Create a view instance of the module
 *
 * @param string $module_name The module name to get a view instance
 * @return mixed Module view instance
 */
function getView($module_name)
{
	return ModuleHandler::getModuleInstance($module_name, 'view');
}

/**
 * Create a admin view instance of the module
 *
 * @param string $module_name The module name to get a admin view instance
 * @return mixed Module admin view instance
 */
function getAdminView($module_name)
{
	return ModuleHandler::getModuleInstance($module_name, 'view', 'admin');
}

/**
 * Create a model instance of the module
 *
 * @param string $module_name The module name to get a model instance
 * @return mixed Module model instance
 */
function getModel($module_name)
{
	return ModuleHandler::getModuleInstance($module_name, 'model');
}

/**
 * Create an admin model instance of the module
 *
 * @param string $module_name The module name to get a admin model instance
 * @return mixed Module admin model instance
 */
function getAdminModel($module_name)
{
	return ModuleHandler::getModuleInstance($module_name, 'model', 'admin');
}

/**
 * Create an api instance of the module
 *
 * @param string $module_name The module name to get a api instance
 * @return mixed Module api class instance
 */
function getAPI($module_name)
{
	return ModuleHandler::getModuleInstance($module_name, 'api');
}

/**
 * Create a mobile instance of the module
 *
 * @param string $module_name The module name to get a mobile instance
 * @return mixed Module mobile instance
 */
function getMobile($module_name)
{
	return ModuleHandler::getModuleInstance($module_name, 'mobile');
}

/**
 * Create a wap instance of the module
 *
 * @param string $module_name The module name to get a wap instance
 * @return mixed Module wap class instance
 */
function getWAP($module_name)
{
	return ModuleHandler::getModuleInstance($module_name, 'wap');
}

/**
 * Create a class instance of the module
 *
 * @param string $module_name The module name to get a class instance
 * @return mixed Module class instance
 */
function getClass($module_name)
{
	return ModuleHandler::getModuleInstance($module_name, 'class');
}

/**
 * The alias of DB::executeQuery()
 *
 * @see DB::executeQuery()
 * @param string $query_id (module name.query XML file)
 * @param array|object $args Arguments
 * @param array $column_list Column list
 * @param string $result_type 'auto', 'array' or 'raw'
 * @return object Query result data
 */
function executeQuery($query_id, $args = [], $column_list = [], $result_type = 'auto')
{
	$oDB = Rhymix\Framework\DB::getInstance();
	return $oDB->executeQuery($query_id, $args, $column_list, $result_type);
}

/**
 * Function to handle the result of DB::executeQuery() as an array
 *
 * @see DB::executeQuery()
 * @param string $query_id (module name.query XML file)
 * @param array|object $args Arguments
 * @param array $column_list Column list
 * @return object Query result data
 */
function executeQueryArray($query_id, $args = [], $column_list = [])
{
	$oDB = Rhymix\Framework\DB::getInstance();
	return $oDB->executeQuery($query_id, $args, $column_list, 'array');
}

/**
 * Alias of DB::getNextSequence()
 *
 * @see DB::getNextSequence()
 * @return int
 */
function getNextSequence()
{
	$seq = Rhymix\Framework\DB::getInstance()->getNextSequence();
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
	if (!isset($_SESSION['seq']) || !is_array($_SESSION['seq']))
	{
		$_SESSION['seq'] = array();
	}
	$seq = intval($seq);
	$_SESSION['seq'][$seq] = $seq;
}

/**
 * Check Sequence number grant
 *
 * @param int $seq sequence number
 * @return boolean
 */
function checkUserSequence($seq)
{
	$seq = intval($seq);
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

	return $url;
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

	return $url;
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

	return $url;
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
function getCurrentPageUrl($escape = true)
{
	$url = Rhymix\Framework\URL::getCurrentURL();
	return $escape ? escape($url) : $url;
}

/**
 * Return if domain of the virtual site is url type or id type
 *
 * @param string $domain
 * @return bool
 */
function isSiteID($domain)
{
	return (bool)preg_match('/^([a-zA-Z0-9\_]+)$/', $domain);
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

	if(isset($GLOBALS['use_mb_strimwidth']) || function_exists('mb_strimwidth'))
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
	if (strlen($str) === 9 || (strlen($str) === 10 && $str <= 2147483647))
	{
		return intval($str);
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
	if($conversion && $format !== 'relative')
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
		$unit_week = (Array)lang('unit_week');
		$unit_meridiem = (Array)lang('unit_meridiem');
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
	$timestamp = ztime($date);
	$gap = RX_TIME - $timestamp;
	
	if ($gap < 60 * 60 * 24)
	{
		return Rhymix\Framework\DateTime::getRelativeTimestamp(($gap >= 60) ? $timestamp : (RX_TIME - 60));
	}
	else
	{
		return zdate($date, $format);
	}
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
	// no-op
}

/**
 * @param void
 */
function flushSlowlog()
{
	// no-op
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

/**
 * Sanitize HTML content.
 *
 * @param string $content Target content
 * @return string
 */
function removeHackTag($content)
{
	return Rhymix\Framework\Filters\HTMLFilter::clean($content);
}

/**
 * HTMLPurifier wrapper (Deprecated)
 *
 * @param string &$content Target content
 * @return string
 */
function purifierHtml(&$content)
{
	$content = Rhymix\Framework\Filters\HTMLFilter::clean($content);
}

/**
 * Check xmp tag (Deprecated)
 *
 * @param string $content Target content
 * @return string
 */
function checkXmpTag($content)
{
	return $content;
}

/**
 * Block widget code (Deprecated)
 *
 * @param string $content Taget content
 * @return string
 **/
function blockWidgetCode($content)
{
	return preg_replace('/(<(?:img|div)(?:[^>]*))(widget)(?:(=([^>]*?)>))/is', '$1blocked-widget$3', $content);
}

/**
 * Remove src hack (Deprecated)
 *
 * @param array $match
 * @return string
 */
function removeSrcHack($match)
{
	return $match[0];
}

/**
 * Check uploaded file (Deprecated)
 *
 * @param string $file Taget file path
 * @return bool
 */
function checkUploadedFile($file, $filename = null)
{
	return true;
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
	return preg_replace('/[<>"]/', '', $_SERVER['REQUEST_URI']);
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
	return Rhymix\Framework\UA::isRobot($agent);
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
	if (!Context::get('is_logged'))
	{
		return;
	}
	
	$logged_info = Context::get('logged_info');
	if ($logged_info->member_srl == $writer_member_srl)
	{
		return;
	}
	
	if ($logged_info->is_admin === 'Y' || getModel('module')->isSiteAdmin($logged_info))
	{
		if ($writer_member_srl)
		{
			$member_info = getModel('member')->getMemberInfoByMemberSrl($writer_member_srl);
			if ($member_info && $member_info->is_admin === 'Y')
			{
				return;
			}
		}
		
		$security_msg = '<div style="border: 1px solid #DDD; background: #FAFAFA; text-align:center; margin: 1em 0;">' .
			'<p style="margin: 1em;">' . lang('security_warning_embed') . '</p></div>';
		$content = Rhymix\Framework\Filters\MediaFilter::removeEmbeddedMedia($content, $security_msg);
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
	return Rhymix\Framework\Security::checkCSRF();
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
	echo sprintf('<script> %s </script>', $reloadScript);
}

/* End of file func.inc.php */
/* Location: ./config/func.inc.php */
