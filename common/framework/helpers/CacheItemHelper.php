<?php

namespace Rhymix\Framework\Helpers;

use Psr\Cache\CacheItemInterface;

/**
 * Helper class to implement PSR-6 cache item using Rhymix cache configuration.
 */
class CacheItemHelper implements CacheItemInterface
{
	/**
	 * Attributes
	 */
	public $key = '';
	public $value = null;
	public $expires = null;

	/**
	 * Constructor.
	 *
	 * @param string $key
	 */
	public function __construct(string $key)
	{
		$this->key = $key;
	}

    /**
     * Returns the key for the current cache item.
     *
     * @return string
     */
    public function getKey()
	{
		return $this->key;
	}

    /**
     * Retrieves the value of the item from the cache associated with this object's key.
	 *
     * @return mixed
     */
    public function get()
	{
		if ($this->value === null)
		{
			$this->value = \Rhymix\Framework\Cache::get($this->key);
		}
		return $this->value;
	}

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
	 *
     * @return bool
     */
    public function isHit()
	{
		return \Rhymix\Framework\Cache::exists($this->key);
	}

    /**
     * Sets the value represented by this cache item.
     *
     * @param mixed $value
     * @return static
     */
    public function set($value)
	{
		$this->value = $value;
		return $this;
	}

    /**
     * Sets the expiration time for this cache item.
     *
     * @param \DateTimeInterface|null $expiration
     * @return static
     */
    public function expiresAt($expiration)
	{
		$this->expires = $expiration->getTimestamp();
		return $this;
	}

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval|null $time
     * @return static
     */
    public function expiresAfter($time)
	{
		if ($time instanceof \DateInterval)
		{
			$date = new \DateTime();
			$date->add($time);
			$this->expires = $date->getTimestamp();
		}
		elseif (is_int($time))
		{
			$this->expires = time() + $time;
		}
		else
		{
			$this->expires = null;
		}
		return $this;
	}
}
