<?php

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
			$result = Rhymix\Framework\Security\FilenameFilter::clean($from);
			$this->assertEquals($to, $result);
		}
	}
}
