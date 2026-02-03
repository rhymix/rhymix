<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Context;

/**
 * The legacy redirect response class.
 *
 * This format was known as "virtual XML" in XE 1.x.
 * It loaded an HTML document in an iframe, which alerted a message
 * and redirected the parent/opener window.
 */
class LegacyRedirectResponse extends AbstractResponse
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
		yield "<!DOCTYPE html>\n";
		yield "<html>\n<head><title></title></head>\n<body>\n<script>\n";

		if (isset($this->_vars['error']) && $this->_vars['error'] != 0)
		{
			$message = json_encode(strval($this->_vars['message'] ?? 'Error'));

			yield "alert($message);\n";
		}
		else
		{
			$redirect_url = $this->_vars['redirect_url'] ?? Context::get('xeRequestURI');
			$redirect_url = json_encode(preg_replace('/#(.+)$/', '', strval($redirect_url)));

			yield "if (opener) {\n";
			yield "  opener.location.href = {$redirect_url};\n";
			yield "} else {\n";
			yield "  parent.location.href = {$redirect_url};\n";
			yield "}\n";
		}

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
}
