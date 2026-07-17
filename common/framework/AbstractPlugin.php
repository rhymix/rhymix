<?php

namespace Rhymix\Framework;

use Rhymix\Modules\Module\Models\Event as EventModel;
use Rhymix\Modules\Module\Models\Plugin as PluginModel;

/**
 * The main class of all plugins must extend this class.
 */
abstract class AbstractPlugin
{
	/**
	 * All plugins must implement a constructor that accepts a configuration object.
	 *
	 * It is up to the plugin to decide what to do with the configuration.
	 * Generally, we expect that it will be stored as a property of the plugin instance
	 * so that it can be referenced later in event handlers and other methods.
	 *
	 * @param object $config
	 */
	abstract public function __construct(object $config);

	/**
	 * This property is defined by default to hold the configuration object.
	 *
	 * However, a plugin is free to ignore it if not needed.
	 * Rhymix does not enforce any further structure on the plugin class.
	 */
	public object $config;

	/**
	 * Register an event handler.
	 *
	 * @param string $event_name
	 * @param string $position (usually 'before' or 'after', but can be null)
	 * @param callable $handler
	 * @return void
	 */
	public function on(string $event_name, string $position, callable $handler): void
	{
		EventModel::subscribe($event_name, $position, $handler);
	}

	/**
	 * Unregister an event handler.
	 *
	 * @param string $event_name
	 * @param string $position
	 * @param callable $handler
	 * @return bool
	 */
	public function off(string $event_name, string $position, callable $handler): bool
	{
		return EventModel::unsubscribe($event_name, $position, $handler);
	}

	/**
	 * Shortcut to on() method for common positions.
	 *
	 * @param string $event_name
	 * @param callable $handler
	 * @return void
	 */
	public function before(string $event_name, callable $handler): void
	{
		$this->on($event_name, 'before', $handler);
	}

	/**
	 * Shortcut to on() method for common positions.
	 *
	 * @param string $event_name
	 * @param callable $handler
	 * @return void
	 */
	public function after(string $event_name, callable $handler): void
	{
		$this->on($event_name, 'after', $handler);
	}

	/**
	 * Shortcut to on() method for common positions.
	 *
	 * @param string $action_name
	 * @param callable $handler
	 * @return void
	 */
	public function beforeAction(string $action_name, callable $handler): void
	{
		$this->on('act:' . $action_name, 'before', $handler);
	}

	/**
	 * Shortcut to on() method for common positions.
	 *
	 * @param string $action_name
	 * @param callable $handler
	 * @return void
	 */
	public function afterAction(string $action_name, callable $handler): void
	{
		$this->on('act:' . $action_name, 'after', $handler);
	}

	/**
	 * Get the name of this plugin.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		$class_name = explode('\\', get_class($this));
		return strtolower($class_name[count($class_name) - 2]);
	}

	/**
	 * Get extra data associated with this plugin.
	 *
	 * @return ?object
	 */
	public function getExtraData(): ?object
	{
		return PluginModel::getPluginExtraData($this->getName());
	}

	/**
	 * Set extra data associated with this plugin.
	 *
	 * @param ?object $extra_data
	 * @return bool
	 */
	public function setExtraData(?object $extra_data): bool
	{
		return PluginModel::setPluginExtraData($this->getName(), $extra_data)->toBool();
	}
}
