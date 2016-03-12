<?php

class SecurityTest extends \Codeception\TestCase\Test
{
	public function testSanitize()
	{
		// Escape
		$this->assertEquals('foo&lt;bar&gt;', Rhymix\Framework\Security::sanitize('foo<bar>', 'escape'));
		
		// Strip
		$this->assertEquals('foobar', Rhymix\Framework\Security::sanitize('foo<p>bar</p>', 'strip'));
		
		// HTML (more thorough tests in HTMLFilterTest)
		$this->assertEquals('<p>safe</p>', Rhymix\Framework\Security::sanitize('<p>safe<script></script></p>', 'html'));
		
		// Filename (more thorough tests in FilenameFilterTest)
		$this->assertEquals('foo(bar).xls', Rhymix\Framework\Security::sanitize('foo<bar>.xls', 'filename'));
	}
	
	public function testCheckCSRF()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_REFERER'] = '';
		$this->assertFalse(Rhymix\Framework\Security::checkCSRF());
		
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertTrue(Rhymix\Framework\Security::checkCSRF());
		
		$_SERVER['HTTP_HOST'] = 'www.rhymix.org';
		$_SERVER['HTTP_REFERER'] = 'http://www.foobar.com/';
		$this->assertFalse(Rhymix\Framework\Security::checkCSRF());
		
		$_SERVER['HTTP_HOST'] = 'www.rhymix.org';
		$this->assertTrue(Rhymix\Framework\Security::checkCSRF('http://www.rhymix.org/'));
	}
	
	public function testCheckXEE()
	{
		$xml = '<methodCall></methodCall>';
		$this->assertTrue(Rhymix\Framework\Security::checkXEE($xml));
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?><methodCall></methodCall>';
		$this->assertTrue(Rhymix\Framework\Security::checkXEE($xml));
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE foo><methodCall attr="value"></methodCall>';
		$this->assertTrue(Rhymix\Framework\Security::checkXEE($xml));
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE foo><whatever></whatever>';
		$this->assertFalse(Rhymix\Framework\Security::checkXEE($xml));
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE foo>';
		$this->assertFalse(Rhymix\Framework\Security::checkXEE($xml));
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?><!ENTITY xxe SYSTEM "http://www.attacker.com/text.txt"><methodCall></methodCall>';
		$this->assertFalse(Rhymix\Framework\Security::checkXEE($xml));
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE foo [<!ELEMENT foo ANY><!ENTITY xxe SYSTEM "file:///etc/passwd" >]><fault></fault>';
		$this->assertFalse(Rhymix\Framework\Security::checkXEE($xml));
	}
}
