<?php

namespace Rhymix\Framework;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Rhymix\Modules\Module\Models\Event as EventModel;
use BaseObject;
use Context;
use ModuleHandler;

/**
 * The Event class implements PSR-14.
 *
 * It is intended to replace, but remains backward compatible with,
 * the old trigger system based on event names and positions.
 */
class Event implements EventDispatcherInterface, ListenerProviderInterface
{
	/**
	 * The singleton instance is stored here.
	 */
	protected static ?self $instance = null;

	/**
	 * Metadata about the last event listener, used for debugging.
	 */
	protected static array $_last_listener_info = [];

	/**
	 * Get a singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * The constructor is not publicly accessible.
	 */
	protected function __construct()
	{

	}

	/**
	 * Dispatch a PSR-14 event to all applicable event listeners.
	 *
	 * @param object $event
	 * @return object
	 */
	public function dispatch(object $event): object
	{
		$listeners = $this->getListenersForEvent($event);
		foreach ($listeners as $listener)
		{
			if ($event instanceof StoppableEventInterface && $event->isPropagationStopped())
			{
				break;
			}

			$debug_info = self::$_last_listener_info;
			$before_time = microtime(true);
			$listener($event);
			$after_time = microtime(true);

			if (Debug::isEnabledForCurrentUser())
			{
				$debug_info['elapsed_time'] = $after_time - $before_time;
				Debug::addTrigger($debug_info);
			}
		}

		self::$_last_listener_info = [];
		return $event;
	}

	/**
	 * Get the list of PSR-14 listeners for a given event.
	 *
	 * @param object $event
	 * @return iterable<callable>
	 */
	public function getListenersForEvent(object $event): iterable
	{
		$class_name = get_class($event);
		$position = $event instanceof AbstractEvent ? $event->getPosition() : 'none';
		foreach (self::getEventHandlers($class_name, $position) as $handler)
		{
			yield $handler;
		}
	}

	/**
	 * Dispatch a legacy event to all applicable event handlers.
	 *
	 * $data is generally an object, but is sometimes a string for legacy reasons.
	 * In all cases, it is passed by reference and can be modified in place.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @param mixed &$data
	 * @return BaseObject
	 */
	public static function trigger(string $event_name, string $position, &$data = null): BaseObject
	{
		$handlers = self::getEventHandlers($event_name, $position);
		foreach ($handlers as $handler)
		{
			if ($data instanceof StoppableEventInterface && $data->isPropagationStopped())
			{
				return self::_toBaseObject($data);
			}

			try
			{
				$debug_info = self::$_last_listener_info;
				$before_time = microtime(true);
				$output = $handler($data);
				$after_time = microtime(true);
			}
			catch (Exception $e)
			{
				$output = new BaseObject(-2, $e->getMessage());
				$after_time = microtime(true);
			}

			if (Debug::isEnabledForCurrentUser())
			{
				$debug_info['elapsed_time'] = $after_time - $before_time;
				Debug::addTrigger($debug_info);
			}

			if ($data instanceof StoppableEventInterface && $data->isPropagationStopped())
			{
				self::$_last_listener_info = [];
				return self::_toBaseObject($output);
			}

			if ($output instanceof BaseObject && !$output->toBool())
			{
				self::$_last_listener_info = [];
				return $output;
			}
		}

		self::$_last_listener_info = [];
		return new BaseObject;
	}

	/**
	 * Get the list of legacy event handlers for a given event.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @return iterable<callable>
	 */
	public static function getEventHandlers(string $event_name, string $position): iterable
	{
		$registered = EventModel::getRegisteredHandlers($event_name, $position);
		$ephemeral = EventModel::getSubscribers($event_name, $position);
		foreach ($registered as $handler)
		{
			$callable = self::_toCallable($handler);
			if (!$callable)
			{
				continue;
			}
			self::$_last_listener_info = [
				'name' => $event_name . ':' . $position,
				'target' => get_class($callable[0]) . '.' . $callable[1],
				'target_plugin' => $handler->module ?: null,
			];
			yield $callable;
		}
		foreach ($ephemeral as $handler)
		{
			self::$_last_listener_info = [
				'name' => $event_name . ':' . $position,
				'target' => $handler->identifier,
				'target_plugin' => null,
			];
			yield $handler->callable;
		}
	}

	/**
	 * Subscribe to an event.
	 *
	 * This is a shortcut for Rhymix\Modules\Module\Models\Event::subscribe().
	 *
	 * @param string $event_name
	 * @param string $position
	 * @param callable $handler
	 * @return void
	 */
	public static function subscribe(string $event_name, string $position, callable $handler): void
	{
		EventModel::subscribe($event_name, $position, $handler);
	}

	/**
	 * Unsubscribe from an event.
	 *
	 * This is a shortcut for Rhymix\Modules\Module\Models\Event::unsubscribe().
	 *
	 * @param string $event_name
	 * @param string $position
	 * @param callable $handler
	 * @return bool
	 */
	public static function unsubscribe(string $event_name, string $position, callable $handler): bool
	{
		return EventModel::unsubscribe($event_name, $position, $handler);
	}

	/**
	 * Convert the old event handler format to a callable.
	 *
	 * @param object $handler
	 * @return ?callable
	 */
	protected static function _toCallable(object $handler): ?callable
	{
		// Extract handler info.
		$module = $handler->module;
		$type = $handler->type;
		$method_name = $handler->called_method;

		// Return if the module is blacklisted.
		if (Context::isBlacklistedPlugin($module))
		{
			return null;
		}

		// Get instance of the module class.
		if (preg_match('/^(controller|model|view|mobile|api|wap|class)$/', $type))
		{
			$oModule = ModuleHandler::getModuleInstance($module, $type);
		}
		else
		{
			$class_name = ($type[0] === '\\') ? $type : sprintf('Rhymix\\Modules\\%s\\%s', $module, $type);
			if (class_exists($class_name))
			{
				$oModule = $class_name::getInstance();
			}
		}

		// Return if the class or method does not exist.
		if (!$oModule || !method_exists($oModule, $method_name))
		{
			return null;
		}

		// Return the callable.
		return [$oModule, $method_name];
	}

	/**
	 * Convert any output to a BaseObject return value.
	 *
	 * @param mixed $output
	 * @return BaseObject
	 */
	protected static function _toBaseObject($output): BaseObject
	{
		if ($output instanceof BaseObject)
		{
			return $output;
		}
		elseif ($output instanceof AbstractEvent && $output->getErrorMessage())
		{
			return new BaseObject(-3, $output->getErrorMessage());
		}
		else
		{
			return new BaseObject();
		}
	}
}
