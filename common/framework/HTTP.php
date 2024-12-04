<?php

namespace Rhymix\Framework;

/**
 * The HTTP class for making requests to external resources.
 */
class HTTP
{
	/**
	 * The default timeout for requests.
	 */
	public const DEFAULT_TIMEOUT = 3;

	/**
	 * Cache the Guzzle client instance here.
	 */
	protected static $_client = null;

	/**
	 * Reset the Guzzle client instance.
	 */
	public static function resetClient(): void
	{
		self::$_client = null;
	}

	/**
	 * Make a GET request.
	 *
	 * @param string $url
	 * @param string|array $data
	 * @param array $headers
	 * @param array $cookies
	 * @param array $settings
	 * @return object
	 */
	public static function get(string $url, $data = null, array $headers = [], array $cookies = [], array $settings = []): object
	{
		return self::request($url, 'GET', $data, $headers, $cookies, $settings);
	}

	/**
	 * Make a HEAD request.
	 *
	 * @param string $url
	 * @param string|array $data
	 * @param array $headers
	 * @param array $cookies
	 * @param array $settings
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public static function head(string $url, $data = null, array $headers = [], array $cookies = [], array $settings = []): \Psr\Http\Message\ResponseInterface
	{
		return self::request($url, 'HEAD', $data, $headers, $cookies, $settings);
	}

	/**
	 * Make a POST request.
	 *
	 * @param string $url
	 * @param string|array $data
	 * @param array $headers
	 * @param array $cookies
	 * @param array $settings
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public static function post(string $url, $data = null, array $headers = [], array $cookies = [], array $settings = []): \Psr\Http\Message\ResponseInterface
	{
		return self::request($url, 'POST', $data, $headers, $cookies, $settings);
	}

	/**
	 * Download a file.
	 *
	 * This helps save memory when downloading large files,
	 * by streaming the response body directly to the filesystem
	 * instead of buffering it in memory.
	 *
	 * @param string $url
	 * @param string $target_filename
	 * @param string|array $data
	 * @param array $headers
	 * @param array $cookies
	 * @param array $settings
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public static function download(string $url, string $target_filename, string $method = 'GET', $data = null, array $headers = [], array $cookies = [], array $settings = []): \Psr\Http\Message\ResponseInterface
	{
		// Try to create the parent directory to save the file.
		$target_dir = dirname($target_filename);
		if (!Storage::isDirectory($target_dir) && !Storage::createDirectory($target_dir))
		{
			return false;
		}

		// Pass to request() with appropriate settings for the filename.
		$settings['sink'] = $target_filename;
		return self::request($url, $method, $data, $headers, $cookies, $settings);
	}

	/**
	 * Make any type of request.
	 *
	 * @param string $url
	 * @param string $method
	 * @param string|array $data
	 * @param array $headers
	 * @param array $cookies
	 * @param array $settings
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public static function request(string $url, string $method = 'GET', $data = null, array $headers = [], array $cookies = [], array $settings = []): \Psr\Http\Message\ResponseInterface
	{
		// Create a new Guzzle client, or reuse a cached one if available.
		$guzzle = self::_createClient();

		// Populate settings.
		$settings = self::_populateSettings($url, $method, '', $data, $headers, $cookies, $settings);

		// Send the request.
		try
		{
			$response = $guzzle->request($method, $url, $settings);
		}
		catch (\Throwable $e)
		{
			$response = new Helpers\HTTPHelper($e);
		}

		return $response;
	}

	/**
	 * Make any type of request asynchronously.
	 *
	 * @param string $url
	 * @param string $method
	 * @param string|array $data
	 * @param array $headers
	 * @param array $cookies
	 * @param array $settings
	 * @return \GuzzleHttp\Promise\PromiseInterface
	 */
	public static function async(string $url, string $method = 'GET', $data = null, array $headers = [], array $cookies = [], array $settings = []): \GuzzleHttp\Promise\PromiseInterface
	{
		// Create a new Guzzle client, or reuse a cached one if available.
		$guzzle = self::_createClient();

		// Populate settings.
		$settings = self::_populateSettings($url, $method, 'async', $data, $headers, $cookies, $settings);

		// Send the request.
		return $guzzle->requestAsync($method, $url, $settings);
	}

	/**
	 * Make multiple concurrent requests.
	 *
	 * @param array $requests[url, method, data, headers, cookies, settings]
	 * @return array
	 */
	public static function multiple(array $requests): array
	{
		// Create a new Guzzle client, or reuse a cached one if available.
		$guzzle = self::_createClient();

		// Add settings for each request.
		$promises = [];
		foreach ($requests as $key => $val)
		{
			$settings = self::_populateSettings($val['url'], $val['method'] ?? 'GET', 'multiple', $val['data'] ?? null, $val['headers'] ?? [], $val['cookies'] ?? [], $val['settings'] ?? []);
			$promise = $guzzle->requestAsync($val['method'] ?? 'GET', $val['url'], $settings);
			$promises[$key] = $promise;
		}

		// Wait for all the requests to complete.
		$responses = \GuzzleHttp\Promise\Utils::settle($promises)->wait();
		$result = [];
		foreach ($responses as $key => $val)
		{
			if ($val['state'] === 'fulfilled')
			{
				$result[$key] = $val['value'];
			}
			else
			{
				$result[$key] = new Helpers\HTTPHelper($val['reason']);
			}
		}

		return $result;
	}

	/**
	 * Create a Guzzle client with default options.
	 *
	 * @return \GuzzleHttp\Client
	 */
	protected static function _createClient(): \GuzzleHttp\Client
	{
		if (!self::$_client)
		{
			// Create default settings.
			$settings = [
				'http_errors' => false,
				'timeout' => self::DEFAULT_TIMEOUT,
				'verify' => \RX_BASEDIR . 'common/vendor/composer/ca-bundle/res/cacert.pem',
			];

			// Add proxy settings.
			$proxy = config('other.proxy') ?: (defined('__PROXY_SERVER__') ? constant('__PROXY_SERVER__') : '');
			if ($proxy !== '')
			{
				$proxy = parse_url($proxy);
				$proxy_scheme = preg_match('/^(https|socks)/', $proxy['scheme'] ?? '') ? ($proxy['scheme'] . '://') : 'http://';
				$proxy_auth = (!empty($proxy['user']) && !empty($proxy['pass'])) ? ($proxy['user'] . ':' . $proxy['pass'] . '@') : '';
				if (!empty($proxy['host']) && !empty($proxy['port']))
				{
					$settings['proxy'] = sprintf('%s%s%s:%s', $proxy_scheme, $proxy_auth, $proxy['host'], $proxy['port']);
				}
			}

			self::$_client = new \GuzzleHttp\Client($settings);
		}
		return self::$_client;
	}

	/**
	 * Populate a settings array with various options.
	 *
	 * @param string $url
	 * @param string $method
	 * @param string $type
	 * @param string|array $data
	 * @param array $headers
	 * @param array $cookies
	 * @param array $settings
	 * @return array
	 */
	protected static function _populateSettings(string $url, string $method = 'GET', string $type = '', $data = null, array $headers = [], array $cookies = [], array $settings = []): array
	{
		// Set headers.
		if ($headers)
		{
			$settings['headers'] = $headers;
		}

		// Set cookies.
		if ($cookies && !isset($settings['cookies']))
		{
			$jar = \GuzzleHttp\Cookie\CookieJar::fromArray($cookies, parse_url($url, \PHP_URL_HOST));
			$settings['cookies'] = $jar;
		}

		// Set the body or POST data.
		if (is_string($data) && strlen($data) > 0)
		{
			$settings['body'] = $data;
		}
		elseif (is_array($data) && count($data) > 0)
		{
			if (isset($headers['Content-Type']) && preg_match('!^multipart/form-data\b!i', $headers['Content-Type']))
			{
				$settings['multipart'] = [];
				foreach ($data as $key => $val)
				{
					$settings['multipart'][] = ['name' => $key, 'contents' => $val];
				}
			}
			elseif (isset($headers['Content-Type']) && preg_match('!^application/json\b!i', $headers['Content-Type']))
			{
				$settings['json'] = $data;
			}
			elseif ($method !== 'GET')
			{
				$settings['form_params'] = $data;
			}
			else
			{
				$settings['query'] = $data;
			}
		}

		// Set the callback for debugging.
		if (Debug::isEnabledForCurrentUser() && in_array('slow_remote_requests', config('debug.display_content')))
		{
			$log = array();
			$log['url'] = $url;
			$log['verb'] = $method;
			$log['type'] = $type;
			$log['status'] = 0;
			$log['elapsed_time'] = 0;
			$log['called_file'] = $log['called_line'] = $log['called_method'] = null;
			$log['redirect_to'] = null;
			$log['backtrace'] = [];

			$bt = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
			foreach ($bt as $no => $call)
			{
				if($call['file'] !== __FILE__ && $call['file'] !== \RX_BASEDIR . 'classes/file/FileHandler.class.php')
				{
					$log['called_file'] = $bt[$no]['file'];
					$log['called_line'] = $bt[$no]['line'];
					$next = $no + 1;
					if (isset($bt[$next]))
					{
						$log['called_method'] = ($bt[$next]['class'] ?? '') . ($bt[$next]['type'] ?? '') . ($bt[$next]['function'] ?? '');
						$log['backtrace'] = array_slice($bt, $next, 1);
					}
					break;
				}
			}

			$settings['on_stats'] = function($stats) use($log) {
				$log['url'] = strval($stats->getEffectiveUri());
				$log['status'] = $stats->hasResponse() ? $stats->getResponse()->getStatusCode() : 0;
				$log['elapsed_time'] = $stats->getTransferTime();
				$log['redirect_to'] = $stats->hasResponse() ? ($stats->getResponse()->getHeaderLine('location') ?: null) : null;
				Debug::addRemoteRequest($log);
			};
		}

		return $settings;
	}

	/**
	 * Record a request with the Debug class.
	 *
	 * @param string $url
	 * @param int $status_code
	 * @param float $elapsed_time
	 * @return void
	 */
	protected static function _debug(string $url, int $status_code = 0, float $elapsed_time = 0): void
	{
		if (!isset($GLOBALS['__remote_request_elapsed__']))
		{
			$GLOBALS['__remote_request_elapsed__'] = 0;
		}
		$GLOBALS['__remote_request_elapsed__'] += $elapsed_time;

		if (Debug::isEnabledForCurrentUser())
		{
			$log = array();
			$log['url'] = $url;
			$log['status'] = $status_code;
			$log['elapsed_time'] = $elapsed_time;
			$log['called_file'] = $log['called_line'] = $log['called_method'] = null;
			$log['backtrace'] = [];

			if (in_array('slow_remote_requests', config('debug.display_content')))
			{
				$bt = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
				foreach ($bt as $no => $call)
				{
					if($call['file'] !== __FILE__ && $call['file'] !== \RX_BASEDIR . 'classes/file/FileHandler.class.php')
					{
						$log['called_file'] = $bt[$no]['file'];
						$log['called_line'] = $bt[$no]['line'];
						$next = $no + 1;
						if (isset($bt[$next]))
						{
							$log['called_method'] = $bt[$next]['class'].$bt[$next]['type'].$bt[$next]['function'];
							$log['backtrace'] = array_slice($bt, $next, 1);
						}
						break;
					}
				}
			}


		}
	}
}
