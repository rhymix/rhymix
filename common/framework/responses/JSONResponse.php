<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Context;

/**
 * The JSON response class.
 *
 * This is a new format that doesn't apply any additional conversions.
 * It will produce the raw output of the json_encode() function.
 *
 * For example, [1 => 'foo', 3 => 'bar'] will be printed as
 * {"1":"foo","3":"bar"}.
 */
class JSONResponse extends AbstractResponse
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
		yield json_encode($this->_vars);
	}

	/**
	 * Finalize the response for presentation.
	 *
	 * @param string $content
	 * @return string
	 */
	public function finalize(string $content): string
	{
		Context::setResponseMethod('JSON');
		return $content;
	}
}
