<?php

class CalendarTest extends \Codeception\Test\Unit
{
	public function testGetMonthName()
	{
		$this->assertEquals('January', Rhymix\Framework\Calendar::getMonthName('01'));
		$this->assertEquals('October', Rhymix\Framework\Calendar::getMonthName('10'));
		$this->assertEquals('Nov', Rhymix\Framework\Calendar::getMonthName(11, false));
		$this->assertEquals('Dec', Rhymix\Framework\Calendar::getMonthName(12, false));
	}

	public function testGetMonthStartDayOfWeek()
	{
		$this->assertEquals(5, Rhymix\Framework\Calendar::getMonthStartDayOfWeek(1, 2016));
		$this->assertEquals(1, Rhymix\Framework\Calendar::getMonthStartDayOfWeek(2, 2016));
		$this->assertEquals(2, Rhymix\Framework\Calendar::getMonthStartDayOfWeek(3, 2016));
		$this->assertEquals(5, Rhymix\Framework\Calendar::getMonthStartDayOfWeek(4, 2016));
	}

	public function testGetMonthDays()
	{
		$this->assertEquals(30, Rhymix\Framework\Calendar::getMonthDays(11, 2015));
		$this->assertEquals(31, Rhymix\Framework\Calendar::getMonthDays(12, 2015));
		$this->assertEquals(31, Rhymix\Framework\Calendar::getMonthDays(1, 2016));
		$this->assertEquals(29, Rhymix\Framework\Calendar::getMonthDays(2, 2016));
	}

	public function testGetMonthCalendar()
	{
		$target_201508 = array(
			array(null, null, null, null, null, null, 1),
			array(2, 3, 4, 5, 6, 7, 8),
			array(9, 10, 11, 12, 13, 14, 15),
			array(16, 17, 18, 19, 20, 21, 22),
			array(23, 24, 25, 26, 27, 28, 29),
			array(30, 31, null, null, null, null, null),
		);

		$target_201603 = array(
			array(null, null, 1, 2, 3, 4, 5),
			array(6, 7, 8, 9, 10, 11, 12),
			array(13, 14, 15, 16, 17, 18, 19),
			array(20, 21, 22, 23, 24, 25, 26),
			array(27, 28, 29, 30, 31, null, null),
			array(null, null, null, null, null, null, null),
		);

		$this->assertEquals($target_201508, Rhymix\Framework\Calendar::getMonthCalendar(8, 2015));
		$this->assertEquals($target_201603, Rhymix\Framework\Calendar::getMonthCalendar(3, 2016));
	}
}
