<?php

namespace Rhymix\Framework;

/**
 * The datetime class.
 */
class DateTime
{
	/**
	 * Time zone objects and settings are cached here.
	 */
	protected static $_timezones = array();
	
	/**
	 * Get the timezone to display for the current user.
	 * 
	 * @return string
	 */
	public static function getCurrentUserTimezone()
	{
		if (isset($_SESSION['timezone']))
		{
			return $_SESSION['timezone'];
		}
		else
		{
			return Config::get('locale.default_timezone');
		}
	}
	
	/**
	 * Get the list of time zones supported on this server.
	 * 
	 * @return array
	 */
	public static function getTimezoneList()
	{
		$result = array();
		$tzlist = \DateTimeZone::listIdentifiers();
		foreach ($tzlist as $tzid)
		{
			if (!preg_match('/^(?:A|Europe|Indian|Pacific)/', $tzid)) continue;
			$name = str_replace('_', ' ', $tzid);
			$datetime = new \DateTime(null, new \DateTimeZone($tzid));
			$offset = $datetime->getOffset();
			$offset = ($offset >= 0 ? '+' : '-') . sprintf('%02d', floor(abs($offset) / 3600)) . ':' . sprintf('%02d', (abs($offset) % 3600) / 60);
			unset($datetime);
			$result[$tzid] = "$name ($offset)";
		}
		asort($result);
		$result['Etc/UTC'] = 'GMT/UTC (+00:00)';
		return $result;
	}
	
	/**
	 * Get the absolute (UTC) offset of a timezone.
	 * 
	 * @param string $timezone Timezone identifier, e.g. Asia/Seoul
	 * @param int $timestamp Unix timestamp (optional, default is now)
	 * @return int
	 */
	public static function getTimezoneOffset($timezone, $timestamp = null)
	{
		if (!isset(self::$_timezones[$timezone]))
		{
			self::$_timezones[$timezone] = new \DateTimeZone($timezone);
		}
		$datetime = new \DateTime();
		$datetime->setTimestamp($timestamp ?: time());
		$datetime->setTimezone(self::$_timezones[$timezone]);
		return $datetime->getOffset();
	}
	
	/**
	 * Get the relative offset between a timezone and Rhymix's internal timezone.
	 * 
	 * @param string $timezone Timezone identifier, e.g. Asia/Seoul
	 * @param int $timestamp Unix timestamp (optional, default is now)
	 * @return int
	 */
	public static function getTimezoneOffsetFromInternal($timezone, $timestamp = null)
	{
		return self::getTimezoneOffset($timezone, $timestamp) - Config::get('locale.internal_timezone');
	}
	
	/**
	 * Get a PHP time zone by UTC offset.
	 * 
	 * @param int $offset
	 * @return bool
	 */
	public static function getTimezoneNameByOffset($offset)
	{
		switch ($offset)
		{
			case 0: return 'Etc/UTC';
			default: return 'Etc/GMT' . ($offset > 0 ? '-' : '+') . intval(abs($offset / 3600));
		}
	}
}
