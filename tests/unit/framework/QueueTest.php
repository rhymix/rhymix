<?php

class QueueTest extends \Codeception\Test\Unit
{
	protected $_prev_queue_config;

	public function _before()
	{
		$this->_prev_queue_config = config('queue');
		config('queue.enabled', true);
		config('queue.driver', 'dummy');
	}

	public function _after()
	{
		config('queue', $this->_prev_queue_config);
	}

	public function testDummyQueue()
	{
		$handler = 'myfunc';
		$args = (object)['foo' => 'bar'];
		$options = (object)['key' => 'val'];

		Rhymix\Framework\Queue::addTask($handler, $args, $options);

		$output = Rhymix\Framework\Queue::getDriver('dummy')->getNextTask();
		$this->assertEquals('myfunc', $output->handler);
		$this->assertEquals('bar', $output->args->foo);
		$this->assertEquals('val', $output->options->key);

		$output = Rhymix\Framework\Queue::getDriver('dummy')->getNextTask();
		$this->assertNull($output);
	}

	public function testScheduledTaskAt()
	{
		$timestamp = time() + 43200;
		$handler = 'MyClass::myFunc';
		$args = (object)['foo' => 'bar'];
		$options = null;

		$task_srl = Rhymix\Framework\Queue::addTaskAt($timestamp, $handler, $args, $options);
		$this->assertGreaterThan(0, $task_srl);

		$output = Rhymix\Framework\Queue::getScheduledTask($task_srl);
		$this->assertTrue(is_object($output));
		$this->assertEquals('once', $output->task_type);
		$this->assertEquals(date('Y-m-d H:i:s', $timestamp), $output->first_run);
		$this->assertEquals('MyClass::myFunc', $output->handler);
		$this->assertEquals('bar', $output->args->foo);
		$this->assertNull($output->options);

		$output = Rhymix\Framework\Queue::cancelScheduledTask($task_srl);
		$this->assertTrue($output);
	}

	public function testScheduledTaskAtInterval()
	{
		$interval = '30 9 1-15 */2 *';
		$handler = 'MyClass::getInstance()->myMethod';
		$args = (object)['foo' => 'bar'];
		$options = null;

		$task_srl = Rhymix\Framework\Queue::addTaskAtInterval($interval, $handler, $args, $options);
		$this->assertGreaterThan(0, $task_srl);

		$output = Rhymix\Framework\Queue::getScheduledTask($task_srl);
		$this->assertTrue(is_object($output));
		$this->assertEquals('interval', $output->task_type);
		$this->assertEquals($interval, $output->run_interval);
		$this->assertEquals('MyClass::getInstance()->myMethod', $output->handler);
		$this->assertEquals('bar', $output->args->foo);
		$this->assertNull($output->options);

		$output = Rhymix\Framework\Queue::cancelScheduledTask($task_srl);
		$this->assertTrue($output);
	}

	public function testCheckIntervalSyntax()
	{
		$this->assertTrue(Rhymix\Framework\Queue::checkIntervalSyntax('* * * * *'));
		$this->assertTrue(Rhymix\Framework\Queue::checkIntervalSyntax('*/2 15 * * 0,3'));
		$this->assertTrue(Rhymix\Framework\Queue::checkIntervalSyntax('10-19,40-49 * 1-12 * *'));
		$this->assertTrue(Rhymix\Framework\Queue::checkIntervalSyntax('*/10 */4 * * *'));
		$this->assertFalse(Rhymix\Framework\Queue::checkIntervalSyntax('* * * *'));
		$this->assertFalse(Rhymix\Framework\Queue::checkIntervalSyntax('1 2 3 4 5,6 */7'));
		$this->assertFalse(Rhymix\Framework\Queue::checkIntervalSyntax('@hourly'));
		$this->assertFalse(Rhymix\Framework\Queue::checkIntervalSyntax('5/* 12h * * *'));
	}

	public function testParseInterval()
	{
		$interval = '* * * * *';
		$this->assertTrue(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-01-01 00:00:00')));
		$this->assertTrue(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-31 23:59:59')));

		$interval = '*/2 15 * * 0,3';
		$this->assertTrue(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-01 15:00:00')));
		$this->assertTrue(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-04 15:30:00')));
		$this->assertFalse(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-02 15:00:00')));
		$this->assertFalse(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-01 03:00:00')));
		$this->assertFalse(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-04 15:31:00')));
		$this->assertFalse(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-11-30 15:44:00')));

		$interval = '3-5,14-21,*/10 * * 12 *';
		$this->assertTrue(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-01 12:05:00')));
		$this->assertTrue(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-01 14:19:00')));
		$this->assertTrue(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-01 16:30:00')));
		$this->assertTrue(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-01 18:00:00')));
		$this->assertFalse(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-01 09:01:00')));
		$this->assertFalse(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-12-01 11:13:00')));
		$this->assertFalse(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-01-02 06:20:00')));
		$this->assertFalse(Rhymix\Framework\Queue::parseInterval($interval, strtotime('2024-11-30 18:50:00')));
	}
}
