<?php

class ModuleInfoParserTest extends \Codeception\Test\Unit
{
	public function testLoadXML()
	{
		// Basic info
		Context::setLangType('ko');
		$info = Rhymix\Framework\Parsers\ModuleInfoParser::loadXML(\RX_BASEDIR . 'tests/_data/module/info.xml');
		$this->assertTrue(is_object($info));
		$this->assertEquals('테스트 모듈', $info->title);
		$this->assertEquals('유닛 테스트용 모듈입니다.', $info->description);
		$this->assertEquals('2.0', $info->version);
		$this->assertEquals('20200707', $info->date);
		$this->assertEquals('service', $info->category);
		$this->assertEquals('GPLv2', $info->license);
		$this->assertEquals('https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html', $info->license_link);

		// Author array
		$this->assertTrue(is_array($info->author));
		$this->assertEquals('Rhymix 개발자', $info->author[0]->name);
		$this->assertEquals('devops@rhymix.org', $info->author[0]->email_address);
		$this->assertEquals('https://rhymix.org', $info->author[0]->homepage);
		$this->assertEquals('다른 개발자', $info->author[1]->name);
		$this->assertEquals('other.developer@rhymix.org', $info->author[1]->email_address);
		$this->assertEquals('', $info->author[1]->homepage);

		// Change language
		Context::setLangType('en');
		$info = Rhymix\Framework\Parsers\ModuleInfoParser::loadXML(\RX_BASEDIR . 'tests/_data/module/info.xml');
		$this->assertEquals('Test Module', $info->title);
		$this->assertEquals('This module is for unit testing.', $info->description);
		$this->assertEquals('Rhymix Developer', $info->author[0]->name);

		// Index actions (from module.xml)
		$this->assertEquals('dispTestView', $info->default_index_act);
		$this->assertEquals('dispTestAdminIndex', $info->admin_index_act);
		$this->assertTrue(is_array($info->error_handlers));
		$this->assertEquals('dispTestErrorHandler', $info->error_handlers[404]);
	}
}
