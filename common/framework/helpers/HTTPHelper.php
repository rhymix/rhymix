<?php

namespace Rhymix\Framework\Helpers;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\MessageTrait;

/**
 * HTTP helper class.
 *
 * We use instances of this class to wrap HTTP error conditions
 * not handled by Guzzle.
 */
class HTTPHelper implements ResponseInterface
{
	use MessageTrait;

	/**
	 * Information about the exception thrown.
	 */
	protected $_exception_class = '';
	protected $_exception_code = 0;
    protected $_error_message = '';

    /**
     * Create a HTTPHelper instance from an exception.
	 *
	 * @param \Throwable $exception
     */
    public function __construct(\Throwable $exception)
	{
		$this->_exception_class = get_class($exception);
		$this->_exception_code = $exception->getCode();
		$this->_error_message = $exception->getMessage();
    }

	/**
	 * Methods to implement ResponseInterface.
	 */
    public function getStatusCode(): int
    {
        return 0;
    }
    public function getReasonPhrase(): string
    {
        return $this->_error_message;
    }
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $new = clone $this;
		$new->_error_message = $reasonPhrase;
        return $new;
    }
}
