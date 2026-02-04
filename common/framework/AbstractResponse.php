<?php

namespace Rhymix\Framework;

use ModuleHandler;

/**
 * The base class for responses.
 *
 * This is an abstract class that cannot be instantiated directly.
 * Use one of the subclasses defined in Rhymix\Framework\Responses.
 */
abstract class AbstractResponse
{
	/**
	 * Internal variables.
	 */
	protected int $_status_code = 200;
	protected string $_content_type = '';
	protected string $_charset = '';
	protected array $_headers = [];
	protected array $_vars = [];

	/**
	 * The constructor accepts a status code and an optional collection of vars.
	 *
	 * @param int $status_code
	 * @param array|object $vars
	 */
	public function __construct(int $status_code = 200, $vars = null)
	{
		$this->_status_code = $status_code;
		$this->_vars = is_array($vars) ? $vars : (is_object($vars) ? get_object_vars($vars) : []);
	}

	/**
	 * Get the status code.
	 *
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->_status_code;
	}

	/**
	 * Set the status code.
	 *
	 * @param int $status_code
	 * @return self
	 */
	public function setStatusCode(int $status_code): self
	{
		$this->_status_code = $status_code;
		return $this;
	}

	/**
	 * Get the content type.
	 *
	 * @return string
	 */
	public function getContentType(): string
	{
		return $this->_content_type;
	}

	/**
	 * Set the content type.
	 *
	 * @param string $content_type
	 * @return self
	 */
	public function setContentType(string $content_type): self
	{
		$this->_content_type = $content_type;
		return $this;
	}

	/**
	 * Get the character set.
	 *
	 * @return string
	 */
	public function getCharacterSet(): string
	{
		return $this->_charset;
	}

	/**
	 * Set the character set.
	 *
	 * @param string $charset
	 * @return self
	 */
	public function setCharacterSet(string $charset): self
	{
		$this->_charset = $charset;
		return $this;
	}

	/**
	 * Get all variables.
	 *
	 * @return array
	 */
	public function getVars(): array
	{
		return $this->_vars;
	}

	/**
	 * Set all variables.
	 *
	 * @param array $vars
	 * @return self
	 */
	public function setVars(array $vars): self
	{
		$this->_vars = $vars;
		return $this;
	}

	/**
	 * Get a variable.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name)
	{
		return isset($this->_vars[$name]) ? $this->_vars[$name] : null;
	}

	/**
	 * Add or replace a variable.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $name, $value): void
	{
		$this->_vars[$name] = $value;
	}

	/**
	 * Check if a variable is set.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset(string $name): bool
	{
		return isset($this->_vars[$name]);
	}

	/**
	 * Unset a variable.
	 *
	 * @param string $name
	 * @return void
	 */
	public function __unset(string $name): void
	{
		unset($this->_vars[$name]);
	}

	/**
	 * Treating this object as a string will call the render() method.
	 *
	 * This behavior cannot be overridden by subclasses.
	 *
	 * @return string
	 */
	final public function __toString(): string
	{
		return implode('', iterator_to_array($this->render()));
	}

	/**
	 * Render the full response.
	 *
	 * This method must be implemented by each subclass.
	 *
	 * @return iterable
	 */
	abstract public function render(): iterable;

	/**
	 * Add a header to this response.
	 *
	 * @param string $header
	 * @return self
	 */
	public function addHeader(string $header): self
	{
		$this->_headers[] = $header;
		return $this;
	}

	/**
	 * Get headers for this response.
	 *
	 * This method may be overridden or expanded by each subclass.
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		if ($this->_status_code !== 200)
		{
			$headers = ['HTTP/1.1 ' . $this->_status_code . ' ' . ModuleHandler::STATUS_MESSAGES[$this->_status_code]];
		}
		if ($this->_content_type)
		{
			$headers[] = 'Content-Type: ' . $this->_content_type . ($this->_charset ? ('; charset=' . $this->_charset) : '');
		}
		foreach ($this->_headers as $header)
		{
			$headers[] = $header;
		}
		return $headers;
	}

	/**
	 * Finalize the response for presentation.
	 *
	 * This method is primarily intended for the HTML response,
	 * but other subclasses are free to use it, too.
	 *
	 * @param string $content
	 * @return string
	 */
	public function finalize(string $content): string
	{
		return $content;
	}
}
