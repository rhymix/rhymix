<?php

namespace Rhymix\Framework\Drivers\Cache;

use Rhymix\Framework\Drivers\CacheInterface;

abstract class Base implements CacheInterface
{
	/**
	 * Set this flag to false to disable cache prefixes
	 */
	public bool $prefix;

	/**
	 * The singleton instance is stored hre
	 */
	protected static ?CacheInterface $_instance = null;

	public static function getInstance(array $config = []): CacheInterface
	{
		if (static::$_instance === null)
		{
			static::$_instance = new static($config);
		}

		return static::$_instance;
	}

	protected function __construct(array $config)
	{
	}

	public static function isSupported()
	{
		return false;
	}
}
