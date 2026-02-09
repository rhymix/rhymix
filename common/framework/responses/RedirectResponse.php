<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Context;

/**
 * The redirect response class.
 *
 * This class will send a HTTP redirect to the specified URL.
 */
class RedirectResponse extends AbstractResponse
{
	/**
	 * Internal state.
	 */
	protected string $_url = '';

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
		yield '';
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
		$headers[] = 'Location: ' . utf8_normalize_spaces($this->_url);
		return $headers;
	}
}
