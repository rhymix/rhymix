<?php

class TemplateTest extends \Codeception\Test\Unit
{
	public function _before()
	{
		\Rhymix\Framework\Debug::disable();
		$this->baseurl = '/' . basename(dirname(dirname(dirname(__DIR__)))) . '/';
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
		$target = $this->baseurl . 'foo/bar.gif';
		$this->assertEquals($target, $tmpl->convertPath($source));
	}

	public function testNormalizePath()
	{
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'empty.html');

		$source = '/rhymix/foo/bar//../hello/world\\..';
		$target = '/rhymix/foo/hello/';
		$this->assertEquals($target, $tmpl->normalizePath($source));

		$source = '../foo\\bar/../baz/';
		$target = '../foo/baz/';
		$this->assertEquals($target, $tmpl->normalizePath($source));

		$source = '/fo/ob/ar/../../baz/./buzz.txt';
		$target = '/fo/baz/buzz.txt';
		$this->assertEquals($target, $tmpl->normalizePath($source));

		$source = 'foo/bar/../../baz/buzz.txt';
		$target = 'baz/buzz.txt';
		$this->assertEquals($target, $tmpl->normalizePath($source));

		$source = 'tests/unit/foo/bar/../../../../../../buzz.txt';
		$target = '../../buzz.txt';
		$this->assertEquals($target, $tmpl->normalizePath($source));

		$source = 'tests/unit/foo/bar/../../../../.././';
		$target = '../';
		$this->assertEquals($target, $tmpl->normalizePath($source));
	}
}
