<?php

class SessionTest extends \Codeception\TestCase\Test
{
	public function _before()
	{
		Rhymix\Framework\Config::set('session.delay', false);
		Rhymix\Framework\Session::close();
		session_id('rhymix-test-session');
		$_SESSION = array();
		$_COOKIE = array();
	}
	
	public function _after()
	{
		Rhymix\Framework\Config::set('session.delay', false);
		Rhymix\Framework\Session::close();
		session_id('rhymix-test-session');
		$_SESSION = array();
		$_COOKIE = array();
	}
	
	public function _failed()
	{
		Rhymix\Framework\Config::set('session.delay', false);
		Rhymix\Framework\Session::close();
		session_id('rhymix-test-session');
		$_SESSION = array();
		$_COOKIE = array();
	}
	
	public function testGetSet()
	{
		$this->assertFalse(isset($_SESSION['foo']['bar']));
		$this->assertNull(Rhymix\Framework\Session::get('foo.bar'));
		Rhymix\Framework\Session::set('foo.bar', 'bazz');
		$this->assertTrue(isset($_SESSION['foo']['bar']));
		$this->assertEquals('bazz', Rhymix\Framework\Session::get('foo.bar'));
		Rhymix\Framework\Session::set('foo.baz', 'bazzzz');
		$this->assertEquals(array('bar' => 'bazz', 'baz' => 'bazzzz'), Rhymix\Framework\Session::get('foo'));
		$this->assertEquals(array('bar' => 'bazz', 'baz' => 'bazzzz'), $_SESSION['foo']);
	}
	
	public function testStart()
	{
		Rhymix\Framework\Config::set('session.use_keys', true);
		
		// Test normal start.
		$this->assertTrue(@Rhymix\Framework\Session::start());
		$this->assertTrue(isset($_COOKIE['rx_sesskey1']));
		$this->assertTrue(isset($_COOKIE['rx_sesskey2']));
		$this->assertNotEmpty($_SESSION['RHYMIX']['secret']);
		$this->assertEquals($_SESSION['RHYMIX']['keys']['www.rhymix.org']['key1'], $_COOKIE['rx_sesskey1']);
		$this->assertEquals($_SESSION['RHYMIX']['keys']['www.rhymix.org']['key2'], $_COOKIE['rx_sesskey2']);
		$this->assertEquals(0, $_SESSION['RHYMIX']['keys']['www.rhymix.org']['trusted']);
		$session_secret = $_SESSION['RHYMIX']['secret'];
		Rhymix\Framework\Session::close();
		
		// Test normal restart.
		$this->assertTrue(@Rhymix\Framework\Session::start());
		$this->assertEquals($session_secret, $_SESSION['RHYMIX']['secret']);
		$session_secret = $_SESSION['RHYMIX']['secret'];
		Rhymix\Framework\Session::close();
		
		// Test missing HTTP key.
		unset($_COOKIE['rx_sesskey1']);
		$this->assertTrue(@Rhymix\Framework\Session::start());
		$this->assertNotEquals($session_secret, $_SESSION['RHYMIX']['secret']);
		$session_secret = $_SESSION['RHYMIX']['secret'];
		Rhymix\Framework\Session::close();
		
		// Test missing HTTPS key.
		unset($_COOKIE['rx_sesskey2']);
		$this->assertTrue(@Rhymix\Framework\Session::start());
		$this->assertNotEquals($session_secret, $_SESSION['RHYMIX']['secret']);
		$session_secret = $_SESSION['RHYMIX']['secret'];
		Rhymix\Framework\Session::close();
		
		// Test invalid HTTP key.
		$_COOKIE['rx_sesskey1'] = substr(md5(mt_rand()), 0, 24);
		$this->assertTrue(@Rhymix\Framework\Session::start());
		$this->assertNotEquals($session_secret, $_SESSION['RHYMIX']['secret']);
		$session_secret = $_SESSION['RHYMIX']['secret'];
		Rhymix\Framework\Session::close();
		
		// Test invalid HTTPS key.
		$_COOKIE['rx_sesskey2'] = substr(md5(mt_rand()), 0, 24);
		$this->assertTrue(@Rhymix\Framework\Session::start());
		$this->assertNotEquals($session_secret, $_SESSION['RHYMIX']['secret']);
		$session_secret = $_SESSION['RHYMIX']['secret'];
		Rhymix\Framework\Session::close();
		
		// Test initial transition from HTTP to HTTPS.
		session_start();
		unset($_SESSION['RHYMIX']['keys']['www.rhymix.org']['key2']);
		unset($_COOKIE['rx_sesskey2']);
		session_write_close();
		$this->assertTrue(@Rhymix\Framework\Session::start());
		$this->assertEquals($session_secret, $_SESSION['RHYMIX']['secret']);
		$session_secret = $_SESSION['RHYMIX']['secret'];
		Rhymix\Framework\Session::close();
	}
	
	public function testCheckStart()
	{
		Rhymix\Framework\Config::set('session.delay', true);
		
		$_SESSION = array();
		unset($_COOKIE['PHPSESSID']);
		$this->assertFalse(@Rhymix\Framework\Session::start());
		$this->assertFalse(Rhymix\Framework\Session::isStarted());
		$this->assertFalse(Rhymix\Framework\Session::checkStart());
		$this->assertFalse(Rhymix\Framework\Session::isStarted());
		Rhymix\Framework\Session::close();
		
		$_SESSION['foo'] = 'bar';
		$this->assertTrue(@Rhymix\Framework\Session::checkStart());
		$this->assertTrue(Rhymix\Framework\Session::isStarted());
		$this->assertEquals('bar', $_SESSION['foo']);
		$this->assertEquals('bar', Rhymix\Framework\Session::get('foo'));
		Rhymix\Framework\Session::close();
		
		$_SESSION = array();
		unset($_COOKIE['PHPSESSID']);
		$this->assertTrue(@Rhymix\Framework\Session::checkStart(true));
		$this->assertTrue(Rhymix\Framework\Session::isStarted());
		Rhymix\Framework\Session::close();
		
		$_SESSION = array();
		unset($_COOKIE['PHPSESSID']);
		$this->assertTrue(@Rhymix\Framework\Session::start(true));
		$this->assertTrue(Rhymix\Framework\Session::isStarted());
	}
	
	public function testCheckSSO()
	{
		$this->assertNull(Rhymix\Framework\Session::checkSSO(new stdClass));
	}
	
	public function testRefresh()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		
		@Rhymix\Framework\Session::start();
		$session_secret = $_SESSION['RHYMIX']['secret'];
		$key1 = $_SESSION['RHYMIX']['keys']['www.rhymix.org']['key1'];
		$key2 = $_SESSION['RHYMIX']['keys']['www.rhymix.org']['key2'];
		$_SESSION['RHYMIX']['keys']['www.rhymix.org']['key1_time'] = time() - 3600;
		$_SESSION['RHYMIX']['keys']['www.rhymix.org']['key2_time'] = time() - 3600;
		Rhymix\Framework\Session::close();
		
		@Rhymix\Framework\Session::start();
		$this->assertNotEquals($key1, $_SESSION['RHYMIX']['keys']['www.rhymix.org']['key1']);
		$this->assertNotEquals($key2, $_SESSION['RHYMIX']['keys']['www.rhymix.org']['key2']);
		$this->assertEquals($key1, $_SESSION['RHYMIX']['keys']['www.rhymix.org']['key1_prev']);
		$this->assertEquals($key2, $_SESSION['RHYMIX']['keys']['www.rhymix.org']['key2_prev']);
		$this->assertEquals($session_secret, $_SESSION['RHYMIX']['secret']);
		Rhymix\Framework\Session::close();
	}
	
	public function testClose()
	{
		@Rhymix\Framework\Session::start();
		$this->assertEquals(\PHP_SESSION_ACTIVE, session_status());
		Rhymix\Framework\Session::close();
		$this->assertEquals(\PHP_SESSION_NONE, session_status());
	}
	
	public function testDestroy()
	{
		@Rhymix\Framework\Session::start();
		$this->assertTrue(isset($_SESSION['RHYMIX']));
		Rhymix\Framework\Session::destroy();
		$this->assertFalse(isset($_SESSION['RHYMIX']));
	}
	
	public function testLoginLogout()
	{
		@Rhymix\Framework\Session::start();
		$this->assertFalse($_SESSION['RHYMIX']['login']);
		$this->assertFalse($_SESSION['member_srl']);
		$this->assertFalse($_SESSION['is_logged']);
		
		Rhymix\Framework\Session::login(42);
		$this->assertEquals(42, $_SESSION['RHYMIX']['login']);
		$this->assertEquals(42, $_SESSION['member_srl']);
		$this->assertTrue($_SESSION['is_logged']);
		
		Rhymix\Framework\Session::logout();
		$this->assertFalse(isset($_SESSION['RHYMIX']['login']));
		$this->assertFalse(isset($_SESSION['member_srl']));
		$this->assertFalse(isset($_SESSION['is_logged']));
		
		Rhymix\Framework\Session::close();
	}
	
	public function testIsStarted()
	{
		$this->assertFalse(Rhymix\Framework\Session::isStarted());
		@Rhymix\Framework\Session::start();
		$this->assertTrue(Rhymix\Framework\Session::isStarted());
		Rhymix\Framework\Session::close();
		$this->assertFalse(Rhymix\Framework\Session::isStarted());
	}
	
	public function testIsMember()
	{
		@Rhymix\Framework\Session::start();
		$this->assertFalse(Rhymix\Framework\Session::isMember());
		
		Rhymix\Framework\Session::login(42);
		$this->assertTrue(Rhymix\Framework\Session::isMember());
		
		Rhymix\Framework\Session::close();
	}
	
	public function testIsAdmin()
	{
		@Rhymix\Framework\Session::start();
		$this->assertFalse(Rhymix\Framework\Session::isAdmin());
		
		Rhymix\Framework\Session::login(42);
		$this->assertFalse(Rhymix\Framework\Session::isAdmin());
		
		Rhymix\Framework\Session::setMemberInfo((object)array('member_srl' => 42, 'is_admin' => 'Y'));
		$this->assertTrue(Rhymix\Framework\Session::isAdmin());
		
		Rhymix\Framework\Session::setMemberInfo((object)array('member_srl' => 99, 'is_admin' => 'Y'));
		$this->assertFalse(Rhymix\Framework\Session::isAdmin());
		
		Rhymix\Framework\Session::close();
	}
	
	public function testIsTrusted()
	{
		@Rhymix\Framework\Session::start();
		
		$_SESSION['RHYMIX']['keys']['www.rhymix.org']['trusted'] = 0;
		$this->assertFalse(Rhymix\Framework\Session::isTrusted());
		$_SESSION['RHYMIX']['keys']['www.rhymix.org']['trusted'] = time() + 300;
		$this->assertTrue(Rhymix\Framework\Session::isTrusted());
		
		Rhymix\Framework\Session::close();
	}
	
	public function testIsValid()
	{
		@Rhymix\Framework\Session::start();

		$member_srl = 4;
		$this->assertTrue(Rhymix\Framework\Session::login($member_srl));
		$validity_info = Rhymix\Framework\Session::getValidityInfo($member_srl);
		$this->assertTrue(is_object($validity_info));
		$this->assertTrue(isset($validity_info->invalid_before));
		
		$validity_info->invalid_before = time() - 3600;
		$this->assertTrue(Rhymix\Framework\Session::setValidityInfo($member_srl, $validity_info));
		$this->assertTrue(Rhymix\Framework\Session::isValid());
		
		$validity_info->invalid_before = time() + 300;
		$this->assertTrue(Rhymix\Framework\Session::setValidityInfo($member_srl, $validity_info));
		$this->assertFalse(@Rhymix\Framework\Session::isValid());
		
		$validity_info->invalid_before = time() - 900;
		$this->assertTrue(Rhymix\Framework\Session::setValidityInfo($member_srl, $validity_info));
		$this->assertTrue(Rhymix\Framework\Session::isValid());
		
		Rhymix\Framework\Session::close();
	}
	
	public function testGetMemberSrl()
	{
		@Rhymix\Framework\Session::start();
		$this->assertEquals(false, Rhymix\Framework\Session::getMemberSrl());
		
		Rhymix\Framework\Session::login(42);
		$this->assertEquals(42, Rhymix\Framework\Session::getMemberSrl());
		
		Rhymix\Framework\Session::close();
	}
	
	public function testGetMemberInfo()
	{
		@Rhymix\Framework\Session::start();
		$this->assertEquals(false, Rhymix\Framework\Session::getMemberInfo());
		
		Rhymix\Framework\Session::login(42);
		$this->assertEquals(false, Rhymix\Framework\Session::getMemberInfo());
		
		Rhymix\Framework\Session::setMemberInfo((object)array('member_srl' => 42));
		$this->assertEquals((object)array('member_srl' => 42), Rhymix\Framework\Session::getMemberInfo());
		
		Rhymix\Framework\Session::setMemberInfo((object)array('member_srl' => 99, 'is_admin' => 'Y'));
		$this->assertEquals(false, Rhymix\Framework\Session::getMemberInfo());
		
		Rhymix\Framework\Session::close();
	}
	
	public function testGetSetLanguage()
	{
		@Rhymix\Framework\Session::start();
		$this->assertEquals(\Context::getLangType(), Rhymix\Framework\Session::getLanguage());
		
		Rhymix\Framework\Session::setLanguage('ja');
		$this->assertEquals('ja', Rhymix\Framework\Session::getLanguage());
		
		Rhymix\Framework\Session::close();
	}
	
	public function testGetSetTimezone()
	{
		@Rhymix\Framework\Session::start();
		$this->assertEquals(config('locale.default_timezone'), Rhymix\Framework\Session::getTimezone());
		
		Rhymix\Framework\Session::setTimezone('Asia/Beijing');
		$this->assertEquals('Asia/Beijing', Rhymix\Framework\Session::getTimezone());
		
		Rhymix\Framework\Session::close();
	}
	
	public function testTokens()
	{
		@Rhymix\Framework\Session::start();
		
		$token1 = Rhymix\Framework\Session::createToken();
		$this->assertTrue(ctype_alnum($token1));
		$this->assertEquals(16, strlen($token1));
		$this->assertTrue(Rhymix\Framework\Session::verifyToken($token1));
		$this->assertFalse(Rhymix\Framework\Session::verifyToken(strrev($token1)));
		
		$token2 = Rhymix\Framework\Session::createToken('/my/key');
		$this->assertTrue(Rhymix\Framework\Session::verifyToken($token2, '/my/key'));
		$this->assertFalse(Rhymix\Framework\Session::verifyToken($token2));
		$this->assertFalse(Rhymix\Framework\Session::verifyToken($token2, '/wrong/key'));
		$this->assertFalse(Rhymix\Framework\Session::verifyToken(strrev($token2)));
		
		$token3 = Rhymix\Framework\Session::getGenericToken();
		$this->assertEquals(16, strlen($token3));
		$this->assertTrue(Rhymix\Framework\Session::verifyToken($token3));
		$this->assertTrue(Rhymix\Framework\Session::verifyToken($token3, ''));
		$this->assertFalse(Rhymix\Framework\Session::verifyToken($token3, '/wrong/key'));
		
		Rhymix\Framework\Session::destroy();
		$this->assertFalse(Rhymix\Framework\Session::verifyToken($token1));
		$this->assertFalse(Rhymix\Framework\Session::verifyToken($token, '/my/key'));
		$this->assertFalse(Rhymix\Framework\Session::getGenericToken());
	}
	
	public function testEncryption()
	{
		@Rhymix\Framework\Session::start();
		
		$plaintext = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
		$ciphertext = Rhymix\Framework\Session::encrypt($plaintext);
		$this->assertNotEquals(false, $ciphertext);
		$this->assertEquals($plaintext, Rhymix\Framework\Session::decrypt($ciphertext));
		
		Rhymix\Framework\Session::destroy();
		$this->assertFalse(Rhymix\Framework\Session::decrypt($ciphertext));
		
		@Rhymix\Framework\Session::start();
		$this->assertFalse(Rhymix\Framework\Session::decrypt($ciphertext));
		
		Rhymix\Framework\Session::close();
	}
}
