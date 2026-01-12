<?php

namespace Rhymix\Framework\Exceptions;

use Psr\Cache\InvalidArgumentException as PsrCacheInvalidArgumentExceptionInterface;

class InvalidCacheKey extends \InvalidArgumentException implements PsrCacheInvalidArgumentExceptionInterface
{

}
