<?php

class XeXmlParserTest extends \Codeception\Test\Unit
{
	public function testParse()
	{
		$xml = file_get_contents(\RX_BASEDIR . 'tests/_data/xml/xecompat.xml');
		$output = Rhymix\Framework\Parsers\XEXMLParser::loadXMLString($xml, 'en');

		$this->assertEquals('Default Layout', $output->layout->title->body);
		$this->assertEquals('Rhymix', $output->layout->author->name->body);
		$this->assertEquals('https://rhymix.org/', $output->layout->author->attrs->link);
		$this->assertEquals('en', $output->layout->author->name->attrs->{'xml:lang'});

		$this->assertEquals('logo_image', $output->layout->extra_vars->var[0]->attrs->name);
		$this->assertEquals('web_font', $output->layout->extra_vars->var[1]->attrs->name);
		$this->assertEquals('Noto Sans', $output->layout->extra_vars->var[1]->options[0]->attrs->value);
		$this->assertEquals('Pretendard', $output->layout->extra_vars->var[1]->options[1]->title->body);

		$output = Rhymix\Framework\Parsers\XEXMLParser::loadXMLString($xml, 'ko');

		$this->assertEquals('기본 레이아웃', $output->layout->title->body);
		$this->assertEquals('라이믹스', $output->layout->author->name->body);
		$this->assertEquals('웹 폰트', $output->layout->extra_vars->var[1]->title->body);
		$this->assertEquals('Noto Sans', $output->layout->extra_vars->var[1]->options[0]->attrs->value);

		$this->assertInstanceOf(\Rhymix\Framework\Parsers\XEXMLParser::class, $output);
		$this->assertNull($output->layout->attrs->foo);
	}
}
