<?php

namespace Rhymix\Framework\Exceptions;

use Psr\Cache\InvalidArgumentException as PsrCacheInvalidArgumentExceptionInterface;

class PsrCacheInvalidArgumentException extends \InvalidArgumentException implements PsrCacheInvalidArgumentExceptionInterface
{
}
