<?php

namespace Rhymix\Framework\Helpers;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * Helper class to implement PSR-6 cache item pool using Rhymix cache configuration.
 */
class CacheItemPoolHelper implements CacheItemPoolInterface
{
	/**
	 * Force persistence even if caching is disabled.
	 */
	public $force = false;

	/**
	 * Constructor.
	 *
	 * @param bool $force
	 */
	public function __construct(bool $force = false)
	{
		$this->force = $force;
	}

    /**
     * Returns a Cache Item representing the specified key.
     *
     * @param string $key
     * @throws InvalidArgumentException
     * @return CacheItemInterface
     */
    public function getItem($key)
	{
		if (!is_scalar($key) || empty($key))
		{
			throw new InvalidArgumentException;
		}
		return new CacheItemHelper((string)$key);
	}

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     * @throws InvalidArgumentException
     * @return array
     */
    public function getItems(array $keys = [])
	{
		$result = [];
		foreach ($keys as $key)
		{
			if (!is_scalar($key) || empty($key))
			{
				throw new InvalidArgumentException;
			}
			$result[(string)$key] = new CacheItemHelper((string)$key);
		}
		return $result;
	}

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $key
     * @throws InvalidArgumentException
     * @return bool
     */
    public function hasItem($key)
	{
		return $this->getItem($key)->isHit();
	}

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     */
    public function clear()
	{
		\Rhymix\Framework\Cache::clearAll();
	}

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     * @throws InvalidArgumentException
     * @return bool
     */
    public function deleteItem($key)
	{
		if (!is_scalar($key) || empty($key))
		{
			throw new InvalidArgumentException;
		}
		return \Rhymix\Framework\Cache::delete((string)$key);
	}

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     * @throws InvalidArgumentException
     * @return bool
     */
    public function deleteItems(array $keys)
	{
		$result = true;
		foreach ($keys as $key)
		{
			if (!is_scalar($key) || empty($key))
			{
				throw new InvalidArgumentException;
			}
			if (!\Rhymix\Framework\Cache::delete((string)$key))
			{
				$result = false;
			}
		}
		return $result;
	}

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     * @return bool
     */
    public function save(CacheItemInterface $item)
	{
		$ttl = $item->expires ? max(0, min(30 * 86400, $item->expires - time())) : 0;
		return \Rhymix\Framework\Cache::set($item->key, $item->value, $ttl, $this->force);
	}

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item)
	{
		return $this->save($item);
	}

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     */
    public function commit()
	{
		return true;
	}
}
