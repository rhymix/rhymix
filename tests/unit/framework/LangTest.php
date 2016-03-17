<?php

class LangTest extends \Codeception\TestCase\Test
{
	public function testLang()
	{
		// Test separation of languages.
		$ko = Rhymix\Framework\Lang::getInstance('ko');
		$en = Rhymix\Framework\Lang::getInstance('en');
		$this->assertTrue($ko instanceof Rhymix\Framework\Lang);
		$this->assertTrue($en instanceof Rhymix\Framework\Lang);
		$this->assertFalse($ko === $en);
		
		// Test backward compatible language code for Japanese.
		$ja = Rhymix\Framework\Lang::getInstance('ja');
		$jp = Rhymix\Framework\Lang::getInstance('jp');
		$this->assertTrue($ja === $jp);
		
		// Test loading new plugins.
		$this->assertNotEquals('ヘルプ', $ja->help);
		$ja->loadPlugin('common');
		$this->assertEquals('ヘルプ', $ja->help);
		
		// Test simple translations with namespacing.
		$this->assertEquals('도움말', $ko->get('common.help'));
		$this->assertEquals('Help', $en->get('common.help'));
		
		// Test simple translations without namespacing.
		$this->assertEquals('도움말', $ko->help);
		$this->assertEquals('Help', $en->help);
		
		// Test complex translations with multidimensional arrays.
		$this->assertEquals('%d분 전', $ko->get('common.time_gap.min'));
		$this->assertEquals('10분 전', $ko->get('common.time_gap.min', 10));
		$this->assertTrue($ko->get('common.time_gap') instanceof \ArrayObject);
		$this->assertEquals('%d분 전', $ko->get('common.time_gap')->min);
		
		// Test nonexistent keys.
		$this->assertEquals('common.nonexistent', $ko->get('common.nonexistent'));
		$this->assertEquals('common.nonexistent', $ko->get('common.nonexistent', 'foo', 'bar'));
		$this->assertEquals('admin.help', $ko->get('admin.help'));
		$this->assertEquals('admin.help', $en->get('admin.help'));
		
		// Test fallback to English.
		$en->only_in_english = 'Hello world';
		$this->assertEquals('Hello world', $ko->only_in_english);
		$this->assertEquals('Hello world', $en->only_in_english);
		$this->assertEquals('Hello world', $ja->only_in_english);
		
		// Test string interpolation.
		$ko->foobartestlang = '%s님 안녕하세요?';
		$this->assertEquals('Travis님 안녕하세요?', $ko->foobartestlang('Travis'));
		$en->foobartestlang = 'Hello, %s!';
		$this->assertEquals('Hello, Travis!', $en->get('foobartestlang', 'Travis'));
	}
}
