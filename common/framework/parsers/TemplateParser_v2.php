<?php

namespace Rhymix\Framework\Parsers;

use Rhymix\Framework\Template;

class TemplateParser_v2
{
	/**
	 * Store template info here.
	 */
	public $template_info;
	
	/**
	 * Convert template code into PHP.
	 * 
	 * @param string $content
	 * @param Template $template_info
	 * @return string
	 */
	public function convert(string $content, Template $template_info): string
	{
		// Store template info in instance property.
		$this->template_info = $template_info;
		
		// Convert echo statements.
		$content = preg_replace('!\{([^{}]+)\}!', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\', false); ?>', $content);
		
		// Remove spaces before and after all PHP tags, in order to maintain clean alignment.
		$content = preg_replace([
			'!(?<=^|\n)([\x20\x09]+)(<\?(?:php\b|=))!',
			'!(\?>)([\x20\x09]+)(?=$|\r|\n)!',
		], ['$2', '$1'], $content);
		
		return $content;
	}
}
