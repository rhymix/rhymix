<?php

define('__DEBUG__', 1);
require dirname(__FILE__).'/../../../classes/xml/XmlParser.class.php';
require dirname(__FILE__).'/../../../classes/handler/Handler.class.php';
require dirname(__FILE__).'/../../../classes/file/FileHandler.class.php';
require dirname(__FILE__).'/../../../classes/validator/Validator.class.php';

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

	public function testCustomRule() {
	}

	public function testJSCompile() {
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
			if(array_key_exists($name, $mock_vars)) $output->{$name} = $mock_vars[$name];
		}

		return $output;
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
}
