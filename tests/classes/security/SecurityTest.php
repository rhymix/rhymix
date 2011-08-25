<?php

define('__DEBUG__', 1);
$xe_path = realpath(dirname(__FILE__).'/../../../');
require "{$xe_path}/classes/security/Security.class.php";

error_reporting(E_ALL & ~E_NOTICE);

class SecurityTest extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		/**
		 * Setup mock data
		 **/

		// string
		Context::set('content1', '<strong>Hello, world</strong>');
		Context::set('content2', 'Wow, >_< !');

		// object
		$args = new stdClass;
		$args->prop1 = 'Normal string';
		$args->prop2 = 'He said, "Very nice!"';
		$args->prop3 = '<strong>Strong</strong> Baby';
		Context::set('object1', $args);

		// array
		$arr = array();
		$arr[] = '<span class="first">F</span>irst';
		$arr[] = '<u>S</u>econd';
		$arr[] = '<b>T</b>hird';
		Context::set('array1', $arr);

		// associative array
		$aarr = array();
		$aarr['elem1'] = 'One <ins>1</ins>';
		$aarr['elem2'] = 'Two <del>2</del>';
		$aarr['elem3'] = 'Three <addr>3</addr>';
		Context::set('array2', $aarr);
	}

	public function testEncodeHTML_DefaultContext()
	{
		$security  = new Security();

		// normal string - one
		$this->setUp();
		$this->assertEquals('<strong>Hello, world</strong>', Context::get('content1'));
		$security->encodeHTML('content1');
		$this->assertEquals('&lt;strong&gt;Hello, world&lt;/strong&gt;', Context::get('content1'));

		// normal string - two
		$this->setUp();
		$this->assertEquals('<strong>Hello, world</strong>', Context::get('content1'));
		$this->assertEquals('Wow, >_< !', Context::get('content2'));
		$security->encodeHTML('content1','content2');
		$this->assertEquals('&lt;strong&gt;Hello, world&lt;/strong&gt;', Context::get('content1'));
		$this->assertEquals('Wow, &gt;_&lt; !', Context::get('content2'));

		// array
		$this->assertEquals(Context::get('array1'), array('<span class="first">F</span>irst','<u>S</u>econd','<b>T</b>hird'));
		$security->encodeHTML('array1'); // should ignore this
		$this->assertEquals(Context::get('array1'), array('<span class="first">F</span>irst','<u>S</u>econd','<b>T</b>hird'));
		$security->encodeHTML('array1.0'); // affect only first element
		$this->assertEquals(Context::get('array1'), array('&lt;span class=&quot;first&quot;&gt;F&lt;/span&gt;irst','<u>S</u>econd','<b>T</b>hird'));
		$security->encodeHTML('array1.2'); // affects only third element
		$this->assertEquals(Context::get('array1'), array('&lt;span class=&quot;first&quot;&gt;F&lt;/span&gt;irst','<u>S</u>econd','&lt;b&gt;T&lt;/b&gt;hird'));
		$this->setUp(); // reset;
		$this->assertEquals(Context::get('array1'), array('<span class="first">F</span>irst','<u>S</u>econd','<b>T</b>hird'));
		$security->encodeHTML('array1.'); // affects all items
		$this->assertEquals(Context::get('array1'), array('&lt;span class=&quot;first&quot;&gt;F&lt;/span&gt;irst','&lt;u&gt;S&lt;/u&gt;econd','&lt;b&gt;T&lt;/b&gt;hird'));

		// associated array
		$this->assertEquals(Context::get('array2'), array('elem1'=>'One <ins>1</ins>','elem2'=>'Two <del>2</del>','elem3'=>'Three <addr>3</addr>'));
		$security->encodeHTML('array2'); // should ignore this
		$this->assertEquals(Context::get('array2'), array('elem1'=>'One <ins>1</ins>','elem2'=>'Two <del>2</del>','elem3'=>'Three <addr>3</addr>'));
		$security->encodeHTML('array2.0'); // should ignore this
		$this->assertEquals(Context::get('array2'), array('elem1'=>'One <ins>1</ins>','elem2'=>'Two <del>2</del>','elem3'=>'Three <addr>3</addr>'));
		$security->encodeHTML('array2.elem2'); // affects only 'elem2'
		$this->assertEquals(Context::get('array2'), array('elem1'=>'One <ins>1</ins>','elem2'=>'Two &lt;del&gt;2&lt;/del&gt;','elem3'=>'Three <addr>3</addr>'));
		$this->setUp(); // reset;
		$this->assertEquals(Context::get('array2'), array('elem1'=>'One <ins>1</ins>','elem2'=>'Two <del>2</del>','elem3'=>'Three <addr>3</addr>'));
		$security->encodeHTML('array2.'); // affects all items
		$this->assertEquals(Context::get('array2'), array('elem1'=>'One &lt;ins&gt;1&lt;/ins&gt;','elem2'=>'Two &lt;del&gt;2&lt;/del&gt;','elem3'=>'Three &lt;addr&gt;3&lt;/addr&gt;'));

		// object
		$obj = new stdClass;
		$obj->prop1 = 'Normal string';
		$obj->prop2 = 'He said, "Very nice!"';
		$obj->prop3 = '<strong>Strong</strong> Baby';
		$this->assertEquals(Context::get('object1'), $obj);
		$security->encodeHTML('object1'); // should ignore this
		$this->assertEquals(Context::get('object1'), $obj);
		$security->encodeHTML('object1.0'); // should ignore this
		$this->assertEquals(Context::get('object1'), $obj);
		$security->encodeHTML('object1.prop1'); // affects only 'prop1' property - no changes
		$this->assertEquals(Context::get('object1'), $obj);
		$security->encodeHTML('object1.prop3'); // affects only 'prop3' property
		$obj->prop3 = '&lt;strong&gt;Strong&lt;/strong&gt; Baby';
		$this->assertEquals(Context::get('object1'), $obj);
		$this->setUp(); // reset
		$obj->prop3 = '<strong>Strong</strong> Baby';
		$this->assertEquals(Context::get('object1'), $obj);
		$security->encodeHTML('object1.'); // affects all properties
		$obj->prop2 = 'He said, &quot;Very nice!&quot;';
		$obj->prop3 = '&lt;strong&gt;Strong&lt;/strong&gt; Baby';
		$this->assertEquals(Context::get('object1'), $obj);
	}

	public function testEncodeHTML_CustomContext()
	{
		$array = array('Hello', 'World', '<b>Bold</b> is not bald');

		// array with no nested objects or arrays
		$security = new Security($array);
		$returned = $security->encodeHTML('.');
		$this->assertEquals($returned, array('Hello', 'World', '&lt;b&gt;Bold&lt;/b&gt; is not bald'));
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

	public function get($name) {
		global $mock_vars;
		return array_key_exists($name, $mock_vars)?$mock_vars[$name]:'';
	}

	public function set($name, $value) {
		global $mock_vars;

		$mock_vars[$name] = $value;
	}

}
