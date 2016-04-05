<?php

class MediaFilterTest extends \Codeception\TestCase\Test
{
	public function testWhitelists()
	{
		// iframe whitelist as array.
		$this->assertTrue(in_array('www.youtube.com/', Rhymix\Framework\Filters\MediaFilter::getIframeWhitelist()));
		$this->assertFalse(in_array('random-website.com/', Rhymix\Framework\Filters\MediaFilter::getIframeWhitelist()));
		
		// iframe whitelist as regex.
		$this->assertTrue(strpos(Rhymix\Framework\Filters\MediaFilter::getIframeWhitelistRegex(), '|www\.youtube\.com/') !== false);
		$this->assertFalse(strpos(Rhymix\Framework\Filters\MediaFilter::getIframeWhitelistRegex(), 'www.youtube.com/') !== false);
		
		// Match individual URL against iframe whitelist.
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchIframeWhitelist('https://www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchIframeWhitelist('http://www-youtube.com/v'));
		
		// Match protocol-relative URLs.
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchIframeWhitelist('//www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchIframeWhitelist('//www-youtube.com/v'));
		
		// object whitelist as array.
		$this->assertTrue(in_array('www.youtube.com/', Rhymix\Framework\Filters\MediaFilter::getObjectWhitelist()));
		$this->assertFalse(in_array('random-website.com/', Rhymix\Framework\Filters\MediaFilter::getObjectWhitelist()));
		
		// object whitelist as regex.
		$this->assertTrue(strpos(Rhymix\Framework\Filters\MediaFilter::getObjectWhitelistRegex(), '|www\.youtube\.com/') !== false);
		$this->assertFalse(strpos(Rhymix\Framework\Filters\MediaFilter::getObjectWhitelistRegex(), 'www.youtube.com/') !== false);
		
		// Match individual URL against object whitelist.
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchObjectWhitelist('https://www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchObjectWhitelist('http://www-youtube.com/v'));
		
		// Match protocol-relative URLs.
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchObjectWhitelist('//www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchObjectWhitelist('//www-youtube.com/v'));
	}
	
	public function testAddPrefix()
	{
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchIframeWhitelist('http://some.custom.website.com/video.mp4'));
		Rhymix\Framework\Filters\MediaFilter::addIframePrefix('*.custom.website.com/');
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchIframeWhitelist('http://some.custom.website.com/video.mp4'));
		
		$this->assertFalse(Rhymix\Framework\Filters\MediaFilter::matchObjectWhitelist('http://some.custom.website.com/video.mp4'));
		Rhymix\Framework\Filters\MediaFilter::addObjectPrefix('*.custom.website.com/');
		$this->assertTrue(Rhymix\Framework\Filters\MediaFilter::matchObjectWhitelist('http://some.custom.website.com/video.mp4'));
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
