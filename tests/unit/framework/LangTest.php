<?php

class LangTest extends \Codeception\TestCase\Test
{
	public function testLang()
	{
		$ko = Rhymix\Framework\Lang::getInstance('ko');
		$en = Rhymix\Framework\Lang::getInstance('en');
		$this->assertTrue($ko instanceof Rhymix\Framework\Lang);
		$this->assertTrue($en instanceof Rhymix\Framework\Lang);
		$this->assertFalse($ko === $en);
		
		$ja = Rhymix\Framework\Lang::getInstance('ja');
		$jp = Rhymix\Framework\Lang::getInstance('jp');
		$this->assertTrue($ja === $jp);
		
		$this->assertEquals('도움말', $ko->get('common.help'));
		$this->assertEquals('Help', $en->get('common.help'));
		$this->assertEquals('도움말', $ko->help);
		$this->assertEquals('Help', $en->help);
		
		$this->assertEquals('nonexistent', $ko->get('common.nonexistent'));
		$this->assertEquals('nonexistent', $ko->get('common.nonexistent', 'foo', 'bar'));
		
		$this->assertEquals('help', $ja->help);
		$ja->loadPlugin('common');
		$this->assertEquals('ヘルプ', $ja->help);
		
		$ko->foobartestlang = '%s님 안녕하세요?';
		$this->assertEquals('Travis님 안녕하세요?', $ko->foobartestlang('Travis'));
		$en->foobartestlang = 'Hello, %s!';
		$this->assertEquals('Hello, Travis!', $en->get('foobartestlang', 'Travis'));
	}
}
