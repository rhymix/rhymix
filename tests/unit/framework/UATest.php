<?php

class UATest extends \Codeception\TestCase\Test
{
	public function testIsMobile()
	{
		// Phones
		$this->assertTrue(Rhymix\Framework\UA::isMobile('Mozilla/5.0 (Linux; Android 5.0; Nexus 5 Build/LPX13D) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.102 Mobile Safari/537.36'));
		$this->assertTrue(Rhymix\Framework\UA::isMobile('Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25'));
		
		// Tablets
		$this->assertTrue(Rhymix\Framework\UA::isMobile('Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25'));
		$this->assertTrue(Rhymix\Framework\UA::isMobile('Mozilla/5.0 (Linux; Android 4.4.2; SM-T530 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.117 Safari/537.36'));
		
		// Not mobile
		$this->assertFalse(Rhymix\Framework\UA::isMobile('Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko'));
		$this->assertFalse(Rhymix\Framework\UA::isMobile('Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36'));
		$this->assertFalse(Rhymix\Framework\UA::isMobile('Mozilla/5.0 (Windows NT 6.1; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0'));
		$this->assertFalse(Rhymix\Framework\UA::isMobile('Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16'));
	}
	
	public function testIsTablet()
	{
		// Phones
		$this->assertFalse(Rhymix\Framework\UA::isTablet('Mozilla/5.0 (Linux; Android 5.0; Nexus 5 Build/LPX13D) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.102 Mobile Safari/537.36'));
		$this->assertFalse(Rhymix\Framework\UA::isTablet('Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25'));
		
		// Tablets
		$this->assertTrue(Rhymix\Framework\UA::isTablet('Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25'));
		$this->assertTrue(Rhymix\Framework\UA::isTablet('Mozilla/5.0 (Linux; Android 4.4.2; SM-T530 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.117 Safari/537.36'));
		
		// Not mobile
		$this->assertFalse(Rhymix\Framework\UA::isTablet('Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko'));
		$this->assertFalse(Rhymix\Framework\UA::isTablet('Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)'));
	}
	
	public function testIsRobot()
	{
		// Robot
		$this->assertTrue(Rhymix\Framework\UA::isRobot('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'));
		$this->assertTrue(Rhymix\Framework\UA::isRobot('Googlebot/2.1 (+http://www.googlebot.com/bot.html)'));
		$this->assertTrue(Rhymix\Framework\UA::isRobot('Yeti/1.0 (NHN Corp.; http://help.naver.com/robots/)'));
		
		// Not robot
		$this->assertFalse(Rhymix\Framework\UA::isRobot('Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25'));
		$this->assertFalse(Rhymix\Framework\UA::isRobot('Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25'));
		$this->assertFalse(Rhymix\Framework\UA::isRobot('Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko'));
	}
	
	public function testGetBrowserInfo()
	{
		// Android default browser
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Linux; U; Android 4.0.3; Device Name) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30');
		$this->assertEquals('Android', $browser->browser);
		$this->assertEquals('4.0', $browser->version);
		$this->assertEquals('Android', $browser->os);
		$this->assertTrue($browser->is_mobile);
		$this->assertTrue($browser->is_tablet);
		
		// Mobile Chrome
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Linux; Android 5.0; Nexus 5 Build/LPX13D) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.102 Mobile Safari/537.36');
		$this->assertEquals('Chrome', $browser->browser);
		$this->assertEquals('38.0', $browser->version);
		$this->assertEquals('Android', $browser->os);
		$this->assertTrue($browser->is_mobile);
		$this->assertFalse($browser->is_tablet);
		
		// Edge
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.10136');
		$this->assertEquals('Edge', $browser->browser);
		$this->assertEquals('12.0', $browser->version);
		$this->assertEquals('Windows', $browser->os);
		$this->assertFalse($browser->is_mobile);
		$this->assertFalse($browser->is_tablet);
		
		// IE 11
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko');
		$this->assertEquals('IE', $browser->browser);
		$this->assertEquals('11.0', $browser->version);
		$this->assertEquals('Windows', $browser->os);
		
		// IE 10 in compatibility mode
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/4.0 (Compatible; MSIE 8.0; Windows NT 5.2; Trident/6.0)');
		$this->assertEquals('IE', $browser->browser);
		$this->assertEquals('10.0', $browser->version);
		
		// IE 8 in compatibility mode
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Trident/4.0)');
		$this->assertEquals('IE', $browser->browser);
		$this->assertEquals('8.0', $browser->version);
		
		// Linux Chrome
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36');
		$this->assertEquals('Chrome', $browser->browser);
		$this->assertEquals('41.0', $browser->version);
		$this->assertEquals('Linux', $browser->os);
		
		// OS X Firefox
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0');
		$this->assertEquals('Firefox', $browser->browser);
		$this->assertEquals('33.0', $browser->version);
		$this->assertEquals('OS X', $browser->os);
	}
}
