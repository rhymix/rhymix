<?php
use Codeception\Util\Debug;

if(!defined('__XE__')) define('__XE__', TRUE);
if(!defined('_XE_PATH_')) define('_XE_PATH_', realpath(dirname(__FILE__).'/../').'/');
$_SERVER['SCRIPT_NAME'] = '/xe/index.php';

error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING ^ E_STRICT);


function _log() {
	$args = func_get_args();
	$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);

	if(is_array($bt))
	{
		$bt_debug_print = array_shift($bt);
		$bt_called_function = array_shift($bt);
	}
	$file_name = str_replace(_XE_PATH_, '', $bt_debug_print['file']);
	$line_num = $bt_debug_print['line'];
	$function = $bt_called_function['class'] . $bt_called_function['type'] . $bt_called_function['function'];

	$print = sprintf("%s() [%s:%d]", $function, $file_name, $line_num);

	Debug::debug("\n" . $print);
	foreach($args as $arg) {
		Debug::debug('(' . gettype($arg) . ') ' . var_export($arg, true));
	}
}
