<?php

namespace Rhymix\Framework\Drivers\Cache;

use Rhymix\Framework\Drivers\CacheInterface;

abstract class Base implements CacheInterface
{
	/**
	 * Set this flag to true in a subclass to enable cache prefixes.
	 */
	public bool $prefix = false;

	/**
	 * Singleton instances of subclasses are stored here.
	 */
	protected static array $_instances = [];

	/**
	 * Create a new instance of the current cache driver, using the given settings.
	 *
	 * @param array $config
	 * @return CacheInterface
	 */
	public static function getInstance(array $config = []): CacheInterface
	{
		$class_name = static::class;
		if (!isset(static::$_instances[$class_name]))
		{
			static::$_instances[$class_name] = new static($config);
		}

		return static::$_instances[$class_name];
	}

	/**
	 * Check if the current cache driver is supported on this server.
	 *
	 * @return bool
	 */
	public static function isSupported()
	{
		return false;
	}
}
