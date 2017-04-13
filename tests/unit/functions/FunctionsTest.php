<?php

class FunctionsTest extends \Codeception\TestCase\Test
{
	public function testArrayFunctions()
	{
		$array = array('foo' => 'xe', 'bar' => 'rhymix', 'key' => array('value1', 'value2', array('bar' => 'value3')), 'last' => 'bears');
		$flattened1 = array('foo' => 'xe', 'bar' => 'value3', 0 => 'value1', 1 => 'value2', 'last' => 'bears');
		$flattened2 = array(0 => 'xe', 1 => 'rhymix', 2 => 'value1', 3 => 'value2', 4 => 'value3', 5 => 'bears');
		
		$this->assertEquals('foo', array_first_key($array));
		$this->assertEquals('xe', array_first($array));
		
		$this->assertEquals('last', array_last_key($array));
		$this->assertEquals('bears', array_last($array));
		
		$this->assertEquals($flattened1, array_flatten($array));
		$this->assertEquals($flattened2, array_flatten($array, false));
	}
	
	public function testArrayEscape()
	{
		$this->assertEquals(array('foo&lt;' => 'bar&gt;', 'baz&quot;baz' => array('fuzz&amp;amp;bazz' => '&lt;rhymix&gt;')), array_escape(array('foo<' => 'bar>', 'baz"baz' => array('fuzz&amp;bazz' => '<rhymix>'))));
		$this->assertEquals(array('invalid' => 'unicode' . "\xEF\xBF\xBD", 'other' => array('key&amp;key')), array_escape(array('invalid' => 'unicode' . "\xE4\xA8", 'other' => array('key&amp;key')), false));
		$this->assertEquals(array('object' => (object)array('foo&gt;' => 'bar&lt;', 'baz&quot;' => '&amp;amp;')), array_escape(array('object' => (object)array('foo>' => 'bar<', 'baz"' => '&amp;'))));
		$this->assertEquals(array('object' => (object)array('foo&gt;' => array('bar&lt;' => array('&amp;')))), array_escape(array('object' => (object)array('foo>' => array('bar<' => array('&amp;')))), false));
	}
	
	public function testClassBasename()
	{
		$this->assertEquals('FunctionsTest', class_basename($this));
		$this->assertEquals('FunctionsTest', class_basename(get_class($this)));
	}
	
	public function testEscapeFunctions()
	{
		$this->assertEquals('&lt;foo&gt;&amp;amp;&lt;/foo&gt;', escape('<foo>&amp;</foo>'));
		$this->assertEquals('&lt;foo&gt;&amp;&lt;/foo&gt;', escape('<foo>&amp;</foo>', false));
		$this->assertEquals('&lt;foo&gt;invalid'. "\xEF\xBF\xBD" . 'unicode&lt;/foo&gt;', escape('<foo>invalid' . "\xE4\xA8" . 'unicode</foo>'));
		$this->assertEquals('&lt;foo&gt;invalid'. "\xEF\xBF\xBD" . 'unicode&lt;/foo&gt;', escape('<foo>invalid' . "\xE4\xA8" . 'unicode&lt;/foo&gt;', false));
		
		$this->assertEquals('expressionalertXSS', escape_css('expression:alert("XSS")'));
		$this->assertEquals('#123456', escape_css('#123456'));
		
		$this->assertEquals('hello\\\\world', escape_js('hello\\world'));
		$this->assertEquals('\u003Cbr \/\u003E', escape_js('<br />'));
		
		$this->assertEquals('hello\\\\world', escape_sqstr('hello\\world'));
		$this->assertEquals('hello"world\\\'quotes', escape_sqstr('hello"world\'quotes'));
		
		$this->assertEquals('hello\\\\\\$world in \\"quotes\\"', escape_dqstr('hello\\$world in "quotes"'));
		$this->assertEquals('\\${array[\'key\']}', escape_dqstr('${array[\'key\']}'));
	}
	
	public function testExplodeWithEscape()
	{
		$this->assertEquals(array('foo', 'bar'), explode_with_escape(',', 'foo,bar'));
		$this->assertEquals(array('foo', 'bar'), explode_with_escape(',', 'foo , bar'));
		$this->assertEquals(array('foo', 'bar', 'baz,rhymix'), explode_with_escape(',', 'foo,bar,baz,rhymix', 3));
		$this->assertEquals(array('foo', 'bar', 'baz , rhymix'), explode_with_escape(',', 'foo,bar,baz , rhymix', 3));
		
		$this->assertEquals(array('foo', 'bar,baz'), explode_with_escape(',', 'foo,bar\\,baz'));
		$this->assertEquals(array('foo', 'bar\\', 'baz'), explode_with_escape(',', 'foo,bar\\ , baz'));
		$this->assertEquals(array('foo', 'bar,baz', 'rhymix'), explode_with_escape(',', 'foo,bar\\,baz,rhymix'));
		$this->assertEquals(array('foo', 'bar,baz'), explode_with_escape(',', 'foo,bar!,baz', null, '!'));
	}
	
	public function testStartsEndsContains()
	{
		$this->assertTrue(starts_with('foo', 'foobar'));
		$this->assertFalse(starts_with('FOO', 'foobar'));
		$this->assertTrue(starts_with('FOO', 'foobar', false));
		$this->assertFalse(starts_with('bar', 'foobar'));
		
		$this->assertTrue(ends_with('bar', 'foobar'));
		$this->assertFalse(ends_with('BAR', 'foobar'));
		$this->assertTrue(ends_with('BAR', 'foobar', false));
		$this->assertFalse(ends_with('foo', 'foobar'));
		
		$this->assertTrue(contains('foo', 'foo bar baz rhymix rocks'));
		$this->assertFalse(contains('barbaz', 'foo bar baz rhymix rocks'));
		$this->assertTrue(contains('RHYMIX', 'foo bar baz rhymix rocks', false));
		$this->assertFalse(contains('ROCKS', 'foo bar baz rhymix rocks'));
	}
	
	public function testRangeFunctions()
	{
		$this->assertTrue(is_between(5, 1, 10));
		$this->assertTrue(is_between(1, 1, 10));
		$this->assertTrue(is_between(10, 1, 10));
		$this->assertTrue(is_between(7, 1, 10, true));
		$this->assertFalse(is_between(1, 1, 10, true));
		$this->assertFalse(is_between(10, 1, 10, true));
		
		$this->assertEquals(10, force_range(14, 1, 10));
		$this->assertEquals(3, force_range(3, 1, 10));
		$this->assertEquals(1, force_range(-4, 1, 10));
	}
	
	public function testUrlSafeBase64()
	{
		$this->assertEquals('Umh5bWl4IF5-', base64_encode_urlsafe('Rhymix ^~'));
		$this->assertEquals('Rhymix ^~', base64_decode_urlsafe('Umh5bWl4IF5-'));
	}
	
	public function testHex2Rgb2Hex()
	{
		$this->assertEquals(array(128, 128, 128), hex2rgb('808080'));
		$this->assertEquals(array(60, 71, 244), hex2rgb('#3c47f4'));
		$this->assertEquals(array(119, 119, 119), hex2rgb('#777'));
		$this->assertEquals(array(51, 102, 153), hex2rgb('369'));
		
		$this->assertEquals('#808080', rgb2hex(array(128, 128, 128)));
		$this->assertEquals('#3c47f4', rgb2hex(array(60, 71, 244)));
		$this->assertEquals('777777', rgb2hex(array(119, 119, 119), false));
		$this->assertEquals('#000000', rgb2hex(array()));
	}
	
	public function testToBool()
	{
		$this->assertTrue(tobool('Y'));
		$this->assertTrue(tobool('yes'));
		$this->assertTrue(tobool('on'));
		$this->assertTrue(tobool('ok'));
		$this->assertTrue(tobool('okay'));
		$this->assertTrue(tobool('oui'));
		$this->assertTrue(tobool('si'));
		$this->assertTrue(tobool('vrai'));
		$this->assertTrue(tobool('true'));
		$this->assertTrue(tobool(1));
		$this->assertTrue(tobool(-1));
		$this->assertTrue(tobool(true));
		$this->assertTrue(tobool(array(1, 2, 3)));
		$this->assertTrue(tobool(new stdClass));
		
		$this->assertFalse(tobool('N'));
		$this->assertFalse(tobool('no'));
		$this->assertFalse(tobool('none'));
		$this->assertFalse(tobool('Nobody'));
		$this->assertFalse(tobool('false'));
		$this->assertFalse(tobool('off'));
		$this->assertFalse(tobool('OUT'));
		$this->assertFalse(tobool('Fuck you!'));
		$this->assertFalse(tobool(0));
		$this->assertFalse(tobool(0.00000000));
		$this->assertFalse(tobool(''));
		$this->assertFalse(tobool(false));
		$this->assertFalse(tobool(null));
		$this->assertFalse(tobool(array()));
	}
	
	public function testUTF8Functions()
	{
		$this->assertTrue(utf8_check('Hello, world!'));
		$this->assertTrue(utf8_check('라이믹스'));
		$this->assertTrue(utf8_check(''));
		$this->assertTrue(utf8_check(iconv('UTF-8', 'EUC-KR', 'One CMS to rule them all...')));
		$this->assertFalse(utf8_check(iconv('UTF-8', 'EUC-KR', '라이믹스')));
		$this->assertFalse(utf8_check(chr(129) . chr(214) . chr(181) . chr(73) . chr(97)));
		
		$this->assertEquals('Emoticon: &#x1f601;', utf8_mbencode("Emoticon: \xf0\x9f\x98\x81"));
		$this->assertEquals('Emoticon: &#x1f61c;', utf8_mbencode("Emoticon: \xf0\x9f\x98\x9c"));
		$this->assertEquals('한글은 인코딩하지 않음', utf8_mbencode('한글은 인코딩하지 않음'));
		
		$this->assertEquals("Weird spaces are in this string", utf8_normalize_spaces("Weird\x20spaces\xe2\x80\x80are\xe2\x80\x84in\xe2\x80\x86\xe2\x80\x8bthis\x0astring"));
		$this->assertEquals("Weird spaces are in this\nstring", utf8_normalize_spaces("Weird\x20spaces\xe2\x80\x80are\xe2\x80\x84in\xe2\x80\x86\xe2\x80\x8bthis\x0astring", true));
		$this->assertEquals("Trimmed", utf8_trim("\x20\xe2\x80\x80Trimmed\xe2\x80\x84\xe2\x80\x86\xe2\x80\x8b"));
		$this->assertEquals("Trimmed", utf8_trim("\x20\xe2\x80\x80Trimmed\x0a\x0c\x07\x09"));
	}
	
	public function testIsEmptyHTMLContent()
	{
		$this->assertTrue(is_empty_html_content('<p>&nbsp;<br><br></p>'));
		$this->assertTrue(is_empty_html_content('<p>&nbsp;</p>' . "\n\n" . '<p><span> </span></p>'));
		$this->assertTrue(is_empty_html_content('<p>&#8194; &#8203; &#8205;</p>'));
		$this->assertFalse(is_empty_html_content('<p>&nbsp;</p>' . "\n\n" . '<p>Hello world</p>'));
		$this->assertFalse(is_empty_html_content('<p><img src="foobar.jpg"></p>'));
		$this->assertFalse(is_empty_html_content('<p><iframe src="http://www.youtube.com/" /></p>'));
		$this->assertFalse(is_empty_html_content('<p><video src="rickroll.webm" /></p>'));
		$this->assertFalse(is_empty_html_content('<p><object></object></p>'));
	}
}
