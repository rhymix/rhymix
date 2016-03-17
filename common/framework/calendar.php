<?php

namespace Rhymix\Framework;

/**
 * The calendar class.
 */
class Calendar
{
	/**
	 * This method returns the English name of a month, e.g. 9 = 'September'.
	 * 
	 * @param int $month_number
	 * @param bool $long_format (optional, default is true)
	 * @return string
	 */
	public static function getMonthName($month_number, $long_format = true)
	{
		$month_number = intval($month_number, 10);
		if (!is_between($month_number, 1, 12))
		{
			return false;
		}
		
		return date($long_format ? 'F' : 'M', mktime(0, 0, 0, $month_number, 1));
	}
	
	/**
	 * This method returns the day on which a month begins.
	 * 
	 * 0 = Sunday, 1 = Monday, 2 = Tuesday, 3 = Wednesday, 4 = Thursday, 5 = Friday, 6 = Saturday.
	 * If you do not specify a year, the current year is assumed.
	 * 
	 * @param int $month_number
	 * @param int $year (optional)
	 * @return int
	 */
	public static function getMonthStartDayOfWeek($month_number, $year = null)
	{
		$month_number = intval($month_number, 10);
		if (!is_between($month_number, 1, 12))
		{
			return false;
		}
		
		return (int)date('w', mktime(0, 0, 0, $month_number, 1, $year ?: date('Y')));
	}
	
	/**
	 * This method returns the number of days in a month, e.g. February 2016 has 29 days.
	 * 
	 * If you do not specify a year, the current year is assumed.
	 * You must specify a year to get the number of days in February.
	 * 
	 * @param int $month_number
	 * @param int $year (optional)
	 * @return int
	 */
	public static function getMonthDays($month_number, $year = null)
	{
		$month_number = intval($month_number, 10);
		if (!is_between($month_number, 1, 12))
		{
			return false;
		}
		
		return (int)date('t', mktime(0, 0, 0, $month_number, 1, $year ?: date('Y')));
	}
	
	/**
	 * This method returns a complete calendar for a month.
	 * 
	 * The return value is an array with six members, each representing a week.
	 * Each week is an array with seven members, each representing a day.
	 * 6 weeks are returned. Empty cells are represented by nulls.
	 * 
	 * If you do not specify a year, the current year is assumed.
	 * 
	 * @param int $month_number
	 * @param int $year (optional)
	 * @param int $start_dow (optional)
	 * @return array
	 */
	public static function getMonthCalendar($month_number, $year = null, $start_dow = 0)
	{
		$month_number = intval($month_number, 10);
		if (!is_between($month_number, 1, 12))
		{
			return false;
		}
		if (!is_between($start_dow, 0, 6))
		{
			return false;
		}
		if (!$year || !is_between($year, 1000, 9999))
		{
			$year = date('Y');
		}
		
		$start = self::getMonthStartDayOfWeek($month_number, $year);
		$count = self::getMonthDays($month_number, $year);
		$initial_blank_cells = (7 + $start - $start_dow) % 7;
		$final_blank_cells = 42 - $count - $initial_blank_cells;
		$temp = array();
		
		for ($i = 0; $i < $initial_blank_cells; $i++)
		{
			$temp[] = null;
		}
		for ($i = 0; $i < $count; $i++)
		{
			$temp[] = $i + 1;
		}
		for ($i = 0; $i < $final_blank_cells; $i++)
		{
			$temp[] = null;
		}
		
		$return = array();
		for ($i = 0; $i < 6; $i++)
		{
			$week = array();
			for ($j = 0; $j < 7; $j++)
			{
				$week[] = array_shift($temp);
			}
			$return[] = $week;
		}
		
		return $return;
	}
}
