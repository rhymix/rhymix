<?php

use Rhymix\Framework\Filters\FilenameFilter;

class FilenameFilterTest extends \Codeception\Test\Unit
{
	public function testFilenameFilterClean()
	{
		$tests = array(

			// Illegal characters
			'foo*\.bar' => 'foo.bar',
			'foobar{baz}.jpg' => 'foobar(baz).jpg',
			'foobar^%.docx' => 'foobar_.docx',
			'foo&bar@rhymix.docx' => 'foo&bar@rhymix.docx',

			// Control characters
			'foobar' . chr(127) . '.gif' => 'foobar.gif',
			'foobar' . "\t\r\n" . '.gif' => 'foobar.gif',

			// Unicode whitepace characters
			'foobar' . html_entity_decode('&#x2001;') . ' space.gif' => 'foobar space.gif',
			'hello     world.png' => 'hello world.png',

			// Extra symbols
			'_foobar.jpg-' => 'foobar.jpg',
			'.htaccess' => 'htaccess',

			// PHP extension
			'foobar.php' => 'foobar.phps',
			'foobar.php.jpg' => 'foobar.php.jpg',

			// Overlong filenames
			str_repeat('f', 200) . '.' . str_repeat('b', 30) => str_repeat('f', 111) . '.' . str_repeat('b', 15),
			str_repeat('한글', 100) . '.hwp' => str_repeat('한글', 61) . '한.hwp',

		);

		foreach ($tests as $from => $to)
		{
			$result = FilenameFilter::clean($from);
			$this->assertEquals($to, $result);
		}
	}

	public function testFilenameFilterCleanPath()
	{
		// Remove extra dots and slashes.
		$this->assertEquals('/usr/share/foo/bar.jpg', FilenameFilter::cleanPath('/usr/share/foo//./baz/../bar.jpg'));
		$this->assertEquals('/usr/share/foo/bar.jpg', FilenameFilter::cleanPath('/usr/share/foo/././baz/../../foo/bar.jpg'));
		$this->assertEquals('/usr/share', FilenameFilter::cleanPath('/usr/share/foo/..'));
		$this->assertEquals('/usr/share', FilenameFilter::cleanPath('/usr/share/foo/bar/../baz/../../'));

		// Test internal paths.
		$this->assertEquals(\RX_BASEDIR . 'common/js/debug.js', FilenameFilter::cleanPath('common/js/debug.js'));
		$this->assertEquals(\RX_BASEDIR . 'common/js/debug.js', FilenameFilter::cleanPath('./common/js/debug.js'));

		// Test Windows paths.
		$this->assertEquals('C:/Windows/Notepad.exe', FilenameFilter::cleanPath('C:\\Windows\\System32\\..\\Notepad.exe'));
		$this->assertEquals('//vboxsrv/hello/world', FilenameFilter::cleanPath('\\\\vboxsrv\\hello\\world'));

		// Test absolute URLs.
		$this->assertEquals('https://www.rhymix.org/foo/bar', FilenameFilter::cleanPath('https://www.rhymix.org/foo/.//bar'));
		$this->assertEquals('//www.rhymix.org/foo/bar', FilenameFilter::cleanPath('//www.rhymix.org/foo/.//bar'));

		// Do not remove .. if there is no parent directory.
		$this->assertEquals('C:/../foobar', FilenameFilter::cleanPath('C:\\..\foobar\\'));
		$this->assertEquals('/../foobar', FilenameFilter::cleanPath('/../foobar/'));

		// Remove query strings and URL fragments.
		$this->assertEquals(\RX_BASEDIR . 'index.php', FilenameFilter::cleanPath('index.php?foo=bar'));
		$this->assertEquals(\RX_BASEDIR . 'index.php', FilenameFilter::cleanPath('index.php#baz'));
	}

	public function testFilenameFilterIsDirectDownload()
	{
		$this->assertTrue(FilenameFilter::isDirectDownload('foobar.GIF'));
		$this->assertTrue(FilenameFilter::isDirectDownload('foobar.jpg'));
		$this->assertTrue(FilenameFilter::isDirectDownload('foo.bar.jpeg'));
		$this->assertTrue(FilenameFilter::isDirectDownload('/foo/bar/baz.png'));
		$this->assertTrue(FilenameFilter::isDirectDownload('picture.webm'));
		$this->assertTrue(FilenameFilter::isDirectDownload('/audio.MP3'));
		$this->assertTrue(FilenameFilter::isDirectDownload('/audio.FLac'));
		$this->assertTrue(FilenameFilter::isDirectDownload('//foo.bar/video.mp4'));

		$this->assertFalse(FilenameFilter::isDirectDownload('rhymix.docx'));
		$this->assertFalse(FilenameFilter::isDirectDownload('rhymix.HWP'));
		$this->assertFalse(FilenameFilter::isDirectDownload('rhymix.jpg.exe'));
		$this->assertFalse(FilenameFilter::isDirectDownload('/foo/bar/rhymix.gif.php'));
		$this->assertFalse(FilenameFilter::isDirectDownload('rhymix.php?filename=test.vbs'));
		$this->assertFalse(FilenameFilter::isDirectDownload(''));
		$this->assertFalse(FilenameFilter::isDirectDownload('http://www.google.com'));
		$this->assertFalse(FilenameFilter::isDirectDownload('/'));

		$this->assertTrue(FilenameFilter::isDirectDownload('foobar.jpg', false));
		$this->assertTrue(FilenameFilter::isDirectDownload('foobar.webp', false));
		$this->assertFalse(FilenameFilter::isDirectDownload('foobar.mp4', false));
		$this->assertFalse(FilenameFilter::isDirectDownload('foobar.webm', false));
	}
}
