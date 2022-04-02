<?php

namespace Rhymix\Framework\Parsers;

use Rhymix\Framework\Template;

class TemplateParser_v1
{
	/**
	 * Configuration variables
	 */
	protected $_autoescape = false;
	
	/**
	 * Convert template code into PHP.
	 * 
	 * @param string $content
	 * @param Template $template_info
	 * @return string
	 */
	public function convert(string $content, Template $template_info): string
	{
		// Extract configuration variables.
		$this->_autoescape = $template_info->config->autoescape;
		
		return $content;
	}
}
