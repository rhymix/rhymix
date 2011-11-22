<?php

if(!defined('__XE__')) require dirname(__FILE__).'/../../Bootstrap.php';

require_once _XE_PATH_.'classes/context/Context.class.php'; 
require_once _XE_PATH_.'classes/handler/Handler.class.php';
require_once _XE_PATH_.'classes/frontendfile/FrontEndFileHandler.class.php';

class ContextTest extends PHPUnit_Framework_TestCase
{
	/**
	 * test whether the singleton works
	 */
	public function testGetInstance()
	{
	}
}

/* End of file ContextTest.php */
/* Location: ./tests/classes/context/ContextTest.php */
