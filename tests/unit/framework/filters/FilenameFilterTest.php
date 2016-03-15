<?php

use Rhymix\Framework\Filters\FilenameFilter;

class FilenameFilterTest extends \Codeception\TestCase\Test
{
	public function testFilenameFilterClean()
	{
		$tests = array(
			
			// Illegal characters
			'foo*\.bar' => 'foo.bar',
			'foobar{baz}.jpg' => 'foobar(baz).jpg',
			'foobar^%.docx' => 'foobar_.docx',
			
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
}
