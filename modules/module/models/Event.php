<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\Helpers\DBResultHelper;
use Closure;
use ReflectionFunction;

class Event
{
	/**
	 * Get the list of event handlers that have been added to an event.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @return array
	 */
	public static function getEventHandlers(string $event_name, string $position): array
	{
		if(isset(ModuleCache::$eventHandlers[$event_name][$position]))
		{
			return ModuleCache::$eventHandlers[$event_name][$position];
		}
		else
		{
			return [];
		}
	}

	/**
	 * Get the list of event handlers that have been registered to an event.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @return array
	 */
	public static function getRegisteredHandlers(string $event_name, string $position): array
	{
		if (!count(ModuleCache::$registeredHandlers))
		{
			$triggers = Cache::get('triggers');
			if ($triggers === null)
			{
				$output = executeQueryArray('module.getTriggers');
				$triggers = $output->data;
				if ($output->toBool())
				{
					Cache::set('triggers', $triggers, 0, true);
				}
			}

			$triggers = $triggers ?: [];
			foreach ($triggers as $item)
			{
				ModuleCache::$registeredHandlers[$item->trigger_name][$item->called_position][] = $item;
			}

			// Create global variables for backward compatibility.
			$GLOBALS['__trigger_functions__'] = &ModuleCache::$eventHandlers;
			$GLOBALS['__triggers__'] = &ModuleCache::$registeredHandlers;
		}

		return ModuleCache::$registeredHandlers[$event_name][$position] ?? [];
	}

	/**
	 * If a handler is registered to an event, return it.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @param string $module
	 * @param string $class_name
	 * @param string $method_name
	 * @return ?object
	 */
	public static function isRegisteredHandler(
		string $event_name,
		string $position,
		string $module,
		string $class_name,
		string $method_name
	): ?object
	{
		$handlers = self::getRegisteredHandlers($event_name, $position);
		foreach ($handlers as $item)
		{
			if ($item->module == $module && $item->type == $class_name && $item->called_method == $method_name)
			{
				return $item;
			}
		}
		return null;
	}

	/**
	 * Add a handler to an event. This is only valid during the current request.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @param callable $handler
	 * @return void
	 */
	public static function addEventHandler(string $event_name, string $position, callable $handler): void
	{
		// Generate a record of who registered this event handler, because closures don't have names.
		if ($handler instanceof Closure)
		{
			$reflection = new ReflectionFunction($handler);
			$trace_str = $reflection->getFileName() . ':' . ($reflection->getStartLine() ?: '0');
			if (str_starts_with($trace_str, \RX_BASEDIR))
			{
				$trace_str = substr($trace_str, strlen(RX_BASEDIR));
			}
		}
		elseif (is_string($handler))
		{
			$trace_str = $handler;
		}
		elseif (is_array($handler) && count($handler) == 2)
		{
			if (is_object($handler[0]))
			{
				$trace_str = get_class($handler[0]) . '.' . strval($handler[1]);
			}
			else
			{
				$trace_str = implode('.', $handler);
			}
		}
		else
		{
			$trace_str = null;
			$trace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			if (isset($trace[0]))
			{
				$bt = $trace[0];
				if (isset($bt['file']) && preg_match('/[\/\\\\](?:module\.controller\.php)$/', $bt['file']) && isset($trace[1]))
				{
					$bt = $trace[1];
				}
				if (isset($bt['file']))
				{
					$bt['file'] = strtr($bt['file'], ['\\' => '/']);
					if (str_starts_with($bt['file'], \RX_BASEDIR))
					{
						$bt['file'] = substr($bt['file'], strlen(RX_BASEDIR));
					}
					$trace_str = $bt['file'] . ':' . ($bt['line'] ?? '0');
				}
			}
		}

		ModuleCache::$eventHandlers[$event_name][$position][] = (object)[
			'callable' => $handler,
			'added_by' => $trace_str,
		];
	}

	/**
	 * Remove a handler from an event.
	 *
	 * In order to remove an event handler, you must provide the same callable that was used to add it.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @param callable $handler
	 * @return bool
	 */
	public static function removeEventHandler(string $event_name, string $position, callable $handler): bool
	{
		$success = false;
		if (isset(ModuleCache::$eventHandlers[$event_name][$position]))
		{
			foreach (ModuleCache::$eventHandlers[$event_name][$position] as $key => $value)
			{
				if ($value->callable === $handler)
				{
					unset(ModuleCache::$eventHandlers[$event_name][$position][$key]);
					$success = true;
				}
			}
		}
		return $success;
	}

	/**
	 * Register a handler to an event.
	 *
	 * A module can persistently register its event handlers using this method.
	 * This is more convenient and reliable than adding handlers on every request.
	 *
	 * If the same handler is already registered, it will be replaced.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @param string $module
	 * @param string $class_name
	 * @param string $method_name
	 * @return DBResultHelper
	 */
	public static function registerHandler(
		string $event_name,
		string $position,
		string $module,
		string $class_name,
		string $method_name
	): DBResultHelper
	{
		$args = new \stdClass;
		$args->trigger_name = $event_name;
		$args->module = $module;
		$args->type = $class_name;
		$args->called_method = $method_name;
		$args->called_position = $position;

		$output = executeQuery('module.deleteTrigger', $args);
		$output = executeQuery('module.insertTrigger', $args);
		if ($output->toBool())
		{
			ModuleCache::$registeredHandlers = [];
			Cache::delete('triggers');
		}
		return $output;
	}

	/**
	 * Unregister a handler from an event.
	 *
	 * This method removes a registered event handler.
	 * All parameters must match the original registration.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @param string $module
	 * @param string $class_name
	 * @param string $method_name
	 * @return DBResultHelper
	 */
	public static function unregisterHandler(
		string $event_name,
		string $position,
		string $module,
		string $class_name,
		string $method_name
	): DBResultHelper
	{
		$args = new \stdClass;
		$args->trigger_name = $event_name;
		$args->module = $module;
		$args->type = $class_name;
		$args->called_method = $method_name;
		$args->called_position = $position;

		$output = executeQuery('module.deleteTrigger', $args);
		if ($output->toBool())
		{
			ModuleCache::$registeredHandlers = [];
			Cache::delete('triggers');
		}
		return $output;
	}

	/**
	 * Unregister all handlers registered by the given module.
	 *
	 * @param string $module
	 * @return DBResultHelper
	 */
	public static function unregisterHandlersByModule(string $module): DBResultHelper
	{
		$output = executeQuery('module.deleteModuleTriggers', ['module' => $module]);
		if ($output->toBool())
		{
			ModuleCache::$registeredHandlers = [];
			Cache::delete('triggers');
		}
		return $output;
	}
}
