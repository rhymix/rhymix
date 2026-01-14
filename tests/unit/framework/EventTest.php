<?php

namespace Rhymix\Tests\Unit\Framework;

use Rhymix\Modules\Module\Models\Event as EventModel;
use Rhymix\Framework\Event as EventDispatcher;
use Rhymix\Framework\Exception as RhymixException;

class DummyEvent extends \Rhymix\Framework\AbstractEvent
{

}

class OtherEvent extends \Rhymix\Framework\AbstractEvent
{

}

class FakeModuleEventHandler extends \ModuleObject
{
	public $counter = 0;

	public function handleEvent($event)
	{
		if (isset($event->error) && $event->error === 'BaseObject')
		{
			return new \BaseObject(-1, 'Returning BaseObject');
		}
		elseif (isset($event->error) && $event->error === 'Exception')
		{
			throw new RhymixException('Throwing Exception');
		}
		else
		{
			$this->counter += 1;
		}
	}

	public function handleAnotherEvent($event)
	{
		$this->counter += 10;
	}
}

class EventTest extends \Codeception\Test\Unit
{
	public function testEphemeralEventHandler()
	{
		// Setup counter and event handler
		$counter = 0;
		$handler = function($event) use(&$counter) {
			$counter += 1;
		};

		EventModel::addEventHandler(DummyEvent::class, 'before', $handler);

		$event = new DummyEvent('before');
		$this->assertFalse($event->isPropagationStopped());
		$this->assertEquals('before', $event->getPosition());

		// Dispatch event
		$dispatcher = EventDispatcher::getInstance();
		$dispatcher->dispatch($event);
		$this->assertEquals(1, $counter);

		$dispatcher->dispatch($event);
		$this->assertEquals(2, $counter);

		// Event with stopped propagation
		$event->stopPropagation();
		$this->assertTrue($event->isPropagationStopped());
		$dispatcher->dispatch($event);
		$this->assertEquals(2, $counter);

		// Event with different position
		$dispatcher->dispatch(new DummyEvent('after'));
		$this->assertEquals(2, $counter);

		// Event with different class
		$dispatcher->dispatch(new OtherEvent('before'));
		$this->assertEquals(2, $counter);

		// New event
		$new_event = new DummyEvent('before');
		$dispatcher->dispatch($new_event);
		$this->assertEquals(3, $counter);

		// Remove event handler
		$output = EventModel::removeEventHandler(DummyEvent::class, 'before', $handler);
		$this->assertTrue($output);
		$dispatcher->dispatch($new_event);
		$this->assertEquals(3, $counter);

		// Error message echo
		$new_event->setErrorMessage('An error occurred.');
		$this->assertEquals('An error occurred.', $new_event->getErrorMessage());
	}

	public function testRegisteredEventHandler()
	{
		// Setup fake module and event handler
		class_alias(FakeModuleEventHandler::class, 'Rhymix\Modules\FakeModule\Controllers\EventHandler');
		$handler = FakeModuleEventHandler::getInstance('fakemodule');

		EventModel::unregisterHandlersByModule('fakemodule');
		EventModel::registerHandler('fakeEvent', 'before', 'fakemodule', 'Controllers\\EventHandler', 'handleEvent');
		$this->assertEquals(0, $handler->counter);

		// Dispatch event
		$event = new DummyEvent('before');
		$output = EventDispatcher::trigger('fakeEvent', 'before', $event);
		$this->assertEquals(1, $handler->counter);
		$this->assertTrue($output instanceof \BaseObject);
		$this->assertTrue($output->toBool());

		// Dispatch stopped event
		$event->stopPropagation();
		$output = EventDispatcher::trigger('fakeEvent', 'before', $event);
		$this->assertEquals(1, $handler->counter);
		$this->assertTrue($output instanceof \BaseObject);
		$this->assertFalse($output->toBool());

		// Dispatch arbitrary object
		$obj = new \stdClass();
		$output = EventDispatcher::trigger('fakeEvent', 'before', $obj);
		$this->assertEquals(2, $handler->counter);
		$this->assertTrue($output instanceof \BaseObject);
		$this->assertTrue($output->toBool());

		// Error case 1
		$obj = new \stdClass();
		$obj->error = 'BaseObject';
		$output = EventDispatcher::trigger('fakeEvent', 'before', $obj);
		$this->assertEquals(2, $handler->counter);
		$this->assertTrue($output instanceof \BaseObject);
		$this->assertFalse($output->toBool());
		$this->assertEquals('Returning BaseObject', $output->getMessage());

		// Error case 2
		$obj = new \stdClass();
		$obj->error = 'Exception';
		$output = EventDispatcher::trigger('fakeEvent', 'before', $obj);
		$this->assertEquals(2, $handler->counter);
		$this->assertTrue($output instanceof \BaseObject);
		$this->assertFalse($output->toBool());
		$this->assertEquals('Throwing Exception', $output->getMessage());

		// Register another handler
		EventModel::registerHandler('fakeEvent', 'before', 'fakemodule', 'Controllers\\EventHandler', 'handleAnotherEvent');
		$obj = new DummyEvent('before');
		$output = EventDispatcher::trigger('fakeEvent', 'before', $obj);
		$this->assertEquals(13, $handler->counter);

		// Unregister both event handlers.
		EventModel::unregisterHandler('fakeEvent', 'before', 'fakemodule', 'Controllers\\EventHandler', 'handleEvent');
		EventModel::unregisterHandler('fakeEvent', 'before', 'fakemodule', 'Controllers\\EventHandler', 'handleAnotherEvent');
		$obj = new \stdClass();
		$output = EventDispatcher::trigger('fakeEvent', 'before', $obj);
		$this->assertEquals(13, $handler->counter);

	}
}
