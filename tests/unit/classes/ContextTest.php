<?php

class ContextTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
		// Reinitialization after test
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET = $_POST = $_REQUEST = array();
		unset($GLOBALS['HTTP_RAW_POST_DATA']);
        unset($_SERVER['HTTP_ACCEPT']);
		Context::clearRequestVars();
		Context::clearUserVars();
        Context::setRequestMethod();
		Context::setRequestArguments();
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf('Context', Context::getInstance());
        $this->assertSame(Context::getInstance(), Context::getInstance());
    }

    public function testSetGetVars()
    {
        $this->assertEquals(Context::get('var1'), null);
        Context::getInstance()->context = new stdClass;
        Context::set('var1', 'val1');
        $this->assertEquals(Context::get('var1'), 'val1');

        Context::set('var2', 'val2');
        $this->assertSame(Context::get('var2'), 'val2');

        Context::set('var3', 'val3');
        $data = new stdClass();
        $data->var1 = 'val1';
        $data->var2 = 'val2';
        $this->assertEquals(Context::gets('var1','var2'), $data);
        $data->var3 = 'val3';
        $this->assertEquals('val1', Context::getAll()->var1);
        $this->assertEquals('val2', Context::getAll()->var2);
        $this->assertEquals('val3', Context::getAll()->var3);
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
        Context::addBodyClass('yellow');
        $this->assertEquals(Context::getBodyClassList(), ['red', 'green', 'blue', 'yellow']);
        
        // remove class manually
        Context::removeBodyClass('yellow');
        $this->assertEquals(Context::getBodyClassList(), ['red', 'green', 'blue']);
        
        // remove duplicated class
        Context::addBodyClass('red');
        $this->assertEquals(Context::getBodyClass(), ' class="red green blue"');
        $this->assertEquals(Context::getBodyClassList(), ['red', 'green', 'blue']);
    }

	public function testSetRequestMethod()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET = $_REQUEST = array('foo' => 'bar');
		$_POST = array();
		Context::clearRequestVars();
		Context::clearUserVars();
        Context::setRequestMethod();
		Context::setRequestArguments();
		$this->assertEquals('GET', Context::getRequestMethod());
		$this->assertEquals('bar', Context::getRequestVars()->foo);
		
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET = array('foo' => 'barrr', 'xe_js_callback' => 'callback12345');
		$_POST = array();
		$_REQUEST = array('foo' => 'barrr');
		Context::clearRequestVars();
		Context::clearUserVars();
        Context::setRequestMethod();
		Context::setRequestArguments();
		$this->assertEquals('JS_CALLBACK', Context::getRequestMethod());
		$this->assertEquals('barrr', Context::getRequestVars()->foo);
		
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_GET = $_REQUEST = array('foo' => 'bazz');  // Request method is POST but actual values are given as GET
		$_POST = array();
		Context::clearRequestVars();
		Context::clearUserVars();
        Context::setRequestMethod();
		Context::setRequestArguments();
		$this->assertEquals('POST', Context::getRequestMethod());
		$this->assertNull(Context::getRequestVars()->foo ?? null);
		$this->assertNull(Context::get('foo'));  // This is different from XE behavior
		
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_GET = array();
		$_POST = $_REQUEST = array('foo' => 'rhymixtest');
		Context::clearRequestVars();
		Context::clearUserVars();
        Context::setRequestMethod();
		Context::setRequestArguments();
		$this->assertEquals('POST', Context::getRequestMethod());
		$this->assertEquals('rhymixtest', Context::getRequestVars()->foo);
		
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_GET = $_POST = $_REQUEST = array();
		$GLOBALS['HTTP_RAW_POST_DATA'] = '<?xml version="1.0" encoding="utf-8" ?><methodCall><params><foo>TestRhymix</foo></params></methodCall>';
		Context::clearRequestVars();
		Context::clearUserVars();
        Context::setRequestMethod();
		Context::setRequestArguments();
		$this->assertEquals('XMLRPC', Context::getRequestMethod());
		$this->assertEquals('TestRhymix', Context::getRequestVars()->foo);
		
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_GET = $_POST = $_REQUEST = array();
		$GLOBALS['HTTP_RAW_POST_DATA'] = 'foo=JSON_TEST';  // Not actual JSON
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
		Context::clearRequestVars();
		Context::clearUserVars();
        Context::setRequestMethod();
		Context::setRequestArguments();
		$this->assertEquals('JSON', Context::getRequestMethod());
		$this->assertEquals('JSON_TEST', Context::getRequestVars()->foo);
		
        Context::setRequestMethod('POST');
		$_GET = $_POST = $_REQUEST = array();
		unset($GLOBALS['HTTP_RAW_POST_DATA']);
        unset($_SERVER['HTTP_ACCEPT']);
		Context::clearRequestVars();
		Context::clearUserVars();
        Context::setRequestMethod();
		Context::setRequestArguments();
        $this->assertEquals('POST', Context::getRequestMethod());
		
        Context::setRequestMethod('POST');
		$_GET = array();
		$_POST = $_REQUEST = array('foo' => 'legacy', '_rx_ajax_compat' => 'XMLRPC');
		$GLOBALS['HTTP_RAW_POST_DATA'] = http_build_query($_POST);
		$_SERVER['HTTP_ACCEPT'] = 'application/json';
		Context::clearRequestVars();
		Context::clearUserVars();
        Context::setRequestMethod();
		Context::setRequestArguments();
        $this->assertEquals('XMLRPC', Context::getRequestMethod());
		$this->assertEquals('legacy', Context::getRequestVars()->foo);
	}
	
    public function testSetResponseMethod()
    {
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

    public function testBlacklistedPlugin()
    {
        $this->assertTrue(Context::isBlacklistedPlugin('autolang'));
		$this->assertFalse(Context::isBlacklistedPlugin('document'));
	}

    public function testReservedWord()
    {
        $this->assertTrue(Context::isReservedWord('mid'));
		$this->assertFalse(Context::isReservedWord('foo'));
	}
}
