<?php

class TemplateTest extends \Codeception\Test\Unit
{
	public function _before()
	{
		Context::init();
	}

	public function testIsRelativePath()
	{
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'empty.html');

		$this->assertTrue($tmpl->isRelativePath('foo.html'));
		$this->assertTrue($tmpl->isRelativePath('foo/bar.html'));
		$this->assertTrue($tmpl->isRelativePath('../foo/bar.html'));
		$this->assertTrue($tmpl->isRelativePath('foo/../bar.html'));
		$this->assertTrue($tmpl->isRelativePath('^/foo/../bar.html'));
		$this->assertFalse($tmpl->isRelativePath('/foo/bar.html'));
		$this->assertFalse($tmpl->isRelativePath('https://foo.com/bar.html'));
		$this->assertFalse($tmpl->isRelativePath('file:///C:/foo/bar.html'));
		$this->assertFalse($tmpl->isRelativePath('data:image/png;base64,AAAAAAAAAAA="'));
		$this->assertFalse($tmpl->isRelativePath('{$foo}'));
	}

	public function testConvertPath()
	{
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'empty.html');

		$source = 'foo.html';
		$target = 'tests/_data/template/foo.html';
		$this->assertEquals($target, $tmpl->convertPath($source));

		$source = 'foo/bar.js';
		$target = 'tests/_data/template/foo/bar.js';
		$this->assertEquals($target, $tmpl->convertPath($source));

		$source = '../foo.scss';
		$target = 'tests/_data/foo.scss';
		$this->assertEquals($target, $tmpl->convertPath($source));

		$source = '../../_output/foo/../bar.jpg';
		$target = 'tests/_output/bar.jpg';
		$this->assertEquals($target, $tmpl->convertPath($source));

		$source = '/foo/bar.blade.php';
		$target = 'tests/_data/template/foo/bar.blade.php';
		$this->assertEquals($target, $tmpl->convertPath($source));

		$source = '^/foo/bar.gif';
		$target = '/rhymix/foo/bar.gif';
		$this->assertEquals($target, $tmpl->convertPath($source));
	}
}
