<?php

class LegacyTest extends \Codeception\Test\Unit
{
	public function testGetModule()
	{
		$this->assertTrue(getModule('board', 'controller') instanceof BoardController);
		$this->assertTrue(getModule('board', 'model') instanceof BoardModel);
		$this->assertTrue(getModule('board', 'view', 'admin') instanceof BoardAdminView);
		$this->assertTrue(getModule('board') instanceof BoardView);
		$this->assertTrue(getAdminController('board') instanceof BoardAdminController);
		$this->assertTrue(getAdminModel('board') instanceof BoardAdminModel);
		$this->assertTrue(getAdminView('board') instanceof BoardAdminView);
		$this->assertTrue(getController('board') instanceof BoardController);
		$this->assertTrue(getModel('board') instanceof BoardModel);
		$this->assertTrue(getView('board') instanceof BoardView);
		$this->assertTrue(getAPI('board') instanceof BoardApi);
		$this->assertTrue(getMobile('board') instanceof BoardMobile);
		$this->assertTrue(getClass('board') instanceof Board);
	}

	public function testGetNextSequence()
	{
		if (!DB::getInstance()->getHandle())
		{
			return;
		}

		$this->assertGreaterThan(0, $sequence1 = getNextSequence());
		$this->assertGreaterThan($sequence1, $sequence2 = getNextSequence());

		$this->assertTrue(checkUserSequence($sequence1));
		$this->assertTrue(checkUserSequence($sequence2));

		$this->assertFalse(checkUserSequence(-1));
		setUserSequence(-1);
		$this->assertTrue(checkUserSequence(-1));
	}

	public function testGetURL()
	{
		/**
		 * TODO:
		 *  - getUrl()
		 *  - getNotEncodedUrl()
		 *  - getAutoEncodedUrl()
		 *  - getFullUrl()
		 *  - getNotEncodedFullUrl()
		 *  - getSiteUrl()
		 *  - getNotEncodedSiteUrl()
		 *  - getFullSiteUrl()
		 *  - getCurrentPageUrl()
		 *  - getScriptPath()
		 *  - getRequestUriByServerEnviroment()
		 */

		// Legacy format
		$this->assertStringContainsString('foo=bar', getUrl('foo', 'bar'));
		$this->assertStringContainsString('?foo=bar&amp;rhy=mix', getUrl('', 'foo', 'bar', 'rhy', 'mix'));
		$this->assertStringContainsString('?foo=bar&amp;rhy=mix', getUrl('', 'foo', 'bar', 'rhy', 'mix', 'empty', '', 'keys', null));
		$this->assertStringContainsString('?foo=bar&rhy=mix', getNotEncodedUrl('', 'foo', 'bar', 'rhy', 'mix'));

		// Array format
		$this->assertStringContainsString('?foo=bar&amp;rhy=mix', getUrl(['foo' => 'bar', 'rhy' => 'mix', 'empty' => false]));
		$this->assertStringContainsString('?foo=bar&rhy=mix', getNotEncodedUrl(['foo' => 'bar', 'rhy' => 'mix']));
		$this->assertStringContainsString('?foo=bar', getNotEncodedUrl(['foo' => 'bar', 'rhymix' => []]));

		// Nested arrays #2123
		$this->assertStringContainsString('?foo=bar&rhy[0]=mix&rhy[1]=xe', urldecode(getNotEncodedUrl(['foo' => 'bar', 'rhy' => ['mix', 'xe']])));
		$this->assertStringContainsString('?foo=bar&rhy[x]=mix&rhy[y]=xe', urldecode(getNotEncodedUrl(['foo' => 'bar', 'rhy' => ['x' => 'mix', 'y' => 'xe']])));
		$this->assertStringContainsString('?foo=bar&rhy[x][0]=mix&rhy[x][1]=xe', urldecode(getNotEncodedUrl(['foo' => 'bar', 'rhy' => ['x' => ['mix', 'xe']]])));
	}

	public function testIsSiteID()
	{
		$this->assertTrue(isSiteID('rhymix_RHYMIX_1234'));
		$this->assertFalse(isSiteID('www.rhymix.org'));
	}

	public function testCutStr()
	{
		$this->assertEquals('안녕하세요? 라이믹스...', cut_str('안녕하세요? 라이믹스입니다. 제목이 너무 길어서 잘립니다.', 20));
		$this->assertEquals('Hello? This is Rhymix...', cut_str('Hello? This is Rhymix. This title is very long.', 20));
		$this->assertEquals('Hello &lt;world&gt; 😁', cut_str('Hello &lt;world&gt; &#x1F601;', 20));
		$this->assertEquals('Hello &quot;Rhymix&quot; test &amp;...', cut_str('Hello "Rhymix" test &amp;amp;', 20));
	}

	public function testTimeFunctions()
	{
		$this->assertEquals(0, get_time_zone_offset('00:00'));
		$this->assertEquals(32400, get_time_zone_offset('+09:00'));
		$this->assertEquals(32400, get_time_zone_offset('+0900'));
		$this->assertEquals(32400, get_time_zone_offset('0900'));
		$this->assertEquals(-18000, get_time_zone_offset('-05:00'));
		$this->assertEquals(-18000, get_time_zone_offset('-0500'));

		$this->assertEquals('Jan', getMonthName(1));
		$this->assertEquals('Sep', getMonthName(9, true));
		$this->assertEquals('September', getMonthName(9, false));

		/**
		 * The following functions are tested in DateTimeTest:
		 *  - zgap()
		 *  - zdate()
		 *  - ztime()
		 *  - getInternalDateTime()
		 *  - getDisplayDateTime()
		 *  - getTimeGap()
		 */
	}

	public function testGetEncodedEmailAddress()
	{
		$this->assertNotEquals('devops@rhymix.org', getEncodeEmailAddress('devops@rhymix.org'));
		$this->assertStringContainsString('&#X', getEncodeEmailAddress('devops@rhymix.org'));
	}

	public function testGetMicrotime()
	{
		$microtime1 = microtime(true);
		$microtime2 = getMicroTime();
		$microtime3 = microtime(true);

		$this->assertEquals('double', gettype($microtime2));
		$this->assertGreaterThanOrEqual($microtime1, $microtime2);
		$this->assertGreaterThanOrEqual($microtime2, $microtime3);
	}

	public function testDelObjectVars()
	{
		$target = (object)array('foo' => 1, 'bar' => 2, 'baz' => 3, 'rhymix' => 4);
		$delete = (object)array('bar' => 5, 'baz' => 6);
		$result = delObjectVars($target, $delete);

		// Check if the keys were deleted from the result.
		$this->assertTrue(isset($result->foo));
		$this->assertFalse(isset($result->bar));
		$this->assertFalse(isset($result->baz));
		$this->assertTrue(isset($result->rhymix));

		// Check if the keys are intact in the original target.
		$this->assertTrue(isset($target->bar));
		$this->assertTrue(isset($target->baz));
	}

	public function testGetDestroyXeVars()
	{
		// Test array. (Keys should be intact in the original target.)
		$target = array('foo' => 1, 'bar' => 2, 'xe_validator_id' => 3);
		$result = getDestroyXeVars($target);
		$this->assertFalse(isset($result['xe_validator_id']));
		$this->assertTrue(isset($target['xe_validator_id']));

		// Test object. (Keys should be deleted from the original target.)
		$target = (object)array('foo' => 1, 'bar' => 2, 'xe_validator_id' => 3);
		$result = getDestroyXeVars($target);
		$this->assertFalse(isset($result->xe_validator_id));
		$this->assertFalse(isset($target->xe_validator_id));
	}

	public function testGetNumberingPath()
	{
		$this->assertEquals('001/', getNumberingPath(1));
		$this->assertEquals('012/', getNumberingPath(12));
		$this->assertEquals('123/', getNumberingPath(123));
		$this->assertEquals('234/001/', getNumberingPath(1234));
		$this->assertEquals('345/012/', getNumberingPath(12345));
		$this->assertEquals('456/123/', getNumberingPath(123456));
		$this->assertEquals('567/234/001/', getNumberingPath(1234567));
		$this->assertEquals('678/345/012/', getNumberingPath(12345678));
		$this->assertEquals('789/456/123/', getNumberingPath(123456789));
	}

	public function testMysqlPre4HashPassword()
	{
		$this->assertEquals('5d2e19393cc5ef67', mysql_pre4_hash_password('password'));
		$this->assertEquals('25a4fb474e17c19a', mysql_pre4_hash_password('pass\'#word'));
	}

	public function testJsonEncode2()
	{
		$data = array('foo' => 1, 'bar' => 2, 'baz' => 3, 'rhymix' => 4);
		$this->assertEquals(json_encode($data), json_encode2($data));
	}

	public function TestIsCrawler()
	{
		$original_user_agent = $_SERVER['HTTP_USER_AGENT'];

		// Test automatic detection from User-Agent string.
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.1; rv:45.0) Gecko/20100101 Firefox/45.0';
		$this->assertFalse(isCrawler());
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
		$this->assertTrue(isCrawler());

		// Test manual detection.
		$this->assertTrue(isCrawler('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'));
		$this->assertTrue(isCrawler('Yeti/1.0 (NHN Corp.; http://help.naver.com/robots/)'));
		$this->assertFalse(isCrawler('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.1 Safari/537.36'));
		$this->assertFalse(isCrawler('Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25'));

		$_SERVER['HTTP_USER_AGENT'] = $original_user_agent;
	}

	public function testMiscUTF8Functions()
	{
		$this->assertEquals('&lt;img&gt;', url_decode('%3Cimg%3E'));
		$this->assertEquals('한글 % English', utf8RawUrlDecode('%uD55C%uAE00%20%25%20English'));
		$this->assertEquals('뷁', _code2utf(48577));

		$this->assertTrue(detectUTF8('라이믹스'));
		$this->assertTrue(detectUTF8(urlencode('라이믹스')));
		$this->assertTrue(detectUTF8('%87%a9%43%cd%ef', false, false));
		$this->assertFalse(detectUTF8(iconv('UTF-8', 'EUC-KR', '라이믹스')));
		$this->assertFalse(detectUTF8(chr(129) . chr(214) . chr(181) . chr(73) . chr(97)));
		$this->assertFalse(detectUTF8('%87%a9%43%cd%ef'));
		$this->assertEquals(mb_convert_encoding('라이믹스', 'UTF-8', 'CP949'), detectUTF8('라이믹스', true));
		$this->assertEquals('라이믹스', detectUTF8(iconv('UTF-8', 'EUC-KR', '라이믹스'), true));
	}

	public function testMiscSecurityFunctions()
	{
		/**
		 * TODO:
		 *  - stripEmbedTagForAdmin()
		 *  - checkCSRF()
		 */
	}

	public function testRecurciveExposureCheck()
	{
		/**
		 * TODO
		 */
	}

	public function testChangeValueInUrl()
	{
		/**
		 * TODO
		 */
	}
}
