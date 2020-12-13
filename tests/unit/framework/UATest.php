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
	
	public function testGetLocale()
	{
		$locale = Rhymix\Framework\UA::getLocale('en-US,en;q=0.8,ko-KR;q=0.5,ko;q=0.3');
		$this->assertEquals('en-US', $locale);
		
		$locale = Rhymix\Framework\UA::getLocale('ko-KR,ko;q=0.8,en-US;q=0.5,en;q=0.3');
		$this->assertEquals('ko-KR', $locale);
	}
	
	public function testGetBrowserInfo()
	{
		// Android default browser
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Linux; U; Android 4.0.3; Device Name) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30');
		$this->assertEquals('Android', $browser->browser);
		$this->assertEquals('4.0', $browser->version);
		$this->assertEquals('Android', $browser->os);
		$this->assertEquals('4.0.3', $browser->os_version);
		$this->assertTrue($browser->is_mobile);
		$this->assertTrue($browser->is_tablet);
		$this->assertFalse($browser->is_webview);
		$this->assertFalse($browser->is_robot);
		
		// Android default browser (possible confusion with Chrome)
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Linux; Android 4.4.4; One Build/KTU84L.H4) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36');
		$this->assertEquals('Android', $browser->browser);
		$this->assertEquals('4.4', $browser->version);
		$this->assertEquals('4.4.4', $browser->os_version);
		$this->assertEquals('One', $browser->device);
		$this->assertTrue($browser->is_mobile);
		$this->assertFalse($browser->is_tablet);
		$this->assertFalse($browser->is_webview);
		$this->assertFalse($browser->is_robot);
		
		// Android webview (possible confusion with Chrome)
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Linux; Android 5.1.1; Nexus 5 Build/LMY48B; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/43.0.2357.65 Mobile Safari/537.36');
		$this->assertEquals('Android', $browser->browser);
		$this->assertEquals('5.1', $browser->version);
		$this->assertEquals('Nexus 5', $browser->device);
		$this->assertTrue($browser->is_mobile);
		$this->assertFalse($browser->is_tablet);
		$this->assertTrue($browser->is_webview);
		$this->assertFalse($browser->is_robot);
		
		// Android Chrome
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Linux; Android 5.0; Nexus 5 Build/LPX13D) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.102 Mobile Safari/537.36');
		$this->assertEquals('Chrome', $browser->browser);
		$this->assertEquals('38.0', $browser->version);
		$this->assertEquals('Android', $browser->os);
		$this->assertTrue($browser->is_mobile);
		$this->assertFalse($browser->is_webview);
		$this->assertFalse($browser->is_tablet);
		
		// Windows Chrome
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36');
		$this->assertEquals('Chrome', $browser->browser);
		$this->assertEquals('51.0', $browser->version);
		$this->assertEquals('Windows', $browser->os);
		$this->assertEquals('10.0', $browser->os_version);
		$this->assertFalse($browser->is_mobile);
		
		// Linux Chrome
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36');
		$this->assertEquals('Chrome', $browser->browser);
		$this->assertEquals('41.0', $browser->version);
		$this->assertEquals('Linux', $browser->os);
		$this->assertFalse($browser->is_mobile);
		
		// Linux Konqueror
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (X11; Linux) KHTML/4.9.1 (like Gecko) Konqueror/4.9');
		$this->assertEquals('Konqueror', $browser->browser);
		$this->assertEquals('4.9', $browser->version);
		$this->assertEquals('Linux', $browser->os);
		
		// Edge 13
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586');
		$this->assertEquals('Edge', $browser->browser);
		$this->assertEquals('13.0', $browser->version);
		$this->assertEquals('Windows', $browser->os);
		$this->assertFalse($browser->is_mobile);
		
		// Edge 12
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.10136');
		$this->assertEquals('Edge', $browser->browser);
		$this->assertEquals('12.0', $browser->version);
		$this->assertEquals('Windows', $browser->os);
		$this->assertFalse($browser->is_mobile);
		
		// IE 11
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko');
		$this->assertEquals('IE', $browser->browser);
		$this->assertEquals('11.0', $browser->version);
		$this->assertEquals('Windows', $browser->os);
		$this->assertEquals('10.0', $browser->os_version);
		
		// IE 10 in compatibility mode
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/4.0 (Compatible; MSIE 8.0; Windows NT 5.2; Trident/6.0)');
		$this->assertEquals('IE', $browser->browser);
		$this->assertEquals('10.0', $browser->version);
		$this->assertEquals('Windows', $browser->os);
		$this->assertEquals('XP', $browser->os_version);
		
		// IE 9
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)');
		$this->assertEquals('IE', $browser->browser);
		$this->assertEquals('9.0', $browser->version);
		$this->assertEquals('Windows', $browser->os);
		$this->assertEquals('7', $browser->os_version);
		
		// IE 8 in compatibility mode
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Trident/4.0)');
		$this->assertEquals('IE', $browser->browser);
		$this->assertEquals('8.0', $browser->version);
		$this->assertEquals('Windows', $browser->os);
		$this->assertEquals('Vista', $browser->os_version);
		
		// iOS Safari
		$browser = Rhymix\Framework\UA::getBrowserInfo('iPad: Mozilla/5.0 (iPad; CPU OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B176 Safari/7534.48.3');
		$this->assertEquals('Safari', $browser->browser);
		$this->assertEquals('5.1', $browser->version);
		$this->assertEquals('iOS', $browser->os);
		$this->assertEquals('5.1', $browser->os_version);
		$this->assertEquals('iPad', $browser->device);
		$this->assertTrue($browser->is_mobile);
		$this->assertTrue($browser->is_tablet);
		
		// iOS Chrome
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (iPhone; U; CPU iPhone OS 5_1_1 like Mac OS X; en) AppleWebKit/534.46.0 (KHTML, like Gecko) CriOS/19.0.1084.60 Mobile/9B206 Safari/7534.48.3');
		$this->assertEquals('Chrome', $browser->browser);
		$this->assertEquals('19.0', $browser->version);
		$this->assertEquals('iOS', $browser->os);
		$this->assertEquals('5.1.1', $browser->os_version);
		$this->assertEquals('iPhone', $browser->device);
		$this->assertTrue($browser->is_mobile);
		$this->assertFalse($browser->is_tablet);
		
		// iOS Firefox
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (iPad; CPU iPhone OS 8_3 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) FxiOS/1.0 Mobile/12F69 Safari/600.1.4');
		$this->assertEquals('Firefox', $browser->browser);
		$this->assertEquals('1.0', $browser->version);
		$this->assertEquals('iOS', $browser->os);
		$this->assertEquals('8.3', $browser->os_version);
		$this->assertEquals('iPad', $browser->device);
		$this->assertTrue($browser->is_mobile);
		$this->assertTrue($browser->is_tablet);
		
		// macOS (OS X) Safari
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/600.3.18 (KHTML, like Gecko) Version/8.0.3 Safari/600.3.18');
		$this->assertEquals('Safari', $browser->browser);
		$this->assertEquals('8.0', $browser->version);
		$this->assertEquals('macOS', $browser->os);
		$this->assertEquals('10.10.2', $browser->os_version);
		$this->assertFalse($browser->is_mobile);
		
		// macOS (OS X) Chrome
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.1 Safari/537.36');
		$this->assertEquals('Chrome', $browser->browser);
		$this->assertEquals('41.0', $browser->version);
		$this->assertEquals('macOS', $browser->os);
		$this->assertEquals('10.10.1', $browser->os_version);
		$this->assertFalse($browser->is_mobile);
		
		// macOS (OS X) Firefox
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0');
		$this->assertEquals('Firefox', $browser->browser);
		$this->assertEquals('33.0', $browser->version);
		$this->assertEquals('macOS', $browser->os);
		$this->assertFalse($browser->is_mobile);
		
		// Opera 15
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.52 Safari/537.36 OPR/15.0.1147.100');
		$this->assertEquals('Opera', $browser->browser);
		$this->assertEquals('15.0', $browser->version);
		$this->assertFalse($browser->is_mobile);
		
		// Opera 12
		$browser = Rhymix\Framework\UA::getBrowserInfo('Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16');
		$this->assertEquals('Opera', $browser->browser);
		$this->assertEquals('12.16', $browser->version);
		$this->assertFalse($browser->is_mobile);
		
		// Opera 9.x
		$browser = Rhymix\Framework\UA::getBrowserInfo('Opera/9.64 (X11; Linux x86_64; U; en) Presto/2.1.1');
		$this->assertEquals('Opera', $browser->browser);
		$this->assertEquals('9.64', $browser->version);
		$this->assertFalse($browser->is_mobile);
		
		// Iceweasel (Debian Firefox)
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (X11; Linux i686; rv:10.0.7) Gecko/20100101 Iceweasel/10.0.7');
		$this->assertEquals('Firefox', $browser->browser);
		$this->assertEquals('10.0', $browser->version);
		$this->assertFalse($browser->is_mobile);
		
		// Whale Mobile (Naver App)
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Linux; Android 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.128 Whale/1.0.0.0 Crosswalk/23.69.590.31 Mobile Safari/537.36 NAVER(inapp; search; 660; 10.7.2)');
		$this->assertEquals('Whale', $browser->browser);
		$this->assertEquals('1.0', $browser->version);
		$this->assertEquals('Android', $browser->os);
		$this->assertTrue($browser->is_mobile);
		$this->assertFalse($browser->is_webview);
		$this->assertFalse($browser->is_robot);
		
		// XE Push App (Webview)
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Linux; Android 9; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/75.0.3770.101 Mobile Safari/537.36;App');
		$this->assertEquals('Chrome', $browser->browser);
		$this->assertEquals('75.0', $browser->version);
		$this->assertEquals('Android', $browser->os);
		$this->assertTrue($browser->is_mobile);
		$this->assertTrue($browser->is_webview);
		$this->assertFalse($browser->is_robot);
		
		// Googlebot
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
		$this->assertEquals('Googlebot', $browser->browser);
		$this->assertEquals('2.1', $browser->version);
		$this->assertTrue($browser->is_robot);
		
		// Googlebot-Image
		$browser = Rhymix\Framework\UA::getBrowserInfo('Googlebot-Image/1.0');
		$this->assertEquals('Googlebot-Image', $browser->browser);
		$this->assertEquals('1.0', $browser->version);
		$this->assertTrue($browser->is_robot);
		
		// Mediapartners-Google
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mediapartners-Google');
		$this->assertEquals('Mediapartners-Google', $browser->browser);
		$this->assertEquals(null, $browser->version);
		$this->assertTrue($browser->is_robot);
		
		// Bingbot
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)');
		$this->assertEquals('Bingbot', $browser->browser);
		$this->assertEquals('2.0', $browser->version);
		$this->assertTrue($browser->is_robot);
		
		// Yeti
		$browser = Rhymix\Framework\UA::getBrowserInfo('Yeti/1.1; +http://naver.me/spd');
		$this->assertEquals('Yeti', $browser->browser);
		$this->assertEquals('1.1', $browser->version);
		$this->assertTrue($browser->is_robot);
		
		// Baiduspider
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)');
		$this->assertEquals('Baiduspider', $browser->browser);
		$this->assertEquals('2.0', $browser->version);
		$this->assertTrue($browser->is_robot);
		
		// wget
		$browser = Rhymix\Framework\UA::getBrowserInfo('wget/1.17.1');
		$this->assertEquals('Wget', $browser->browser);
		$this->assertEquals('1.17', $browser->version);
		$this->assertTrue($browser->is_robot);
		
		// curl
		$browser = Rhymix\Framework\UA::getBrowserInfo('curl/7.47.0');
		$this->assertEquals('Curl', $browser->browser);
		$this->assertEquals('7.47', $browser->version);
		$this->assertTrue($browser->is_robot);
		
		// PHP with version
		$browser = Rhymix\Framework\UA::getBrowserInfo('PHP/5.2.9');
		$this->assertEquals('PHP', $browser->browser);
		$this->assertEquals('5.2', $browser->version);
		$this->assertTrue($browser->is_robot);
		
		// PHP without version
		$browser = Rhymix\Framework\UA::getBrowserInfo('PHP');
		$this->assertEquals('PHP', $browser->browser);
		$this->assertNull($browser->version);
		
		// PHP with HTTP_Request2
		$browser = Rhymix\Framework\UA::getBrowserInfo('HTTP_Request2/2.1.1 (http://pear.php.net/package/http_request2) PHP/5.3.2');
		$this->assertEquals('PHP', $browser->browser);
		$this->assertEquals('5.3', $browser->version);
		
		// Some random browser with the 'Mozilla' version
		$browser = Rhymix\Framework\UA::getBrowserInfo('Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; XH; rv:8.578.498) fr, Gecko/20121021 Camino/8.723+ (Firefox compatible)');
		$this->assertEquals('Mozilla', $browser->browser);
		$this->assertEquals('5.0', $browser->version);
		
		// Some random browser without the 'Mozilla' version
		$browser = Rhymix\Framework\UA::getBrowserInfo('W3C_Validator/1.650');
		$this->assertEquals('W3C_Validator', $browser->browser);
		$this->assertEquals('1.650', $browser->version);
	}
	
	public function testEncodeFilenameForDownload()
	{
		$this->assertEquals('filename*=UTF-8\'\'%ED%95%9C%EA%B8%80%20filename.jpg', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Chrome/50.0'));
		$this->assertEquals('filename*=UTF-8\'\'%ED%95%9C%EA%B8%80%20filename.jpg', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Firefox/46.0'));
		$this->assertEquals('filename*=UTF-8\'\'%ED%95%9C%EA%B8%80%20filename.jpg', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Edge/12.10240'));
		$this->assertEquals('filename*=UTF-8\'\'%ED%95%9C%EA%B8%80%20filename.jpg', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'MSIE/7.0 Trident/7.0'));
		$this->assertEquals('filename*=UTF-8\'\'%ED%95%9C%EA%B8%80%20filename.jpg', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko'));
		$this->assertEquals('filename="%ED%95%9C%EA%B8%80%20filename.jpg"', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'MSIE 8.0'));
		$this->assertEquals('filename="%ED%95%9C%EA%B8%80%20filename.jpg"', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Unknown Browser'));
		$this->assertEquals('filename="한글 filename.jpg"', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Chrome/69.0.3497.128 Whale/1.0.0.0 Crosswalk/23.69.590.31 Mobile Safari/537.36 NAVER(inapp; search; 660; 10.7.2)'));
		$this->assertEquals('filename="한글 filename.jpg"', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Mozilla/5.0 (Linux; Android 9; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/75.0.3770.101 Mobile Safari/537.36'));
		$this->assertEquals('filename="%ED%95%9C%EA%B8%80%20filename.jpg"', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Dalvik/2.1.0 (Linux; U; Android 9)'));
		$this->assertEquals('filename="한글 filename.jpg"', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Safari/5.0 Version/5.0'));
		$this->assertEquals('filename="한글 filename.jpg"', Rhymix\Framework\UA::encodeFilenameForDownload('한글 filename.jpg', 'Linux; Android 5.1.1; Version/4.0 Chrome/43.0.2357.65 Mobile Safari/537.36'));
	}
	
	public function testGetSetColorScheme()
	{
		$_COOKIE['rx_color_scheme'] = 'light';
		$this->assertEquals('light', Rhymix\Framework\UA::getColorScheme());
		$_COOKIE['rx_color_scheme'] = 'dark';
		$this->assertEquals('dark', Rhymix\Framework\UA::getColorScheme());
		$_COOKIE['rx_color_scheme'] = 'none';
		$this->assertEquals('auto', Rhymix\Framework\UA::getColorScheme());
		$_COOKIE['rx_color_scheme'] = 'invalid';
		$this->assertEquals('auto', Rhymix\Framework\UA::getColorScheme());
		
		Rhymix\Framework\UA::setColorScheme('light');
		$this->assertEquals('light', $_COOKIE['rx_color_scheme']);
		Rhymix\Framework\UA::setColorScheme('dark');
		$this->assertEquals('dark', $_COOKIE['rx_color_scheme']);
		Rhymix\Framework\UA::setColorScheme('auto');
		$this->assertNull($_COOKIE['rx_color_scheme'] ?? null);
		Rhymix\Framework\UA::setColorScheme('invalid');
		$this->assertNull($_COOKIE['rx_color_scheme'] ?? null);
	}
}
