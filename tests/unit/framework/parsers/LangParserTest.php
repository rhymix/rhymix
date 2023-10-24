<?php

class LangParserTest extends \Codeception\Test\Unit
{
	protected $_dir = 'tests/_data/lang';

	public function _before()
	{
		$files = Rhymix\Framework\Storage::readDirectory(\RX_BASEDIR . $this->_dir);
		foreach ($files as $file)
		{
			if (preg_match('/\.php$/', $file))
			{
				Rhymix\Framework\Storage::delete($file);
			}
		}
	}

	public function _after()
	{
		$files = Rhymix\Framework\Storage::readDirectory(\RX_BASEDIR . $this->_dir);
		foreach ($files as $file)
		{
			if (preg_match('/\.php$/', $file))
			{
				Rhymix\Framework\Storage::delete($file);
			}
		}
	}

	public function testConvertDirectory()
	{
		Rhymix\Framework\Parsers\LangParser::convertDirectory(\RX_BASEDIR . $this->_dir, ['ko', 'en']);
		$this->assertTrue(file_exists(\RX_BASEDIR . $this->_dir . '/ko.php'));
		$this->assertTrue(file_exists(\RX_BASEDIR . $this->_dir . '/en.php'));
		$this->assertFalse(file_exists(\RX_BASEDIR . $this->_dir . '/ja.php'));
		$this->assertFalse(file_exists(\RX_BASEDIR . $this->_dir . '/fr.php'));

		$lang = new stdClass;
		include \RX_BASEDIR . $this->_dir . '/ko.php';
		$this->assertEquals('테스트 언어', $lang->testlang);
		$this->assertEquals('<p>HTML<br>내용</p>', $lang->testhtml);
		$this->assertEquals(['foo' => '푸', 'bar' => '바'], $lang->testarray);

		$lang = new stdClass;
		include \RX_BASEDIR . $this->_dir . '/en.php';
		$this->assertEquals('Test Lang', $lang->testlang);
		$this->assertEquals('<p>HTML<br>Content</p>', $lang->testhtml);
		$this->assertEquals(['foo' => 'FOO', 'bar' => 'BAR'], $lang->testarray);
	}

	public function testCompileXMLtoPHP()
	{
		$in = \RX_BASEDIR . $this->_dir . '/lang.xml';
		$out = \RX_BASEDIR . $this->_dir . '/ja.php';
		$noout = \RX_BASEDIR . $this->_dir . '/en.php';
		$result = Rhymix\Framework\Parsers\LangParser::compileXMLtoPHP($in, 'ja', $out);
		$this->assertEquals($out, $result);
		$this->assertTrue(file_exists($result));
		$this->assertFalse(file_exists($noout));

		$lang = new stdClass;
		include \RX_BASEDIR . $this->_dir . '/ja.php';
		$this->assertEquals('テスト言語', $lang->testlang);
		$this->assertEquals('<p>HTML&nbsp;コンテンツ</p>', $lang->testhtml);
		$this->assertNull($lang->testarray ?? null);
	}
}
