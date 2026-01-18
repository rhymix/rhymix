<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Rhymix\Framework\Template;

/**
 * The HTML response class.
 *
 * This is the default response for most web pages.
 */
class HTMLResponse extends AbstractResponse
{
	/**
	 * Override the default content type.
	 */
	protected string $_content_type = 'text/html';
	protected string $_charset = 'UTF-8';

	/**
	 * Internal state.
	 */
	protected string $_layout_dirname = '';
	protected string $_layout_filename = '';
	protected string $_template_dirname = '';
	protected string $_template_filename = '';

	/**
	 * Set the layout path and filename.
	 *
	 * @param string $dirname
	 * @param string $filename
	 * @return self
	 */
	public function setLayout(string $dirname, string $filename): self
	{
		$this->_layout_dirname = $dirname;
		$this->_layout_filename = $filename;
		return $this;
	}

	/**
	 * Set the template path and filename.
	 *
	 * @param string $dirname
	 * @param string $filename
	 * @return self
	 */
	public function setTemplate(string $dirname, string $filename): self
	{
		$this->_template_dirname = $dirname;
		$this->_template_filename = $filename;
		return $this;
	}

	/**
	 * Get the current layout path.
	 *
	 * @return string
	 */
	public function getLayoutPath(): string
	{
		return $this->_layout_dirname;
	}

	/**
	 * Get the current layout filename.
	 *
	 * @return string
	 */
	public function getLayoutFile(): string
	{
		return $this->_layout_filename;
	}

	/**
	 * Get the current template path.
	 *
	 * @return string
	 */
	public function getTemplatePath(): string
	{
		return $this->_template_dirname;
	}

	/**
	 * Get the current template filename.
	 *
	 * @return string
	 */
	public function getTemplateFile(): string
	{
		return $this->_template_filename;
	}

	/**
	 * Render the full response.
	 *
	 * @return iterable
	 */
	public function render(): iterable
	{
		if ($this->_template_dirname && $this->_template_filename)
		{
			$tpl = new Template($this->_template_dirname, $this->_template_filename);
			if ($this->_vars)
			{
				$tpl->setVars($this->_vars);
			}
			yield $tpl->compile();
		}
		else
		{
			yield '';
		}
	}

	/**
	 * Finalize the response.
	 *
	 * @param string $content
	 * @return string
	 */
	public function finalize(string $content): string
	{
		return $content;
	}
}
