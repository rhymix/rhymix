<?php

namespace Rhymix\Framework;

/**
 * The formatter class.
 */
class Formatter
{
	/**
	 * Options for text to HTML conversion.
	 */
	const TEXT_NEWLINE_AS_P = 1;
	const TEXT_DOUBLE_NEWLINE_AS_P = 2;
	
	/**
	 * Options for Markdown to HTML conversion.
	 */
	const MD_NEWLINE_AS_BR = 16;
	const MD_ENABLE_EXTRA = 128;
	
	/**
	 * Convert plain text to HTML.
	 * 
	 * @param string $text
	 * @param int $options (optional)
	 * @return string
	 */
	public static function text2html($text, $options = 0)
	{
		// This option uses <p> instead of <br> to separate lines.
		if ($options & self::TEXT_NEWLINE_AS_P)
		{
			$lines = array_map('trim', explode("\n", escape(trim($text))));
			$result = '';
			foreach ($lines as $line)
			{
				$result .= "<p>$line</p>\n";
			}
			return $result;
		}
		
		// This option uses <br> to separate lines and <p> to separate paragraphs.
		if ($options & self::TEXT_DOUBLE_NEWLINE_AS_P)
		{
			$lines = preg_replace('!(<br />)+\s*$!', '', nl2br(escape(trim($text))));
			$lines = preg_split('!(<br />\s*)+<br />!', $lines);
			foreach ($lines as $line)
			{
				$result .= "<p>\n" . trim($line) . "\n</p>\n";
			}
			return $result;
		}
		
		// The default is to use <br> always.
		return nl2br(escape(trim($text))) . "<br />\n";
	}
	
	/**
	 * Convert HTML to plain text.
	 * 
	 * @param string $html
	 * @return string
	 */
	public static function html2text($html)
	{
		// Add line breaks after <br> and <p> tags.
		$html = preg_replace('!<br[^>]*>\s*!i', "\n", $html);
		$html = preg_replace('!<p\b[^>]*>\s*!i', '', $html);
		$html = preg_replace('!</p[^>]*>\s*!i', "\n\n", $html);
		
		// Encode links and images to preserve essential information.
		$html = preg_replace_callback('!<a\b[^>]*href="([^>"]+)"[^>]*>([^<]*)</a>!i', function($matches) {
			return trim($matches[2] . ' &lt;' . $matches[1] . '&gt;');
		}, $html);
		$html = preg_replace_callback('!<img\b[^>]*src="([^>"]+)"[^>]*>!i', function($matches) {
			$title = preg_match('!title="([^>"]+)"!i', $matches[0], $m) ? $m[1] : null;
			$title = $title ?: (preg_match('!alt="([^>"]+)"!i', $matches[0], $m) ? $m[1] : 'IMAGE');
			return trim('[' . $title . '] &lt;' . $matches[1] . '&gt;');
		}, $html);
		
		// Strip all other HTML.
		$text = html_entity_decode(strip_tags($html));
		unset($html);
		
		// Normalize whitespace and return.
		$text = str_replace("\r\n", "\n", $text);
		$text = preg_replace('/\n(?:\s*\n)+/', "\n\n", $text);
		return trim($text) . "\n";
	}
	
	/**
	 * Convert Markdown to HTML.
	 * 
	 * @param string $markdown
	 * @param int $options (optional)
	 * @return string
	 */
	public static function markdown2html($markdown, $options = 0)
	{
		if ($options & self::MD_ENABLE_EXTRA)
		{
			$classes = array('footnote-ref', 'footnote-backref');
			$parser = new \Michelf\MarkdownExtra;
			$parser->fn_id_prefix = 'user_content_';
		}
		else
		{
			$classes = false;
			$parser = new \Michelf\Markdown;
		}
		
		if ($options & self::MD_NEWLINE_AS_BR)
		{
			$parser->hard_wrap = true;
		}
		
		$html = $parser->transform($markdown);
		return Filters\HTMLFilter::clean($html, $classes);
	}
	
	/**
	 * Convert HTML to Markdown.
	 * 
	 * @param string $html
	 * @return string
	 */
	public static function html2markdown($html)
	{
		$converter = new \League\HTMLToMarkdown\HtmlConverter();
		$converter->getConfig()->setOption('bold_style', '**');
		$converter->getConfig()->setOption('strip_tags', true);
		return trim($converter->convert($html)) . "\n";
	}
	
	/**
	 * Convert BBCode to HTML.
	 * 
	 * @param string $bbcode
	 * @return string
	 */
	public static function bbcode($bbcode)
	{
		$parser = new \JBBCode\Parser;
		$parser->addCodeDefinitionSet(new \JBBCode\DefaultCodeDefinitionSet());
		
		$builder = new \JBBCode\CodeDefinitionBuilder('quote', '<blockquote>{param}</blockquote>');
		$parser->addCodeDefinition($builder->build());
		$builder = new \JBBCode\CodeDefinitionBuilder('code', '<pre><code>{param}</code></pre>');
		$builder->setParseContent(false);
		$parser->addCodeDefinition($builder->build());
		
		$parser->parse($bbcode);
		$html = $parser->getAsHtml();
		return Filters\HTMLFilter::clean($html);
	}
	
	/**
	 * Apply smart quotes and other stylistic enhancements to HTML.
	 * 
	 * @param string $html
	 * @return string
	 */
	public static function applySmartQuotes($html)
	{
		return \Michelf\SmartyPants::defaultTransform($html, 'qbBdDiew');
	}
	
	/**
	 * Compile LESS into CSS.
	 * 
	 * @param string|array $source_filename
	 * @param string $target_filename
	 * @param array $variables (optional)
	 * @parsm bool $minify (optional)
	 * @return bool
	 */
	public static function compileLESS($source_filename, $target_filename, $variables = array(), $minify = false)
	{
		// Get the cleaned and concatenated content.
		$content = self::concatCSS($source_filename, $target_filename);
		
		// Compile!
		try
		{
			$less_compiler = new \lessc;
			$less_compiler->setFormatter($minify ? 'compressed' : 'lessjs');
			$less_compiler->setImportDir(array(dirname(is_array($source_filename) ? array_first($source_filename) : $source_filename)));
			if ($variables)
			{
				$less_compiler->setVariables($variables);
			}
			
			$charset = strpos($content, '@charset') === false ? ('@charset "UTF-8";' . "\n") : '';
			$content = $charset . $less_compiler->compile($content) . "\n";
			$result = true;
		}
		catch (\Exception $e)
		{
			$content = '/*' . "\n" . 'Error while compiling LESS:' . "\n" . $e->getMessage() . "\n" . '*/' . "\n";
			$result = false;
		}
		
		// Save the result to the target file.
		Storage::write($target_filename, $content);
		return $result;
	}
	
	/**
	 * Compile SCSS into CSS.
	 * 
	 * @param string|array $source_filename
	 * @param string $target_filename
	 * @param array $variables (optional)
	 * @parsm bool $minify (optional)
	 * @return bool
	 */
	public static function compileSCSS($source_filename, $target_filename, $variables = array(), $minify = false)
	{
		// Get the cleaned and concatenated content.
		$content = self::concatCSS($source_filename, $target_filename);
		
		// Compile!
		try
		{
			$scss_compiler = new \ScssPhp\ScssPhp\Compiler;
			$scss_compiler->setFormatter($minify ? '\ScssPhp\ScssPhp\Formatter\Crunched' : '\ScssPhp\ScssPhp\Formatter\Expanded');
			$scss_compiler->setImportPaths(array(dirname(is_array($source_filename) ? array_first($source_filename) : $source_filename)));
			if ($variables)
			{
				$scss_compiler->setVariables($variables);
			}
			
			$charset = strpos($content, '@charset') === false ? ('@charset "UTF-8";' . "\n") : '';
			$content = $charset . $scss_compiler->compile($content) . "\n";
			$result = true;
		}
		catch (\Exception $e)
		{
			$content = '/*' . "\n" . 'Error while compiling SCSS:' . "\n" . $e->getMessage() . "\n" . '*/' . "\n";
			$result = false;
		}
		
		// Save the result to the target file.
		Storage::write($target_filename, $content);
		return $result;
	}
	
	/**
	 * Minify CSS.
	 * 
	 * @param string|array $source_filename
	 * @param string $target_filename
	 * @return bool
	 */
	public static function minifyCSS($source_filename, $target_filename)
	{
		$minifier = new \MatthiasMullie\Minify\CSS();
		$minifier->setMaxImportSize(5);
		$minifier->setImportExtensions(['svg' => 'data:image/svg+xml']);
		if (is_array($source_filename))
		{
			foreach ($source_filename as $filename)
			{
				$minifier->add($filename);
			}
		}
		else
		{
			$minifier->add($source_filename);
		}
		$content = $minifier->execute($target_filename);
		Storage::write($target_filename, $content);
		return strlen($content) ? true : false;
	}
	
	/**
	 * Minify JS.
	 * 
	 * @param string|array $source_filename
	 * @param string $target_filename
	 * @return bool
	 */
	public static function minifyJS($source_filename, $target_filename)
	{
		$minifier = new \MatthiasMullie\Minify\JS();
		if (is_array($source_filename))
		{
			foreach ($source_filename as $filename)
			{
				$minifier->add($filename);
			}
		}
		else
		{
			$minifier->add($source_filename);
		}
		$content = $minifier->execute($target_filename);
		Storage::write($target_filename, $content);
		return strlen($content) ? true : false;
	}
	
	/**
	 * CSS concatenation subroutine for compileLESS() and compileSCSS().
	 * 
	 * @param string|array $source_filename
	 * @param string $target_filename
	 * @return string
	 */
	public static function concatCSS($source_filename, $target_filename)
	{
		$result = '';
		
		if (!is_array($source_filename))
		{
			$source_filename = array($source_filename);
		}
		
		foreach ($source_filename as $filename)
		{
			// Get the media query.
			if (is_array($filename) && count($filename) >= 2)
			{
				list($filename, $media) = $filename;
			}
			else
			{
				$media = null;
			}
			
			// Clean the content.
			$content = utf8_clean(file_get_contents($filename));
			
			// Convert all paths in LESS and SCSS imports, too.
			$import_type = ends_with('.scss', $filename) ? 'scss' : 'normal';
			$content = preg_replace_callback('/@import\s+(?:\\([^()]+\\))?([^;]+);/', function($matches) use($filename, $target_filename, $import_type) {
				$import_content = '';
				$import_files = array_map(function($str) use($filename, $import_type) {
					$str = trim(trim(trim(preg_replace('/^url\\(([^()]+)\\)$/', '$1', trim($str))), '"\''));
					if ($import_type === 'scss')
					{
						if (($dirpos = strrpos($str, '/')) !== false)
						{
							return dirname($filename) . '/' . substr($str, 0, $dirpos) . '/_' . substr($str, $dirpos + 1) . '.scss';
						}
						else
						{
							return dirname($filename) . "/_$str.scss";
						}
					}
					else
					{
						return dirname($filename) . '/' . $str;
					}
				}, explode(',', $matches[1]));
				foreach ($import_files as $import_filename)
				{
					if (file_exists($import_filename))
					{
						$import_content .= self::concatCSS($import_filename, $target_filename);
					}
				}
				return trim($import_content);
			}, $content);
			
			// Convert all paths to be relative to the new filename.
			$path_converter = new \MatthiasMullie\PathConverter\Converter($filename, $target_filename);
			$content = preg_replace_callback('/\burl\\(([^)]+)\\)/iU', function($matches) use ($path_converter) {
				$url = trim($matches[1], '\'"');
				if (!strlen($url) || $url[0] === '/' || preg_match('#^(?:https?|data):#', $url))
				{
					return $matches[0];
				}
				else
				{
					return 'url("' . str_replace('\\$', '$', escape_dqstr($path_converter->convert($url))) . '")';
				}
			}, $content);
			unset($path_converter);
			
			// Wrap the content in a media query if there is one.
			if ($media !== null)
			{
				$content = "@media $media {\n\n" . trim($content) . "\n\n}";
			}
			
			// Append to the result string.
			$original_filename = starts_with(\RX_BASEDIR, $filename) ? substr($filename, strlen(\RX_BASEDIR)) : $filename;
			$result .= '/* Original file: ' . $original_filename . ' */' . "\n\n" . trim($content) . "\n\n";
		}
		
		return $result;
	}
	
	/**
	 * JS concatenation subroutine.
	 * 
	 * @param string|array $source_filename
	 * @param string $target_filename
	 * @return string
	 */
	public static function concatJS($source_filename, $target_filename)
	{
		$result = '';
		
		if (!is_array($source_filename))
		{
			$source_filename = array($source_filename);
		}
		
		foreach ($source_filename as $filename)
		{
			// Get the IE condition.
			if (is_array($filename) && count($filename) >= 2)
			{
				list($filename, $targetie) = $filename;
			}
			else
			{
				$targetie = null;
			}
			
			// Clean the content.
			$content = utf8_clean(file_get_contents($filename));
			
			// Wrap the content in an IE condition if there is one.
			if ($targetie !== null)
			{
				$content = 'if (' . self::convertIECondition($targetie) . ') {' . "\n\n" . trim($content) . ";\n\n" . '}';
			}
			
			// Append to the result string.
			$original_filename = starts_with(\RX_BASEDIR, $filename) ? substr($filename, strlen(\RX_BASEDIR)) : $filename;
			$result .= '/* Original file: ' . $original_filename . ' */' . "\n\n" . trim($content) . ";\n\n";
		}
		
		return $result;
	}
	
	/**
	 * Convert IE conditional comments to JS conditions.
	 * 
	 * @param string $condition
	 * @return string
	 */
	public static function convertIECondition($condition)
	{
		$conversions = array(
			'/^true$/i' => 'true',
			'/^false$/i' => 'false',
			'/^IE$/i' => 'window.navigator.userAgent.match(/MSIE\s/)',
			'/^IE\s*(\d+)$/i' => '(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] == %d)',
			'/^gt IE\s*(\d+)$/i' => '(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] > %d)',
			'/^gte IE\s*(\d+)$/i' => '(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] >= %d)',
			'/^lt IE\s*(\d+)$/i' => '(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] < %d)',
			'/^lte IE\s*(\d+)$/i' => '(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] <= %d)',
		);
		
		$result = array();
		$conditions = preg_split('/([\&\|])/', $condition, -1, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE);
		foreach ($conditions as $condition)
		{
			$condition = trim(preg_replace('/[\(\)]/', '', $condition));
			if ($condition === '')
			{
				continue;
			}
			
			if ($condition === '&' || $condition === '|')
			{
				$result[] = $condition . $condition;
				continue;
			}
			
			$negation = $condition[0] === '!';
			if ($negation)
			{
				$condition = trim(substr($condition, 1));
			}
			
			foreach ($conversions as $regexp => $replacement)
			{
				if (preg_match($regexp, $condition, $matches))
				{
					if (count($matches) > 1)
					{
						array_shift($matches);
						$result[] = ($negation ? '!' : '') . vsprintf($replacement, $matches);
					}
					else
					{
						$result[] = ($negation ? '!' : '') . $replacement;
					}
					break;
				}
			}
		}
		
		return count($result) ? implode(' ', $result) : 'false';
	}
}
