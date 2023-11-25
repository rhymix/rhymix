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
	public static function text2html(string $text, int $options = 0): string
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
			$result = '';
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
	public static function html2text(string $html): string
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
	public static function markdown2html(string $markdown, int $options = 0): string
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
	public static function html2markdown(string $html): string
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
	public static function bbcode(string $bbcode): string
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
	public static function applySmartQuotes(string $html): string
	{
		return \Michelf\SmartyPants::defaultTransform($html, 'qbBdDiew');
	}

	/**
	 * Compile LESS into CSS.
	 *
	 * @param string|array $source_filename
	 * @param string $target_filename
	 * @param array $variables (optional)
	 * @param bool $minify (optional)
	 * @return bool
	 */
	public static function compileLESS($source_filename, string $target_filename, array $variables = [], bool $minify = false): bool
	{
		// Get the cleaned and concatenated content.
		$imported_list = [];
		$content = self::concatCSS($source_filename, $target_filename, true, $imported_list);

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

			$content = $less_compiler->compile($content) . "\n";
			$content = strpos($content, '@charset') === false ? ('@charset "UTF-8";' . "\n" . $content) : $content;
			$result = true;
		}
		catch (\Exception $e)
		{
			$filename = starts_with(\RX_BASEDIR, $source_filename) ? substr($source_filename, strlen(\RX_BASEDIR)) : $source_filename;
			$message = $e->getMessage();
			$content = sprintf("/*\n  Error while compiling %s\n\n  %s\n*/\n", $filename, $message);
			$result = false;
		}

		// Save the result to the target file.
		Storage::write($target_filename, $content);

		// Save the list of imported files.
		Storage::writePHPData(preg_replace('/\.css$/', '.imports.php', $target_filename), $imported_list, null, false);

		// Also return the compiled CSS content.
		return $result;
	}

	/**
	 * Compile SCSS into CSS.
	 *
	 * @param string|array $source_filename
	 * @param string $target_filename
	 * @param array $variables (optional)
	 * @param bool $minify (optional)
	 * @return bool
	 */
	public static function compileSCSS($source_filename, string $target_filename, array $variables = [], bool $minify = false): bool
	{
		// Get the cleaned and concatenated content.
		$imported_list = [];
		$content = self::concatCSS($source_filename, $target_filename, false, $imported_list);
		if (strpos($content, '@charset') === false)
		{
			$content = '@charset "UTF-8"; ' . $content;
		}
		$primary_filename = is_array($source_filename) ? array_first($source_filename) : $source_filename;
		$sourcemap_filename = preg_replace('/\.css$/', '.map', $target_filename);

		// Compile!
		try
		{
			$scss_compiler = new \ScssPhp\ScssPhp\Compiler;
			$scss_compiler->setOutputStyle($minify ? \ScssPhp\ScssPhp\OutputStyle::COMPRESSED : \ScssPhp\ScssPhp\OutputStyle::EXPANDED);
			$scss_compiler->setImportPaths(array(dirname($primary_filename)));
			$scss_compiler->setSourceMap(\ScssPhp\ScssPhp\Compiler::SOURCE_MAP_FILE);
			$scss_compiler->setSourceMapOptions([
				'sourceMapURL' => basename($sourcemap_filename),
				'sourceMapFilename' => basename($target_filename),
				'sourceMapBasepath' => \RX_BASEDIR,
				'sourceRoot' => \RX_BASEURL,
			]);
			if ($variables)
			{
				$converted_variables = [];
				foreach ($variables as $key => $val)
				{
					if (is_string($val) && $val !== '')
					{
						$converted_variables[$key] = \ScssPhp\ScssPhp\ValueConverter::parseValue($val);
					}
					else
					{
						$converted_variables[$key] = \ScssPhp\ScssPhp\ValueConverter::fromPhp($val);
					}
				}
				$scss_compiler->addVariables($converted_variables);
			}

			$compiler = $scss_compiler->compileString($content, $primary_filename);
			$content = $compiler->getCss() . "\n";
			$sourcemap = $compiler->getSourceMap();
			$result = true;
		}
		catch (\Exception $e)
		{
			$filename = starts_with(\RX_BASEDIR, $primary_filename) ? substr($primary_filename, strlen(\RX_BASEDIR)) : $primary_filename;
			$message = preg_replace('/\(stdin\)\s/', '', $e->getMessage());
			$content = sprintf("/*\n  Error while compiling %s\n\n  %s\n*/\n", $filename, $message);
			$sourcemap = '';
			$result = false;
		}

		// Save the result to the target file.
		Storage::write($target_filename, $content);
		if ($sourcemap)
		{
			Storage::write($sourcemap_filename, $sourcemap);
		}

		// Save the list of imported files.
		Storage::writePHPData(preg_replace('/\.css$/', '.imports.php', $target_filename), $imported_list, null, false);

		// Also return the compiled CSS content.
		return $result;
	}

	/**
	 * Minify CSS.
	 *
	 * @param string|array $source_filename
	 * @param string $target_filename
	 * @return bool
	 */
	public static function minifyCSS($source_filename, string $target_filename): bool
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
	public static function minifyJS($source_filename, string $target_filename): bool
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
	 * @param bool $add_comment
	 * @param array &$imported_list
	 * @return string
	 */
	public static function concatCSS($source_filename, string $target_filename, bool $add_comment = true, array &$imported_list = []): string
	{
		$charsets = [];
		$imported_urls = [];
		$import_type = 'normal';
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
			$dirname = dirname($filename);
			$import_type = ends_with('.scss', $filename) ? 'scss' : 'normal';
			$content = preg_replace_callback('/@import\s+((?:url\([^)]+\)|"[^"]+"|\'[^\']+\')[^;]*);/', function($matches) use($dirname, $filename, $target_filename, $import_type, &$imported_list, &$imported_urls) {
				if (preg_match('!^url\([\'"]?((?:https?:)?//[^()\'"]+)!i', $matches[1], $urlmatches))
				{
					$imported_urls[] = $urlmatches[1];
					return '';
				}
				$import_content = '';
				$import_files = array_map(function($str) use($dirname, $filename, $import_type) {
					$str = trim(trim(trim(preg_replace('!^url\([\'"]?([^()\'"]+)[\'"]?\)!i', '$1', $str)), '"\''));
					if (preg_match('!^(?:https?:)?//!i', $str))
					{
						return $str;
					}
					if ($import_type === 'scss')
					{
						if (($dirpos = strrpos($str, '/')) !== false)
						{
							$basename = substr($str, $dirpos + 1);
							if (!ends_with('.scss', $basename))
							{
								$basename = '_' . $basename . '.scss';
							}
							$basepath = substr($str, 0, $dirpos);
							if (preg_match('!^\\^/(.+)!', $basepath, $bpmatches))
							{
								return \RX_BASEDIR . $bpmatches[1] . '/' . $basename;
							}
							else
							{
								return $dirname . '/' . $basepath . '/' . $basename;
							}
						}
						else
						{
							$basename = $str;
							if (!ends_with('.scss', $basename))
							{
								$basename = '_' . $basename . '.scss';
							}
							return $dirname . '/' . $basename;
						}
					}
					else
					{
						return $dirname . '/' . $str;
					}
				}, explode(',', $matches[1]));
				foreach ($import_files as $import_filename)
				{
					if (preg_match('!^(https?:)?//!i', $import_filename))
					{
						$imported_urls[] = $import_filename;
					}
					elseif (file_exists($import_filename))
					{
						$imported_list[] = $import_filename;
						$import_content .= self::concatCSS($import_filename, $target_filename, false, $imported_list);
					}
					else
					{
						$error_filename = substr($import_filename, strlen(\RX_BASEDIR));
						trigger_error('Imported file not found: ' . $error_filename, \E_USER_WARNING);
					}
				}
				if ($import_type === 'scss')
				{
					$import_content = preg_replace('!//.*?\n!s', '', $import_content);
					$import_content = preg_replace('![\r\n]+!', ' ', $import_content);
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

			// Extract all @charset declarations.
			$content = preg_replace_callback('/@charset\s+(["\'a-z0-9_-]+);[\r\n]*/i', function($matches) use (&$charsets) {
				$charsets[] = trim($matches[1], '"\'');
				return '';
			}, $content);

			// Wrap the content in a media query if there is one.
			if ($media !== null)
			{
				$content = "@media $media {\n\n" . trim($content) . "\n\n}";
			}

			// Remove out-of-place sourcemap declarations.
			$content = preg_replace('!/\\*# (sourceMappingURL=.+?)\\*/!s', '/* $1*/', $content);

			// Append to the result string.
			$original_filename = starts_with(\RX_BASEDIR, $filename) ? substr($filename, strlen(\RX_BASEDIR)) : $filename;
			if ($add_comment)
			{
				$result .= '/* Original file: ' . $original_filename . ' */' . "\n\n" . trim($content) . "\n\n";
			}
			else
			{
				$result .= trim($content) . "\n\n";
			}
		}

		// Place all @charset and @import statements at the beginning.
		if (count($imported_urls))
		{
			$imports = implode("\n", array_map(function($url) {
				return '@import url("' . escape_dqstr($url) . '");';
			}, $imported_urls));
			$result = $imports . "\n" . $result;
		}
		if (count($charsets))
		{
			$charset = '@charset "' . escape_dqstr(array_first($charsets)) . '";';
			$delimiter = $import_type === 'scss' ? ' ' : "\n";
			$result = $charset . $delimiter . $result;
		}

		return $result;
	}

	/**
	 * JS concatenation subroutine.
	 *
	 * @param string|array $source_filename
	 * @return string
	 */
	public static function concatJS($source_filename): string
	{
		$result = '';

		if (!is_array($source_filename))
		{
			$source_filename = array($source_filename);
		}

		foreach ($source_filename as $filename)
		{
			// Handle the array format, previously used for the targetIE attribute.
			if (is_array($filename) && count($filename) >= 1)
			{
				$filename = reset($filename);
			}

			// Clean the content.
			$content = utf8_clean(file_get_contents($filename));
			$content = preg_replace('!(\n)//# (sourceMappingURL=\S+)!', '$1/* $2 */', $content);

			// Append to the result string.
			$original_filename = starts_with(\RX_BASEDIR, $filename) ? substr($filename, strlen(\RX_BASEDIR)) : $filename;
			$result .= '/* Original file: ' . $original_filename . ' */' . "\n\n" . trim($content) . ";\n\n";
		}

		return $result;
	}

	/**
	 * Convert IE conditional comments to JS conditions.
	 *
	 * @deprecated
	 * @param string $condition
	 * @return string
	 */
	public static function convertIECondition($condition)
	{
		throw new Exceptions\FeatureDisabled;
	}
}
