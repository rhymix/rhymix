<?php
// This is global bootstrap for autoloading
if(!defined('__XE__')) define('__XE__', true);
if(!defined('_XE_PATH_')) define('_XE_PATH_', realpath(dirname(__FILE__).'/../').'/');
require_once _XE_PATH_.'config/config.inc.php';

function _debug() {
	$args = func_get_args();
	$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);

	if(is_array($bt))
	{
		$bt_debug_print = array_shift($bt);
		$bt_called_function = array_shift($bt);
	}
	$file_name = str_replace(_XE_PATH_, '', $bt_debug_print['file']);
	$line_num = $bt_debug_print['line'];
	if($bt_called_function) $function = $bt_called_function['class'] . $bt_called_function['type'] . $bt_called_function['function'];

	$print = sprintf("%s() [%s:%d]", $function, $file_name, $line_num);

	codecept_debug("\n" . $print);
	foreach($args as $arg) {
		codecept_debug('(' . gettype($arg) . ') ' . var_export($arg, true));
	}
}
