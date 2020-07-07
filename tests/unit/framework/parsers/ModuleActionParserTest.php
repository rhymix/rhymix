<?php

class ModuleActionParserTest extends \Codeception\TestCase\Test
{
	public function testLoadXML()
	{
		$info = Rhymix\Framework\Parsers\ModuleActionParser::loadXML(\RX_BASEDIR . 'tests/_data/module/module.xml');
		$this->assertTrue(is_object($info));
	}
}
