<?php

namespace Rhymix\Framework\Exceptions\Psr;

use Psr\SimpleCache\InvalidArgumentException as PsrSimpleCacheInvalidArgumentExceptionInterface;

class Psr16_InvalidArgumentException extends \InvalidArgumentException implements PsrSimpleCacheInvalidArgumentExceptionInterface
{

}
