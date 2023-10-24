<?php

use Rhymix\Framework\Filters\FileContentFilter;

class FileContentFilterTest extends \Codeception\Test\Unit
{
	public function testSVG()
	{
		$this->assertTrue(FileContentFilter::check(\RX_BASEDIR . 'tests/_data/security/example.svg'));
		$this->assertFalse(FileContentFilter::check(\RX_BASEDIR . 'tests/_data/security/ssrf.svg'));
		$this->assertFalse(FileContentFilter::check(\RX_BASEDIR . 'tests/_data/security/ssrf.svg', 'cover.jpg'));
		$this->assertFalse(FileContentFilter::check(\RX_BASEDIR . 'tests/_data/security/xss.svg'));
	}
}
