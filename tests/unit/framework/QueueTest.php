<?php

class QueueTest extends \Codeception\Test\Unit
{
	public function testDummyQueue()
	{
		config('queue.driver', 'dummy');

		$handler = 'myfunc';
		$args = (object)['foo' => 'bar'];
		$options = (object)['key' => 'val'];

		Rhymix\Framework\Queue::addTask($handler, $args, $options);

		$output = Rhymix\Framework\Queue::getTask();
		$this->assertEquals('myfunc', $output->handler);
		$this->assertEquals('bar', $output->args->foo);
		$this->assertEquals('val', $output->options->key);

		$output = Rhymix\Framework\Queue::getTask();
		$this->assertNull($output);
	}
}
