<?php

namespace Rhymix\Framework\Parsers\Template;

use Rhymix\Framework\Template;

/**
 * Template parser v2
 *
 * This is a new template engine, rewritten from scratch to act as a bridge
 * between legacy XE-style templates and new Blade-style templates.
 *
 * It supports not only Blade directives but also a number of v1 features,
 * such as automatic path conversion and centralized asset management,
 * which are especially important given the modular structure of Rhymix.
 *
 * Commonly used elements of the v1 template language are also supported
 * for ease of migration, while some of the older and/or more bug-prone parts
 * of v1 have been dropped.
 */
class TemplateParser_v2
{
	/**
	 * Cache template path info here.
	 */
	public $template;

	/**
	 * Properties for internal bookkeeping.
	 */
	protected $_aliases = [];
	protected $_stack = [];
	protected $_uniq_order = 1;

	/**
	 * Definitions of loop and condition directives.
	 *
	 * %s     : Full PHP code passed to the directive in parentheses.
	 * %array : The array used in a foreach/forelse loop.
	 * %uniq  : A random string that identifies a specific loop or condition.
	 */
	protected static $_loopdef = [
		'if' => ['if (%s):', 'endif;'],
		'unless' => ['if (!(%s)):', 'endif;'],
		'for' => ['for (%s):', 'endfor;'],
		'while' => ['while (%s):', 'endwhile;'],
		'switch' => ['switch (%s):', 'endswitch;'],
		'foreach' => [
			'$__tmp_%uniq = %array ?? []; $__loop_%uniq = $this->_v2_initLoopVar("%uniq", $__tmp_%uniq); foreach ($__tmp_%uniq as %remainder):',
			'$this->_v2_incrLoopVar($__loop_%uniq); endforeach; $this->_v2_removeLoopVar($__loop_%uniq); unset($__loop_%uniq);',
		],
		'forelse' => [
			'$__tmp_%uniq = %array ?? []; if($__tmp_%uniq): $__loop_%uniq = $this->_v2_initLoopVar("%uniq", $__tmp_%uniq); foreach ($__tmp_%uniq as %remainder):',
			'$this->_v2_incrLoopVar($__loop_%uniq); endforeach; $this->_v2_removeLoopVar($__loop_%uniq); unset($__loop_%uniq); else:',
			'endif;',
		],
		'once' => [
			"if (!isset(\$GLOBALS['tplv2_once']['%uniq'])):",
			"\$GLOBALS['tplv2_once']['%uniq'] = true; endif;",
		],
		'fragment' => [
			'ob_start(); $__last_fragment_name = %s;',
			'$this->_fragments[$__last_fragment_name] = ob_get_flush();',
		],
		'error' => [
			'if ($this->_v2_errorExists(%s)):',
			'endif;',
		],
		'push' => [
			'ob_start(); if (!isset(self::$_stacks[%s])): self::$_stacks[%s] = []; endif;',
			'array_push(self::$_stacks[%s], trim(ob_get_clean()));',
		],
		'pushif' => [
			'list($__stack_cond, $__stack_name) = [%s]; if ($__stack_cond): ob_start(); if (!isset(self::$_stacks[$__stack_name])): self::$_stacks[$__stack_name] = []; endif;',
			'array_push(self::$_stacks[$__stack_name], trim(ob_get_clean())); endif;',
		],
		'prepend' => [
			'ob_start(); if (!isset(self::$_stacks[%s])): self::$_stacks[%s] = []; endif;',
			'array_unshift(self::$_stacks[%s], trim(ob_get_clean()));',
		],
		'prependif' => [
			'list($__stack_cond, $__stack_name) = [%s]; if ($__stack_cond): ob_start(); if (!isset(self::$_stacks[$__stack_name])): self::$_stacks[$__stack_name] = []; endif;',
			'array_unshift(self::$_stacks[$__stack_name], trim(ob_get_clean())); endif;',
		],
		'isset' => ['if (isset(%s)):', 'endif;'],
		'unset' => ['if (!isset(%s)):', 'endif;'],
		'empty' => ['if (empty(%s)):', 'endif;'],
		'admin' => ['if ($this->user->isAdmin()):', 'endif;'],
		'auth' => ['if ($this->_v2_checkAuth(%s)):', 'endif;'],
		'guest' => ['if (!$this->user->isMember()):', 'endif;'],
		'desktop' => ['if (!$__Context->m):', 'endif;'],
		'mobile' => ['if ($__Context->m):', 'endif;'],
		'else' => ['else:'],
		'elseif' => ['elseif (%s):'],
		'case' => ['case %s:'],
		'default' => ['default:'],
		'continue' => ['continue;'],
		'break' => ['break;'],
	];

	/**
	 * Convert template code into PHP.
	 *
	 * @param string $content
	 * @param Template $template
	 * @return string
	 */
	public function convert(string $content, Template $template): string
	{
		// Store template info in instance property.
		$this->template = $template;

		// Preprocessing.
		$content = $this->_preprocess($content);

		// Apply conversions.
		$content = $this->_addContextSwitches($content);
		$content = $this->_removeComments($content);
		$content = $this->_convertRelativePaths($content);
		$content = $this->_convertPHPSections($content);
		$content = $this->_convertVerbatimSections($content);
		$content = $this->_convertFragments($content);
		$content = $this->_convertClassAliases($content);
		$content = $this->_convertIncludes($content);
		$content = $this->_convertResource($content);
		$content = $this->_convertLoopDirectives($content);
		$content = $this->_convertInlineDirectives($content);
		$content = $this->_convertMiscDirectives($content);
		$content = $this->_convertEchoStatements($content);

		// Postprocessing.
		$content = $this->_postprocess($content);

		return $content;
	}

	/**
	 * Preprocessing.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _preprocess(string $content): string
	{
		// Remove trailing whitespace.
		$content = preg_replace('#[\x20\x09]+$#m', '', $content);

		return $content;
	}

	/**
	 * Insert context switch points (HTML <-> JS).
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _addContextSwitches(string $content): string
	{
		return preg_replace_callback('#(<script(\s[^>]*)?|</script)#i', function($match) {
			if (substr($match[1], 1, 1) === '/')
			{
				return '<?php $this->config->context = "HTML"; ?>' . $match[1];
			}
			else
			{
				return $match[1] . '<?php $this->config->context = "JS"; ?>';
			}
		}, $content);
	}

	/**
	 * Remove comments that should not be visible in the output.
	 *
	 * <!--// XE-style Comment -->
	 * {{-- Blade-style Comment --}}
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _removeComments(string $content): string
	{
		return preg_replace([
			'#<!--//[^\n]+?-->#',
			'#\{\{--[^\n]+?--\}\}#',
		], '', $content);
	}

	/**
	 * Convert relative paths to absolute paths.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertRelativePaths(string $content): string
	{
		// Get the base path for this template.
		$basepath = \RX_BASEURL . $this->template->relative_dirname;

		// Convert all src and srcset attributes.
		$regexp = '#(<(?:img|audio|video|script|input|source|link)\s[^>]*)(src|srcset|poster)="([^"]+)"#';
		return preg_replace_callback($regexp, function($match) use ($basepath) {
			if ($match[2] !== 'srcset')
			{
				$src = trim($match[3]);
				return $match[1] . sprintf('%s="%s"', $match[2], $this->template->isRelativePath($src) ? $this->template->convertPath($src, $basepath) : $src);
			}
			else
			{
				$srcset = array_map('trim', explode(',', $match[3]));
				$result = array_map(function($src) use($basepath) {
					return $this->template->isRelativePath($src) ? $this->template->convertPath($src, $basepath) : $src;
				}, array_filter($srcset, function($src) {
					return !empty($src);
				}));
				return $match[1] . sprintf('srcset="%s"', implode(', ', $result));
			}
		}, $content);
	}

	/**
	 * Convert PHP sections.
	 *
	 * Unlike v1, all variables in all PHP code belong to the same scope.
	 *
	 * Supported syntaxes:
	 * <?php ... ?>
	 * <? ... ?>
	 * {@ ... }
	 * @php ... @endphp
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertPHPSections(string $content): string
	{
		$callback = function($match) {
			$open = '<?php' . (preg_match('#^\s#', $match[2]) ? '' : ' ');
			$close = (preg_match('#\s$#', $match[2]) ? '' : ' ') . '?>';
			return $open . self::_convertVariableScope($match[2]) . $close;
		};

		$content = preg_replace_callback('#(<\?php|<\?(?!=))(.+?)(\?>)#s', $callback, $content);
		$content = preg_replace_callback('#(\{@)(.+?)(\})#s', $callback, $content);
		$content = preg_replace_callback('#(?<!@)(@php)\b(.+?)(?<!@)(@endphp)\b#s', $callback, $content);
		return $content;
	}

	/**
	 * Convert verbatim sections.
	 *
	 * Nothing inside a @verbatim ... @endverbatim section will be converted.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertVerbatimSections(string $content): string
	{
		$content = preg_replace_callback('#(@verbatim)\b(.+?)(@endverbatim)\b#s', function($match) {
			return preg_replace(['#(?<!@)\{\{#', '#(?<!@)@([a-z]+)#', '#\$#'], ['@{{', '@@$1', '&#x1B;&#x24;'], $match[2]);
		}, $content);
		return $content;
	}

	/**
	 * Convert fragments.
	 *
	 * Sections delimited by <fragment name="name"> ... </fragment> (XE-style)
	 * or @fragment('name') ... @endfragment (Blade-style) are stored
	 * separately when executed, and can be accessed through getFragment()
	 * afterwards. They are, of course, also included in the primary output.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertFragments(string $content): string
	{
		// Convert XE-style fragment code. Blade-style is handled elsewhere.
		$regexp = '#<fragment\s+name="([^"]+)"\s*/?>(.*?)</fragment>#s';
		$content = preg_replace_callback($regexp, function($match) {
			$name = trim($match[1]);
			$content = $match[2];
			$tpl = '<?php ob_start(); \$__last_fragment_name = ' . var_export($name, true) . '; ?>';
			$tpl .= $content;
			$tpl .= '<?php $this->_fragments[\$__last_fragment_name] = ob_get_flush(); ?>';
			return $tpl;
		}, $content);

		return $content;
	}

	/**
	 * Convert class aliases.
	 *
	 * This makes it easier to reference classes in deeply nested namespaces
	 * without cluttering the template source code.
	 * It works much the same way as the native "use" statement,
	 * except that it does not actually import the class anywhere.
	 *
	 * XE-style syntax:
	 * <use class="Rhymix\Modules\Foobar\Models\HelloWorld" as="HelloWorldModel" />
	 *
	 * Blade-style syntax:
	 * @use('Rhymix\Modules\Foobar\Models\HelloWorld', 'HelloWorldModel')
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertClassAliases(string $content): string
	{
		// Find all alias directives.
		$regexp = '#(?:<use\s+class="([^"]+)"\s+as="([^"]+)"\s*/?>|(?<!@)@use\x20?\([\'"]([^\'"]+)[\'"],\s*[\'"]([^\'"]+)[\'"]\))#';
		$content = preg_replace_callback($regexp, function($match) {
			$class = isset($match[3]) ? $match[3] : $match[1];
			$alias = isset($match[4]) ? $match[4] : $match[2];
			$this->_aliases[$alias] = $class;
			return '';
		}, $content);

		// Replace aliases.
		if (count($this->_aliases))
		{
			$regexp = implode('|', array_map(function($str) {
				return preg_quote($str, '#');
			}, array_keys($this->_aliases)));

			$content = preg_replace_callback('#\b(new\s+)?(' . $regexp . ')\b#', function($match) {
				return $match[1] . $this->_aliases[$match[2]];
			}, $content);
		}

		return $content;
	}

	/**
	 * Convert include directives.
	 *
	 * Templates can be included conditionally
	 * using the 'if', 'when', and 'unless' attributes.
	 *
	 * Templates can be included with an array of variables that will
	 * only be available in the included template.
	 * If variables are supplied, global variables will not be available
	 * inside of the included template.
	 *
	 * XE-style syntax:
	 * <include src="view" />
	 * <include src="view.html" if="$condition" vars="$vars" />
	 *
	 * Blade-style syntax:
	 * @include('view')
	 * @include('view.blade.php', $vars)
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertIncludes(string $content): string
	{
		// Convert XE-style include directives.
		$regexp = '#(<include(?:\s+(?:target|src|if|when|cond|unless|vars)="(?:[^"]+)")+\s*/?>)#';
		$content = preg_replace_callback($regexp, function($match) {

			// Convert the path if necessary.
			$attrs = self::_getTagAttributes($match[1]);
			$path = $attrs['src'] ?? ($attrs['target'] ?? null);
			if (!$path) return $match[0];
			$dir = '$this->relative_dirname';
			if (preg_match('#^\^/?(\w.+)$#s', $path, $m))
			{
				$dir = '"' . (str_contains($m[1], '/') ? dirname($m[1]) : '') . '"';
				$path = basename($m[1]);
			}

			// Generate the code to create a new Template object and compile it.
			$tpl = '<?php $__tpl = new \Rhymix\Framework\Template(' . $dir . ', "' . $path . '", "' . ($this->template->extension ?: 'auto') . '"); ';
			$tpl .= !empty($attrs['vars']) ? '$__tpl->setVars(' . self::_convertVariableScope($attrs['vars']) . '); ' : '';
			$tpl .= 'echo $__tpl->compile(); ?>';

			// Add conditions around the code.
			if (!empty($attrs['if']) || !empty($attrs['when']) || !empty($attrs['cond']))
			{
				$condition = $attrs['if'] ?? ($attrs['when'] ?? $attrs['cond']);
				$tpl = '<?php if(!empty(' . $condition . ')): ?>' . $tpl . '<?php endif; ?>';
			}
			if (!empty($attrs['unless']))
			{
				$condition = $attrs['unless'];
				$tpl = '<?php if(empty(' . $condition . ')): ?>' . $tpl . '<?php endif; ?>';
			}
			return self::_escapeVars($tpl);
		}, $content);

		// Convert Blade-style include directives.
		$parentheses = self::_getRegexpForParentheses(2);
		$regexp = '#(?<!@)@(include(?:If|When|Unless)?)\x20?(' . $parentheses . ')#';
		$content = preg_replace_callback($regexp, function($match) {
			$directive = trim($match[1]);
			$args = self::_convertVariableScope(substr($match[2], 1, strlen($match[2]) - 2));
			return sprintf("<?php echo \$this->_v2_include('%s', %s); ?>", $directive, $args);
		}, $content);

		// Handle the @each directive.
		$parentheses = self::_getRegexpForParentheses(1);
		$regexp = '#(?<!@)@each\x20?(' . $parentheses . ')#';
		$content = preg_replace_callback($regexp, function($match) {

			// Convert the path if necessary.
			$args = self::_convertVariableScope(substr($match[1], 1, strlen($match[1]) - 2));

			// Generate the loop code.
			$tpl = '<?php (function($__filename, $__vars, $__varname, $__empty = null) { ';
			$tpl .= 'if (!$__vars): $__vars = []; if ($__empty): $__filename = $__empty; $__vars[] = \'\'; endif; endif; ';
			$tpl .= 'foreach ($__vars as $__var): ';
			$tpl .= 'echo $this->_v2_include("include", $__filename, [(string)$__varname => $__var]); ';
			$tpl .= 'endforeach; })(' . $args . '); ?>';
			return self::_escapeVars($tpl);
		}, $content);

		return $content;
	}

	/**
	 * Convert resource loading directives.
	 *
	 * This can be used to load nearly every kind of asset, from scripts
	 * and stylesheets to lang files to Rhymix core Javascript plugins.
	 *
	 * XE-style syntax:
	 * <load src="script.js" type="body" index="10" />
	 * <load src="^/common/js/plugins/ckedior/" />
	 * <load lang="./lang" />
	 *
	 * Blade-style syntax:
	 * @load('dir/script.js', 'body', 10)
	 * @load('^/common/js/plugins/ckeditor')
	 * @load('styles.scss', $vars)
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertResource(string $content): string
	{
		// Convert XE-style load directives.
		$regexp = '#(<load(?:\s+(?:target|src|type|media|index|vars)="(?:[^"]+)")+\s*/?>)#';
		$content = preg_replace_callback($regexp, function($match) {
			$attrs = self::_getTagAttributes($match[1]);
			return vsprintf('<?php \$this->_v2_loadResource(%s, %s, %s, %s); ?>', [
				var_export($attrs['src'] ?? ($attrs['target'] ?? ''), true),
				var_export($attrs['type'] ?? ($attrs['media'] ?? ''), true),
				var_export($attrs['index'] ?? '', true),
				self::_convertVariableScope($attrs['vars'] ?? '') ?: '[]',
			]);
		}, $content);

		// Convert Blade-style load directives.
		$parentheses = self::_getRegexpForParentheses(1);
		$regexp = '#(?<!@)@load\x20?(' . $parentheses . ')#';
		$content = preg_replace_callback($regexp, function($match) {
			$args = self::_convertVariableScope(substr($match[1], 1, strlen($match[1]) - 2));
			return sprintf('<?php \$this->_v2_loadResource(%s); ?>', $args);
		}, $content);

		return $content;
	}

	/**
	 * Convert loop and condition directives.
	 *
	 * Loops and conditions can be written inside HTML comments (XE-style)
	 * or without comments (Blade-style).
	 *
	 * It is highly recommended that ending directives match the starting
	 * directive, e.g. @if ... @endif. However, for compatibility with legacy
	 * templates, Rhymix will automatically find out which loop you are
	 * trying to close if you simply write @end. Either way, your loops must
	 * balance out or you will see 'unexpected end of file' errors.
	 *
	 * XE-style syntax:
	 * <!--@if($cond)-->
	 * <!--@end-->
	 *
	 * Blade-style syntax:
	 * @if ($cond)
	 * @endif
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertLoopDirectives(string $content): string
	{
		// Generate the list of directives to match.
		foreach (self::$_loopdef as $directive => $def)
		{
			$directive = preg_replace('#(.+)if$#', '$1[iI]f', $directive);
			$directives[] = $directive;
			if (count($def) > 1)
			{
				$directives[] = 'end[' . substr($directive, 0, 1) . strtoupper(substr($directive, 0, 1)) . ']' . substr($directive, 1);
			}
		}
		usort($directives, function($a, $b) { return strlen($b) - strlen($a); });
		$directives = implode('|', $directives) . '|end';

		// Convert both XE-style and Blade-style directives.
		$parentheses = self::_getRegexpForParentheses(2);
		$regexp = '#(?:<!--)?(?<!@)@(' . $directives . ')\b\x20?(' . $parentheses . ')?(?:[\x20\x09]*-->)?#';
		$content = preg_replace_callback($regexp, function($match) {

			// Collect the necessary information.
			$directive = strtolower($match[1]);
			$args = isset($match[2]) ? self::_convertVariableScope(substr($match[2], 1, strlen($match[2]) - 2)) : '';
			$stack = null;
			$code = null;

			// If this is an ending directive, find the loop information in the stack.
			if (preg_match('#^end(.*)$#', $directive, $m))
			{
				$stack = array_pop($this->_stack);
				$directive = $m[1] ?: ($stack ? $stack['directive'] : '');
			}

			// Handle intermediate directives first.
			if ($directive === 'empty' && !$args && !$stack && end($this->_stack)['directive'] === 'forelse')
			{
				$stack = end($this->_stack);
				$code = self::$_loopdef['forelse'][1];
				$code = strtr($code, ['%uniq' => $stack['uniq'], '%array' => $stack['array'], '%remainder' => $stack['remainder']]);
			}

			// Single directives.
			elseif (isset(self::$_loopdef[$directive]) && count(self::$_loopdef[$directive]) === 1)
			{
				$code = self::$_loopdef[$directive][0];
				$code = str_contains($code, '%s') ? sprintf($code, $args) : $code;
			}

			// Paired directives.
			elseif (isset(self::$_loopdef[$directive]))
			{
				// Starting directive.
				if (!$stack)
				{
					$uniq = substr(sha1($this->template->absolute_path . ':' . $this->_uniq_order++), 0, 14);
					$array = '';
					$remainder = '';
					if ($directive === 'foreach' || $directive === 'forelse')
					{
						if (preg_match('#^(.+?)\sas\s(.+)#is', $args, $m))
						{
							$array = trim($m[1]);
							$remainder = trim($m[2]);
						}
						else
						{
							$array = $args;
							$remainder = '';
						}
					}
					$code = self::$_loopdef[$directive][0];
					$code = strtr($code, ['%s' => $args, '%uniq' => $uniq, '%array' => $array, '%remainder' => $remainder]);
					$this->_stack[] = [
						'directive' => $directive,
						'args' => $args,
						'uniq' => $uniq,
						'array' => $array,
						'remainder' => $remainder,
					];
				}

				// Ending directive.
				else
				{
					$code = end(self::$_loopdef[$directive]);
					$code = strtr($code, ['%s' => $stack['args'], '%uniq' => $stack['uniq'], '%array' => $stack['array'], '%remainder' => $stack['remainder']]);
				}
			}
			else
			{
				return $match[0];
			}

			// Put together the PHP code.
			return self::_escapeVars("<?php $code ?>");

		}, $content);

		return $content;
	}

	/**
	 * Convert inline directives.
	 *
	 * This helps display commonly used attributes conditionally,
	 * without littering the template with inline @if ... @endif directives.
	 * The 'cond' attribute usd in XE has been expanded to support
	 * the shorter 'if' notation, 'when', and 'unless'.
	 *
	 * XE-style syntax:
	 * <option selected="selected"|if="$cond">
	 *
	 * Blade-style syntax:
	 * <option @selected($cond)>
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertInlineDirectives(string $content): string
	{
		// Convert XE-style inline directives.
		$regexp = '#\s*([a-zA-Z0-9_-]+)(?:="([^"]+)")?\|(if|when|cond|unless)="([^"]+)"#i';
		$content = preg_replace_callback($regexp, function($match) {
			$condition = sprintf(($match[3] === 'unless') ? 'if (!(%s)):' : 'if (%s):', self::_convertVariableScope($match[4]));
			return sprintf('<?php %s ?> %s="%s"<?php endif; ?>', $condition, $match[1], $match[2] ?: $match[1]);
		}, $content);

		// Convert Blade-style inline directives.
		$parentheses = self::_getRegexpForParentheses(2);
		$regexp = '#\s*(?<!@)@(checked|selected|disabled|readonly|required)\x20?(' . $parentheses . ')#';
		$content = preg_replace_callback($regexp, function($match) {
			$condition = self::_convertVariableScope($match[2]);
			return sprintf('<?php if %s: ?> %s="%s"<?php endif; ?>', $condition, $match[1], $match[1]);
		}, $content);

		// Convert Blade-style @class and @style conditions.
		$regexp = '#\s*(?<!@)@(class|style)\x20?(' . $parentheses . ')#';
		$content = preg_replace_callback($regexp, function($match) {
			$attribute = trim($match[1]);
			$definitions = self::_convertVariableScope(substr($match[2], 1, strlen($match[2]) - 2));
			return sprintf("<?php echo \$this->_v2_buildAttribute('%s', %s); ?>", $attribute, $definitions);
		}, $content);

		return $content;
	}

	/**
	 * Convert miscellaneous directives.
	 *
	 * @csrf
	 * @json($var)
	 * @lang('foo.bar')
	 * @stack('name')
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertMiscDirectives(string $content): string
	{
		// Insert CSRF tokens.
		$content = preg_replace_callback('#(?<!@)@csrf\b#', function($match) {
			return '<input type="hidden" name="_rx_csrf_token" value="<?php echo \Rhymix\Framework\Session::getGenericToken(); ?>" />';
		}, $content);

		// Insert JSON, lang codes, and dumps.
		$parentheses = self::_getRegexpForParentheses(2);
		$content = preg_replace_callback('#(?<!@)@(json|lang|dump|stack)\x20?('. $parentheses . ')#', function($match) {
			$args = self::_convertVariableScope(substr($match[2], 1, strlen($match[2]) - 2));
			if ($match[1] === 'json')
			{
				return sprintf('<?php echo $this->config->context === \'JS\' ? ' .
					'json_encode(%s, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_HEX_TAG | \JSON_HEX_QUOT) : ' .
					'htmlspecialchars(json_encode(%s, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_HEX_TAG | \JSON_HEX_QUOT), \ENT_QUOTES, \'UTF-8\', false); ?>', $args, $args);
			}
			elseif ($match[1] === 'lang')
			{
				return sprintf('<?php echo $this->config->context === \'JS\' ? escape_js(lang(%s)) : lang(%s); ?>', $args, $args);
			}
			elseif ($match[1] === 'dump')
			{
				return sprintf('<?php ob_start(); var_dump(%s); \$__dump = ob_get_clean(); echo rtrim(\$__dump); ?>', $args);
			}
			elseif ($match[1] === 'stack')
			{
				return sprintf('<?php echo implode("\n", self::\$_stacks[%s] ?? []) . "\n"; ?>', $args);
			}
			else
			{
				return $match[0];
			}
		}, $content);

		return $content;
	}

	/**
	 * Convert echo statements.
	 *
	 * XE-style syntax:
	 * {$var|filter}
	 *
	 * Blade-style syntax:
	 * {{ $var | filter }}
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertEchoStatements(string $content): string
	{
		// Convert {{ double }} curly braces.
		$content = preg_replace_callback('#(?<!@)\{\{(.+?)\}\}#s', [$this, '_arrangeOutputFilters'], $content);

		// Convert {!! unescaped !!} curly braces.
		$content = preg_replace_callback('#(?<!@)\{!!(.+?)!!\}#s', function($match) {
			$match[1] .= '|noescape';
			return $this->_arrangeOutputFilters($match);
		}, $content);

		// Convert {single} curly braces.
		$content = preg_replace_callback('#(?<!\{)\{(?!\s)([^{}]+?)\}#', [$this, '_arrangeOutputFilters'], $content);

		return $content;
	}

	/**
	 * Subroutine for applying filters to an echo statement.
	 *
	 * @param array $match
	 * @return string
	 */
	protected function _arrangeOutputFilters(array $match): string
	{
		// Escape is 'autoescape' by default.
		$escape_option = 'autoescape';

		// Split content into filters.
		$filters = array_map('trim', preg_split('#(?<![\\\\\|])\|(?![\|\'"])#', $match[1]));
		$str = strtr(array_shift($filters), ['\\|' => '|']);

		// Convert variable scope before applying filters.
		$str = $this->_escapeCurly($str);
		$str = $this->_convertVariableScope($str);

		// Prevent null errors.
		if (preg_match('#^\$[\\\\\w\[\]\'":>-]+$#', $str))
		{
			$str = preg_match('/^\$lang->/', $str) ? $str : "$str ?? ''";
		}

		// Apply filters.
		foreach ($filters as $filter)
		{
			// Separate the filter option from the filter name.
			$filter_option = null;
			if (preg_match('#^([a-z0-9_-]+):(.+)$#', $filter, $m))
			{
				$filter = $m[1];
				$filter_option = strtr($m[2], ['\|' => '|']);
				if (!preg_match('#^\$#', $filter_option) && !preg_match('#^([\'"]).*\1$#', $filter_option))
				{
					$filter_option = "'" . escape_sqstr($filter_option) . "'";
				}
				else
				{
					$filter_option = self::_convertVariableScope($filter_option);
				}
			}

			// Apply each filter.
			switch ($filter)
			{
				case 'autoescape':
				case 'autolang':
				case 'escape':
				case 'noescape':
					$escape_option = $filter;
					break;
				case 'escapejs':
				case 'js':
					$str = "escape_js({$str})";
					$escape_option = 'noescape';
					break;
				case 'json':
					$str = "json_encode({$str}, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_HEX_TAG | \JSON_HEX_QUOT)";
					$escape_option = 'autocontext';
					break;
				case 'strip':
				case 'strip_tags':
					$str = $filter_option ? "strip_tags({$str}, {$filter_option})" : "strip_tags({$str})";
					break;
				case 'trim':
					$str = "trim({$str})";
					break;
				case 'urlencode':
					$str = "rawurlencode({$str})";
					break;
				case 'lower':
					$str = "strtolower({$str})";
					break;
				case 'upper':
					$str = "strtoupper({$str})";
					break;
				case 'nl2br':
					$str = self::_applyEscapeOption($str, $escape_option);
					$str = "nl2br({$str})";
					$escape_option = 'noescape';
					break;
				case 'join':
					$str = $filter_option ? "implode({$filter_option}, {$str})" : "implode(', ', {$str})";
					break;
				case 'date':
					$str = $filter_option ? "getDisplayDateTime(ztime({$str}), {$filter_option})" : "getDisplayDateTime(ztime({$str}), 'Y-m-d H:i:s')";
					break;
				case 'format':
				case 'number_format':
					$str = $filter_option ? "number_format({$str}, {$filter_option})" : "number_format({$str})";
					break;
				case 'shorten':
				case 'number_shorten':
					$str = $filter_option ? "number_shorten({$str}, {$filter_option})" : "number_shorten({$str})";
					break;
				case 'link':
					$str = self::_applyEscapeOption($str, $escape_option);
					if ($filter_option)
					{
						$filter_option = self::_applyEscapeOption($filter_option, $escape_option);
						$str = "'<a href=\"' . ($filter_option) . '\">' . ($str) . '</a>'";
					}
					else
					{
						$str = "'<a href=\"' . ($str) . '\">' . ($str) . '</a>'";
					}
					$escape_option = 'noescape';
					break;
				default:
					$filter = escape_sqstr($filter);
					$str = "'INVALID FILTER ({$filter})'";
			}
		}

		// Apply the escape option and return.
		return '<?php echo ' . self::_applyEscapeOption($str, $escape_option) . '; ?>';
	}

	/**
	 * Subroutine for applying escape options to an echo statement.
	 *
	 * @param string $str
	 * @param string $option
	 * @return string
	 */
	protected static function _applyEscapeOption(string $str, string $option): string
	{
		switch($option)
		{
			case 'autocontext':
				return "\$this->config->context === 'JS' ? ({$str}) : htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', false)";
			case 'autoescape':
				return "htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', false)";
			case 'autolang':
				return "(preg_match('/^\\$(?:user_)?lang->\\w+$/', {$str}) ? ({$str}) : htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', false))";
			case 'escape':
				return "htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', true)";
			case 'noescape':
				return "{$str}";
			default:
				return "htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', false)";
		}
	}

	/**
	 * Postprocessing.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _postprocess(string $content): string
	{
		// Restore curly braces and escaped variables.
		$content = strtr($content, [
			'&#x1B;&#x7B;' => '{',
			'&#x1B;&#x7D;' => '}',
			'&#x1B;&#x24;' => '$',
			'\\$' => '$',
		]);

		// Restore escaped Blade-style directives.
		$content = preg_replace([
			'#@(@[a-z]{2,})#',
			'#@(\{\{)#',
		], '$1', $content);

		// Prepend the version number.
		if (!str_contains($content, '$this->config->version'))
		{
			$content = '<?php $this->config->version = 2; ?>' . $content;
		}

		// Prepend constant check to block direct invocation of the cache file.
		$content = '<?php if (!defined("RX_VERSION")) exit(); ?>' . $content;

		// Remove unnecessary spaces before and after PHP tags.
		$content = preg_replace([
			'#^[\x20\x09]+(<\?(?:php\b|=))#m',
			'#(\?>)[\x20\x09]+$#m',
		], '$1', $content);

		return $content;
	}

	/**
	 * Convert variable scope.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _convertVariableScope(string $content): string
	{
		// Replace variables that need to be enclosed in curly braces, using temporary entities to prevent double-replacement.
		$content = preg_replace_callback('#(?<!\$__Context)->\$([a-zA-Z_][a-zA-Z0-9_]*)#', function($match) {
			return '->' . self::_escapeCurly('{') . '$__Context->' . $match[1] . self::_escapeCurly('}');
		}, $content);

		// Replace all other variables with Context attributes.
		$content = preg_replace_callback('#(?<!::|\\\\|\$__Context->|\')\$([a-zA-Z_][a-zA-Z0-9_]*)#', function($match) {
			if (preg_match('/^(?:GLOBALS|_SERVER|_COOKIE|_ENV|_GET|_POST|_REQUEST|_SESSION|__Context|this)$/', $match[1]))
			{
				return '$' . $match[1];
			}
			elseif ($match[1] === 'loop')
			{
				return 'end(self::$_loopvars)';
			}
			else
			{
				return '$__Context->' . $match[1];
			}
		}, $content);

		return $content;
	}

	/**
	 * Get attributes of an HTML tag as an associative array.
	 *
	 * @param string $html
	 * @return array
	 */
	protected static function _getTagAttributes(string $html): array
	{
		$result = [];
		if (preg_match_all('#([a-zA-Z0-9_-]+)="([^"]*)"#', $html, $matches, \PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$result[$match[1]] = $match[2];
			}
		}
		return $result;
	}

	/**
	 * Get a recursive regular expression to match (balanced (parentheses)).
	 *
	 * The (?R) in the example is replaced with the actual match position (?n)
	 * in order to allow this pattern to be used as part of a larger regexp.
	 *
	 * https://www.php.net/manual/en/regexp.reference.recursive.php
	 * https://stackoverflow.com/a/35271017
	 *
	 * @param int $position_in_regexp
	 * @return string
	 */
	protected static function _getRegexpForParentheses(int $position_in_regexp): string
	{
		return '\([^)(]*+(?:(?' . $position_in_regexp . ')[^)(]*)*+\)';
	}

	/**
	 * Escape curly braces so that they will not be interpreted as echo statements.
	 *
	 * @param string $code
	 * @return string
	 */
	protected static function _escapeCurly(string $code): string
	{
		return strtr($code, [
			'{' => '&#x1B;&#x7B;',
			'}' => '&#x1B;&#x7D;',
		]);
	}

	/**
	 * Escape the dollar sign in PHP code so that its scope will not be converted.
	 *
	 * @param string $code
	 * @return string
	 */
	protected static function _escapeVars(string $code): string
	{
		return strtr($code, [
			'$' => '&#x1B;&#x24;',
		]);
	}
}
