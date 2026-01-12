<?php

namespace Rhymix\Framework\Helpers;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Rhymix\Framework\Cache;
use Rhymix\Framework\Drivers\Cache\APC as APCDriver;
use Rhymix\Framework\Drivers\Cache\Dummy as DummyDriver;
use Rhymix\Framework\Drivers\CacheInterface;
use Rhymix\Framework\Exceptions\InvalidCacheKey;

/**
 * Helper class to implement PSR-6 cache item pool using Rhymix cache configuration.
 */
class CacheItemPoolHelper implements CacheItemPoolInterface
{
	/**
	 * Cache the driver instance here.
	 *
	 * @var CacheInterface
	 */
	protected $_driver;

	/**
	 * Force persistence even if caching is disabled.
	 *
	 * @var bool
	 */
	public $force = false;

	/**
	 * Constructor.
	 *
	 * @param bool $force
	 */
	public function __construct(bool $force = false)
	{
		$this->_driver = Cache::getDriverInstance();
		if ($this->_driver === null || (\PHP_SAPI === 'cli' && $this->_driver instanceof APCDriver))
		{
			$this->_driver = DummyDriver::getInstance([]);
		}
		if ($this->_driver instanceof DummyDriver)
		{
			$this->force = $force;
		}
	}

    /**
     * Returns a Cache Item representing the specified key.
     *
     * @param string $key
     * @throws InvalidCacheKey
     * @return CacheItemInterface
     */
    public function getItem($key)
	{
		if (!is_scalar($key) || empty($key))
		{
			throw new InvalidCacheKey;
		}

		$key = $this->_getRealKey($key);
		return new CacheItemHelper($key, $this->_driver);
	}

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     * @throws InvalidCacheKey
     * @return array
     */
    public function getItems(array $keys = [])
	{
		$result = [];
		foreach ($keys as $key)
		{
			if (!is_scalar($key) || empty($key))
			{
				throw new InvalidCacheKey;
			}

			$key = $this->_getRealKey($key);
			$result[$key] = new CacheItemHelper($key, $this->_driver);
		}
		return $result;
	}

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $key
     * @throws InvalidCacheKey
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
		return $this->_driver->clear();
	}

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     * @throws InvalidCacheKey
     * @return bool
     */
    public function deleteItem($key)
	{
		if (!is_scalar($key) || empty($key))
		{
			throw new InvalidCacheKey;
		}

		$key = $this->_getRealKey($key);
		return $this->_driver->delete($key);
	}

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     * @throws InvalidCacheKey
     * @return bool
     */
    public function deleteItems(array $keys)
	{
		$result = true;
		foreach ($keys as $key)
		{
			if (!is_scalar($key) || empty($key))
			{
				throw new InvalidCacheKey;
			}

			$key = $this->_getRealKey($key);
			if (!$this->_driver->delete($key))
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
		return $this->_driver->set($item->key, $item->value, $ttl, $this->force);
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

	/**
	 * Get the real key in a way that is mostly compatible with R\F\Cache.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function _getRealKey($key): string
	{
		$key = preg_replace_callback('/[^\x21-\x7E]/', function($match) {
			return rawurlencode($match[0]);
		}, $key);

		if (preg_match('/^([^:]+):(.+)$/i', $key, $matches))
		{
			$key = $matches[1] . '#0:' . $matches[2];
		}

		return Cache::getPrefix() . $key;
	}
}
