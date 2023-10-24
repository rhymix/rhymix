<?php

class SecurityTest extends \Codeception\Test\Unit
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

	public function testEncryption()
	{
		$plaintext = Rhymix\Framework\Security::getRandom();
		config('crypto.encryption_key', Rhymix\Framework\Security::getRandom());

		// Encryption with default key.
		$encrypted = Rhymix\Framework\Security::encrypt($plaintext);
		$this->assertNotEquals(false, $encrypted);
		$decrypted = Rhymix\Framework\Security::decrypt($encrypted);
		$this->assertEquals($plaintext, $decrypted);

		// Encryption with custom key.
		$key = Rhymix\Framework\Security::getRandom();
		$encrypted = Rhymix\Framework\Security::encrypt($plaintext, $key);
		$this->assertNotEquals(false, $encrypted);
		$decrypted = Rhymix\Framework\Security::decrypt($encrypted, $key);
		$this->assertEquals($plaintext, $decrypted);

		// Test invalid ciphertext.
		$decrypted = Rhymix\Framework\Security::decrypt('1234' . substr($encrypted, 4));
		$this->assertEquals(false, $decrypted);
		$decrypted = Rhymix\Framework\Security::decrypt(substr($encrypted, strlen($encrypted) - 4) . 'abcd');
		$this->assertEquals(false, $decrypted);
		$decrypted = Rhymix\Framework\Security::decrypt($plaintext);
		$this->assertEquals(false, $decrypted);
	}

	public function testSignature()
	{
		$plaintext = Rhymix\Framework\Security::getRandom();
		config('crypto.authentication_key', Rhymix\Framework\Security::getRandom());

		$signature = Rhymix\Framework\Security::createSignature($plaintext);
		$this->assertRegexp('/^[a-zA-Z0-9-_]{40}$/', $signature);
		$this->assertEquals(true, Rhymix\Framework\Security::verifySignature($plaintext, $signature));
		$this->assertEquals(false, Rhymix\Framework\Security::verifySignature($plaintext, $signature . 'x'));
		$this->assertEquals(false, Rhymix\Framework\Security::verifySignature($plaintext, 'x' . $signature));
	}

	public function testGetRandom()
	{
		$this->assertRegExp('/^[0-9a-zA-Z]{32}$/', Rhymix\Framework\Security::getRandom());
		$this->assertRegExp('/^[0-9a-zA-Z]{256}$/', Rhymix\Framework\Security::getRandom(256));
		$this->assertRegExp('/^[0-9a-zA-Z]{16}$/', Rhymix\Framework\Security::getRandom(16, 'alnum'));
		$this->assertRegExp('/^[0-9a-f]{16}$/', Rhymix\Framework\Security::getRandom(16, 'hex'));
		$this->assertRegExp('/^[\x21-\x7e]{16}$/', Rhymix\Framework\Security::getRandom(16, 'printable'));
	}

	public function testGetRandomNumber()
	{
		for ($i = 0; $i < 10; $i++)
		{
			$min = mt_rand(0, 10000);
			$max = $min + mt_rand(0, 10000);
			$random = Rhymix\Framework\Security::getRandomNumber($min, $max);
			$this->assertTrue($random >= $min && $random <= $max);
		}
	}

	public function testGetRandomUUID()
	{
		$regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
		for ($i = 0; $i < 10; $i++)
		{
			$this->assertRegExp($regex, Rhymix\Framework\Security::getRandomUUID());
		}
		for ($i = 0; $i < 10; $i++)
		{
			$this->assertRegExp($regex, Rhymix\Framework\Security::getRandomUUID(4));
		}

		$regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
		for ($i = 0; $i < 10; $i++)
		{
			$this->assertRegExp($regex, Rhymix\Framework\Security::getRandomUUID(7));
		}
	}

	public function testCompareStrings()
	{
		$this->assertTrue(Rhymix\Framework\Security::compareStrings('foobar', 'foobar'));
		$this->assertFalse(Rhymix\Framework\Security::compareStrings('foobar', 'foobar*'));
		$this->assertFalse(Rhymix\Framework\Security::compareStrings('foo', 'bar'));
	}

	public function testCheckCSRF()
	{
		$error_reporting = error_reporting(0);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_REFERER'] = '';
		$_SERVER['HTTP_X_CSRF_TOKEN'] = '';
		$this->assertFalse(Rhymix\Framework\Security::checkCSRF());
		$_SERVER['HTTP_X_CSRF_TOKEN'] = Rhymix\Framework\Session::createToken();
		$this->assertTrue(Rhymix\Framework\Security::checkCSRF());

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['HTTP_REFERER'] = '';
		$_SERVER['HTTP_X_CSRF_TOKEN'] = '';
		$this->assertFalse(Rhymix\Framework\Security::checkCSRF());
		$_SERVER['HTTP_X_CSRF_TOKEN'] = Rhymix\Framework\Session::createToken();
		$this->assertTrue(Rhymix\Framework\Security::checkCSRF());

		$_SERVER['HTTP_REFERER'] = 'http://www.foobar.com/';
		$_SERVER['HTTP_X_CSRF_TOKEN'] = '';
		$this->assertFalse(Rhymix\Framework\Security::checkCSRF());

		$_SERVER['HTTP_REFERER'] = 'http://www.rhymix.org/foo/bar';
		$_SERVER['HTTP_X_CSRF_TOKEN'] = '';
		$this->assertTrue(Rhymix\Framework\Security::checkCSRF());
		$_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid value';
		$this->assertFalse(Rhymix\Framework\Security::checkCSRF());

		$_SERVER['HTTP_ORIGIN'] = 'http://www.rhymix.org';
		$_SERVER['HTTP_REFERER'] = 'http://www.foobar.com';
		$_SERVER['HTTP_X_CSRF_TOKEN'] = '';
		$this->assertTrue(Rhymix\Framework\Security::checkCSRF());
		$_SERVER['HTTP_REFERER'] = '';
		$this->assertTrue(Rhymix\Framework\Security::checkCSRF());
		$_SERVER['HTTP_ORIGIN'] = 'http://www.foobar.com';
		$this->assertFalse(Rhymix\Framework\Security::checkCSRF());
		$_SERVER['HTTP_ORIGIN'] = 'null';
		$this->assertFalse(Rhymix\Framework\Security::checkCSRF());

		$_SERVER['HTTP_REFERER'] = '';
		$_SERVER['HTTP_X_CSRF_TOKEN'] = '';
		$this->assertTrue(Rhymix\Framework\Security::checkCSRF('http://www.rhymix.org/'));

		error_reporting($error_reporting);
	}

	public function testCheckXXE()
	{
		$xml = '<methodCall></methodCall>';
		$this->assertTrue(Rhymix\Framework\Security::checkXXE($xml));

		$xml = '<?xml version="1.0" encoding="UTF-8"?><methodCall></methodCall>';
		$this->assertTrue(Rhymix\Framework\Security::checkXXE($xml));

		$xml = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE foo><methodCall attr="value"></methodCall>';
		$this->assertTrue(Rhymix\Framework\Security::checkXXE($xml));

		$xml = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE foo><whatever></whatever>';
		$this->assertFalse(Rhymix\Framework\Security::checkXXE($xml));

		$xml = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE foo>';
		$this->assertFalse(Rhymix\Framework\Security::checkXXE($xml));

		$xml = '<?xml version="1.0" encoding="UTF-8"?><!ENTITY xxe SYSTEM "http://www.attacker.com/text.txt"><methodCall></methodCall>';
		$this->assertFalse(Rhymix\Framework\Security::checkXXE($xml));

		$xml = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE foo [<!ELEMENT foo ANY><!ENTITY xxe SYSTEM "file:///etc/passwd" >]><fault></fault>';
		$this->assertFalse(Rhymix\Framework\Security::checkXXE($xml));
	}
}
