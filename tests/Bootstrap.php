<?php

if(!defined('__DEBUG__')) define('__DEBUG__', 1);
if(!defined('__XE__')) define('__XE__', true);
if(!defined('__ZBXE__')) define('__ZBXE__', true);
if(!defined('_XE_PATH_')) define('_XE_PATH_', realpath(dirname(__FILE__).'/../').'/');

error_reporting(E_ALL & ~E_NOTICE);

/**
 * Print out the message
 **/
function _log($msg) {
	$args = func_get_args();

	foreach($args as $arg) {
		fwrite(STDOUT, "\n");
		fwrite(STDOUT, print_r($arg, true));
	}
		fwrite(STDOUT, "\n");
}

/* End of file Bootstrap.php */
/* Location: ./tests/Bootstrap.php */
