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
		$this->assertInstanceOf('Context', Context::getInstance());
		$this->assertSame(Context::getInstance(), Context::getInstance());
	}

	public function testSetGetVars()
	{
		$this->assertSame(Context::get('var1'), null);
		Context::set('var1', 'val1');
		$this->assertSame(Context::get('var1'), 'val1');

		Context::set('var2', 'val2');
		$this->assertSame(Context::get('var2'), 'val2');
		Context::set('var3', 'val3');
		$data = new stdClass;
		$data->var1 = 'val1';
		$data->var2 = 'val2';
		$this->assertEquals(Context::gets('var1','var2'), $data);
		$data->var3 = 'val3';
		$this->assertEquals(Context::getAll(), $data);
	}

	public function testAddGetBodyClass()
	{
		$this->assertEquals(Context::getBodyClass(), '');
		Context::addBodyClass('red');
		$this->assertEquals(Context::getBodyClass(), ' class="red"');
		Context::addBodyClass('green');
		$this->assertEquals(Context::getBodyClass(), ' class="red green"');
		Context::addBodyClass('blue');
		$this->assertEquals(Context::getBodyClass(), ' class="red green blue"');

		// remove duplicated class
		Context::addBodyClass('red');
		$this->assertEquals(Context::getBodyClass(), ' class="red green blue"');
	}

	public function testRequsetResponseMethod()
	{
		$this->assertEquals(Context::getRequestMethod(), 'GET');

		$_SERVER['REQUEST_METHOD'] = 'POST';
		Context::setRequestMethod();
		$this->assertEquals(Context::getRequestMethod(), 'POST');

		$GLOBALS['HTTP_RAW_POST_DATA'] = 'abcde';
		Context::setRequestMethod();
		$this->assertEquals(Context::getRequestMethod(), 'XMLRPC');

		$_SERVER['CONTENT_TYPE'] = 'application/json';
		Context::setRequestMethod();
		$this->assertEquals(Context::getRequestMethod(), 'JSON');

		Context::setRequestMethod('POST');
		$this->assertEquals(Context::getRequestMethod(), 'POST');

		$this->assertEquals(Context::getResponseMethod(), 'HTML');
		Context::setRequestMethod('JSON');
		$this->assertEquals(Context::getResponseMethod(), 'JSON');

		Context::setResponseMethod('WRONG_TYPE');
		$this->assertEquals(Context::getResponseMethod(), 'HTML');
		Context::setResponseMethod('XMLRPC');
		$this->assertEquals(Context::getResponseMethod(), 'XMLRPC');
		Context::setResponseMethod('HTML');
		$this->assertEquals(Context::getResponseMethod(), 'HTML');
	}
}

/* End of file ContextTest.php */
/* Location: ./tests/classes/context/ContextTest.php */
