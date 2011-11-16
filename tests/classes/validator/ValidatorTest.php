<?php

define('__DEBUG__', 1);
$xe_path = realpath(dirname(__FILE__).'/../../../');
require_once "{$xe_path}/classes/xml/XmlParser.class.php";
require_once "{$xe_path}/classes/handler/Handler.class.php";
require_once "{$xe_path}/classes/file/FileHandler.class.php";
require_once "{$xe_path}/classes/validator/Validator.class.php";

error_reporting(E_ALL & ~E_NOTICE);

class ValidatorTest extends PHPUnit_Framework_TestCase
{
	public function _testRequired() {
		$vd = new Validator();
		$vd->addFilter('userid', array('required'=>'true'));

		// given data
		$this->assertFalse( $vd->validate(array('no-userid'=>'hello')) );
		$this->assertTrue( $vd->validate(array('userid'=>'myuserid')) );
		$this->assertFalse( $vd->validate(array('userid'=>'')) );

		// context data
		$this->assertFalse( $vd->validate() );
		Context::set('userid', '');
		$this->assertFalse( $vd->validate() );
		Context::set('userid', 'myuserid');
		$this->assertTrue( $vd->validate() );
		$vd->removeFilter('userid');
		$this->assertTrue( $vd->validate() );
	}

	public function testNamePattern() {
		$vd = new Validator();
		$vd->addFilter('^user_', array('length'=>'5:'));

		Context::set('user_123', 'abcd');
		Context::set('user_456', '123');
		$this->assertFalse( $vd->validate() );

		Context::set('user_123', 'abcdefg');
		$this->assertFalse( $vd->validate() );

		Context::set('user_456', '123456');
		$this->assertTrue( $vd->validate() );
	}

	public function testDefault() {
		global $mock_vars;

		$vd = new Validator();
		$vd->addFilter('userid', array('default'=>'ididid'));

		// given data
		$arr = array('no-userid'=>'');
		$vd->validate($arr);
		$this->assertEquals( $arr, array('no-userid'=>'') );
		
		$arr = array('userid'=>'');
		$vd->validate(&$arr); // pass-by-reference
		$this->assertEquals( $arr, array('userid'=>'ididid') );

		$arr = array('userid'=>'ownid');
 		$vd->validate(&$arr);
 		$this->assertEquals( $arr, array('userid'=>'ownid') );

		// context data
		$mock_vars = array(); // empty context variables
		$vd->validate();
		$this->assertEquals( 'ididid', Context::get('userid') );

		$vd->load(dirname(__FILE__).'/login.xml');

		Context::set('userid', '');
		$vd->validate();
		$this->assertEquals( 'idididid', Context::get('userid') );
	}

	public function testLength() {
		$vd = new Validator();

		$vd->addFilter('field1', array('length'=>'3:'));
		$this->assertFalse( $vd->validate(array('field1'=>'ab')) );
		$this->assertTrue( $vd->validate(array('field1'=>'abc')) );
		$this->assertTrue( $vd->validate(array('field1'=>'abcd')) );
	}

	public function testCustomRule() {
	}

	public function testJSCompile() {
		$vd = new Validator();
		$vd->setCacheDir(dirname(__FILE__));
	}

	public function testCondition() {
		$vd = new Validator();
		$data = array('greeting1'=>'hello');

		// No condition
		$vd->addFilter('greeting1', array('required'=>'true'));
		$this->assertTrue($vd->validate($data));

		// Now greeting2 being mandatory if greeting1 is 'Hello'
		$vd->addFilter('greeting2', array('if'=>array('test'=>'$greeting1 == "Hello"', 'attr'=>'required', 'value'=>'true')));

		// Because greeting1 is 'hello', including lowercase 'h', greeting2 isn't required yet.
		$this->assertTrue($vd->validate($data));

		// Change the value of greeting1. Greeting2 is required now
		$data['greeting1'] = 'Hello';
		$this->assertFalse($vd->validate($data));

		$data['greeting2'] = 'World';
		$this->assertTrue($vd->validate($data));
	}

	public function testConditionXml() {
		$vd = new Validator(dirname(__FILE__).'/condition.xml');
		$data = array('greeting1'=>'hello');

		$this->assertTrue($vd->validate($data));

		// Change the value of greeting1. Greeting2 is required now
		$data['greeting1'] = 'Hello';
		$this->assertFalse($vd->validate($data));

		$data['greeting2'] = 'World';
		$this->assertTrue($vd->validate($data));
	}
}

$mock_vars = array();

class Context
{
	public function gets() {
		global $mock_vars;

		$args = func_get_args();
		$output = new stdClass;

		foreach($args as $name) {
			$output->{$name} = $mock_vars[$name];
		}

		return $output;
	}

	public function getRequestVars() {
		global $mock_vars;

		return $mock_vars;
	}

	public function get($name) {
		global $mock_vars;
		return array_key_exists($name, $mock_vars)?$mock_vars[$name]:'';
	}

	public function set($name, $value) {
		global $mock_vars;

		$mock_vars[$name] = $value;
	}

	public function getLangType() {
		return 'en';
	}
	public function getLang($str) {
		return $str;
	}
}
