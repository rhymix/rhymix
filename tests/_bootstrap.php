<?php

// Set some superglobal variables for unit tests.
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'www.rhymix.org';
$_SERVER['SERVER_NAME'] = 'www.rhymix.org';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(__DIR__));
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/' . basename(dirname(__DIR__)) . '/index.php';
$_SERVER['REQUEST_URI'] = '/' . basename(dirname(__DIR__)) . '/index.php';

// Include the autoloader.
require_once dirname(__DIR__) . '/common/autoload.php';
Context::init();

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
