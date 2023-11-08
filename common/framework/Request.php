<?php

namespace Rhymix\Framework;

/**
 * The request class.
 */
class Request
{
	/**
	 * The actual HTTP request method.
	 */
	public $method = '';

	/**
	 * XE-compatible request method, e.g. XMLRPC.
	 */
	public $compat_method = '';

	/**
	 * The request URL, not including RX_BASEURL.
	 */
	public $url = '';

	/**
	 * The requested host name and current domain information.
	 */
	public $hostname = '';
	public $domain;

	/**
	 * The protocol used.
	 */
	public $protocol = 'http';

	/**
	 * The callback function for JSONP requests, also known as "JS callback" in XE.
	 */
	public $callback_function = '';

	/**
	 * Routing information, request arguments, and options.
	 */
	protected $_route_status = 200;
	protected $_route_options;
	public $module = '';
	public $mid = '';
	public $act = '';
	public $args = [];

	/**
	 * Constructor.
	 *
	 * @param string $method
	 * @param string $url
	 * @param string $hostname
	 * @param string $protocol
	 */
	public function __construct(string $method = '', string $url = '', string $hostname = '', string $protocol = '')
	{
		// Set instance properties.
		$this->method = $method ?: (\PHP_SAPI === 'cli' ? 'CLI' : ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
		$this->compat_method = \Context::getRequestMethod();
		$this->url = $url ?: \RX_REQUEST_URL;
		$this->hostname = $hostname ?: $_SERVER['HTTP_HOST'];
		$this->protocol = \RX_SSL ? 'https' : 'http';
		$this->callback_function = \Context::get('js_callback_func') ?: '';

		// Initialize the arguments object.
		$this->args = [];

		// Initialize route options.
		$this->_route_options = new \stdClass;
		$this->_route_options->cache_control = true;
		$this->_route_options->check_csrf = true;
		$this->_route_options->is_forwarded = false;
		$this->_route_options->is_indexable = true;
		$this->_route_options->enable_session = true;
	}

	/**
	 * Get a request argument, optionally coerced into a type.
	 *
	 * @param string $name
	 * @param string $type
	 * @return mixed
	 */
	public function get(string $name, string $type = '')
	{
		$value = $this->args[$name] ?? null;
		switch ($type)
		{
			case 'int': $value = (int)$value; break;
			case 'float': $value = (float)$value; break;
			case 'bool': $value = tobool($value); break;
		}
		return $value;
	}

	/**
	 * Get all request arguments.
	 *
	 * @return object
	 */
	public function getAll(): object
	{
		return (object)($this->args);
	}

	/**
	 * Get the complete URL of this request.
	 *
	 * @return string
	 */
	public function getFullUrl(): string
	{
		return sprintf('%s://%s%s%s', $this->protocol, $this->hostname, \RX_BASEURL, $this->url);
	}

	/**
	 * Get the HTTP method of this request.
	 *
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * Get the JS callback function.
	 *
	 * @return string
	 */
	public function getCallbackFunction(): string
	{
		return $this->callback_function;
	}

	/**
	 * Get route status.
	 *
	 * @return int
	 */
	public function getRouteStatus(): int
	{
		return $this->_route_status;
	}

	/**
	 * Set a request argument.
	 *
	 * @param string $name
	 * @param string|array $value
	 * @return void
	 */
	public function set(string $name, $value): void
	{
		if ($value === null || $value === '')
		{
			unset($this->args[$name]);
		}
		else
		{
			$this->args[$name] = $value;
		}
	}

	/**
	 * Set all request arguments.
	 *
	 * @param array $args
	 * @return void
	 */
	public function setAll(array $args): void
	{
		$this->args = array_filter($args, function($item) {
			return $item !== null && $item !== '';
		});
	}


	/**
	 * Set route status.
	 *
	 * @param int $status
	 * @return void
	 */
	public function setRouteStatus(int $status): void
	{
		$this->_route_status = $status;
	}

	/**
	 * Get route options.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getRouteOption(string $name)
	{
		return $this->_route_options->{$name} ?? null;
	}

	/**
	 * Get all route options.
	 *
	 * @return object
	 */
	public function getRouteOptions(): object
	{
		return $this->_route_options;
	}

	/**
	 * Set route option.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setRouteOption(string $name, $value): void
	{
		$this->_route_options->{$name} = $value;
	}
}
