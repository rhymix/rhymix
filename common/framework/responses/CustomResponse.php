<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;

/**
 * The custom response class.
 *
 * This class will print the raw string supplied to it.
 */
class CustomResponse extends AbstractResponse implements LateRenderingResponse
{
	/**
	 * Internal state.
	 */
	protected $_content = null;
	protected $_stream = null;

	/**
	 * Get the content.
	 *
	 * @return string
	 */
	public function getContent(): string
	{
		return $this->_content;
	}

	/**
	 * Set the content.
	 *
	 * @param string $content
	 * @return void
	 */
	public function setContent(string $content): void
	{
		$this->_content = $content;
	}

	/**
	 * Get the content as a stream.
	 *
	 * @return resource
	 */
	public function getStream()
	{
		return $this->_stream;
	}

	/**
	 * Set the content as a stream.
	 *
	 * @param resource $stream
	 * @param bool $rewind
	 * @return void
	 */
	public function setStream($stream, bool $rewind = true): void
	{
		$this->_stream = $stream;
		if ($rewind && is_resource($this->_stream))
		{
			rewind($this->_stream);
		}
	}

	/**
	 * Render the full response.
	 *
	 * @return iterable
	 */
	public function render(): iterable
	{
		if (isset($this->_content))
		{
			yield $this->_content;
			return;
		}
		elseif (is_resource($this->_stream))
		{
			while (!feof($this->_stream))
			{
				yield fread($this->_stream, 1024);
			}
			return;
		}
	}
}
