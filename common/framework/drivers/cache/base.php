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
	 * The singleton instance is stored here.
	 */
	protected static ?CacheInterface $_instance = null;

	/**
	 * Create a new instance of the current cache driver, using the given settings.
	 *
	 * @param array $config
	 * @return CacheInterface
	 */
	public static function getInstance(array $config = []): CacheInterface
	{
		if (static::$_instance === null)
		{
			static::$_instance = new static($config);
		}

		return static::$_instance;
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
