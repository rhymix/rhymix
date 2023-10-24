<?php

class DateTimeTest extends \Codeception\Test\Unit
{
	private $old_timezone;

	public function _before()
	{
		// Add some dummy data to system configuration. Asia/Seoul offset is 32400.
		Rhymix\Framework\Config::set('locale.default_timezone', 'Asia/Seoul');
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
		Context::getInstance();
		Context::set('_default_timezone', $GLOBALS['_time_zone'] = 'Asia/Seoul');

		// Set PHP time zone to the internal time zone.
		$this->old_timezone = @date_default_timezone_get();
		date_default_timezone_set('Etc/GMT-3');
	}

	public function _after()
	{
		// Restore the default and internal timezone.
		Rhymix\Framework\Config::set('locale.default_timezone', 'Asia/Seoul');
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
		Context::set('_default_timezone', $GLOBALS['_time_zone'] = 'Asia/Seoul');

		// Restore the old timezone.
		date_default_timezone_set($this->old_timezone);
	}

	public function testZgap()
	{
		// Test zgap() when the current user's time zone is different from the system default.
		$_SESSION['RHYMIX']['timezone'] = 'Etc/UTC';
		$this->assertEquals(-10800, zgap());

		// Test zgap() when the current user's time zone is the same as the system default.
		unset($_SESSION['RHYMIX']['timezone']);
		$this->assertEquals(21600, zgap());
	}

	public function testZtime()
	{
		$timestamp = 1454000000;

		// Test ztime() when the internal time zone is different from the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
		$this->assertEquals($timestamp, ztime('20160128195320'));

		// Test ztime() when the internal time zone is the same as the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 32400);
		$this->assertEquals($timestamp, ztime('20160129015320'));

		// Test ztime() with alternative formats.
		$this->assertEquals(1576555660, ztime('2019-12-17 13:07:40'));
		$this->assertEquals(1576555660, ztime('2019-12-17T13:07:40+09:00'));
		$this->assertEquals(1576555660, ztime('2019-12-17T02:07:40-02:00'));
		$this->assertEquals(1576555640, ztime('20191217130720'));
		$this->assertEquals(1576555620, ztime('201912171307'));

		// Restore the internal timezone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
	}

	public function testZtimeDateOnly()
	{
		$timestamp = -271641600;

		// Test ztime() when the internal time zone is different from the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
		$this->assertEquals($timestamp, ztime('19610524'));

		// Test ztime() when the internal time zone is the same as the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 32400);
		$this->assertEquals($timestamp, ztime('19610524'));

		// Restore the internal timezone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
	}

	public function testZdate()
	{
		$expected = '2016-01-29 01:53:20';

		// Test zdate() when the internal time zone is different from the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
		$this->assertEquals($expected, zdate('20160128195320'));
		$this->assertEquals($expected, zdate('2016-01-28 19:53:20'));
		$this->assertEquals($expected, zdate('2016-01-28T09:53:20-07:00'));
		$this->assertEquals($expected, zdate('2016-01-28 21:23:20+04:30'));

		// Test zdate() when the internal time zone is the same as the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 32400);
		$this->assertEquals($expected, zdate('20160129015320'));
		$this->assertEquals($expected, zdate('2016-01-29 01:53:20'));
		$this->assertEquals($expected, zdate('2016-01-29 05:53:20+13:00'));
		$this->assertEquals($expected, zdate('2016-01-28T20:53:20+04:00'));

		// Restore the internal timezone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
	}

	public function testZdateDateOnly()
	{
		// Korea Standard Time was UTC+08:30 until 1961
		$expected = '1960-04-19 08:30:00';

		// Test zdate() when the internal time zone is different from the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
		$this->assertEquals($expected, zdate('19600419', 'Y-m-d H:i:s'));

		// Test zdate() when the internal time zone is the same as the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 32400);
		$this->assertEquals($expected, zdate('19600419', 'Y-m-d H:i:s'));

		// Restore the internal timezone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
	}

	public function testGetInternalDateTime()
	{
		$timestamp = 1454000000;

		// Test when the internal time zone is different from the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
		$this->assertEquals('20160128195320', getInternalDateTime($timestamp));

		// Test when the internal time zone is the same as the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 32400);
		$this->assertEquals('20160129015320', getInternalDateTime($timestamp));
	}

	public function testGetDisplayDateTime()
	{
		$timestamp = 1454000000;

		// Test when the display time zone is different from the internal time zone.
		$_SESSION['RHYMIX']['timezone'] = 'America/Los_Angeles';
		$this->assertEquals('20160128085320', getDisplayDateTime($timestamp));

		// Test when the display time zone is the same as the internal time zone.
		$_SESSION['RHYMIX']['timezone'] = 'Etc/GMT-3';
		$this->assertEquals('20160128195320', getDisplayDateTime($timestamp));
	}

	public function testGetTimeGap()
	{
		$GLOBALS['lang'] = Rhymix\Framework\Lang::getInstance('en');
		$GLOBALS['lang']->loadPlugin('common');

		// Test getTimeGap() when the internal time zone is different from the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 10800);
		$this->assertEquals('1 minute ago', getTimeGap(getInternalDateTime(RX_TIME - 30)));
		$this->assertEquals('30 minutes ago', getTimeGap(getInternalDateTime(RX_TIME - 1800)));
		$this->assertEquals('2 hours ago', getTimeGap(getInternalDateTime(RX_TIME - 8000)));

		// Test getTimeGap() when the internal time zone is the same as the default time zone.
		Rhymix\Framework\Config::set('locale.internal_timezone', 32400);
		$this->assertEquals('30 minutes ago', getTimeGap(getInternalDateTime(RX_TIME - 1800)));
		$this->assertEquals('2 hours ago', getTimeGap(getInternalDateTime(RX_TIME - 8000)));
		$this->assertEquals(getInternalDateTime(RX_TIME - 240000, 'Y.m.d'), getTimeGap(getInternalDateTime(RX_TIME - 240000)));
	}

	public function testGetTimezoneForCurrentUser()
	{
		// Test when the current user's time zone is different from the system default.
		$_SESSION['RHYMIX']['timezone'] = 'Pacific/Auckland';
		$this->assertEquals('Pacific/Auckland', Rhymix\Framework\DateTime::getTimezoneForCurrentUser());

		// Test when the current user's time zone is the same as the system default.
		unset($_SESSION['RHYMIX']['timezone']);
		$this->assertEquals('Asia/Seoul', Rhymix\Framework\DateTime::getTimezoneForCurrentUser());
	}

	public function testFormatTimestampForCurrentUser()
	{
		$timestamp_winter = 1454000000;
		$timestamp_summer = $timestamp_winter - (86400 * 184);

		// Test when the current user's time zone is in the Northern hemisphere with DST.
		$_SESSION['RHYMIX']['timezone'] = 'America/Chicago';
		$this->assertEquals('20160128 105320', Rhymix\Framework\DateTime::formatTimestampForCurrentUser('Ymd His', $timestamp_winter));
		$this->assertEquals('20150728 115320', Rhymix\Framework\DateTime::formatTimestampForCurrentUser('Ymd His', $timestamp_summer));
		$this->assertEquals('20150728 115320', getDisplayDateTime($timestamp_summer, 'Ymd His'));

		// Test when the current user's time zone is in the Southern hemisphere with DST.
		$_SESSION['RHYMIX']['timezone'] = 'Pacific/Auckland';
		$this->assertEquals('20160129 055320', Rhymix\Framework\DateTime::formatTimestampForCurrentUser('Ymd His', $timestamp_winter));
		$this->assertEquals('20150729 045320', Rhymix\Framework\DateTime::formatTimestampForCurrentUser('Ymd His', $timestamp_summer));
		$this->assertEquals('20150729 045320', getDisplayDateTime($timestamp_summer, 'Ymd His'));

		// Test when the current user's time zone is the same as the system default without DST.
		unset($_SESSION['RHYMIX']['timezone']);
		$this->assertEquals('20160129 015320', Rhymix\Framework\DateTime::formatTimestampForCurrentUser('Ymd His', $timestamp_winter));
		$this->assertEquals('20150729 015320', Rhymix\Framework\DateTime::formatTimestampForCurrentUser('Ymd His', $timestamp_summer));
		$this->assertEquals('20150729 015320', getDisplayDateTime($timestamp_summer, 'Ymd His'));
	}

	public function testGetTimezoneList()
	{
		$tzlist = Rhymix\Framework\DateTime::getTimezoneList();
		$this->assertTrue(array_key_exists('Etc/UTC', $tzlist));
		$this->assertEquals('Asia/Seoul (+09:00)', $tzlist['Asia/Seoul']);
	}

	public function testGetTimezoneOffset()
	{
		$this->assertEquals(32400, Rhymix\Framework\DateTime::getTimezoneOffset('Asia/Seoul'));
		$this->assertEquals(39600, Rhymix\Framework\DateTime::getTimezoneOffset('Australia/Sydney', strtotime('2016-01-01')));
		$this->assertEquals(36000, Rhymix\Framework\DateTime::getTimezoneOffset('Australia/Sydney', strtotime('2015-07-01')));
		$this->assertEquals(-18000, Rhymix\Framework\DateTime::getTimezoneOffset('America/New_York', strtotime('2016-01-01')));
		$this->assertEquals(-14400, Rhymix\Framework\DateTime::getTimezoneOffset('America/New_York', strtotime('2015-07-01')));
	}

	public function testGetTimezoneOffsetFromInternal()
	{
		$this->assertEquals(21600, Rhymix\Framework\DateTime::getTimezoneOffsetFromInternal('Asia/Seoul'));
		$this->assertEquals(28800, Rhymix\Framework\DateTime::getTimezoneOffsetFromInternal('Australia/Sydney', strtotime('2016-01-01')));
		$this->assertEquals(25200, Rhymix\Framework\DateTime::getTimezoneOffsetFromInternal('Australia/Sydney', strtotime('2015-07-01')));
		$this->assertEquals(-28800, Rhymix\Framework\DateTime::getTimezoneOffsetFromInternal('America/New_York', strtotime('2016-01-01')));
		$this->assertEquals(-25200, Rhymix\Framework\DateTime::getTimezoneOffsetFromInternal('America/New_York', strtotime('2015-07-01')));
	}

	public function testGetTimezoneOffsetByLegacyFormat()
	{
		$this->assertEquals(32400, Rhymix\Framework\DateTime::getTimezoneOffsetByLegacyFormat('+0900'));
		$this->assertEquals(-25200, Rhymix\Framework\DateTime::getTimezoneOffsetByLegacyFormat('-0700'));
		$this->assertEquals(19800, Rhymix\Framework\DateTime::getTimezoneOffsetByLegacyFormat('+0530'));
		$this->assertEquals(-38700, Rhymix\Framework\DateTime::getTimezoneOffsetByLegacyFormat('-1045'));
	}

	public function testGetTimezoneNameByOffset()
	{
		$this->assertEquals('Etc/GMT-9', Rhymix\Framework\DateTime::getTimezoneNameByOffset(32400));
		$this->assertEquals('Etc/GMT+5', Rhymix\Framework\DateTime::getTimezoneNameByOffset(-18000));
		$this->assertEquals('Etc/UTC', Rhymix\Framework\DateTime::getTimezoneNameByOffset(0));
		$this->assertEquals('Asia/Kolkata', Rhymix\Framework\DateTime::getTimezoneNameByOffset(19800));
		$this->assertEquals('Australia/Eucla', Rhymix\Framework\DateTime::getTimezoneNameByOffset(31500));
	}

	public function testGetRelativeTimestamp()
	{
		$GLOBALS['lang'] = Rhymix\Framework\Lang::getInstance('ko');
		$GLOBALS['lang']->loadPlugin('common');

		$this->assertEquals('방금', Rhymix\Framework\DateTime::getRelativeTimestamp(RX_TIME));
		$this->assertEquals('20초 전', Rhymix\Framework\DateTime::getRelativeTimestamp(RX_TIME - 20));
		$this->assertEquals('1분 전', Rhymix\Framework\DateTime::getRelativeTimestamp(RX_TIME - 60));
		$this->assertEquals('30분 전', Rhymix\Framework\DateTime::getRelativeTimestamp(RX_TIME - 1800));
		$this->assertEquals('10일 전', Rhymix\Framework\DateTime::getRelativeTimestamp(RX_TIME - 86400 * 10));
		$this->assertEquals('6개월 전', Rhymix\Framework\DateTime::getRelativeTimestamp(RX_TIME - 86400 * 190));

		$GLOBALS['lang'] = Rhymix\Framework\Lang::getInstance('en');
		$GLOBALS['lang']->loadPlugin('common');

		$this->assertEquals('just now', getInternalDateTime(RX_TIME + 3600, 'relative'));
		$this->assertEquals('5 days ago', getDisplayDateTime(RX_TIME - 86400 * 5.4, 'relative'));
		$this->assertEquals('3 months ago', zdate(date('YmdHis', RX_TIME - 86400 * 100), 'relative'));
		$this->assertEquals('1 year ago', Rhymix\Framework\DateTime::formatTimestamp('relative', RX_TIME - 86400 * 420));
		$this->assertEquals('2000 years ago', Rhymix\Framework\DateTime::formatTimestampForCurrentUser('relative', RX_TIME - 86400 * 365.25 * 2000));
		$this->assertEquals('just now', Rhymix\Framework\DateTime::formatTimestampForCurrentUser('relative', RX_TIME + pow(2, 31)));
	}
}
