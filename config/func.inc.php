<?php
    /**
     * @file   config/func.inc.php
     * @author NHN (developers@xpressengine.com)
     * @brief function library files for convenience
    **/

    if(!defined('__XE__') && !defined('__ZBXE__')) exit();

    /**
     * @brief define clone for php5
     **/
    if (version_compare(phpversion(), '5.0') < 0) {
        eval('
            function clone($object) {
            return $object;
            }
        ');
    }

    /**
     * @brief define an empty function to avoid errors when iconv function doesn't exist
     **/
    if(!function_exists('iconv')) {
        eval('
            function iconv($in_charset, $out_charset, $str) {
                return $str;
            }
        ');
	}

	if ( !function_exists('htmlspecialchars_decode') )
	{
		function htmlspecialchars_decode($text)
		{
			return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
		}
	}

    // time zone
    $time_zone = array(
        '-1200' => '[GMT -12:00] Baker Island Time',
        '-1100' => '[GMT -11:00] Niue Time, Samoa Standard Time',
        '-1000' => '[GMT -10:00] Hawaii-Aleutian Standard Time, Cook Island Time',
        '-0930' => '[GMT -09:30] Marquesas Islands Time',
        '-0900' => '[GMT -09:00] Alaska Standard Time, Gambier Island Time',
        '-0800' => '[GMT -08:00] Pacific Standard Time',
        '-0700' => '[GMT -07:00] Mountain Standard Time',
        '-0600' => '[GMT -06:00] Central Standard Time',
        '-0500' => '[GMT -05:00] Eastern Standard Time',
        '-0400' => '[GMT -04:00] Atlantic Standard Time',
        '-0330' => '[GMT -03:30] Newfoundland Standard Time',
        '-0300' => '[GMT -03:00] Amazon Standard Time, Central Greenland Time',
        '-0200' => '[GMT -02:00] Fernando de Noronha Time, South Georgia &amp; the South Sandwich Islands Time',
        '-0100' => '[GMT -01:00] Azores Standard Time, Cape Verde Time, Eastern Greenland Time',
        '0000'  => '[GMT  00:00] Western European Time, Greenwich Mean Time',
        '+0100' => '[GMT +01:00] Central European Time, West African Time',
        '+0200' => '[GMT +02:00] Eastern European Time, Central African Time',
        '+0300' => '[GMT +03:00] Moscow Standard Time, Eastern African Time',
        '+0330' => '[GMT +03:30] Iran Standard Time',
        '+0400' => '[GMT +04:00] Gulf Standard Time, Samara Standard Time',
        '+0430' => '[GMT +04:30] Afghanistan Time',
        '+0500' => '[GMT +05:00] Pakistan Standard Time, Yekaterinburg Standard Time',
        '+0530' => '[GMT +05:30] Indian Standard Time, Sri Lanka Time',
        '+0545' => '[GMT +05:45] Nepal Time',
        '+0600' => '[GMT +06:00] Bangladesh Time, Bhutan Time, Novosibirsk Standard Time',
        '+0630' => '[GMT +06:30] Cocos Islands Time, Myanmar Time',
        '+0700' => '[GMT +07:00] Indochina Time, Krasnoyarsk Standard Time',
        '+0800' => '[GMT +08:00] Chinese Standard Time, Australian Western Standard Time, Irkutsk Standard Time',
        '+0845' => '[GMT +08:45] Southeastern Western Australia Standard Time',
        '+0900' => '[GMT +09:00] Korea Standard Time, Japan Standard Time, China Standard Time',
        '+0930' => '[GMT +09:30] Australian Central Standard Time',
        '+1000' => '[GMT +10:00] Australian Eastern Standard Time, Vladivostok Standard Time',
        '+1030' => '[GMT +10:30] Lord Howe Standard Time',
        '+1100' => '[GMT +11:00] Solomon Island Time, Magadan Standard Time',
        '+1130' => '[GMT +11:30] Norfolk Island Time',
        '+1200' => '[GMT +12:00] New Zealand Time, Fiji Time, Kamchatka Standard Time',
        '+1245' => '[GMT +12:45] Chatham Islands Time',
        '+1300' => '[GMT +13:00] Tonga Time, Phoenix Islands Time',
        '+1400' => '[GMT +14:00] Line Island Time'
    ) ;

    /**
     * @brief define a function to use ModuleHandler::getModuleObject ($module_name, $type)
     * @param module_name
     * @param type disp, proc, controller, class
     * @param kind admin, null
     * @return module instance
     **/
    function &getModule($module_name, $type = 'view', $kind = '') {
        return ModuleHandler::getModuleInstance($module_name, $type, $kind);
    }

    /**
     * @brief create a controller instance of the module
     * @param module_name
     * @return module controller instance
     **/
    function &getController($module_name) {
        return getModule($module_name, 'controller');
    }

    /**
     * @brief create a controller instance of the module
     * @param module_name
     * @return module admin controller instance
     **/
    function &getAdminController($module_name) {
        return getModule($module_name, 'controller','admin');
    }

    /**
     * @brief create a view instance of the module
     * @param module_name
     * @return module view instance
     **/
    function &getView($module_name) {
        return getModule($module_name, 'view');
    }

    /**
     * @brief create a view instance of the module
     * @param module_name
     * @return module mobile instance
     **/
    function &getMobile($module_name) {
        return getModule($module_name, 'mobile');
    }

    /**
     * @brief module의 admin view 객체 생성용
     * @param module_name 모듈이름
     * @return module admin view instance
     **/
    function &getAdminView($module_name) {
        return getModule($module_name, 'view','admin');
    }

    /**
     * @brief create a model instance of the module
     * @param module_name
     * @return module model instance
     **/
    function &getModel($module_name) {
        return getModule($module_name, 'model');
    }

    /**
     * @brief create an admin model instance of the module
     * @param module_name
     * @return module admin model instance
     **/
    function &getAdminModel($module_name) {
        return getModule($module_name, 'model','admin');
    }

    /**
     * @brief create an api instance of the module
     * @param module_name
     * @return module api class instance
     **/
    function &getAPI($module_name) {
        return getModule($module_name, 'api');
    }

    /**
     * @brief create a wap instance of the module
     * @param module_name
     * @return module wap class instance
     **/
    function &getWAP($module_name) {
        return getModule($module_name, 'wap');
    }

    /**
     * @brief create a class instance of the module
     * @param module_name
     * @return module class instance
     **/
    function &getClass($module_name) {
        return getModule($module_name, 'class');
    }

    /**
     * @brief the alias of DB::executeQuery() 
     * @param query_id (module name.query XML file)
     * @param argument values of args object
     * @return results
     **/
    function executeQuery($query_id, $args = null, $arg_columns = null) {
        $oDB = &DB::getInstance();
        return $oDB->executeQuery($query_id, $args, $arg_columns);
    }

    /**
     * @brief function to handle the result of DB::executeQuery() as an array
     * @param query_id(module name.query XML file)
     * @param argument values of args object
     * @return results
     **/
    function executeQueryArray($query_id, $args = null, $arg_columns = null) {
        $oDB = &DB::getInstance();
        $output = $oDB->executeQuery($query_id, $args, $arg_columns);
        if(!is_array($output->data) && count($output->data) > 0){
            $output->data = array($output->data);
        }
        return $output;
    }

    /**
     * @brief DB:: alias of getNextSequence() 
     * @return big int
     **/
    function getNextSequence() {
        $oDB = &DB::getInstance();
        return $oDB->getNextSequence();
    }

    /**
     * @brief define a function to use Context::getUrl()
     * @return string
     *
     * getUrl() returns the URL transformed from given arguments of RequestURI\n
     * 1. argument format follows as (key, value).\.
     * ex) getUrl('key1', 'val1', 'key2',''): transform key1 and key2 to val1 and '' respectively\n
     * 2. returns URL without the argument if no argument is given.
     * 3. URL made of args_list added to RequestUri if the first argument value is ''.
     **/
    function getUrl() {
        $num_args  = func_num_args();
        $args_list = func_get_args();

		if($num_args) $url = Context::getUrl($num_args, $args_list);
		else $url = Context::getRequestUri();

		return preg_replace('@\berror_return_url=[^&]*|\w+=(?:&|$)@', '', $url);
    }

    function getNotEncodedUrl() {
        $num_args = func_num_args();
        $args_list = func_get_args();

		if($num_args) $url = Context::getUrl($num_args, $args_list, null, false);
		else $url = Context::getRequestUri();

		return preg_replace('@\berror_return_url=[^&]*|\w+=(?:&|$)@', '', $url);
    }

    function getAutoEncodedUrl() {
        $num_args = func_num_args();
        $args_list = func_get_args();

		if($num_args) $url = Context::getUrl($num_args, $args_list, null, true, true);
		else $url = Context::getRequestUri();

		return preg_replace('@\berror_return_url=[^&]*|\w+=(?:&|$)@', '', $url);
    }
    /**
     * @brief return the value adding request uri to getUrl()
     * to get the full url
     **/
    function getFullUrl() {
        $num_args = func_num_args();
        $args_list = func_get_args();
		$request_uri = Context::getRequestUri();
        if(!$num_args) return $request_uri;

        $url = Context::getUrl($num_args, $args_list);
        if(!preg_match('/^http/i',$url)){
			preg_match('/^(http|https):\/\/([^\/]+)\//',$request_uri,$match);
			return substr($match[0],0,-1).$url;
		}
        return $url;
    }

    function getNotEncodedFullUrl() {
        $num_args = func_num_args();
        $args_list = func_get_args();
		$request_uri = Context::getRequestUri();
        if(!$num_args) return $request_uri;

        $url = Context::getUrl($num_args, $args_list);
        if(!preg_match('/^http/i',$url)){
			preg_match('/^(http|https):\/\/([^\/]+)\//',$request_uri,$match);
			$url = Context::getUrl($num_args, $args_list, null, false);
			return substr($match[0],0,-1).$url;
		}
        return $url;
    }

    /**
     * @brief Context:: getUrl() function is declared as easier to write
     * @return string
     *
     * getSiteUrl() returns the URL by transforming the given argument value of domain\n
     * The first argument should consist of domain("http://" not included) and path
     * 
     **/
    function getSiteUrl() {
        $num_args = func_num_args();
        $args_list = func_get_args();

        if(!$num_args) return Context::getRequestUri();

        $domain = array_shift($args_list);
        $num_args = count($args_list);

        return Context::getUrl($num_args, $args_list, $domain);
    }

    function getNotEncodedSiteUrl() {
        $num_args = func_num_args();
        $args_list = func_get_args();

        if(!$num_args) return Context::getRequestUri();

        $domain = array_shift($args_list);
        $num_args = count($args_list);

        return Context::getUrl($num_args, $args_list, $domain, false);
    }

    /**
     * @brief return the value adding request uri to the getSiteUrl()
     * To get the full url
     **/
    function getFullSiteUrl() {
        $num_args = func_num_args();
        $args_list = func_get_args();

		$request_uri = Context::getRequestUri();
        if(!$num_args) return $request_uri;

        $domain = array_shift($args_list);
        $num_args = count($args_list);

        $url = Context::getUrl($num_args, $args_list, $domain);
        if(!preg_match('/^http/i',$url)){
			preg_match('/^(http|https):\/\/([^\/]+)\//',$request_uri,$match);
			return substr($match[0],0,-1).$url;
		}
        return $url;
    }

    /**
     * @brief return if domain of the virtual site is url type or id type
     **/
    function isSiteID($domain) {
        return preg_match('/^([a-z0-9\_]+)$/i', $domain);
    }

    /**
     * @brief put a given tail after trimming string to the specified size
     * @param the original string to trim
     * @param cut_size: the size to be
     * @param tail: tail to put in the end of the string after trimming
     * @return string
     **/
	function cut_str($string, $cut_size = 0, $tail = '...')
	{
		if($cut_size < 1 || !$string) return $string;

		if($GLOBALS['use_mb_strimwidth'] || function_exists('mb_strimwidth'))
		{
			$GLOBALS['use_mb_strimwidth'] = TRUE; 
			return mb_strimwidth($string, 0, $cut_size + 4, $tail, 'utf-8');
		}

		$chars = array(12, 4, 3, 5, 7, 7, 11, 8, 4, 5, 5, 6, 6, 4, 6, 4, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 4, 4, 8, 6, 8, 6, 10, 8, 8, 9, 8, 8, 7, 9, 8, 3, 6, 7, 7, 11, 8, 9, 8, 9, 8, 8, 7, 8, 8, 10, 8, 8, 8, 6, 11, 6, 6, 6, 4, 7, 7, 7, 7, 7, 3, 7, 7, 3, 3, 6, 3, 9, 7, 7, 7, 7, 4, 7, 3, 7, 6, 10, 6, 6, 7, 6, 6, 6, 9);
		$max_width = $cut_size*$chars[0]/2;
		$char_width = 0;

		$string_length = strlen($string);
		$char_count = 0;

		$idx = 0;
		while($idx < $string_length && $char_count < $cut_size && $char_width <= $max_width) {
			$c = ord(substr($string, $idx,1));
			$char_count++;
			if($c < 128) 
			{
				$char_width += (int)$chars[$c-32];
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

    function zgap() {
        $time_zone = $GLOBALS['_time_zone'];
        if($time_zone < 0) $to = -1; else $to = 1;
        $t_hour = substr($time_zone, 1, 2) * $to;
        $t_min = substr($time_zone, 3, 2) * $to;

        $server_time_zone = date("O");
        if($server_time_zone < 0) $so = -1; else $so = 1;
        $c_hour = substr($server_time_zone, 1, 2) * $so;
        $c_min = substr($server_time_zone, 3, 2) * $so;

        $g_min = $t_min - $c_min;
        $g_hour = $t_hour - $c_hour;

        $gap = $g_min*60 + $g_hour*60*60;
        return $gap;
    }

    /**
     * @brief YYYYMMDDHHIISS format changed to unix time value
     * @param str: time value in format of YYYYMMDDHHIISS
     * @return int
     **/
    function ztime($str) {
        if(!$str) return;
        $hour = (int)substr($str,8,2);
        $min = (int)substr($str,10,2);
        $sec = (int)substr($str,12,2);
        $year = (int)substr($str,0,4);
        $month = (int)substr($str,4,2);
        $day = (int)substr($str,6,2);
        if(strlen($str) <= 8) {
            $gap = 0;
        } else {
            $gap = zgap();
        }

        return mktime($hour, $min, $sec, $month?$month:1, $day?$day:1, $year)+$gap;
    }

    /**
     * @brief If the recent post within a day, output format of YmdHis is "min/hours ago from now". If not within a day, it return format string.
     **/
    function getTimeGap($date, $format = 'Y.m.d') {
        $gap = time() + zgap() - ztime($date);

        $lang_time_gap = Context::getLang('time_gap');
        if($gap<60) $buff = sprintf($lang_time_gap['min'], (int)($gap / 60)+1);
        elseif($gap<60*60) $buff =  sprintf($lang_time_gap['mins'], (int)($gap / 60)+1);
        elseif($gap<60*60*2) $buff =  sprintf($lang_time_gap['hour'], (int)($gap / 60 /60)+1);
        elseif($gap<60*60*24) $buff =  sprintf($lang_time_gap['hours'], (int)($gap / 60 /60)+1);
        else $buff =  zdate($date, $format);
        return $buff;
    }

    /**
     * @brief Name of the month return
     **/
    function getMonthName($month, $short = true) {
        $short_month = array('','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
        $long_month = array('','January','February','March','April','May','June','July','August','September','October','November','December');
        return !$short?$long_month[$month]:$short_month[$month];
    }

    /**
     * @brief change the time format YYYYMMDDHHIISS to the user defined format
     * @param string|int str is YYYYMMDDHHIISS format time values
     * @param string format is time format of php date() function
     * @param bool conversion means whether to convert automatically according to the language
     * @return string
     **/
    function zdate($str, $format = 'Y-m-d H:i:s', $conversion=true) {
        // return null if no target time is specified
        if(!$str) return;
        // convert the date format according to the language
        if($conversion == true) {
            switch(Context::getLangType()) {
                case 'en' :
                case 'es' :
                        if($format == 'Y-m-d') $format = 'M d, Y';
                        elseif($format == 'Y-m-d H:i:s') $format = 'M d, Y H:i:s';
                        elseif($format == 'Y-m-d H:i') $format = 'M d, Y H:i';
                    break;
                case 'vi' :
                        if($format == 'Y-m-d') $format = 'd-m-Y';
                        elseif($format == 'Y-m-d H:i:s') $format = 'H:i:s d-m-Y';
                        elseif($format == 'Y-m-d H:i') $format = 'H:i d-m-Y';
                    break;

            }
        }

        // If year value is less than 1970, handle it separately.
        if((int)substr($str,0,4) < 1970) {
            $hour  = (int)substr($str,8,2);
            $min   = (int)substr($str,10,2);
            $sec   = (int)substr($str,12,2);
            $year  = (int)substr($str,0,4);
            $month = (int)substr($str,4,2);
            $day   = (int)substr($str,6,2);

			// leading zero?
			$lz = create_function('$n', 'return ($n>9?"":"0").$n;');

			$trans = array(
				'Y'=>$year,
				'y'=>$lz($year%100),
				'm'=>$lz($month),
				'n'=>$month,
				'd'=>$lz($day),
				'j'=>$day,
				'G'=>$hour,
				'H'=>$lz($hour),
				'g'=>$hour%12,
				'h'=>$lz($hour%12),
				'i'=>$lz($min),
				's'=>$lz($sec),
				'M'=>getMonthName($month),
				'F'=>getMonthName($month,false)
			);

            $string = strtr($format, $trans);
        } else {
            // if year value is greater than 1970, get unixtime by using ztime() for date() function's argument. 
            $string = date($format, ztime($str));
        }
        // change day and am/pm for each language
        $unit_week = Context::getLang('unit_week');
        $unit_meridiem = Context::getLang('unit_meridiem');
        $string = str_replace(array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),$unit_week, $string);
        $string = str_replace(array('am','pm','AM','PM'), $unit_meridiem, $string);
        return $string;
    }

	function getEncodeEmailAddress($email) {
	    $return = '';
		for ($i=0,$c=strlen($email);$i<$c;$i++) {
			$return .= '&#' . (rand(0,1)==0 ? ord($email[$i]) : 'X'.dechex(ord($email[$i]))) . ';';
		}
		return $return;
	}

    /**
     * @brief prints debug messages 
     * @param debug_output target object to be printed
     * @param display_line boolean flag whether to print seperator (default:true)
     * @return none
     *
     * Display $buff contents into the file ./files/_debug_message.php.
     * You can see the file on your prompt by command: tail-f./files/_debug_message.php
     **/
    function debugPrint($debug_output = null, $display_option = true, $file = '_debug_message.php') {
        if(!(__DEBUG__ & 1)) return;

        static $firephp;
        $bt = debug_backtrace();
        if(is_array($bt)) $first = array_shift($bt);
        $file_name = array_pop(explode(DIRECTORY_SEPARATOR, $first['file']));
        $line_num = $first['line'];

        if(__DEBUG_OUTPUT__ == 2 && version_compare(PHP_VERSION, '6.0.0') === -1) {
            if(!isset($firephp)) $firephp = FirePHP::getInstance(true);
            if(function_exists("memory_get_usage"))
            {
                $label = sprintf('[%s:%d] (m:%s)', $file_name, $line_num, FileHandler::filesize(memory_get_usage()));
            }
            else
            {
                $label = sprintf('[%s:%d] ', $file_name, $line_num);
            }
            // Check a FirePHP option
            if($display_option === 'TABLE') $label = $display_option;
            // Check if the IP specified by __DEBUG_PROTECT__ option is same as the access IP.
            if(__DEBUG_PROTECT__ === 1 && __DEBUG_PROTECT_IP__ != $_SERVER['REMOTE_ADDR']) {
                $debug_output = 'The IP address is not allowed. Change the value of __DEBUG_PROTECT_IP__ into your IP address in config/config.user.inc.php or config/config.inc.php';
                $label = null;
            }

            $firephp->fb($debug_output, $label);

        } else {
            if(__DEBUG_PROTECT__ === 1 && __DEBUG_PROTECT_IP__ != $_SERVER['REMOTE_ADDR']) {
                return;
            }
            $debug_file = _XE_PATH_.'files/'.$file;
            if(function_exists("memory_get_usage"))
            {
                $debug_output = sprintf("[%s %s:%d] - mem(%s)\n%s\n", date('Y-m-d H:i:s'), $file_name, $line_num, FileHandler::filesize(memory_get_usage()), print_r($debug_output, true));
            }
            else
            {
                $debug_output = sprintf("[%s %s:%d]\n%s\n", date('Y-m-d H:i:s'), $file_name, $line_num, print_r($debug_output, true));
            }

            if($display_option === true) $debug_output = str_repeat('=', 40)."\n".$debug_output.str_repeat('-', 40);
            $debug_output = "\n<?php\n/*".$debug_output."*/\n?>\n";

            if(@!$fp = fopen($debug_file, 'a')) return;
            fwrite($fp, $debug_output);
            fclose($fp);
        }
    }


    /**
     * @brief microtime() return
     * @return float
     **/
    function getMicroTime() {
        list($time1, $time2) = explode(' ', microtime());
        return (float)$time1 + (float)$time2;
    }

    /**
     * @brief Delete the second object vars from the first argument
     * @param target_obj is an original object
     * @param del_obj is object vars to delete from the original object
     * @return object
     **/
    function delObjectVars($target_obj, $del_obj) {
        if(!is_object($target_obj)) return;
        if(!is_object($del_obj)) return;

        $target_vars = get_object_vars($target_obj);
        $del_vars = get_object_vars($del_obj);

        $target = array_keys($target_vars);
        $del = array_keys($del_vars);
        if(!count($target)||!count($del)) return $target_obj;

        $return_obj = NULL;

        $target_count = count($target);
        for($i = 0; $i < $target_count; $i++) {
            $target_key = $target[$i];
            if(!in_array($target_key, $del)) $return_obj->{$target_key} = $target_obj->{$target_key};
        }

        return $return_obj;
    }

    /**
     * @brief change error_handing to debugPrint on php5 higher 
     * @param errno
     * @param errstr
     * @return file
     * @return line
     **/
    function handleError($errno, $errstr, $file, $line) {
        if(!__DEBUG__) return;
        $errors = array(E_USER_ERROR, E_ERROR, E_PARSE);
        if(!in_array($errno, $errors)) return;

        $output = sprintf("Fatal error : %s - %d", $file, $line);
        $output .= sprintf("%d - %s", $errno, $errstr);

        debugPrint($output);
    }

    /**
     * @brief Trim a given number to a fiven size recursively
     * @param no : a given number
     * @param size : a given digits
     **/
    function getNumberingPath($no, $size=3) {
        $mod = pow(10, $size);
        $output = sprintf('%0'.$size.'d/', $no%$mod);
        if($no >= $mod) $output .= getNumberingPath((int)$no/$mod, $size);
        return $output;
    }

    /**
     * @brief decode the URL in Korean
     **/
    function url_decode($str) {
        return preg_replace('/%u([[:alnum:]]{4})/', '&#x\\1;',$str);
    }

    /**
     * @brief Pre-block the codes which may be hacking attempts
     **/
    function removeHackTag($content) {
        // change the specific tags to the common texts
        $content = preg_replace('@<(\/?(?:html|body|head|title|meta|base|link|script|style|applet|iframe)[\s>])@i', '&lt;$1', $content);

        /**
         * Remove codes to abuse the admin session in src by tags of imaages and video postings
         * - Issue reported by Sangwon Kim
         **/
		$content = preg_replace_callback('@<(/?)([a-z]+[0-9]?)((?>"[^"]*"|\'[^\']*\'|[^>])*?\b(?:on[a-z]+|data|style|background|href|(?:dyn|low)?src)\s*=[\s\S]*?)(/?)($|>|<)@i', 'removeSrcHack', $content);

		// xmp tag 확인 및 추가
		$content = checkXmpTag($content);

		if(version_compare(PHP_VERSION, "5.3.0") >= 0)
		{
			// purifier setting
			require_once _XE_PATH_.'classes/security/htmlpurifier/library/HTMLPurifier.auto.php';
			require_once 'HTMLPurifier.func.php';

			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.TidyLevel', 'light');
			$config->set('HTML.SafeObject', true);
			$purifier = new HTMLPurifier($config);
			$content = $purifier->purify($content);
		}
		return $content;
    }

    /**
     * @brief xmp tag 확인 및 닫히지 않은 경우 추가
     **/
	function checkXmpTag($content) {
		$content = preg_replace('@<(/?)xmp.*?>@i', '<\1xmp>', $content);

		if(($start_xmp = strrpos($content, '<xmp>')) !==false) {
			if(($close_xmp = strrpos($content, '</xmp>')) === false) $content .= '</xmp>';
			else if($close_xmp < $start_xmp) $content .= '</xmp>';
		}
		
		return $content;
	}

    function removeSrcHack($match) {
        $tag = strtolower($match[2]);

		// xmp tag 정리
		if($tag=='xmp') return "<{$match[1]}xmp>";
 		if($match[1]) return $match[0];
		if($match[4]) $match[4] = ' '.$match[4];

		$attrs = array();
		if(preg_match_all('/([\w:-]+)\s*=(?:\s*(["\']))?(?(2)(.*?)\2|([^ ]+))/s', $match[3], $m)) {
			foreach($m[1] as $idx=>$name){
				if(substr($name,0,2) == 'on') continue;

				$val = preg_replace('/&#(?:x([a-fA-F0-9]+)|0*(\d+));/e','chr("\\1"?0x00\\1:\\2+0)',$m[3][$idx].$m[4][$idx]);
				$val = preg_replace('/^\s+|[\t\n\r]+/', '', $val);

				if(preg_match('/^[a-z]+script:/i', $val)) continue;

				$attrs[$name] = $val;
			}
		}

		if(isset($attrs['style']) && preg_match('@(?:/\*|\*/|\n|:\s*expression\s*\()@i', $attrs['style'])) {
			unset($attrs['style']);
		}

		$attr = array();
		foreach($attrs as $name=>$val) {
			if($tag == 'object' || $tag == 'embed')
			{
				$attribute = strtolower(trim($name));
				if($attribute == 'data' || $attribute == 'src')
				{
					if(strpos(strtolower($val), 'data:') === 0)
					{
						continue;
					}
				}
			}
			$val    = str_replace('"', '&quot;', $val);
			$attr[] = $name."=\"{$val}\"";
		}
		$attr = count($attr)?' '.implode(' ',$attr):'';

        return "<{$match[1]}{$tag}{$attr}{$match[4]}>";
    }

    // convert hexa value to RGB
    if(!function_exists('hexrgb')) {
        function hexrgb($hexstr) {
          $int = hexdec($hexstr);

          return array('red' => 0xFF & ($int >> 0x10),
                       'green' => 0xFF & ($int >> 0x8),
                       'blue' => 0xFF & $int);
        }
    }

    /**
     * @brief php function for mysql old_password()
     * provides backward compatibility for zero board4 which uses old_password() of mysql 4.1 earlier versions. 
     * the function implemented by referring to the source codes of password.c file in mysql
     **/
    function mysql_pre4_hash_password($password) {
        $nr = 1345345333;
        $add = 7;
        $nr2 = 0x12345671;

        settype($password, "string");

        for ($i=0; $i<strlen($password); $i++) {
            if ($password[$i] == ' ' || $password[$i] == '\t') continue;
            $tmp = ord($password[$i]);
            $nr ^= ((($nr & 63) + $add) * $tmp) + ($nr << 8);
            $nr2 += ($nr2 << 8) ^ $nr;
            $add += $tmp;
        }
        $result1 = sprintf("%08lx", $nr & ((1 << 31) -1));
        $result2 = sprintf("%08lx", $nr2 & ((1 << 31) -1));

        if($result1 == '80000000') $nr += 0x80000000;
        if($result2 == '80000000') $nr2 += 0x80000000;

        return sprintf("%08lx%08lx", $nr, $nr2);
    }

    /**
     * return the requested script path
     **/
    function getScriptPath() {
        static $url = null;
        if($url == null) $url = preg_replace('/\/tools\//i','/',preg_replace('/index.php$/i','',str_replace('\\','/',$_SERVER['SCRIPT_NAME'])));
        return $url;
    }

    /**
     * return the requested script path
     **/
	function getRequestUriByServerEnviroment()
	{
		return $_SERVER['REQUEST_URI'];
	}

    /**
     * php unescape function of javascript's escape
     * Function converts an Javascript escaped string back into a string with specified charset (default is UTF-8).
     * Modified function from http://pure-essence.net/stuff/code/utf8RawUrlDecode.phps
     **/
    function utf8RawUrlDecode ($source) {
        $decodedStr = '';
        $pos = 0;
        $len = strlen ($source);
        while ($pos < $len) {
            $charAt = substr ($source, $pos, 1);
            if ($charAt == '%') {
                $pos++;
                $charAt = substr ($source, $pos, 1);
                if ($charAt == 'u') {
                    // we got a unicode character
                    $pos++;
                    $unicodeHexVal = substr ($source, $pos, 4);
                    $unicode = hexdec ($unicodeHexVal);
                    $decodedStr .= _code2utf($unicode);
                    $pos += 4;
                }
                else {
                    // we have an escaped ascii character
                    $hexVal = substr ($source, $pos, 2);
                    $decodedStr .= chr (hexdec ($hexVal));
                    $pos += 2;
                }
            } else {
                $decodedStr .= $charAt;
                $pos++;
            }
        }
        return $decodedStr;
    }

    function _code2utf($num){
        if($num<128)return chr($num);
        if($num<2048)return chr(($num>>6)+192).chr(($num&63)+128);
        if($num<65536)return chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
        if($num<2097152)return chr(($num>>18)+240).chr((($num>>12)&63)+128).chr((($num>>6)&63)+128) .chr(($num&63)+128);
        return '';
    }


    function detectUTF8($string, $return_convert = false, $urldecode = true) {
        if($urldecode) $string = urldecode($string);

        $sample = iconv('utf-8', 'utf-8', $string);
        $is_utf8 = (md5($sample) == md5($string));

        if(!$urldecode) $string = urldecode($string);

        if($return_convert) return ($is_utf8) ? $string : iconv('euc-kr', 'utf-8', $string);

        return $is_utf8;
    }


    function json_encode2($data) {
        switch (gettype($data)) {
            case 'boolean':
              return $data?'true':'false';
            case 'integer':
            case 'double':
              return $data;
            case 'string':
              return '"'.strtr($data, array('\\'=>'\\\\','"'=>'\\"')).'"';
            case 'object':
              $data = get_object_vars($data);
            case 'array':
              $rel = false; // relative array?
              $key = array_keys($data);
              foreach ($key as $v) {
                if (!is_int($v)) {
                  $rel = true;
                  break;
                }
              }

              $arr = array();
              foreach ($data as $k=>$v) {
                $arr[] = ($rel?'"'.strtr($k, array('\\'=>'\\\\','"'=>'\\"')).'":':'').json_encode2($v);
              }

              return $rel?'{'.join(',', $arr).'}':'['.join(',', $arr).']';
            default:
              return '""';
        }
    }


    function isCrawler($agent = null) {
        if(!$agent) $agent = $_SERVER['HTTP_USER_AGENT'];
        $check_agent = array('bot', 'spider', 'google', 'yahoo', 'daum', 'teoma', 'fish', 'hanrss', 'facebook');
        $check_ip = array(
            '211.245.21.11*' /* mixsh */
        );

        foreach($check_agent as $str) {
            if(stristr($agent, $str) != FALSE) return true;
        }

        $check_ip = '/^('.implode($check_ip, '|').')/';
        $check_ip = str_replace('.', '\.', $check_ip);
        $check_ip = str_replace('*', '.+', $check_ip);
        $check_ip = str_replace('?', '.?', $check_ip);

        if(preg_match($check_ip, $_SERVER['REMOTE_ADDR'], $matches)) return true;

        return false;
    }

	function stripEmbedTagForAdmin(&$content, $writer_member_srl)
	{
		if(!Context::get('is_logged')) return;
		$oModuleModel = &getModel('module');
		$logged_info = Context::get('logged_info');

		if($writer_member_srl != $logged_info->member_srl && ($logged_info->is_admin == "Y" || $oModuleModel->isSiteAdmin($logged_info)) )
		{   
			if($writer_member_srl)
			{
				$oMemberModel =& getModel('member');
				$member_info = $oMemberModel->getMemberInfoByMemberSrl($writer_member_srl);
				if($member_info->is_admin == "Y")
				{
					return;
				}
			}
			$security_msg = "<div style='border: 1px solid #DDD; background: #FAFAFA; text-align:center; margin: 1em 0;'><p style='margin: 1em;'>".Context::getLang('security_warning_embed')."</p></div>";
			$content = preg_replace('/<object[^>]+>(.*?<\/object>)?/is', $security_msg, $content);
			$content = preg_replace('/<embed[^>]+>(\s*<\/embed>)?/is', $security_msg, $content);
			$content = preg_replace('/<img[^>]+editor_component="multimedia_link"[^>]*>(\s*<\/img>)?/is', $security_msg, $content);
		}

		return;
	}

	function requirePear()
	{
		if(version_compare(PHP_VERSION, "5.3.0") < 0)
		{
			set_include_path(_XE_PATH_."libs/PEAR");	
		}
		else
		{
			set_include_path(_XE_PATH_."libs/PEAR.1.9");	

		}
	}

	function htmlHeader()
	{
		echo <<<HTMLHEADER
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="ko" xml:lang="ko" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
HTMLHEADER;
	}

	function htmlFooter()
	{
		echo '</body></html>';
	}

	function alertScript($msg)
	{
		if(!$msg) return;
		echo '<script type="text/javascript">alert("'.$msg.'");</script>';
	}

	function closePopupScript()
	{
		echo '<script type="text/javascript">window.close();</script>';
	}

	function reload($isOpener = false)
	{
		$reloadScript = $isOpener ? 'window.opener.location.reload()' : 'document.location.reload()';

		echo '<script type="text/javascript">'.$reloadScript.'</script>';
	}
