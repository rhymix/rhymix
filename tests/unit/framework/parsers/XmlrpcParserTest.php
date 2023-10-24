<?php

class XmlrpcParserTest extends \Codeception\Test\Unit
{
	public function testParse()
	{
		$xml = file_get_contents(\RX_BASEDIR . 'tests/_data/xmlrpc/request.xml');
		$params = Rhymix\Framework\Parsers\XMLRPCParser::parse($xml);
		$this->assertTrue(is_array($params));
		$this->assertEquals('board', $params['module']);
		$this->assertEquals('procBoardInsertDocument', $params['act']);
		$this->assertEquals('제목', $params['title']);
		$this->assertEquals('<p>내용</p>' . "\n\t\t\t" . '<p>내용</p>' . "\n\t\t", $params['content']);
		$this->assertTrue(is_array($params['foobar']));
		$this->assertEquals('customvalue1', $params['foobar']['subkey1']);
		$this->assertEquals('customvalue2', $params['foobar']['subkey2']);
		$this->assertTrue(is_array($params['foobar']['subkey3']));
		$this->assertEquals('look here', $params['foobar']['subkey3']['subsubkey']);
	}
}
