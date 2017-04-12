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
