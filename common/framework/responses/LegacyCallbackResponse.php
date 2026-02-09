<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Context;

/**
 * The legacy callback response class.
 *
 * This format was used by XE 1.x for JSONP-like AJAX responses.
 * It was always loaded in a hidden iframe, so it is wrapped in a HTML document
 * instead of javascript as in standard JSONP.
 */
class LegacyCallbackResponse extends AbstractResponse
{
	/**
	 * Override the default content type.
	 */
	protected string $_content_type = 'text/html';

	/**
	 * Render the full response.
	 *
	 * @return iterable
	 */
	public function render(): iterable
	{
		// Ensure that 'error' and 'message' are always present.
		if (!isset($this->_vars['error']) || !isset($this->_vars['message']))
		{
			$default_vars = ['error' => 0, 'message' => 'success'];
			$this->_vars = array_merge($default_vars, $this->_vars);
		}

		// Encode the result as JSON. If there is an error, also encode the error as JSON.
		$result = @json_encode($this->_vars);
		if (json_last_error() != \JSON_ERROR_NONE)
		{
			trigger_error('JSON encoding error: ' . json_last_error_msg(), E_USER_WARNING);
			$result = json_encode([
				'error' => -1,
				'message' => 'JSON encoding error',
			]);
		}

		// Output the result.
		yield "<!DOCTYPE html>\n";
		yield "<html>\n<head><title></title></head>\n<body>\n<script>\n";
		yield '//<![CDATA[' . "\n";
		yield sprintf("%s(%s);\n", Context::getInstance()->js_callback_func ?? '', $result);
		yield '//]]>' . "\n";
		yield "</script>\n</body>\n</html>";
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
		return 'JS_CALLBACK';
	}
}
