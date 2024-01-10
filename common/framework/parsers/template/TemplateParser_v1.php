<?php

namespace Rhymix\Framework\Parsers\Template;

use HTMLDisplayHandler;
use Rhymix\Framework\Template;

/**
 * Template parser v1 for XE compatibility.
 *
 * Originally part of TemplateHandler, this parser is preserved here
 * for bug-for-bug compatibility with XE and older versions of Rhymix.
 * A significant part of this code dates back to the early days of XE,
 * though Rhymix has managed to squeeze in a few new features.
 *
 * Except in the case of a serious security issue, there will be no change
 * to the parsing and conversion logic, and no new features.
 * It is strongly recommended that new templates be written in v2.
 */
class TemplateParser_v1
{
	/**
	 * Instance properties.
	 */
	public $autoescape_config_exists;
	public $source_type;
	public $template;

	/**
	 * Convert template code into PHP.
	 *
	 * @param string $content
	 * @param Template $template
	 * @return string
	 */
	public function convert(string $content, Template $template): string
	{
		// Prepare default settings.
		ini_set('pcre.jit', false);
		$this->autoescape_config_exists = str_contains($content, '$this->config->autoescape = ');
		$this->source_type = preg_match('!^((?:m\.)?[a-z]+)/!', $template->relative_dirname, $matches) ? $matches[1] : null;
		$this->template = $template;

		// replace comments
		$content = preg_replace('@<!--//.*?-->@s', '', $content);

		// replace value of src in img/input/script tag
		$content = preg_replace_callback('/<(?:img|input|script)(?:[^<>]*?)(?(?=cond=")(?:cond="[^"]+"[^<>]*)+|)[^<>]* src="(?!(?:https?|file|data):|[\/\{])([^"]+)"/is', array($this, '_replacePath'), $content);

		// replace value of srcset in img/source/link tag
		$content = preg_replace_callback('/<(?:img|source|link)(?:[^<>]*?)(?(?=cond=")(?:cond="[^"]+"[^<>]*)+|)[^<>]* srcset="([^"]+)"/is', array($this, '_replaceSrcsetPath'), $content);

		// replace loop and cond template syntax
		$content = $this->_parseInline($content);

		// include, unload/load, import
		$content = preg_replace_callback('/{(@[\s\S]+?|(?=[\$\\\\]\w+|_{1,2}[A-Z]+|[!\(+-]|\w+(?:\(|::)|\d+|[\'"].*?[\'"]).+?)}|<(!--[#%])?(include|import|(un)?load(?(4)|(?:_js_plugin)?)|config)(?(2)\(["\']([^"\']+)["\'])(.*?)(?(2)\)--|\/)>|<!--(@[a-z@]*)([\s\S]*?)-->(\s*)/', array($this, '_parseResource'), $content);

		// remove block which is a virtual tag
		$content = preg_replace('@</?block\s*>@is', '', $content);

		// form auto generation
		$temp = preg_replace_callback('/(<form(?:<\?php.+?\?>|[^<>]+)*?>)(.*?)(<\/form>)/is', array($this, '_compileFormAuthGeneration'), $content);
		if($temp)
		{
			$content = $temp;
		}

		// prevent from calling directly before writing into file
		$content = '<?php if (!defined("RX_VERSION")) exit();?>' . $content;

		// restore curly braces from temporary entities
		$content = self::_replaceTempEntities($content);

		// remove php script reopening
		$content = preg_replace_callback('/([;{])?( )*\?\>\<\?php\s/', function($match) {
			return $match[1] === '{' ? '{ ' : '; ';
		}, $content);

		// remove empty lines
		$content = preg_replace([
			'/>\<\?php } \?\>\n[\t\x20]*?(?=\n<!--)/',
			'/\n[\t\x20]*?(?=\n<!--)/',
			'/\n[\t\x20]+?\<\?php/',
		], [
			"><?php } ?>\n<?php echo \"\\n\"; ?>",
			"\n<?php ?>",
			"\n\t<?php",
		], $content);

		return $content;
	}

	/**
	 * preg_replace_callback handler
	 * 1. remove ruleset from form tag
	 * 2. add hidden tag with ruleset value
	 * 3. if empty default hidden tag, generate hidden tag (ex:mid, act...)
	 * 4. generate return url, return url use in server side validator
	 * @param array $matches
	 * @return string
	 */
	private function _compileFormAuthGeneration($matches)
	{
		// check rx-autoform attribute
		if (preg_match('/\srx-autoform="([^">]*?)"/', $matches[1], $m1))
		{
			$autoform = toBool($m1[1]);
			$matches[1] = preg_replace('/\srx-autoform="([^">]*?)"/', '', $matches[1]);
		}
		else
		{
			$autoform = true;
		}

		// form ruleset attribute move to hidden tag
		if ($autoform && $matches[1])
		{
			preg_match('/ruleset="([^"]*?)"/is', $matches[1], $m);
			if(isset($m[0]) && $m[0])
			{
				$matches[1] = preg_replace('/' . addcslashes($m[0], '?$') . '/i', '', $matches[1]);

				if(strpos($m[1], '@') !== FALSE)
				{
					$path = str_replace('@', '', $m[1]);
					$path = './files/ruleset/' . $path . '.xml';
					$autoPath = '';
				}
				else if(strpos($m[1], '#') !== FALSE)
				{
					$fileName = str_replace('#', '', $m[1]);
					$fileName = str_replace('<?php echo ', '', $fileName);
					$fileName = str_replace(' ?>', '', $fileName);
					$path = '#./files/ruleset/' . $fileName . '.xml';

					preg_match('@(?:^|\.?/)(modules/[\w-]+)@', $this->template->relative_path, $mm);
					$module_path = $mm[1];
					list($rulsetFile) = explode('.', $fileName);
					$autoPath = $module_path . '/ruleset/' . $rulsetFile . '.xml';
					$m[1] = $rulsetFile;
				}
				else if(preg_match('@(?:^|\.?/)(modules/[\w-]+)@', $this->template->relative_path, $mm))
				{
					$module_path = $mm[1];
					$path = $module_path . '/ruleset/' . $m[1] . '.xml';
					$autoPath = '';
				}

				$matches[2] = '<input type="hidden" name="ruleset" value="' . $m[1] . '" />' . $matches[2];
				//assign to addJsFile method for js dynamic recache
				$matches[1] = '<?php Context::addJsFile("' . $path . '", FALSE, "", 0, "body", TRUE, "' . $autoPath . '") ?' . '>' . $matches[1];
			}
		}

		// if not exists default hidden tag, generate hidden tag
		if ($autoform)
		{
			preg_match_all('/<input[^>]* name="(act|mid)"/is', $matches[2], $m2);
			$missing_inputs = array_diff(['act', 'mid'], $m2[1]);
			if(is_array($missing_inputs))
			{
				$generatedHidden = '';
				foreach($missing_inputs as $key)
				{
					$generatedHidden .= '<input type="hidden" name="' . $key . '" value="<?php echo $__Context->' . $key . ' ?? \'\'; ?>" />';
				}
				$matches[2] = $generatedHidden . $matches[2];
			}
		}

		// return url generate
		if ($autoform)
		{
			if (!preg_match('/no-(?:error-)?return-url="true"/i', $matches[1]))
			{
				preg_match('/<input[^>]*name="error_return_url"[^>]*>/is', $matches[2], $m3);
				if(!isset($m3[0]) || !$m3[0])
				{
					$matches[2] = '<input type="hidden" name="error_return_url" value="<?php echo escape(getRequestUriByServerEnviroment(), false); ?>" />' . $matches[2];
				}
			}
			else
			{
				$matches[1] = preg_replace('/no-(?:error-)?return-url="true"/i', '', $matches[1]);
			}
		}

		array_shift($matches);
		return implode('', $matches);
	}

	/**
	 * preg_replace_callback handler
	 *
	 * replace image path
	 * @param array $match
	 *
	 * @return string changed result
	 */
	private function _replacePath($match)
	{
		$src = $this->_replaceRelativePath($match);
		return substr($match[0], 0, -strlen($match[1]) - 6) . "src=\"{$src}\"";
	}

	/**
	 * replace relative path
	 * @param array $match
	 *
	 * @return string changed result
	 */
	private function _replaceRelativePath($match)
	{
		//return origin code when src value started '${'.
		if(preg_match('@^\${@', $match[1]))
		{
			return $match[1];
		}

		//return origin code when src value include variable.
		if(preg_match('@^[\'|"]\s*\.\s*\$@', $match[1]))
		{
			return $match[0];
		}

		$src = preg_replace('@^(\./)+@', '', trim($match[1]));

		$src = \RX_BASEURL . $this->template->relative_dirname . $src;
		$src = str_replace('/./', '/', $src);

		// for backward compatibility
		$src = preg_replace('@/((?:[\w-]+/)+)\1@', '/\1', $src);

		while(($tmp = preg_replace('@[^/]+/\.\./@', '', $src, 1)) !== $src)
		{
			$src = $tmp;
		}

		return $src;
	}

	/**
	 * preg_replace_callback handler
	 *
	 * replace srcset string with multiple paths
	 * @param array $match
	 *
	 * @return string changed result
	 */
	private function _replaceSrcsetPath($match)
	{
		// explode urls by comma
		$url_list = explode(",", $match[1]);

		foreach ($url_list as &$url) {
			// replace if url is not starting with the pattern
			$url = preg_replace_callback(
				'/^(?!(?:https?|file|data):|[\/\{])(\S+)/i',
				array($this, '_replaceRelativePath'),
				trim($url)
			);
		}
		$srcset = implode(", ", $url_list);

		return substr($match[0], 0, -strlen($match[1]) - 9) . "srcset=\"{$srcset}\"";
	}

	/**
	 * replace loop and cond template syntax
	 * @param string $content
	 * @return string changed result
	 */
	private function _parseInline($content)
	{
		// list of self closing tags
		$self_closing = array('area' => 1, 'base' => 1, 'basefont' => 1, 'br' => 1, 'hr' => 1, 'input' => 1, 'img' => 1, 'link' => 1, 'meta' => 1, 'param' => 1, 'frame' => 1, 'col' => 1);

		$skip = sprintf('(?!%s)', implode('|', ['marquee']));
		$split_regex = "@(</?{$skip}[a-zA-Z](?>[^<>{}\"]+|<!--.*?-->.*?<!--.*?end-->|{[^}]*}|\"(?>'.*?'|.)*?\"|.)*?>)@s";
		$nodes = preg_split($split_regex, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

		for($idx = 1, $node_len = count($nodes); $idx < $node_len; $idx+=2)
		{
			if(!($node = $nodes[$idx]))
			{
				continue;
			}

			if(preg_match_all('@\s(loop|cond)="([^"]+)"@', $node, $matches))
			{
				// this tag
				$tag = substr($node, 1, strpos($node, ' ') - 1);

				// if the vale of $closing is 0, it means 'skipping'
				$closing = 0;

				// process opening tag
				foreach($matches[1] as $n => $stmt)
				{
					$expr = $matches[2][$n];
					$expr = self::_replaceVar($expr);
					$closing++;

					switch($stmt)
					{
						case 'cond':
							if (preg_match('/^\$[\\\\\w\[\]\'":>-]+$/i', $expr))
							{
								$expr = "$expr ?? false";
							}
							$nodes[$idx - 1] .= "<?php if({$expr}){ ?>";
							break;
						case 'loop':
							if(!preg_match('@^(?:(.+?)=>(.+?)(?:,(.+?))?|(.*?;.*?;.*?)|(.+?)\s*=\s*(.+?))$@', $expr, $expr_m))
							{
								break;
							}
							if($expr_m[1])
							{
								$expr_m[1] = trim($expr_m[1]);
								$expr_m[2] = trim($expr_m[2]);
								if(isset($expr_m[3]) && $expr_m[3])
								{
									$expr_m[2] .= '=>' . trim($expr_m[3]);
								}
								$nodes[$idx - 1] .= sprintf('<?php $__loop_tmp=%1$s;if($__loop_tmp)foreach($__loop_tmp as %2$s){ ?>', $expr_m[1], $expr_m[2]);
							}
							elseif(isset($expr_m[4]) && $expr_m[4])
							{
								$nodes[$idx - 1] .= "<?php for({$expr_m[4]}){ ?>";
							}
							elseif(isset($expr_m[5]) && $expr_m[5])
							{
								$nodes[$idx - 1] .= "<?php while({$expr_m[5]}={$expr_m[6]}){ ?>";
							}
							break;
					}
				}
				$node = preg_replace('@\s(loop|cond)="([^"]+)"@', '', $node);

				// find closing tag
				$close_php = '<?php ' . str_repeat('}', $closing) . ' ?>';
				//  self closing tag
				if($node[1] == '!' || substr($node, -2, 1) == '/' || isset($self_closing[$tag]))
				{
					$nodes[$idx + 1] = $close_php . $nodes[$idx + 1];
				}
				else
				{
					$depth = 1;
					for($i = $idx + 2; $i < $node_len; $i+=2)
					{
						$nd = $nodes[$i];
						if(strpos($nd, $tag) === 1)
						{
							$depth++;
						}
						elseif(strpos($nd, '/' . $tag) === 1)
						{
							$depth--;
							if(!$depth)
							{
								$nodes[$i - 1] .= $nodes[$i] . $close_php;
								$nodes[$i] = '';
								break;
							}
						}
					}
				}
			}

			if(strpos($node, '|cond="') !== false)
			{
				$node = preg_replace('@(\s[-\w:]+(?:="[^"]+?")?)\|cond="(.+?)"@s', '<?php if($2){ ?>$1<?php } ?>', $node);
				$node = self::_replaceVar($node);
			}

			if($nodes[$idx] != $node)
			{
				$nodes[$idx] = $node;
			}
		}

		$content = implode('', $nodes);

		return $content;
	}

	/**
	 * preg_replace_callback handler
	 * replace php code.
	 * @param array $m
	 * @return string changed result
	 */
	private function _parseResource($m)
	{
		// {@ ... } or {$var} or {func(...)}
		if($m[1])
		{
			if(preg_match('@^(\w+)\(@', $m[1], $mm) && (!function_exists($mm[1]) && !in_array($mm[1], ['isset', 'unset', 'empty'])))
			{
				return $m[0];
			}

			if($m[1][0] == '@')
			{
				$m[1] = self::_replaceVar(substr($m[1], 1));
				return "<?php {$m[1]} ?>";
			}
			else
			{
				// Get escape options.
				if($m[1] === '$content' && preg_match('@^layouts/.+/layout\.html$@', $this->template->relative_path))
				{
					$escape_option = 'noescape';
				}
				elseif(preg_match('/^\$(?:user_)?lang->[a-zA-Z0-9\_]+$/', $m[1]))
				{
					$escape_option = 'noescape';
				}
				elseif(preg_match('/^lang\(.+\)$/', $m[1]))
				{
					$escape_option = 'noescape';
				}
				else
				{
					$escape_option = $this->autoescape_config_exists ? 'auto' : 'noescape';
				}

				// Separate filters from variable.
				if (preg_match('@^(.+?)(?<![|\s])((?:\|[a-z]{2}[a-z0-9_]+(?::.+)?)+)$@', $m[1], $mm))
				{
					$m[1] = $mm[1];
					$filters = array_map('trim', explode_with_escape('|', substr($mm[2], 1)));
				}
				else
				{
					$filters = array();
				}

				// Process the variable.
				$var = self::_replaceVar($m[1]);

				// Apply filters.
				foreach ($filters as $filter)
				{
					// Separate filter option from the filter name.
					if (preg_match('/^([a-z0-9_-]+):(.+)$/', $filter, $matches))
					{
						$filter = $matches[1];
						$filter_option = $matches[2];
						if (!self::_isVar($filter_option) && !preg_match("/^'.*'$/", $filter_option) && !preg_match('/^".*"$/', $filter_option))
						{
							$filter_option = "'" . escape_sqstr($filter_option) . "'";
						}
						else
						{
							$filter_option = self::_replaceVar($filter_option);
						}
					}
					else
					{
						$filter_option = null;
					}

					// Apply each filter.
					switch ($filter)
					{
						case 'auto':
						case 'autoescape':
						case 'autolang':
						case 'escape':
						case 'noescape':
							$escape_option = $filter;
							break;

						case 'escapejs':
							$var = "escape_js({$var})";
							break;

						case 'json':
							$var = "json_encode({$var})";
							break;

						case 'strip':
						case 'strip_tags':
							$var = $filter_option ? "strip_tags({$var}, {$filter_option})" : "strip_tags({$var})";
							break;

						case 'trim':
							$var = "trim({$var})";
							break;

						case 'urlencode':
							$var = "rawurlencode({$var})";
							break;

						case 'lower':
							$var = "strtolower({$var})";
							break;

						case 'upper':
							$var = "strtoupper({$var})";
							break;

						case 'nl2br':
							$var = $this->_applyEscapeOption($var, $escape_option);
							$var = "nl2br({$var})";
							$escape_option = 'noescape';
							break;

						case 'join':
							$var = $filter_option ? "implode({$filter_option}, {$var})" : "implode(', ', {$var})";
							break;

						case 'date':
							$var = $filter_option ? "getDisplayDateTime(ztime({$var}), {$filter_option})" : "getDisplayDateTime(ztime({$var}), 'Y-m-d H:i:s')";
							break;

						case 'format':
						case 'number_format':
							$var = $filter_option ? "number_format({$var}, {$filter_option})" : "number_format({$var})";
							break;

						case 'shorten':
						case 'number_shorten':
							$var = $filter_option ? "number_shorten({$var}, {$filter_option})" : "number_shorten({$var})";
							break;

						case 'link':
							$var = $this->_applyEscapeOption($var, $escape_option);
							if ($filter_option)
							{
								$filter_option = $this->_applyEscapeOption($filter_option, $escape_option);
								$var = "'<a href=\"' . ($filter_option) . '\">' . ($var) . '</a>'";
							}
							else
							{
								$var = "'<a href=\"' . ($var) . '\">' . ($var) . '</a>'";
							}
							$escape_option = 'noescape';
							break;

						default:
							$filter = escape_sqstr($filter);
							$var = "'INVALID FILTER ({$filter})'";
					}
				}

				// Apply the escape option and return.
				return '<?php echo ' . $this->_applyEscapeOption($var, $escape_option) . ' ?>';
			}
		}

		if($m[3])
		{
			$attr = array();
			if($m[5])
			{
				if(preg_match_all('@,(\w+)="([^"]+)"@', $m[6], $mm))
				{
					foreach($mm[1] as $idx => $name)
					{
						$attr[$name] = $mm[2][$idx];
					}
				}
				$attr['target'] = $m[5];
			}
			else
			{
				if(!preg_match_all('@ (\w+)="([^"]+)"@', $m[6], $mm))
				{
					return $m[0];
				}
				foreach($mm[1] as $idx => $name)
				{
					$attr[$name] = $mm[2][$idx];
				}
			}

			switch($m[3])
			{
				// <!--#include--> or <include ..>
				case 'include':
					if(!$this->template->relative_dirname || !$attr['target'])
					{
						return '';
					}

					if (preg_match('!^\\^/(.+)!', $attr['target'], $tmatches))
					{
						$pathinfo = pathinfo(\RX_BASEDIR . $tmatches[1]);
						$fileDir = $pathinfo['dirname'];
					}
					else
					{
						$pathinfo = pathinfo($attr['target']);
						$fileDir = $this->_getRelativeDir($pathinfo['dirname']);
					}

					if(!$fileDir)
					{
						return '';
					}

					return "<?php \$__tpl=TemplateHandler::getInstance();echo \$__tpl->compile('{$fileDir}','{$pathinfo['basename']}') ?>";
				// <!--%load_js_plugin-->
				case 'load_js_plugin':
					$plugin = self::_replaceVar($m[5]);
					$s = "<!--#JSPLUGIN:{$plugin}-->";
					if(strpos($plugin, '$__Context') === false)
					{
						$plugin = "'{$plugin}'";
					}

					$s .= "<?php Context::loadJavascriptPlugin({$plugin}); ?>";
					return $s;
				// <load ...> or <unload ...> or <!--%import ...--> or <!--%unload ...-->
				case 'import':
				case 'load':
				case 'unload':
					$metafile = '';
					$metavars = '';
					$replacements = HTMLDisplayHandler::$replacements;
					$attr['target'] = preg_replace(array_keys($replacements), array_values($replacements), $attr['target']);
					$pathinfo = pathinfo($attr['target']);
					$doUnload = ($m[3] === 'unload');
					$isRemote = !!preg_match('@^(https?:)?//@i', $attr['target']);

					if($isRemote)
					{
						if (empty($pathinfo['extension']))
						{
							$pathinfo['extension'] = preg_match('/[\.\/](css|js)[0-9]?\b/', $attr['target'], $mx) ? $mx[1] : null;
						}
					}
					else
					{
						if (preg_match('!^\\^/(.+)!', $attr['target'], $tmatches))
						{
							$pathinfo = pathinfo($tmatches[1]);
							$relativeDir = $pathinfo['dirname'];
							$attr['target'] = $relativeDir . '/' . $pathinfo['basename'];
						}
						else
						{
							if(!preg_match('@^\.?/@', $attr['target']))
							{
								$attr['target'] = './' . $attr['target'];
							}
							$relativeDir = $this->_getRelativeDir($pathinfo['dirname']);
							$attr['target'] = $relativeDir . '/' . $pathinfo['basename'];
						}
					}

					switch($pathinfo['extension'])
					{
						case 'xml':
							if($isRemote || $doUnload)
							{
								return '';
							}
							// language file?
							if($pathinfo['basename'] == 'lang.xml' || substr($pathinfo['dirname'], -5) == '/lang')
							{
								$result = "Context::loadLang('{$relativeDir}');";
							}
							else
							{
								$result = "require_once('./classes/xml/XmlJsFilter.class.php');\$__xmlFilter=new XmlJsFilter('{$relativeDir}','{$pathinfo['basename']}');\$__xmlFilter->compile();";
							}
							break;
						case 'js':
							if($doUnload)
							{
								$result = vsprintf("Context::unloadFile('%s', '');", [$attr['target'] ?? '']);
							}
							else
							{
								$metafile = isset($attr['target']) ? $attr['target'] : '';
								$result = vsprintf("Context::loadFile(['%s', '%s', '%s', '%s']);", [
									$attr['target'] ?? '', $attr['type'] ?? '', $isRemote ? $this->source_type : '', $attr['index'] ?? '',
								]);
							}
							break;
						case 'css':
						case 'less':
						case 'scss':
							if($doUnload)
							{
								$result = vsprintf("Context::unloadFile('%s', '', '%s');", [
									$attr['target'] ?? '', $attr['media'] ?? '',
								]);
							}
							else
							{
								$metafile = isset($attr['target']) ? $attr['target'] : '';
								$metavars = isset($attr['vars']) ? ($attr['vars'] ? self::_replaceVar($attr['vars']) : '') : '';
								$result = vsprintf("Context::loadFile(['%s', '%s', '%s', '%s', %s]);", [
									$attr['target'] ?? '', $attr['media'] ?? '', $isRemote ? $this->source_type : '', $attr['index'] ?? '',
									isset($attr['vars']) ? ($attr['vars'] ? self::_replaceVar($attr['vars']) : '[]') : '[]',
								]);
							}
							break;
					}

					$result = "<?php {$result} ?>";
					if($metafile)
					{
						if(!$metavars)
						{
							$result = "<!--#Meta:{$metafile}-->" . $result;
						}
						else
						{
							// LESS or SCSS needs the variables to be substituted.
							$result = "<!--#Meta:{$metafile}?{$metavars}-->" . $result;
						}
					}

					return $result;
				// <config ...>
				case 'config':
					$result = '';
					if(preg_match_all('@ (\w+)="([^"]+)"@', $m[6], $config_matches, PREG_SET_ORDER))
					{
						foreach($config_matches as $config_match)
						{
							$config_value = toBool(trim(strtolower($config_match[2]))) ? 'true' : 'false';
							$result .= "\$this->config->{$config_match[1]} = $config_value;";
						}
					}
					return "<?php {$result} ?>";
			}
		}

		// <!--@..--> such as <!--@if($cond)-->, <!--@else-->, <!--@end-->
		if($m[7])
		{
			$m[7] = substr($m[7], 1);
			if(!$m[7])
			{
				return '<?php ' . self::_replaceVar($m[8]) . '{ ?>' . $m[9];
			}
			if(!preg_match('/^(?:((?:end)?(?:if|switch|for(?:each)?|while)|end)|(else(?:if)?)|(break@)?(case|default)|(break))$/', $m[7], $mm))
			{
				return '';
			}
			if($mm[1])
			{
				if($mm[1][0] == 'e')
				{
					return '<?php } ?>' . $m[9];
				}

				$precheck = '';
				if($mm[1] == 'switch')
				{
					$m[9] = '';
				}
				elseif($mm[1] == 'foreach')
				{
					$var = preg_replace('/^\s*\(\s*(.+?) .*$/', '$1', $m[8]);
					$precheck = "if({$var})";
				}
				return '<?php ' . self::_replaceVar($precheck . $m[7] . $m[8]) . '{ ?>' . $m[9];
			}
			if($mm[2])
			{
				return "<?php }{$m[7]}" . self::_replaceVar($m[8]) . "{ ?>" . $m[9];
			}
			if($mm[4])
			{
				return "<?php " . ($mm[3] ? 'break;' : '') . "{$m[7]} " . trim($m[8], '()') . ": ?>" . $m[9];
			}
			if($mm[5])
			{
				return "<?php break; ?>";
			}
			return '';
		}
		return $m[0];
	}

	/**
	 * Apply escape option to an expression.
	 */
	private function _applyEscapeOption($str, $escape_option)
	{
		if (preg_match('/^\$[\\\\\w\[\]\'":>-]+$/i', $str))
		{
			$str = preg_match('/^\$(__Context->)?lang->/', $str) ? $str : "$str ?? ''";
		}

		switch($escape_option)
		{
			case 'escape':
				return "htmlspecialchars({$str}, ENT_QUOTES, 'UTF-8', true)";
			case 'noescape':
				return "{$str}";
			case 'autoescape':
				return "htmlspecialchars({$str}, ENT_QUOTES, 'UTF-8', false)";
			case 'autolang':
				return "(preg_match('/^\\$(?:user_)?lang->[a-zA-Z0-9\_]+$/', {$str}) ? ({$str}) : htmlspecialchars({$str}, ENT_QUOTES, 'UTF-8', false))";
			case 'auto':
			default:
				return "(\$this->config->autoescape ? htmlspecialchars({$str}, ENT_QUOTES, 'UTF-8', false) : ({$str}))";
		}
	}

	/**
	 * change relative path
	 * @param string $path
	 * @return string
	 */
	private function _getRelativeDir($path)
	{
		$_path = $path;

		$fileDir = $this->template->absolute_dirname;
		if($path[0] != '/')
		{
			$path = strtr(realpath($fileDir . '/' . $path), '\\', '/');
		}

		// for backward compatibility
		if(!$path)
		{
			$dirs = explode('/', $fileDir);
			$paths = explode('/', $_path);
			$idx = array_search($paths[0], $dirs);

			if($idx !== false)
			{
				while($dirs[$idx] && $dirs[$idx] === $paths[0])
				{
					array_splice($dirs, $idx, 1);
					array_shift($paths);
				}
				$path = strtr(realpath($fileDir . '/' . implode('/', $paths)), '\\', '/');
			}
		}

		$path = preg_replace('/^' . preg_quote(\RX_BASEDIR, '/') . '/', '', $path);

		return $path;
	}

	/**
	 * Check if a string seems to contain a variable.
	 *
	 * @param string $str
	 * @return bool
	 */
	private static function _isVar($str)
	{
		return preg_match('@(?<!::|\\\\|(?<!eval\()\')\$([a-z_][a-z0-9_]*)@i', $str) ? true : false;
	}

	/**
	 * Replace PHP variables of $ character
	 *
	 * @param string $php
	 * @return string
	 */
	private static function _replaceVar($php)
	{
		if(!strlen($php))
		{
			return '';
		}

		// Replace variables that need to be enclosed in curly braces, using temporary entities to prevent double-replacement.
		$php = preg_replace_callback('@(?<!\$__Context)->\$([a-z_][a-z0-9_]*)@i', function($matches) {
			return '->' . self::_getTempEntityForChar('{') . '$__Context->' . $matches[1] . self::_getTempEntityForChar('}');
		}, $php);

		// Replace all other variables with Context attributes.
		$php = preg_replace_callback('@(?<!::|\\\\|\$__Context->|(?<!eval\()\')\$([a-z_][a-z0-9_]*)@i', function($matches) {
			if (preg_match('/^(?:GLOBALS|_SERVER|_COOKIE|_ENV|_GET|_POST|_REQUEST|_SESSION|__Context|this)$/', $matches[1]))
			{
				return '$' . $matches[1];
			}
			else
			{
				return '$__Context->' . $matches[1];
			}
		}, $php);

		return $php;
	}

	/**
	 * Replace temporary entities to curly braces.
	 *
	 * @param string $str
	 * @return string
	 */
	private static function _replaceTempEntities($str)
	{
		return strtr($str, [
			'&#x1B;&#x7B;' => '{',
			'&#x1B;&#x7D;' => '}',
		]);
	}

	/**
	 * Get the temporary entity for a character.
	 *
	 * @param string $char
	 * @return string
	 */
	private static function _getTempEntityForChar($char)
	{
		return '&#x1B;&#x' . strtoupper(bin2hex($char)) . ';';
	}
}
