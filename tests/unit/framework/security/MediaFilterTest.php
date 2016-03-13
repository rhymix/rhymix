<?php

class MediaFilterTest extends \Codeception\TestCase\Test
{
	public function testWhitelists()
	{
		// iframe whitelist as array.
		$this->assertTrue(in_array('www.youtube.com/', Rhymix\Framework\Security\MediaFilter::getIframeWhitelist()));
		$this->assertFalse(in_array('random-website.com/', Rhymix\Framework\Security\MediaFilter::getIframeWhitelist()));
		
		// iframe whitelist as regex.
		$this->assertTrue(strpos(Rhymix\Framework\Security\MediaFilter::getIframeWhitelistRegex(), '|www\.youtube\.com/') !== false);
		$this->assertFalse(strpos(Rhymix\Framework\Security\MediaFilter::getIframeWhitelistRegex(), 'www.youtube.com/') !== false);
		
		// Match individual URL against iframe whitelist.
		$this->assertTrue(Rhymix\Framework\Security\MediaFilter::matchIframeWhitelist('https://www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Security\MediaFilter::matchIframeWhitelist('http://www-youtube.com/v'));
		
		// object whitelist as array.
		$this->assertTrue(in_array('www.youtube.com/', Rhymix\Framework\Security\MediaFilter::getObjectWhitelist()));
		$this->assertFalse(in_array('random-website.com/', Rhymix\Framework\Security\MediaFilter::getObjectWhitelist()));
		
		// object whitelist as regex.
		$this->assertTrue(strpos(Rhymix\Framework\Security\MediaFilter::getObjectWhitelistRegex(), '|www\.youtube\.com/') !== false);
		$this->assertFalse(strpos(Rhymix\Framework\Security\MediaFilter::getObjectWhitelistRegex(), 'www.youtube.com/') !== false);
		
		// Match individual URL against object whitelist.
		$this->assertTrue(Rhymix\Framework\Security\MediaFilter::matchObjectWhitelist('https://www.youtube.com/v'));
		$this->assertFalse(Rhymix\Framework\Security\MediaFilter::matchObjectWhitelist('http://www-youtube.com/v'));
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
			$this->assertEquals($to, Rhymix\Framework\Security\MediaFilter::removeEmbeddedMedia($from));
		}
	}
}
