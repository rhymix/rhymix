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
	 * This is not the exhaustive list of Blade-style directives
	 * supported by TemplateParser v2. Other directives may be handled
	 * by individual conversion methods, such as _convertIncludes().
	 *
	 * %s         : Full PHP code passed to the directive in parentheses.
	 * %array     : The array used in a foreach/forelse loop.
	 * %remainder : The remainder of the foreach/forelse loop ($key => $val)
	 * %uniq      : A random string that identifies a specific loop or condition.
	 */
	protected static $_directives = [
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
		'pushonce' => [
			'ob_start(); if (!isset(self::$_stacks[%s])): self::$_stacks[%s] = []; endif;',
			'$__tmp_%uniq = trim(ob_get_clean()); if (!in_array($__tmp_%uniq, self::$_stacks[%s])): array_push(self::$_stacks[%s], $__tmp_%uniq); endif;',
		],
		'prepend' => [
			'ob_start(); if (!isset(self::$_stacks[%s])): self::$_stacks[%s] = []; endif;',
			'array_unshift(self::$_stacks[%s], trim(ob_get_clean()));',
		],
		'prependif' => [
			'list($__stack_cond, $__stack_name) = [%s]; if ($__stack_cond): ob_start(); if (!isset(self::$_stacks[$__stack_name])): self::$_stacks[$__stack_name] = []; endif;',
			'array_unshift(self::$_stacks[$__stack_name], trim(ob_get_clean())); endif;',
		],
		'prependonce' => [
			'ob_start(); if (!isset(self::$_stacks[%s])): self::$_stacks[%s] = []; endif;',
			'$__tmp_%uniq = trim(ob_get_clean()); if (!in_array($__tmp_%uniq, self::$_stacks[%s])): array_unshift(self::$_stacks[%s], $__tmp_%uniq); endif;',
		],
		'isset' => ['if (isset(%s)):', 'endif;'],
		'unset' => ['if (!isset(%s)):', 'endif;'],
		'empty' => ['if (empty(%s)):', 'endif;'],
		'admin' => ['if ($this->user->isAdmin()):', 'endif;'],
		'auth' => ['if ($this->_v2_checkAuth(%s)):', 'endif;'],
		'can' => ['if ($this->_v2_checkCapability(1, %s)):', 'endif;'],
		'cannot' => ['if ($this->_v2_checkCapability(2, %s)):', 'endif;'],
		'canany' => ['if ($this->_v2_checkCapability(3, %s)):', 'endif;'],
		'guest' => ['if (!$this->user->isMember()):', 'endif;'],
		'desktop' => ["if (!\\Context::get('m')):", 'endif;'],
		'mobile' => ["if (\\Context::get('m')):", 'endif;'],
		'env' => ['if (!empty($_ENV[%s])):', 'endif;'],
		'else' => ['else:'],
		'elseif' => ['elseif (%s):'],
		'case' => ['case %s:'],
		'default' => ['default:'],
		'continue' => ['continue;'],
		'break' => ['break;'],
	];

	/**
	 * Cache the compiled regexp for directives here.
	 */
	protected static $_directives_regexp;

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
		$content = $this->_addDeprecationMessages($content);

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
		return preg_replace_callback('#(<script\b([^>]*)|</script)#i', function($match) {
			if (substr($match[1], 1, 1) === '/')
			{
				return '<?php $this->config->context = "HTML"; ?>' . $match[1];
			}
			elseif (!str_contains($match[2] ?? '', 'src="'))
			{
				return $match[1] . '<?php $this->config->context = "JS"; ?>';
			}
			else
			{
				return $match[0];
			}
		}, $content);
	}

	/**
	 * Remove context switch points.
	 *
	 * @param string $content
	 * @return string
	 */
	protected static function _removeContextSwitches(string $content): string
	{
		return preg_replace('#<\?php \$this->config->context = "[A-Z]+"; \?>#', '', $content);
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
		$content = preg_replace_callback($regexp, function($match) use ($basepath) {
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

		// Convert relative paths in CSS url() function.
		$regexp = ['#\b(style=")([^"]+)(")#', '#(<style\b)(.*?)(</style>)#s'];
		$content = preg_replace_callback($regexp, function($match) use ($basepath) {
			$regexp = '#\b(url\([\'"]?)([^\'"\(\)]+)([\'"]?\))#';
			$match[2] = preg_replace_callback($regexp, function($match) use ($basepath) {
				if ($this->template->isRelativePath($match[2] = trim($match[2])))
				{
					$match[2] = $this->template->convertPath($match[2], $basepath);
				}
				return $match[1] . $match[2] . $match[3];
			}, $match[2]);
			return $match[1] . $match[2] . $match[3];
		}, $content);
		return $content;
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
			if ($match[1] === '<?=')
			{
				$open = '<?php echo' . (preg_match('#^\s#', $match[2]) ? '' : ' ');
			}
			else
			{
				$open = '<?php' . (preg_match('#^\s#', $match[2]) ? '' : ' ');
			}
			$close = (preg_match('#\s$#', $match[2]) ? '' : ' ') . '?>';
			return $open . self::_convertVariableScope(self::_removeContextSwitches($match[2])) . $close;
		};

		$content = preg_replace_callback('#(<\?php|<\?=?)(.+?)(\?>)#s', $callback, $content);
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
		$conversions = [
			'#(?<!\{)\{(?!\s)([^{}]+?)\}#' => '&#x1B;&#x7B;$1&#x1B;&#x7D;',
			'#(?<!@)\{\{#' => '@{{',
			'#(?<!@)@([a-z]+)#' => '@@$1',
			'#\$#' => '&#x1B;&#x24;',
		];

		$content = preg_replace_callback('#(@verbatim)\b(.+?)(@endverbatim)\b#s', function($match) use($conversions) {
			return preg_replace(array_keys($conversions), array_values($conversions), $match[2]);
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
			if (preg_match('#^(.+)/([^/]+)$#', $path, $match))
			{
				$dir = '$this->normalizePath(' . $dir . ' . "' . $match[1] . '")';
				$path = $match[2];
			}

			// Generate the code to create a new Template object and compile it.
			$tpl = '<?php $__tpl = new \Rhymix\Framework\Template(' . $dir . ', "' . $path . '", "' . ($this->template->extension ?: 'auto') . '"); ';
			$tpl .= '$__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif; ';
			$tpl .= !empty($attrs['vars']) ? '$__tpl->addVars(' . self::_convertVariableScope($attrs['vars']) . '); ' : '';
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
			$args = self::_convertVariableScope(substr($match[2], 1, -1));
			return sprintf("<?php echo \$this->_v2_include('%s', %s); ?>", $directive, $args);
		}, $content);

		// Handle the @each directive.
		$parentheses = self::_getRegexpForParentheses(1);
		$regexp = '#(?<!@)@each\x20?(' . $parentheses . ')#';
		$content = preg_replace_callback($regexp, function($match) {

			// Convert the path if necessary.
			$args = self::_convertVariableScope(substr($match[1], 1, -1));

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
		$regexp = '#(<(load|unload)(?:\s+(?:target|src|type|media|index|vars)="(?:[^"]+)")+\s*/?>)#';
		$content = preg_replace_callback($regexp, function($match) {
			$attrs = self::_getTagAttributes($match[1]);
			if ($match[2] === 'load')
			{
				return vsprintf('<?php \$this->_v2_loadResource(%s, %s, %s, %s); ?>', [
					var_export($attrs['src'] ?? ($attrs['target'] ?? ''), true),
					var_export($attrs['type'] ?? ($attrs['media'] ?? ''), true),
					var_export($attrs['index'] ?? '', true),
					self::_convertVariableScope($attrs['vars'] ?? '') ?: '[]',
				]);
			}
			else
			{
				return vsprintf('<?php \Context::unloadFile(%s, \'\', %s); ?>', [
					var_export($this->template->convertPath($attrs['src'] ?? ($attrs['target'] ?? '')), true),
					var_export($attrs['media'] ?? 'all', true),
				]);
			}
		}, $content);

		// Convert Blade-style load directives.
		$parentheses = self::_getRegexpForParentheses(2);
		$regexp = '#(?<!@)@(load|unload)\x20?(' . $parentheses . ')#';
		$content = preg_replace_callback($regexp, function($match) {
			$args = self::_convertVariableScope(substr($match[2], 1, -1));
			if ($match[1] === 'load')
			{
				return sprintf('<?php \$this->_v2_loadResource(%s); ?>', $args);
			}
			else
			{
				return sprintf('<?php \Context::unloadFile(\$this->convertPath(%s)); ?>', $args);
			}
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
		if (self::$_directives_regexp === null)
		{
			foreach (self::$_directives as $directive => $def)
			{
				$directive = preg_replace(['#(?<=\w)once$#', '#(?<=\w)if$#'], ['[Oo]nce', '[Ii]f'], $directive);
				$directives[] = $directive;

				if (count($def) > 1)
				{
					$directives[] = 'end[' . substr($directive, 0, 1) . strtoupper(substr($directive, 0, 1)) . ']' . substr($directive, 1);
				}
			}
			usort($directives, function($a, $b) { return strlen($b) - strlen($a); });
			self::$_directives_regexp = implode('|', $directives) . '|end';
		}

		// Convert both XE-style and Blade-style directives.
		$parentheses = self::_getRegexpForParentheses(2);
		$regexp = '#(?:<!--)?(?<!@)@(' . self::$_directives_regexp . ')\b\x20?(' . $parentheses . ')?(?:[\x20\x09]*-->)?#';
		$content = preg_replace_callback($regexp, function($match) {

			// Collect the necessary information.
			$directive = strtolower($match[1]);
			$args = isset($match[2]) ? self::_convertVariableScope(substr($match[2], 1, -1)) : '';
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
				$code = self::$_directives['forelse'][1];
				$code = strtr($code, ['%uniq' => $stack['uniq'], '%array' => $stack['array'], '%remainder' => $stack['remainder']]);
			}

			// Single directives.
			elseif (isset(self::$_directives[$directive]) && count(self::$_directives[$directive]) === 1)
			{
				$code = self::$_directives[$directive][0];
				$code = str_contains($code, '%s') ? sprintf($code, $args) : $code;
			}

			// Paired directives.
			elseif (isset(self::$_directives[$directive]))
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
					$code = self::$_directives[$directive][0];
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
					$code = end(self::$_directives[$directive]);
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
			$definitions = self::_convertVariableScope(substr($match[2], 1, -1));
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
	 * @dump($var, $var, ...)
	 * @dd($var, $var, ...)
	 * @stack('name')
	 * @url(['mid' => $mid, 'act' => $act])
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
		$content = preg_replace_callback('#(?<!@)@(json|lang|dump|stack|url)\x20?('. $parentheses . ')#', function($match) {
			$args = self::_convertVariableScope(substr($match[2], 1, -1));
			switch ($match[1])
			{
				case 'json':
					return sprintf('<?php echo $this->config->context === \'JS\' ? ' .
						'json_encode(%s, self::$_json_options2) : ' .
						'htmlspecialchars(json_encode(%s, self::$_json_options), \ENT_QUOTES, \'UTF-8\', false); ?>', $args, $args);
				case 'lang':
					return sprintf('<?php echo $this->config->context === \'JS\' ? escape_js($this->_v2_lang(%s)) : $this->_v2_lang(%s); ?>', $args, $args);
				case 'dump':
					return sprintf('<?php ob_start(); var_dump(%s); \$__dump = ob_get_clean(); echo rtrim(\$__dump); ?>', $args);
				case 'dd':
					return sprintf('<?php while (ob_get_level()) ob_end_flush(); var_dump(%s); exit(); ?>', $args);
				case 'stack':
					return sprintf('<?php echo implode("\n", self::\$_stacks[%s] ?? []) . "\n"; ?>', $args);
				case 'url':
					return sprintf('<?php echo $this->config->context === \'JS\' ? escape_js(getNotEncodedUrl(%s)) : getUrl(%s); ?>', $args, $args);
				default:
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
		// Split content into filters.
		$filters = array_map('trim', preg_split('#(?<![\\\\\|])\|(?![\|\'"])#', $match[1]));
		$str = strtr(array_shift($filters), ['\\|' => '|']);

		// Set default escape option.
		if (preg_match('/^\\$(?:user_)?lang->\\w+$/', $str))
		{
			$escape_option = 'autocontext_lang';
		}
		else
		{
			$escape_option = 'autocontext';
		}

		// Prevent null errors.
		if (preg_match('#^\$[\\\\\w\[\]\'":>-]+$#', $str) && !str_starts_with($str, '$lang->'))
		{
			$str = "$str ?? ''";
		}

		// Convert variable scope and escape any curly braces.
		$str = $this->_escapeCurly($str);
		$str = $this->_convertVariableScope($str);

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
					$str = "json_encode({$str}, self::\$_json_options)";
					$escape_option = 'autocontext_json';
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
					$str = self::_applyEscapeOption($str, $escape_option === 'autocontext' ? 'autoescape' : $escape_option);
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
					$str = self::_applyEscapeOption($str, $escape_option === 'autocontext' ? 'autoescape' : $escape_option);
					if ($filter_option)
					{
						$filter_option = self::_applyEscapeOption($filter_option, $escape_option === 'autocontext' ? 'autoescape' : $escape_option);
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
		$str2 = strtr($str, ["\n" => ' ']);
		switch($option)
		{
			case 'autocontext':
				return "\$this->config->context === 'JS' ? escape_js({$str2}) : htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', false)";
			case 'autocontext_json':
				return "\$this->config->context === 'JS' ? {$str2} : htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', false)";
			case 'autocontext_lang':
				return "\$this->config->context === 'JS' ? escape_js({$str2}) : ({$str})";
			case 'autoescape':
				return "htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', false)";
			case 'autolang':
				return "(preg_match('/^\\\\\$(?:user_)?lang->\\w+$/', {$str2}) ? ({$str}) : htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', false))";
			case 'escape':
				return "htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', true)";
			case 'noescape':
				return "{$str}";
			default:
				return "htmlspecialchars({$str}, \ENT_QUOTES, 'UTF-8', false)";
		}
	}

	/**
	 * Add an error message if any supported v1 syntax is found.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _addDeprecationMessages(string $content): string
	{
		// <!--#include-->, <!--%import-->, etc.
		$content = preg_replace_callback('#<!--(\#include|%import|%unload|%load_js_plugin)\s?\(.+?-->#', function($match) {
			return '<?php trigger_error("' . $match[1] . ' is not supported in template v2", \E_USER_WARNING); ?>';
		}, $content);

		// <block>
		$content = preg_replace_callback('#<block(?=\s)#', function($match) {
			return $match[0] . '<?php trigger_error("block element is not supported in template v2", \E_USER_WARNING); ?>';
		}, $content);

		// cond, loop
		$content = preg_replace_callback('#(?<=\s)(cond|loop)="([^"]+)"#', function($match) {
			if ($match[1] === 'loop' && ctype_alnum($match[2]))
			{
				return $match[0];
			}
			return '<?php trigger_error("' . $match[1] . ' attribute is not supported in template v2", \E_USER_WARNING); ?>';
		}, $content);

		return $content;
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
