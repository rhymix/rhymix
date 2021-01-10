<?php

namespace Rhymix\Framework\Drivers\Social;

abstract class Base extends \Rhymix\Framework\Social implements \Rhymix\Framework\Drivers\SocialInterface
{
	public static function getInstance(array $config)
	{
		return new static($config);
	}
}
