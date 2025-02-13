<?php

/**
 * Legacy function library for XE Compatibility
 *
 * Copyright (c) NAVER <http://www.navercorp.com>
 */

/**
 * Legacy class names for the autoloader.
 */
$GLOBALS['RX_AUTOLOAD_FILE_MAP'] = [
	'cachehandler' => 'classes/cache/CacheHandler.class.php',
	'context' => 'classes/context/Context.class.php',
	'db' => 'classes/db/DB.class.php',
	'displayhandler' => 'classes/display/DisplayHandler.class.php',
	'htmldisplayhandler' => 'classes/display/HTMLDisplayHandler.php',
	'jscallbackdisplayhandler' => 'classes/display/JSCallbackDisplayHandler.php',
	'jsondisplayhandler' => 'classes/display/JSONDisplayHandler.php',
	'rawdisplayhandler' => 'classes/display/RawDisplayHandler.php',
	'virtualxmldisplayhandler' => 'classes/display/VirtualXMLDisplayHandler.php',
	'xmldisplayhandler' => 'classes/display/XMLDisplayHandler.php',
	'editorhandler' => 'classes/editor/EditorHandler.class.php',
	'extravar' => 'classes/extravar/Extravar.class.php',
	'extraitem' => 'classes/extravar/Extravar.class.php',
	'filehandler' => 'classes/file/FileHandler.class.php',
	'fileobject' => 'classes/file/FileObject.class.php',
	'frontendfilehandler' => 'classes/frontendfile/FrontEndFileHandler.class.php',
	'handler' => 'classes/handler/Handler.class.php',
	'xehttprequest' => 'classes/httprequest/XEHttpRequest.class.php',
	'mail' => 'classes/mail/Mail.class.php',
	'mobile' => 'classes/mobile/Mobile.class.php',
	'modulehandler' => 'classes/module/ModuleHandler.class.php',
	'moduleobject' => 'classes/module/ModuleObject.class.php',
	'pagehandler' => 'classes/page/PageHandler.class.php',
	'embedfilter' => 'classes/security/EmbedFilter.class.php',
	'ipfilter' => 'classes/security/IpFilter.class.php',
	'password' => 'classes/security/Password.class.php',
	'purifier' => 'classes/security/Purifier.class.php',
	'security' => 'classes/security/Security.class.php',
	'uploadfilefilter' => 'classes/security/UploadFileFilter.class.php',
	'templatehandler' => 'classes/template/TemplateHandler.class.php',
	'validator' => 'classes/validator/Validator.class.php',
	'widgethandler' => 'classes/widget/WidgetHandler.class.php',
	'generalxmlparser' => 'classes/xml/GeneralXmlParser.class.php',
	'xml_node_' => 'classes/xml/XmlParser.class.php',
	'xmlgenerator' => 'classes/xml/XmlGenerator.class.php',
	'xmljsfilter' => 'classes/xml/XmlJsFilter.class.php',
	'xmllangparser' => 'classes/xml/XmlLangParser.class.php',
	'xmlparser' => 'classes/xml/XmlParser.class.php',
	'xexmlparser' => 'classes/xml/XmlParser.class.php',
	'ftp' => 'common/libraries/ftp.php',
	'tar' => 'common/libraries/tar.php',
	'cryptocompat' => 'common/libraries/cryptocompat.php',
	'vendorpass' => 'common/libraries/vendorpass.php',
];

/**
 * Define a function to use {@see ModuleHandler::getModuleObject()} ($module_name, $type)
 *
 * @param string $module_name
 * @param string $type model, controller, view, class, etc.
 * @param string $kind admin, null
 * @return ?ModuleObject
 */
function getModule($module_name, $type = 'view', $kind = ''): ?ModuleObject
{
	$oModule = ModuleHandler::getModuleInstance($module_name, $type, $kind);
	return $oModule instanceof ModuleObject ? $oModule : null;
}

/**
 * Create a controller instance of the module
 *
 * @param string $module_name
 * @return ?ModuleObject
 */
function getController($module_name): ?ModuleObject
{
	return ModuleHandler::getModuleInstance($module_name, 'controller');
}

/**
 * Create a admin controller instance of the module
 *
 * @param string $module_name
 * @return ?ModuleObject
 */
function getAdminController($module_name): ?ModuleObject
{
	return ModuleHandler::getModuleInstance($module_name, 'controller', 'admin');
}

/**
 * Create a view instance of the module
 *
 * @param string $module_name
 * @return ?ModuleObject
 */
function getView($module_name): ?ModuleObject
{
	return ModuleHandler::getModuleInstance($module_name, 'view');
}

/**
 * Create a admin view instance of the module
 *
 * @param string $module_name
 * @return ?ModuleObject
 */
function getAdminView($module_name): ?ModuleObject
{
	return ModuleHandler::getModuleInstance($module_name, 'view', 'admin');
}

/**
 * Create a model instance of the module
 *
 * @param string $module_name
 * @return ?ModuleObject
 */
function getModel($module_name): ?ModuleObject
{
	return ModuleHandler::getModuleInstance($module_name, 'model');
}

/**
 * Create an admin model instance of the module
 *
 * @param string $module_name
 * @return ?ModuleObject
 */
function getAdminModel($module_name): ?ModuleObject
{
	return ModuleHandler::getModuleInstance($module_name, 'model', 'admin');
}

/**
 * Create an api instance of the module
 *
 * @param string $module_name
 * @return ?ModuleObject
 */
function getAPI($module_name): ?ModuleObject
{
	return ModuleHandler::getModuleInstance($module_name, 'api');
}

/**
 * Create a mobile instance of the module
 *
 * @param string $module_name
 * @return ?ModuleObject
 */
function getMobile($module_name): ?ModuleObject
{
	return ModuleHandler::getModuleInstance($module_name, 'mobile');
}

/**
 * Create a wap instance of the module
 *
 * @deprecated
 * @param string $module_name
 * @return ?ModuleObject
 */
function getWAP($module_name): ?ModuleObject
{
	return ModuleHandler::getModuleInstance($module_name, 'wap');
}

/**
 * Create a class instance of the module
 *
 * @param string $module_name
 * @return ?ModuleObject
 */
function getClass($module_name): ?ModuleObject
{
	$oModule = ModuleHandler::getModuleInstance($module_name, 'class');
	return $oModule instanceof ModuleObject ? $oModule : null;
}

/**
 * The alias of DB::executeQuery()
 *
 * @see DB::executeQuery()
 * @param string $query_id (module name.query XML file)
 * @param array|object $args Arguments
 * @param array $column_list Column list
 * @param string $result_type 'auto', 'array' or 'raw'
 * @param string $result_class Name of class to use instead of stdClass
 * @return Rhymix\Framework\Helpers\DBResultHelper
 */
function executeQuery($query_id, $args = [], $column_list = [], string $result_type = 'auto', string $result_class = 'stdClass'): Rhymix\Framework\Helpers\DBResultHelper
{
	$oDB = Rhymix\Framework\DB::getInstance();
	$column_list = is_array($column_list) ? $column_list : [];
	return $oDB->executeQuery($query_id, $args, $column_list, $result_type, $result_class);
}

/**
 * Function to handle the result of DB::executeQuery() as an array
 *
 * @see DB::executeQuery()
 * @param string $query_id (module name.query XML file)
 * @param array|object $args Arguments
 * @param array $column_list Column list
 * @param string $result_class Name of class to use instead of stdClass
 * @return Rhymix\Framework\Helpers\DBResultHelper
 */
function executeQueryArray($query_id, $args = [], $column_list = [], string $result_class = 'stdClass'): Rhymix\Framework\Helpers\DBResultHelper
{
	$oDB = Rhymix\Framework\DB::getInstance();
	$column_list = is_array($column_list) ? $column_list : [];
	return $oDB->executeQuery($query_id, $args, $column_list, 'array', $result_class);
}

/**
 * Alias of DB::getNextSequence()
 *
 * @see DB::getNextSequence()
 * @return int
 */
function getNextSequence(): int
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
function setUserSequence($seq): void
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
 * @return bool
 */
function checkUserSequence($seq): bool
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
function getUrl(): string
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
function getNotEncodedUrl(): string
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
function getAutoEncodedUrl(): string
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
function getFullUrl(): string
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
function getNotEncodedFullUrl(): string
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
function getSiteUrl(): string
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
function getNotEncodedSiteUrl(): string
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
function getFullSiteUrl(): string
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
function getCurrentPageUrl($escape = true): string
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
function isSiteID($domain): bool
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
function cut_str($string, $cut_size = 0, $tail = '...'): string
{
	$string = (string)$string;
	if($cut_size < 1 || !$string)
	{
		return $string;
	}

	if(isset($GLOBALS['use_mb_strimwidth']) || function_exists('mb_strimwidth'))
	{
		$GLOBALS['use_mb_strimwidth'] = TRUE;
		$string = html_entity_decode($string);
		return escape(mb_strimwidth($string, 0, $cut_size + 4, $tail, 'utf-8'));
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
function get_time_zone_offset($timezone): int
{
	return Rhymix\Framework\DateTime::getTimezoneOffsetByLegacyFormat((string)$timezone);
}

/**
 * Get the offset between the current user's time zone and Rhymix's internal time zone.
 *
 * @return int
 */
function zgap($timestamp = null): int
{
	$current_user_timezone = Rhymix\Framework\DateTime::getTimezoneForCurrentUser();
	return Rhymix\Framework\DateTime::getTimezoneOffsetFromInternal($current_user_timezone, $timestamp);
}

/**
 * Convert timestamp string to Unix timestamp.
 * This function assumes the internal timezone.
 *
 * Supported formats:
 *   - YYYYMMDDHHMMSS
 *   - YYYYMMDD
 *   - YYYY-MM-DD HH:MM:SS
 *   - YYYY-MM-DDTHH:MM:SS+xx:xx (ISO 8601)
 *
 * @param string $str Timestamp in one of the supported formats
 * @return ?int
 */
function ztime($str): ?int
{
	$len = strlen($str);
	if (!$len)
	{
		return null;
	}
	elseif ($len === 9 || ($len === 10 && $str <= 2147483647))
	{
		return intval($str);
	}
	elseif ($len >= 19 && preg_match('/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})(?:([+-])(\d{2}):(\d{2}))?/', $str, $matches))
	{
		$has_time = true;
		$year = intval($matches[1], 10);
		$month = intval($matches[2], 10);
		$day = intval($matches[3], 10);
		$hour = intval($matches[4], 10);
		$min = intval($matches[5], 10);
		$sec = intval($matches[6], 10);
		if (isset($matches[7]))
		{
			$has_offset = true;
			$offset = (intval($matches[8], 10) * 3600) + (intval($matches[9], 10) * 60);
			$offset = $offset * (($matches[7] === '+') ? 1 : -1);
		}
		else
		{
			$has_offset = false;
			$offset = 0;
		}
	}
	elseif (preg_match('/^(\d{4})(\d{2})(\d{2})(?:(\d{2})(\d{2})?(\d{2})?)?$/', $str, $matches))
	{
		$year = intval($matches[1], 10);
		$month = intval($matches[2], 10);
		$day = intval($matches[3], 10);
		if (isset($matches[4]))
		{
			$has_time = true;
			$hour = intval($matches[4], 10);
			$min = intval($matches[5] ?? 0, 10);
			$sec = intval($matches[6] ?? 0, 10);
		}
		else
		{
			$has_time = false;
			$hour = $min = $sec = 0;
		}
		$has_offset = false;
	}
	else
	{
		return null;
	}

	$timestamp = gmmktime($hour, $min, $sec, $month, $day, $year);
	if (!$has_offset)
	{
		$offset = $has_time ? (Rhymix\Framework\Config::get('locale.internal_timezone') ?: date('Z', $timestamp)) : 0;
	}
	return $timestamp - $offset;
}

/**
 * Convert timestamp to user-defined format.
 * This function assumes the internal timezone.
 * See ztime() for the list of supported formats.
 *
 * @param string $str Timestamp in one of the supported formats
 * @param string $format Time format for date() function
 * @param bool $conversion If true, convert automatically for the current language.
 * @return ?string
 */
function zdate($str, $format = 'Y-m-d H:i:s', $conversion = false): ?string
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
	$result = Rhymix\Framework\DateTime::formatTimestampForCurrentUser((string)$format, ztime($str));

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
 * @param ?int $timestamp Unix timestamp
 * @param string $format
 * @return string
 */
function getInternalDateTime(?int $timestamp = null, string $format = 'YmdHis'): string
{
	$timestamp = ($timestamp !== null) ? $timestamp : time();
	return Rhymix\Framework\DateTime::formatTimestamp($format, $timestamp);
}

/**
 * Convert a Unix timestamp to YYYYMMDDHHIISS format, using the internal time zone.
 * If the timestamp is not given, the current time is used.
 *
 * @param ?int $timestamp Unix timestamp
 * @param string $format
 * @return string
 */
function getDisplayDateTime(?int $timestamp = null, string $format = 'YmdHis'): string
{
	$timestamp = ($timestamp !== null) ? $timestamp : time();
	return Rhymix\Framework\DateTime::formatTimestampForCurrentUser($format, $timestamp);
}

/**
 * If the recent post within a day, output format of YmdHis is "min/hours ago from now". If not within a day, it return format string.
 *
 * @param string $date Time value in format of YYYYMMDDHHIISS
 * @param string $format If gap is within a day, returns this format.
 * @return ?string
 */
function getTimeGap($date, $format = 'Y.m.d'): ?string
{
	$timestamp = intval(ztime($date));
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
 * @param bool $short If set, returns short string
 * @return string
 */
function getMonthName(int $month, bool $short = true): string
{
	$short_month = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	$long_month = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	return strval($short ? $short_month[$month - 1] : $long_month[$month - 1]);
}

/**
 * Returns encoded value of given email address for email scraping
 *
 * @param string $email The email
 * @return string
 */
function getEncodeEmailAddress($email): string
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
 * @param mixed $value The expression to dump.
 * @param mixed $values Further expressions to dump.
 * @return void
 */
function debugPrint($value, ...$values): void
{
	Rhymix\Framework\Debug::addEntry($value);

	foreach ($values as $v)
	{
		Rhymix\Framework\Debug::addEntry($v);
	}
}

/**
 * Delete the second object vars from the first argument
 *
 * @param object $target_obj An original object
 * @param object $del_obj Object vars to delete from the original object
 * @return object
 */
function delObjectVars($target_obj, $del_obj): object
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

/**
 * Delete variables that are commonly submitted but don't need to be saved.
 *
 * @param array|object $vars
 * @return array|object
 */
function getDestroyXeVars($vars)
{
	$delete_vars = array(
		'error_return_url', 'success_return_url', 'ruleset', 'xe_validator_id',
		'_filter', '_rx_ajax_compat', '_rx_ajax_form', '_rx_csrf_token',
	);

	foreach($delete_vars as $var)
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
 * Trim a given number to a fiven size recursively
 *
 * @param int $no A given number
 * @param int $size A given digits
 * @return string
 */
function getNumberingPath($no, int $size = 3): string
{
	$mod = pow(10, $size);
	$output = sprintf('%0' . $size . 'd/', intval($no % $mod));
	if($no >= $mod)
	{
		$output .= getNumberingPath(intval($no / $mod), $size);
	}
	return $output;
}

/**
 * Sanitize HTML content.
 *
 * @param string $content Target content
 * @return string
 */
function removeHackTag($content): string
{
	return Rhymix\Framework\Filters\HTMLFilter::clean((string)$content);
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
 * Get is current user crawler
 *
 * @param string $agent if set, use this value instead HTTP_USER_AGENT
 * @return bool
 */
function isCrawler($agent = null): bool
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
function stripEmbedTagForAdmin(&$content, $writer_member_srl): void
{
	if (!Context::get('is_logged'))
	{
		return;
	}

	$logged_info = Context::get('logged_info');
	$writer_member_srl = abs($writer_member_srl);
	if ($logged_info->member_srl == $writer_member_srl)
	{
		return;
	}

	if ($logged_info && isset($logged_info->is_admin) && $logged_info->is_admin === 'Y')
	{
		if ($writer_member_srl)
		{
			$member_info = MemberModel::getMemberInfoByMemberSrl($writer_member_srl);
			if ($member_info && isset($member_info->is_admin) && $member_info->is_admin === 'Y')
			{
				return;
			}
		}

		$security_msg = '<div style="border: 1px solid #DDD; background: #FAFAFA; text-align:center; margin: 1em 0;">' .
			'<p style="margin: 1em;">' . lang('security_warning_embed') . '</p></div>';
		$content = Rhymix\Framework\Filters\MediaFilter::removeEmbeddedMedia((string)$content, $security_msg);
	}

	return;
}

/**
 * Check for CSRF attacks
 *
 * @return bool
 */
function checkCSRF(): bool
{
	return Rhymix\Framework\Security::checkCSRF();
}

/**
 * menu exposure check by isShow column
 * @param array $menu
 * @return void
 */
function recurciveExposureCheck(&$menu): void
{
	if(is_array($menu))
	{
		foreach($menu AS $key=>$value)
		{
			if(!$value['isShow'])
			{
				unset($menu[$key]);
				continue;
			}
			if(is_array($value['list']) && count($value['list']) > 0)
			{
				recurciveExposureCheck($menu[$key]['list']);
			}
		}
	}
}

/**
 * Alias to hex2rgb()
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
 * =========================== DEPRECATED FUNCTIONS ===========================
 * ====================== KEPT FOR COMPATIBILITY WITH XE ======================
 */

/**
 * Print raw html header
 *
 * @deprecated
 * @return void
 */
function htmlHeader(): void
{
	echo implode("\n", array('<!DOCTYPE html>', '<html lang="ko">', '<head>', '<meta charset="UTF-8" />', '</head>', '<body>', ''));
}

/**
 * Print raw html footer
 *
 * @deprecated
 * @return void
 */
function htmlFooter(): void
{
	echo implode("\n", array('', '</body>', '</html>', ''));
}

/**
 * Print raw alert message script
 *
 * @deprecated
 * @param string $msg
 * @return void
 */
function alertScript($msg = null): void
{
	if($msg)
	{
		echo sprintf('<script> alert(%s); </script>', json_encode(strval($msg)));
	}
}

/**
 * Print raw close window script
 *
 * @deprecated
 * @return void
 */
function closePopupScript(): void
{
	echo '<script> window.open("", "_self", ""); window.close(); </script>';
}

/**
 * Print raw reload script
 *
 * @deprecated
 * @param bool $isOpener
 * @return void
 */
function reload($isOpener = FALSE): void
{
	$reloadScript = $isOpener ? 'window.opener.location.reload();' : 'window.location.reload();';
	echo sprintf('<script> %s </script>', $reloadScript);
}

/**
 * Legacy error handler
 *
 * @deprecated
 * @param int $errno
 * @param string $errstr
 * @param string $file
 * @param int $line
 * @return void
 */
function handleError($errno, $errstr, $file, $line, $context): void
{
	Rhymix\Framework\Debug::addError($errno, $errstr, $file, $line, $context);
}

/**
 * Alias to microtime(true)
 *
 * @deprecated
 * @return float
 */
function getMicroTime(): float
{
	return microtime(true);
}

/**
 * Return the requested script path
 *
 * @deprecated
 * @return string
 */
function getScriptPath(): string
{
	return RX_BASEURL;
}

/**
 * Return the requested script path
 *
 * @deprecated
 * @return string
 */
function getRequestUriByServerEnviroment(): string
{
	return preg_replace('/[<>"]/', '', $_SERVER['REQUEST_URI']);
}

/**
 * get json encoded string of data
 *
 * @deprecated
 * @param mixed $data
 * @return string|false
 */
function json_encode2($data)
{
	return json_encode($data);
}

/**
 * Decode the URL in Korean
 *
 * @deprecated
 * @param string $str The url
 * @return string
 */
function url_decode($str): string
{
	return escape(utf8RawUrlDecode($str));
}

/**
 * Block widget code (Deprecated)
 *
 * @deprecated
 * @param string $content Taget content
 * @return string
 **/
function blockWidgetCode($content): string
{
	return preg_replace('/(<(?:img|div)(?:[^>]*))(widget)(?:(=([^>]*?)>))/is', '$1blocked-widget$3', (string)$content);
}

/**
 * HTMLPurifier wrapper (Deprecated)
 *
 * @deprecated
 * @param string &$content Target content
 * @return void
 */
function purifierHtml(&$content): void
{
	$content = Rhymix\Framework\Filters\HTMLFilter::clean((string)$content);
}

/**
 * Check xmp tag (Deprecated)
 *
 * @deprecated
 */
function checkXmpTag($content): string
{
	return (string)$content;
}

/**
 * Remove src hack (Deprecated)
 *
 * @deprecated
 * @param array $match
 * @return string
 */
function removeSrcHack(array $match): string
{
	return strval($match[0]);
}

/**
 * Check uploaded file (Deprecated)
 *
 * @deprecated
 * @param string $file Taget file path
 * @return bool
 */
function checkUploadedFile($file, $filename = null): bool
{
	return true;
}

/**
 * Php function for mysql old_password()
 * provides backward compatibility for zero board4 which uses old_password() of mysql 4.1 earlier versions.
 * the function implemented by referring to the source codes of password.c file in mysql
 *
 * @deprecated
 * @param string $password
 * @return string
 */
function mysql_pre4_hash_password($password): string
{
	return VendorPass::mysql_old_password(strval($password));
}

/**
 * Change values inside a user-submitted URL, most commonly success_return_url.
 *
 * @deprecated
 * @param string $key
 * @param string $requestKey
 * @param string $dbKey
 * @param string $urlName
 * @return void
 */
function changeValueInUrl($key, $requestKey, $dbKey, $urlName = 'success_return_url'): void
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
				$successReturnUrl = $arrayUrl['path'].'?'.http_build_query($parsedStr);
				Context::set($urlName, $successReturnUrl);
			}
		}
	}
}

/**
 * PHP unescape function of javascript's escape
 *
 * Function converts an Javascript escaped string back into a string with specified charset (default is UTF-8).
 * Modified function from http://pure-essence.net/stuff/code/utf8RawUrlDecode.phps
 *
 * @deprecated
 * @param string $source
 * @return string
 */
function utf8RawUrlDecode($source): string
{
	return preg_replace_callback('/%u([0-9a-f]+)/i', function($m) {
		return html_entity_decode('&#x' . $m[1] . ';');
	}, rawurldecode($source ?? ''));
}

/**
 * Returns utf-8 string of given code
 *
 * @deprecated
 * @param int $num
 * @return string
 */
function _code2utf($num): string
{
	return html_entity_decode('&#' . $num . ';');
}

/**
 * @deprecated
 */
function writeSlowlog(): void
{
	// no-op
}

/**
 * @deprecated
 */
function flushSlowlog(): void
{
	// no-op
}

/**
 * @deprecated
 */
function requirePear(): void
{
	// no-op
}
