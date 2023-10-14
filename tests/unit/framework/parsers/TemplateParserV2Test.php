<?php

class TemplateParserV2Test extends \Codeception\TestCase\Test
{
	private $baseurl;

	public function _before()
	{
		$this->baseurl = '/' . basename(dirname(dirname(dirname(dirname(__DIR__))))) . '/';
	}

	public function testParse()
	{

	}
}
