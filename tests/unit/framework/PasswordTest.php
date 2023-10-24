<?php

class PasswordTest extends \Codeception\Test\Unit
{
	public function testIsValidAlgorithm()
	{
		$this->assertTrue(Rhymix\Framework\Password::isValidAlgorithm('bcrypt'));
		$this->assertTrue(Rhymix\Framework\Password::isValidAlgorithm('whirlpool,pbkdf2'));
		$this->assertTrue(Rhymix\Framework\Password::isValidAlgorithm(array('md5', 'sha1', 'md5')));

		$this->assertFalse(Rhymix\Framework\Password::isValidAlgorithm('bunga_bunga'));
		Rhymix\Framework\Password::addAlgorithm('bunga_bunga', '/bunga_bunga/', function($hash) { return 'bunga_bunga'; });
		$this->assertTrue(Rhymix\Framework\Password::isValidAlgorithm('bunga_bunga'));
	}

	public function testGetSupportedAlgorithms()
	{
		$algos = Rhymix\Framework\Password::getSupportedAlgorithms();
		$this->assertTrue(in_array('bcrypt', $algos));
		$this->assertTrue(in_array('pbkdf2', $algos));
		$this->assertTrue(in_array('md5', $algos));
	}

	public function testGetBestSupportedAlgorithm()
	{
		$algo = Rhymix\Framework\Password::getBestSupportedAlgorithm();
		$this->assertTrue($algo === 'bcrypt' || $algo === 'pbkdf2');
	}

	public function testGetDefaultAlgorithm()
	{
		$algo = Rhymix\Framework\Password::getDefaultAlgorithm();
		$this->assertTrue($algo === 'argon2id' || $algo === 'bcrypt' || $algo === 'pbkdf2' || $algo === 'md5');
	}

	public function testGetWorkFactor()
	{
		$work_factor = $algo = Rhymix\Framework\Password::getWorkFactor();
		$this->assertTrue($work_factor >= 4);
		$this->assertTrue($work_factor <= 31);
	}

	public function testGetRandomPassword()
	{
		$password = Rhymix\Framework\Password::getRandomPassword(16);
		$this->assertEquals(16, strlen($password));
		$this->assertRegexp('/[a-z]/', $password);
		$this->assertRegexp('/[A-Z]/', $password);
		$this->assertRegexp('/[0-9]/', $password);
		$this->assertRegexp('/[^a-zA-Z0-9]/', $password);
	}

	public function testHashPassword()
	{
		$password = Rhymix\Framework\Security::getRandom(32);
		$this->assertEquals(md5($password), Rhymix\Framework\Password::hashPassword($password, 'md5'));
		$this->assertEquals(md5(sha1(md5($password))), Rhymix\Framework\Password::hashPassword($password, 'md5,sha1,md5'));
		$this->assertEquals(hash('whirlpool', $password), Rhymix\Framework\Password::hashPassword($password, 'whirlpool'));
		$this->assertEquals('5d2e19393cc5ef67', Rhymix\Framework\Password::hashPassword('password', 'mysql_old_password'));
	}

	public function testCheckPassword()
	{
		$password = Rhymix\Framework\Security::getRandom(32);

		$algos = array('whirlpool', 'ripemd160', 'bcrypt');
		$hash = Rhymix\Framework\Password::hashPassword($password, $algos);
		$this->assertRegExp('/^\$2y\$/', $hash);
		$this->assertEquals(60, strlen($hash));
		$this->assertTrue(Rhymix\Framework\Password::checkPassword($password, $hash, $algos));

		$algos = array('sha384', 'pbkdf2');
		$hash = Rhymix\Framework\Password::hashPassword($password, $algos);
		$this->assertRegExp('/^(sha256|sha512):[0-9]+:/', $hash);
		$this->assertEquals(60, strlen($hash));
		$this->assertTrue(Rhymix\Framework\Password::checkPassword($password, $hash, $algos));

		$algos = array('sha1', 'portable');
		$hash = Rhymix\Framework\Password::hashPassword($password, $algos);
		$this->assertRegExp('/^\$P\$/', $hash);
		$this->assertEquals(34, strlen($hash));
		$this->assertTrue(Rhymix\Framework\Password::checkPassword($password, $hash, $algos));

		foreach (array('drupal', 'joomla', 'kimsqrb', 'mysql_old_password', 'mysql_new_password', 'mssql_pwdencrypt') as $algo)
		{
			$hash = Rhymix\Framework\Password::hashPassword($password, $algo);
			$this->assertTrue(Rhymix\Framework\Password::checkPassword($password, $hash, $algo));
			$this->assertFalse(Rhymix\Framework\Password::checkPassword($password, $hash . 'x', $algo));
		}
	}

	public function testCheckAlgorithm()
	{
		$password = Rhymix\Framework\Security::getRandom(32, 'hex');

		$this->assertEquals(array('md5', 'md5,sha1,md5'), Rhymix\Framework\Password::checkAlgorithm($password));
		$this->assertEquals(array('sha512', 'whirlpool'), Rhymix\Framework\Password::checkAlgorithm(hash('sha512', $password)));

		$hash = '$2y$10$VkxBdEBTZ1HyLluZPjXCjuFffw0a6alZlbb733CF/zA22HDpBNsMm';
		$this->assertEquals(array('bcrypt'), Rhymix\Framework\Password::checkAlgorithm($hash));

		$hash = 'sha512:0008192:hoXcLXQzIiIJ:ElokybdRf+i512M4/4PIdEiSDgZ8f0uL';
		$this->assertEquals(array('pbkdf2'), Rhymix\Framework\Password::checkAlgorithm($hash));
	}

	public function testCheckWorkFactor()
	{
		$hash = '$2y$10$VkxBdEBTZ1HyLluZPjXCjuFffw0a6alZlbb733CF/zA22HDpBNsMm';
		$this->assertEquals(10, Rhymix\Framework\Password::checkWorkFactor($hash));

		$hash = 'sha512:0008192:hoXcLXQzIiIJ:ElokybdRf+i512M4/4PIdEiSDgZ8f0uL';
		$this->assertEquals(8, Rhymix\Framework\Password::checkWorkFactor($hash));

		$hash = '5f4dcc3b5aa765d61d8327deb882cf99';
		$this->assertEquals(0, Rhymix\Framework\Password::checkWorkFactor($hash));
	}

	public function testBcrypt()
	{
		$password = 'password';
		$hash = '$2y$10$VkxBdEBTZ1HyLluZPjXCjuFffw0a6alZlbb733CF/zA22HDpBNsMm';
		$this->assertEquals($hash, Rhymix\Framework\Password::bcrypt($password, $hash));
	}

	public function testPBKDF2()
	{
		$password = 'password';
		$salt = 'rtmIxdEUoWUk';
		$hash = 'sha512:0016384:rtmIxdEUoWUk:1hrwGP3ScWvxslnqNFqyhM6Ddn4iYrwf';
		$this->assertEquals($hash, Rhymix\Framework\Password::pbkdf2($password, $salt, 'sha512', 16384, 24));

		$hash = 'sha512:16384:rtmIxdEUoWUk:1hrwGP3ScWvxslnqNFqyhM6Ddn4iYrwf';
		$this->assertEquals($hash, Rhymix\Framework\Password::pbkdf2($password, $salt, 'sha512', 16384, 24, 5));

		$salt = 'KpnA8ZAxvig32n7p2PnEjx4NN7gPpUQm';
		$hash = 'sha1:12000:KpnA8ZAxvig32n7p2PnEjx4NN7gPpUQm:TeILMSF8ao/NVJ4wdk7lXDKQre9TUCht';
		$this->assertEquals($hash, Rhymix\Framework\Password::pbkdf2($password, $salt, 'sha1', 12000, 24, 5));
	}

	public function testCountEntropyBits()
	{
		$this->assertEquals(0, Rhymix\Framework\Password::countEntropyBits(''));
		$this->assertEquals(13, round(Rhymix\Framework\Password::countEntropyBits('1234')));
		$this->assertEquals(20, round(Rhymix\Framework\Password::countEntropyBits('123456')));
		$this->assertEquals(28, round(Rhymix\Framework\Password::countEntropyBits('rhymix')));
		$this->assertEquals(52, round(Rhymix\Framework\Password::countEntropyBits('rhymix1234')));
		$this->assertEquals(60, round(Rhymix\Framework\Password::countEntropyBits('RhymiX1234')));
		$this->assertEquals(125, round(Rhymix\Framework\Password::countEntropyBits('Rhymix_is*the%Best!')));
	}
}
