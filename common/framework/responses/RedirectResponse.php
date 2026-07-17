<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Context;

/**
 * The redirect response class.
 *
 * This class will send a HTTP redirect to the specified URL,
 * unless the request was made by XMLHttpRequest,
 * in which case it will send a JSON response with the redirect URL.
 */
class RedirectResponse extends AbstractResponse
{
	/*
	 * Internal state.
	 */
	protected string $_url = '';
	protected bool $_fake_redirect = false;

	/**
	 * Get the content.
	 *
	 * @return string
	 */
	public function getRedirectUrl(): string
	{
		return $this->_url;
	}

	/**
	 * Set the content.
	 *
	 * @param string $url
	 * @return self
	 */
	public function setRedirectUrl(string $url): self
	{
		$this->_url = $url;
		return $this;
	}

	/**
	 * Render the full response.
	 *
	 * @return iterable
	 */
	public function render(): iterable
	{
		if ($this->_status_code === 200 && in_array($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', ['Rhymix.ajax', 'XMLHttpRequest']))
		{
			$this->_fake_redirect = true;
			$this->setContentType('application/json');
			yield json_encode(['error' => 0, 'message' => 'success', 'redirect_url' => $this->_url]) . "\n";
		}
		else
		{
			yield '';
		}
	}

	/**
	 * Finalize the response for presentation.
	 *
	 * @param string $content
	 * @return string
	 */
	public function finalize(string $content): string
	{
		return $content;
	}

	/**
	 * Get headers for this response.
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		$headers = parent::getHeaders();
		if (!$this->_fake_redirect)
		{
			$headers[] = 'Location: ' . utf8_normalize_spaces($this->_url);
		}
		return $headers;
	}
}
