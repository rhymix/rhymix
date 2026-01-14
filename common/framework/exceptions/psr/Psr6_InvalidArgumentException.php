<?php

namespace Rhymix\Framework\Exceptions\Psr;

use Psr\Cache\InvalidArgumentException as PsrCacheInvalidArgumentExceptionInterface;

class Psr6_InvalidArgumentException extends \InvalidArgumentException implements PsrCacheInvalidArgumentExceptionInterface
{

}
