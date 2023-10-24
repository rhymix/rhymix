<?php

class MediaFilterTest extends \Codeception\Test\Unit
{
	public function testWhitelists()
	{
		// whitelist as array.
		$this->assertTrue(in_array('www.youtube.com/', Rhymix\Framework\Filters\MediaFilter::getWhitelist()));
		$this->assertFalse(in_array('random-website.com/', Rhymix\Framework\Filters\MediaFilter::getWhitelist()));

		// whitelist as regex.
		$this->assertTrue(strpos(Rhymix\Framework\Filters\MediaFilter::getWhitelistRegex(), '|www\.youtube\.com/') !== false);
		$this->assertFalse(strpos(Rhymix\Framework\Filters\MediaFilter::getWhitelistRegex(), 'www.youtube.com/') !== false);

		// Match individual URL against whitelist.
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchWhitelist('https://www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchWhitelist('http://www-youtube.com/v'));

		// Match protocol-relative URLs.
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchWhitelist('//www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchWhitelist('//www-youtube.com/v'));

		// Check deprecated methods for compatibility.
		$this->assertTrue(in_array('www.youtube.com/', Rhymix\Framework\Filters\MediaFilter::getIframeWhitelist()));
		$this->assertFalse(strpos(Rhymix\Framework\Filters\MediaFilter::getObjectWhitelistRegex(), 'www.youtube.com/') !== false);
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchIframeWhitelist('https://www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchObjectWhitelist('http://www-youtube.com/v'));
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchIframeWhitelist('//www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchObjectWhitelist('//www-youtube.com/v'));
	}

	public function testAddPrefix()
	{
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchWhitelist('http://some.custom.website.com/video.mp4'));
		Rhymix\Framework\Filters\MediaFilter::addPrefix('*.custom.website.com/');
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchWhitelist('http://some.custom.website.com/video.mp4'));
	}

	public function testRemoveEmbeddedMedia()
	{
		$tests = array(
			'<div><object></object></div>' => '<div></div>',
			'<div><object><embed></embed></object></div>' => '<div></div>',
			'<div><object><param /></object></div>' => '<div></div>',
			'<div><img class="foo" editor_component="multimedia_link" /></div>' => '<div></div>',
			'<div><img editor_component="multimedia_link"></img></div>' => '<div></div>',
		);

		foreach ($tests as $from => $to)
		{
			$this->assertEquals($to, Rhymix\Framework\Filters\MediaFilter::removeEmbeddedMedia($from));
		}
	}
}
