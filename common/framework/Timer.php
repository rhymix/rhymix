<?php

namespace Rhymix\Framework;

/**
 * The timer class.
 */
class Timer
{
	/**
	 * Timestamps are stored here.
	 */
	protected static $_timestamps = array();

	/**
	 * Start a timer.
	 *
	 * This method returns the current microtime.
	 *
	 * @param string $name (optional)
	 * @return float
	 */
	public static function start(string $name = ''): float
	{
		$timestamp = microtime(true);

		if ($name === '')
		{
			$name = 'anon-timer-' . $timestamp;
		}

		self::$_timestamps[$name] = $timestamp;
		return $timestamp;
	}

	/**
	 * Stop a timer and return the elapsed time.
	 *
	 * If the name is not given, the most recently started timer will be stopped.
	 * If no timer has been started, this method returns zero.
	 *
	 * @param string $name (optional)
	 * @return float
	 */
	public static function stop(string $name = ''): float
	{
		$timestamp = microtime(true);
		$started_timestamp = 0;

		if ($name === '')
		{
			if (count(self::$_timestamps))
			{
				$started_timestamp = array_pop(self::$_timestamps);
			}
			else
			{
				return 0;
			}
		}
		elseif (array_key_exists($name, self::$_timestamps))
		{
			$started_timestamp = self::$_timestamps[$name];
			unset(self::$_timestamps[$name]);
		}
		else
		{
			return 0;
		}

		return $timestamp - $started_timestamp;
	}

	/**
	 * Stop a timer and return the elapsed time in a human-readable format.
	 *
	 * If the name is not given, the most recently started timer will be stopped.
	 * If no timer has been started, this method returns '0'.
	 *
	 * @param string $name (optional)
	 * @return string
	 */
	public static function stopFormat(string $name = ''): string
	{
		$result = self::stop($name);
		if ($result === 0) return '0';
		return number_format($result * 1000, 1, '.', ',') . 'ms';
	}

	/**
	 * This method returns how much time has elapsed since Rhymix startup.
	 *
	 * @return float
	 */
	public static function sinceStartup(): float
	{
		return microtime(true) - \RX_MICROTIME;
	}

	/**
	 * This method returns how much time has elapsed since startup in a human-readable format.
	 *
	 * @return string
	 */
	public static function sinceStartupFormat(): string
	{
		return number_format((microtime(true) - \RX_MICROTIME) * 1000, 1, '.', ',') . 'ms';
	}
}
