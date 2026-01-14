<?php

namespace Rhymix\Framework\Helpers;

use Psr\SimpleCache\CacheInterface as Psr16_CacheInterface;
use Rhymix\Framework\Cache;
use Rhymix\Framework\Drivers\Cache\APC as APCDriver;
use Rhymix\Framework\Drivers\Cache\Dummy as DummyDriver;
use Rhymix\Framework\Drivers\CacheInterface;
use Rhymix\Framework\Exceptions\Psr\Psr16_InvalidArgumentException;
use DateInterval;
use DateTimeImmutable;

/**
 * Helper class to implement PSR-16 simple cache using Rhymix cache configuration.
 */
class SimpleCacheHelper implements Psr16_CacheInterface
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
	 * Returns data from a single key.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @throws Psr16_InvalidArgumentException
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if (!is_scalar($key) || $key === '')
		{
			throw new Psr16_InvalidArgumentException;
		}

		$key = $this->_getRealKey($key);
		return $this->_driver->get($key) ?? $default;
	}

	/**
	 * Persists data in the cache.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param null|int|DateInterval $ttl
	 * @throws Psr16_InvalidArgumentException
	 * @return bool
	 */
	public function set($key, $value, $ttl = null)
	{
		if (!is_scalar($key) || $key === '')
		{
			throw new Psr16_InvalidArgumentException;
		}

		$key = $this->_getRealKey($key);
		$ttl = $this->_normalizeTTL($ttl);
		return $this->_driver->set($key, $value, $ttl, $this->force);
	}

	/**
	 * Delete an item from the cache.
	 *
	 * @param string $key
	 * @throws Psr16_InvalidArgumentException
	 * @return bool
	 */
	public function delete($key)
	{
		if (!is_scalar($key) || $key === '')
		{
			throw new Psr16_InvalidArgumentException;
		}

		$key = $this->_getRealKey($key);
		return $this->_driver->delete($key);
	}

	/**
	 * Deletes all items in the cache pool.
	 *
	 * @return bool
	 */
	public function clear()
	{
		return $this->_driver->clear();
	}

	/**
	 * Returns a key-value array of the specified keys.
	 *
	 * @param iterable $keys
	 * @param mixed $default
	 * @throws Psr16_InvalidArgumentException
	 * @return iterable
	 */
	public function getMultiple($keys, $default = null)
	{
		$result = [];
		foreach ($keys as $key)
		{
			if (!is_scalar($key) || $key === '')
			{
				throw new Psr16_InvalidArgumentException;
			}

			$set_key = $this->_getRealKey($key);
			$result[$key] = $this->_driver->get($set_key) ?? $default;
		}
		return $result;
	}

	/**
	 * Persists a set of key-value pairs in the cache.
	 *
	 * @param iterable $values
	 * @param null|int|DateInterval $ttl
	 * @throws Psr16_InvalidArgumentException
	 * @return bool
	 */
	public function setMultiple($values, $ttl = null)
	{
		$result = true;
		$ttl = $this->_normalizeTTL($ttl);
		foreach ($values as $key => $value)
		{
			if (!is_scalar($key) || $key === '')
			{
				throw new Psr16_InvalidArgumentException;
			}

			$key = $this->_getRealKey($key);
			$result = $result && $this->_driver->set($key, $value, $ttl, $this->force);
		}
		return $result;
	}

	/**
	 * Removes multiple items from the pool.
	 *
	 * @param iterable $keys
	 * @throws Psr16_InvalidArgumentException
	 * @return bool
	 */
	public function deleteMultiple($keys)
	{
		$result = true;
		foreach ($keys as $key)
		{
			if (!is_scalar($key) || $key === '')
			{
				throw new Psr16_InvalidArgumentException;
			}

			$key = $this->_getRealKey($key);
			$result = $result && $this->_driver->delete($key);
		}
		return $result;
	}

	/**
	 * Confirms if the cache contains specified cache item.
	 *
	 * @param string $key
	 * @throws Psr16_InvalidArgumentException
	 * @return bool
	 */
	public function has($key)
	{
		if (!is_scalar($key) || $key === '')
		{
			throw new Psr16_InvalidArgumentException;
		}

		$key = $this->_getRealKey($key);
		return $this->_driver->exists($key);
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

	/**
	 * Normalize TTL value.
	 *
	 * @param null|int|DateInterval $ttl
	 * @return int
	 */
	protected function _normalizeTTL($ttl): int
	{
		if ($ttl instanceof DateInterval)
		{
			$start = new DateTimeImmutable;
			$end = $start->add($ttl);
			$ttl = $end->getTimestamp() - $start->getTimestamp();
		}
		if ($ttl === null)
		{
			$ttl = Cache::getDefaultTTL();
		}
		return $ttl;
	}
}
