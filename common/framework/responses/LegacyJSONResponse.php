<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Context;

/**
 * The legacy JSON response class.
 *
 * This format was used by XE 1.x for JSON-based AJAX responses.
 * It is similar to the regular JSON response, except:
 *
 * 1) It will convert associative arrays with all-numeric keys into lists.
 *    For example, [1 => 'foo', 3 => 'bar'] will be printed as ["foo","bar"].
 *
 * 2) It will produce arrays nested under an 'items' key for compatibility
 *    with legacy XML responses, but only if the request method was XMLRPC.
 */
class LegacyJSONResponse extends AbstractResponse
{
	/**
	 * Override the default content type.
	 */
	protected string $_content_type = 'application/json';

	/**
	 * Render the full response.
	 *
	 * @return iterable
	 */
	public function render(): iterable
	{
		$vars = self::_convertArray($this->_vars, Context::getRequestMethod());
		if (!isset($vars['error']) || !isset($vars['message']))
		{
			$default_vars = ['error' => 0, 'message' => 'success'];
			$vars = array_merge($default_vars, $vars);
		}

		$result = @json_encode($vars) . "\n";
		if (json_last_error() != \JSON_ERROR_NONE)
		{
			trigger_error('JSON encoding error: ' . json_last_error_msg(), E_USER_WARNING);
			yield json_encode([
				'error' => -1,
				'message' => 'JSON encoding error',
			]) . "\n";
		}
		else
		{
			yield $result;
		}
	}

	/**
	 * For backward compatibility, this response always returns status code 200.
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		$this->_status_code = 200;
		return parent::getHeaders();
	}

	/**
	 * Get the legacy response type string.
	 *
	 * @return string
	 */
	public function getLegacyResponseType(): string
	{
		return 'JSON';
	}

	/**
	 * Convert an array into a format that is compatible with the XE 1.x JSON response format.
	 *
	 * @param array $array
	 * @param string $compat_type
	 * @return array
	 */
	protected static function _convertArray(array $array, string $compat_type = 'JSON'): array
	{
		foreach ($array as $key => $value)
		{
			if (is_object($value))
			{
				$value = get_object_vars($value);
			}
			if (is_array($value))
			{
				$value = self::_convertArray($value, $compat_type);
				if (self::_isNumericArray($value))
				{
					if ($compat_type === 'XMLRPC')
					{
						$value = array('item' => array_values($value));
					}
					else
					{
						$value = array_values($value);
					}
				}
			}
			$array[$key] = $value;
		}
		return $array;
	}

	/**
	 * Check if an array only has numeric keys.
	 *
	 * The keys do not need to be sequential.
	 *
	 * @param array $array
	 * @return bool
	 */
	protected static function _isNumericArray(array $array): bool
	{
		if (!is_array($array) || !count($array))
		{
			return false;
		}
		foreach ($array as $key => $value)
		{
			if (!is_numeric($key))
			{
				return false;
			}
		}
		return true;
	}
}
