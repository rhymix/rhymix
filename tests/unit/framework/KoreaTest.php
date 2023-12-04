<?php

class KoreaTest extends \Codeception\Test\Unit
{
	public function testFormatPhoneNumber()
	{
		$this->assertEquals('1588-0000', Rhymix\Framework\Korea::formatPhoneNumber('1588-0000'));
		$this->assertEquals('02-345-6789', Rhymix\Framework\Korea::formatPhoneNumber('+82 23456789'));
		$this->assertEquals('02-3000-5000', Rhymix\Framework\Korea::formatPhoneNumber('0230005000'));
		$this->assertEquals('031-222-3333', Rhymix\Framework\Korea::formatPhoneNumber('82-0312-2233-33'));
		$this->assertEquals('031-2222-3333', Rhymix\Framework\Korea::formatPhoneNumber('03122223333'));
		$this->assertEquals('011-444-5555', Rhymix\Framework\Korea::formatPhoneNumber('011 444 5555'));
		$this->assertEquals('010-6666-7777', Rhymix\Framework\Korea::formatPhoneNumber('82+1066667777'));
		$this->assertEquals('0303-456-7890', Rhymix\Framework\Korea::formatPhoneNumber('03034567890'));
		$this->assertEquals('0505-987-6543', Rhymix\Framework\Korea::formatPhoneNumber('050-5987-6543'));
		$this->assertEquals('0303-4567-8900', Rhymix\Framework\Korea::formatPhoneNumber('030345678900'));
		$this->assertEquals('0505-9876-5432', Rhymix\Framework\Korea::formatPhoneNumber('050-5987-65432'));
		$this->assertEquals('070-7432-1000', Rhymix\Framework\Korea::formatPhoneNumber('0707-432-1000'));
	}

	public function testIsValidPhoneNumber()
	{
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('1588-0000'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('02-345-6789'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('+82-2-345-6789'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('+82-02-2345-6789'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('053-444-5555'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('053-4444-5555'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('011-444-5555'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('010-4444-5555'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('0303-4444-5555'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('0505-4444-5555'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('0507-1234-5678'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidPhoneNumber('0506-123-4567'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidPhoneNumber('010-4444-55555'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidPhoneNumber('010-1234-5678'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidPhoneNumber('02-123-4567'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidPhoneNumber('02-123456'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidPhoneNumber('03-456-7890'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidPhoneNumber('090-9876-5432'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidPhoneNumber('0303-0000-5432'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidPhoneNumber('0505-9876-543210'));
	}

	public function testIsValidMobilePhoneNumber()
	{
		$this->assertTrue(Rhymix\Framework\Korea::isValidMobilePhoneNumber('011-345-6789'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidMobilePhoneNumber('010-2345-6789'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidMobilePhoneNumber('+82-11-345-6789'));
		$this->assertTrue(Rhymix\Framework\Korea::isValidMobilePhoneNumber('82 010-2345-6789'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidMobilePhoneNumber('010-1111-1111'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidMobilePhoneNumber('02-345-6789'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidMobilePhoneNumber('063-9876-5432'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidMobilePhoneNumber('070-7654-3210'));
	}

	public function testIsValidJuminNumber()
	{
		// These numbers are fake.
		$this->assertTrue(Rhymix\Framework\Korea::isValidJuminNumber('123456-3456787'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidJuminNumber('123456-3456788'));
	}

	public function testIsValidCorporationNumber()
	{
		// These numbers are fake.
		$this->assertTrue(Rhymix\Framework\Korea::isValidCorporationNumber('123456-0123453'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidCorporationNumber('123456-0123454'));
	}

	public function testIsValidBusinessNumber()
	{
		// These numbers are fake.
		$this->assertTrue(Rhymix\Framework\Korea::isValidBusinessNumber('123-45-67891'));
		$this->assertFalse(Rhymix\Framework\Korea::isValidBusinessNumber('123-45-67892'));
	}

	public function testIsKoreanIP()
	{
		// Private IP ranges.
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanIP('10.12.34.210'));
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanIP('127.0.123.45'));
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanIP('192.168.10.1'));
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanIP('::1'));

		// Korean IP ranges.
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanIP('115.71.233.0'));
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanIP('114.207.12.3'));
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanIP('2001:0320::1'));
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanIP('2407:B800::F'));

		// Foreign IP ranges.
		$this->assertFalse(Rhymix\Framework\Korea::isKoreanIP('216.58.197.0'));
		$this->assertFalse(Rhymix\Framework\Korea::isKoreanIP('170.14.168.0'));
		$this->assertFalse(Rhymix\Framework\Korea::isKoreanIP('2001:41d0:8:e8ad::1'));
		$this->assertFalse(Rhymix\Framework\Korea::isKoreanIP('2404:6800:4005:802::200e'));
	}

	public function testIsKoreanEmailAddress()
	{
		// Test Korean portals.
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanEmailAddress('test@naver.com'));
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanEmailAddress('test@hanmail.net'));
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanEmailAddress('test@worksmobile.com'));

		// Test foreign portals.
		$this->assertFalse(Rhymix\Framework\Korea::isKoreanEmailAddress('test@gmail.com'));
		$this->assertFalse(Rhymix\Framework\Korea::isKoreanEmailAddress('test@hotmail.com'));
		$this->assertFalse(Rhymix\Framework\Korea::isKoreanEmailAddress('test@yahoo.com'));

		// Test third-party MX services.
		$this->assertTrue(Rhymix\Framework\Korea::isKoreanEmailAddress('test@woorimail.com'));
		$this->assertFalse(Rhymix\Framework\Korea::isKoreanEmailAddress('test@rhymix.org'));
	}
}
