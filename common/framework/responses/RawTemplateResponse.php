<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Rhymix\Framework\Template;
use Context;

/**
 * The raw template response class.
 *
 * This class will print the raw output of a template file, without
 * assuming that it contains a complete HTML document, or any HTML at all.
 * All that matters is that the output be printable.
 *
 * This can be used to print a CSV file or RSS feed, for example.
 */
class RawTemplateResponse extends AbstractResponse
{
	/**
	 * Internal state.
	 */
	protected string $_template_dirname;
	protected string $_template_filename;

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
	 * Get the current template path.
	 *
	 * @return ?string
	 */
	public function getTemplatePath(): ?string
	{
		return $this->_template_dirname;
	}

	/**
	 * Get the current template filename.
	 *
	 * @return ?string
	 */
	public function getTemplateFile(): ?string
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
	 * Finalize the response for presentation.
	 *
	 * @param string $content
	 * @return string
	 */
	public function finalize(string $content): string
	{
		return $content;
	}
}
