<?php

class TemplateParserV2Test extends \Codeception\Test\Unit
{
	private $prefix = '<?php if (!defined("RX_VERSION")) exit(); ?><?php $this->config->version = 2; ?>';
	private $baseurl;

	public function _before()
	{
		\Rhymix\Framework\Debug::disable();
		$this->baseurl = '/' . basename(dirname(dirname(dirname(dirname(__DIR__))))) . '/';
	}

	public function testVersionDetection()
	{
		// Extension is .html and config is explicitly declared
		$source = '<config version="2" />' . "\n" . '<div>{{ RX_VERSION|noescape }}</div>';
		$target = '<div><?php echo RX_VERSION; ?></div>';
		$this->assertEquals("\n" . $target, $this->_parse($source), false);

		$source = '@version(2)' . "\n" . '<div>@php func_get_args(); @endphp</div>';
		$target = '<div><?php func_get_args(); ?></div>';
		$this->assertEquals("\n" . $target, $this->_parse($source), false);

		// Extension is .blade.php and config is not declared
		$source = '<input @disabled(foo())>';
		$target = '<input<?php if (foo()): ?> disabled="disabled"<?php endif; ?>>';
		$this->assertEquals($target, $this->_parse($source));

		// Extension is .blade.php but version is incorrectly declared: will be parsed as v1
		$source = '@version(1)' . "\n" . '<input @disabled(foo())>';
		$target = '<input @disabled(foo())>';
		$this->assertStringContainsString($target, $this->_parse($source));
	}

	public function testClassAliases()
	{
		// XE-style
		$source = '<use class="Rhymix\Framework\Template" as="TemplateHandler" />' . "\n" . '{@ $foo = TemplateHandler::getInstance()}';
		$target = "\n" . '<?php $__Context->foo = Rhymix\Framework\Template::getInstance() ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style
		$source = "@use('Rhymix\Framework\Template', 'TemplateHandler')" . "\n" . '{@ $foo = new TemplateHandler()}';
		$target = "\n" . '<?php $__Context->foo = new Rhymix\Framework\Template() ?>';
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testInclude()
	{
		// Basic usage
		$source = '<include src="foobar" />';
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "foobar", "html"); $__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif; echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Legacy 'target' attribute
		$source = '<include target="subdir/foobar" />';
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->normalizePath($this->relative_dirname . "subdir"), "foobar", "html"); $__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif; echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Conditional include
		$source = '<include src="../up/foobar" if="$cond" />';
		$target = '<?php if(!empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template($this->normalizePath($this->relative_dirname . "../up"), "foobar", "html"); $__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif; echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Conditional include with legacy 'cond' attribute
		$source = '<include target="legacy/cond.statement.html" cond="$cond" />';
		$target = '<?php if(!empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template($this->normalizePath($this->relative_dirname . "legacy"), "cond.statement.html", "html"); $__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif; echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Path relative to Rhymix installation directory
		$source = '<include src="^/modules/foobar/views/baz" when="$cond" />';
		$target = '<?php if(!empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template("modules/foobar/views", "baz", "html"); $__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif; echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Unless
		$source = '<include src="^/modules/foobar/views/baz" unless="$cond" />';
		$target = '<?php if(empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template("modules/foobar/views", "baz", "html"); $__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif; echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// With variables
		$source = '<include src="foobar" vars="$vars" />';
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "foobar", "html"); $__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif; $__tpl->addVars($__Context->vars); echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// With array literal passed as variables
		$source = '<include src="foobar" vars="[\'foo\' => \'bar\']" />';
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "foobar", "html"); $__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif; $__tpl->addVars([\'foo\' => \'bar\']); echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @include
		$source = "@include ('foobar')";
		$target = "<?php echo \$this->_v2_include('include', 'foobar'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @include with variable in filename
		$source = "@include(\$var)";
		$target = "<?php echo \$this->_v2_include('include', \$__Context->var); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @include with path relative to Rhymix installation directory
		$source = '@include ("^/common/js/plugins/foobar/baz.blade.php")';
		$target = '<?php echo $this->_v2_include(\'include\', "^/common/js/plugins/foobar/baz.blade.php"); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @includeIf with variables
		$source = "@includeIf('dir/foobar', \$vars)";
		$target = "<?php echo \$this->_v2_include('includeIf', 'dir/foobar', \$__Context->vars); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @includeWhen
		$source = "@includeWhen(\$foo->isBar(), '../../foobar.html', \$vars)";
		$target = "<?php echo \$this->_v2_include('includeWhen', \$__Context->foo->isBar(), '../../foobar.html', \$__Context->vars); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @includeUnless with path relative to Rhymix installation directory
		$source = "@includeUnless (false, '^common/tpl/foobar.html', \$vars)";
		$target = "<?php echo \$this->_v2_include('includeUnless', false, '^common/tpl/foobar.html', \$__Context->vars); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @each
		$source = "@each('incl/eachtest', \$jobs, 'job')";
		$target = 'foreach ($__vars as $__var):';
		$this->assertStringContainsString($target, $this->_parse($source));

		// Blade-style @each with fallback template
		$source = "@each('incl/eachtest', \$jobs, 'job', 'incl/empty')";
		$target = 'echo $this->_v2_include("include"';
		$this->assertStringContainsString($target, $this->_parse($source));
	}

	public function testResourceLoading()
	{
		// CSS, SCSS, LESS with media and variables
		$source = '<load src="assets/hello.scss" media="print" vars="$foo" />';
		$target = "<?php \$this->_v2_loadResource('assets/hello.scss', 'print', '', \$__Context->foo); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '<load target="../hello.css" media="screen and (max-width: 800px)" />';
		$target = "<?php \$this->_v2_loadResource('../hello.css', 'screen and (max-width: 800px)', '', []); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// JS with type and index
		$source = '<load src="assets/hello.js" type="head" />';
		$target = "<?php \$this->_v2_loadResource('assets/hello.js', 'head', '', []); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '<load target="assets/../otherdir/hello.js" type="body" index="20" />';
		$target = "<?php \$this->_v2_loadResource('assets/../otherdir/hello.js', 'body', '20', []); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// External script
		$source = '<load src="//cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js" />';
		$target = "<?php \$this->_v2_loadResource('//cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js', '', '', []); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// External webfont
		$source = '<load src="https://fonts.googleapis.com/css2?family=Roboto&display=swap" />';
		$target = "<?php \$this->_v2_loadResource('https://fonts.googleapis.com/css2?family=Roboto&display=swap', '', '', []); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Path relative to Rhymix installation directory
		$source = '<load src="^/common/js/foobar.js" />';
		$target = "<?php \$this->_v2_loadResource('^/common/js/foobar.js', '', '', []); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// JS plugin
		$source = '<load src="^/common/js/plugins/ckeditor/" />';
		$target = "<?php \$this->_v2_loadResource('^/common/js/plugins/ckeditor/', '', '', []); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Lang file
		$source = '<load src="^/modules/member/lang" />';
		$target = "<?php \$this->_v2_loadResource('^/modules/member/lang', '', '', []); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '<load src="^/modules/legacy_module/lang/lang.xml" />';
		$target = "<?php \$this->_v2_loadResource('^/modules/legacy_module/lang/lang.xml', '', '', []); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style SCSS with media and variables
		$source = "@load('assets/hello.scss', 'print', 0, \$vars)";
		$target = "<?php \$this->_v2_loadResource('assets/hello.scss', 'print', 0, \$__Context->vars); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = "@load ('../hello.css', 'screen')";
		$target = "<?php \$this->_v2_loadResource('../hello.css', 'screen'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style JS with type and index
		$source = "@load('assets/hello.js', 'body', 10)";
		$target = "<?php \$this->_v2_loadResource('assets/hello.js', 'body', 10); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = "@load ('assets/hello.js', 'head')";
		$target = "<?php \$this->_v2_loadResource('assets/hello.js', 'head'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = "@load ('assets/hello.js')";
		$target = "<?php \$this->_v2_loadResource('assets/hello.js'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style external script
		$source = "@load ('//cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js')";
		$target = "<?php \$this->_v2_loadResource('//cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style external webfont
		$source = "@load('https://fonts.googleapis.com/css2?family=Roboto&display=swap')";
		$target = "<?php \$this->_v2_loadResource('https://fonts.googleapis.com/css2?family=Roboto&display=swap'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style path relative to Rhymix installation directory
		$source = '@load ("^/common/js/foobar.js")';
		$target = '<?php $this->_v2_loadResource("^/common/js/foobar.js"); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style JS plugin
		$source = "@load('^/common/js/plugins/ckeditor/')";
		$target = "<?php \$this->_v2_loadResource('^/common/js/plugins/ckeditor/'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style lang file
		$source = "@load('^/modules/member/lang')";
		$target = "<?php \$this->_v2_loadResource('^/modules/member/lang'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '@load("^/modules/legacy_module/lang/lang.xml")';
		$target = '<?php $this->_v2_loadResource("^/modules/legacy_module/lang/lang.xml"); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// XE-style unload
		$source = '<unload src="script.js" />';
		$target = "<?php \Context::unloadFile('tests/_data/template/script.js', '', 'all'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '<unload src="css/styles.css" media="braille" />';
		$target = "<?php \Context::unloadFile('tests/_data/template/css/styles.css', '', 'braille'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style unload
		$source = "@unload('../script.js')";
		$target = "<?php \Context::unloadFile(\$this->convertPath('../script.js')); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = "@unload('^/common/js/jquery.js')";
		$target = "<?php \Context::unloadFile(\$this->convertPath('^/common/js/jquery.js')); ?>";
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testContextSwitches()
	{
		// <script> tag
		$source = '<script type="text/javascript"> foobar(); </script>';
		$target = '<script type="text/javascript"<?php $this->config->context = \'JS\'; ?>> foobar(); <?php $this->config->context = \'HTML\'; ?></script>';
		$this->assertEquals($target, $this->_parse($source, true, false));

		// Inline script in link href
		$source = '<a href="javascript:void(0)">Hello</a>';
		$target = '<a href="javascript:<?php $this->config->context = \'JS\'; ?>void(0)<?php $this->config->context = \'HTML\'; ?>">Hello</a>';
		$this->assertEquals($target, $this->_parse($source, true, false));

		// Inline script in event handler
		$source = '<div class="foo" onClick="bar.barr()">Hello</div>';
		$target = '<div class="foo" onClick="<?php $this->config->context = \'JS\'; ?>bar.barr()<?php $this->config->context = \'HTML\'; ?>">Hello</div>';
		$this->assertEquals($target, $this->_parse($source, true, false));

		// <style> tag
		$source = '<style> body { font-size: 16px; } </style>';
		$target = '<style<?php $this->config->context = \'CSS\'; ?>> body { font-size: 16px; } <?php $this->config->context = \'HTML\'; ?></style>';
		$this->assertEquals($target, $this->_parse($source, true, false));

		// Inline style
		$source = '<div style="background-color: #ffffff;" class="foobar"><span></span></div>';
		$target = '<div style="<?php $this->config->context = \'CSS\'; ?>background-color: #ffffff;<?php $this->config->context = \'HTML\'; ?>" class="foobar"><span></span></div>';
		$this->assertEquals($target, $this->_parse($source, true, false));
	}

	public function testEchoStatements()
	{
		// Basic usage of XE-style single braces
		$source = '{$var}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(\$__Context->var ?? '', \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(\$__Context->var ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Single braces with space at beginning will not be parsed
		$source = '{ $var}';
		$target = '{ $var}';
		$this->assertEquals($target, $this->_parse($source));

		// Single braces with space at end are OK
		$source = '{$var  }';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(\$__Context->var ?? '', \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(\$__Context->var ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Correct handling of object property and array access
		$source = '{Context::getRequestVars()->$foo[$bar]}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(Context::getRequestVars()->{\$__Context->foo}[\$__Context->bar], \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(Context::getRequestVars()->{\$__Context->foo}[\$__Context->bar]); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Basic usage of Blade-style double braces
		$source = '{{ $var }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(\$__Context->var ?? '', \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(\$__Context->var ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Double braces without spaces are OK
		$source = '{{$var}}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(\$__Context->var ?? '', \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(\$__Context->var ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Literal double braces
		$source = '@{{ $var }}';
		$target = '{{ $var }}';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style shortcut for unescaped output
		$source = '{!! Context::getInstance()->get($var) !!}';
		$target = "<?php echo Context::getInstance()->get(\$__Context->var); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Callback function inside echo statement
		$source = '{{ implode("|", array_map(function(\$i) { return \$i + 1; }, $list) | noescape }}';
		$target = "<?php echo implode(\"|\", array_map(function(\$i) { return \$i + 1; }, \$__Context->list); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Multiline echo statement
		$source = '{{ $foo ?' . "\n" . '  date($foo) :' . "\n" . '  toBool($bar) }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(\$__Context->foo ?\n  date(\$__Context->foo) :\n  toBool(\$__Context->bar), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(\$__Context->foo ?   date(\$__Context->foo) :   toBool(\$__Context->bar)); ?>";
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testOutputFilters()
	{
		// Filters with no whitespace
		$source = '{$foo|upper|noescape}';
		$target = "<?php echo strtoupper(\$__Context->foo ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Randomly distributed whitespace
		$source = '{$foo | upper |noescape }';
		$target = "<?php echo strtoupper(\$__Context->foo ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Pipe character in filter option
		$source = "{\$foo|join:'|'|noescape}";
		$target = "<?php echo implode('|', \$__Context->foo ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Pipe character in filter option, escaped
		$source = "{\$foo|join:'foo\|bar'|noescape}";
		$target = "<?php echo implode('foo|bar', \$__Context->foo ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Pipe character in OR operator
		$source = '{$foo || $bar | noescape}';
		$target = "<?php echo \$__Context->foo || \$__Context->bar; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Autoescape
		$source = '{{ $foo|autoescape }}';
		$target = "<?php echo htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Autolang (lang codes are not escaped, but escape_js() is applied in JS context)
		$source = '{{ $foo|autolang }}';
		$target = "<?php echo (preg_match('/^\\\$(?:user_)?lang->\w+$/', \$__Context->foo ?? '') ? (\$__Context->foo ?? '') : htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', false)); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '{{ $lang->cmd_hello_world }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? (\$__Context->lang->cmd_hello_world) : \$this->_v2_escape(\$__Context->lang->cmd_hello_world); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '{{ $user_lang->user_lang_1234567890 }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? (\$__Context->user_lang->user_lang_1234567890 ?? '') : \$this->_v2_escape(\$__Context->user_lang->user_lang_1234567890 ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Escape
		$source = '{{ $foo|escape }}';
		$target = "<?php echo htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', true); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Noescape
		$source = '{{ $foo|escape|noescape }}';
		$target = "<?php echo \$__Context->foo ?? ''; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Escape for Javascript
		$source = '{{ $foo|js }}';
		$target = "<?php echo escape_js(\$__Context->foo ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Escape for Javascript (alternate name)
		$source = '{{ $foo|escapejs }}';
		$target = "<?php echo escape_js(\$__Context->foo ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// JSON using context-aware escape
		$source = '{{ $foo|json }}';
		$target = implode('', [
			"<?php echo \$this->config->context === 'JS' ? ",
			"json_encode(\$__Context->foo ?? '', self::\$_json_options) : ",
			"htmlspecialchars(json_encode(\$__Context->foo ?? '', self::\$_json_options), \ENT_QUOTES, 'UTF-8', false); ?>",
		]);
		$this->assertEquals($target, $this->_parse($source));

		// strip_tags
		$source = '{{ $foo|strip }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(strip_tags(\$__Context->foo ?? ''), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(strip_tags(\$__Context->foo ?? '')); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// strip_tags (alternate name)
		$source = '{{ $foo|upper|strip_tags }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(strip_tags(strtoupper(\$__Context->foo ?? '')), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(strip_tags(strtoupper(\$__Context->foo ?? ''))); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Trim
		$source = '{{ $foo|trim|noescape }}';
		$target = "<?php echo trim(\$__Context->foo ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// URL encode
		$source = '{{ $foo|trim|urlencode }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(rawurlencode(trim(\$__Context->foo ?? '')), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(rawurlencode(trim(\$__Context->foo ?? ''))); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Lowercase
		$source = '{{ $foo|trim|lower }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(strtolower(trim(\$__Context->foo ?? '')), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(strtolower(trim(\$__Context->foo ?? ''))); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Uppercase
		$source = '{{ $foo|upper|escape }}';
		$target = "<?php echo htmlspecialchars(strtoupper(\$__Context->foo ?? ''), \ENT_QUOTES, 'UTF-8', true); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// nl2br()
		$source = '{{ $foo|nl2br }}';
		$target = "<?php echo nl2br(htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', false)); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// nl2br() with gratuitous escape
		$source = '{{ $foo|nl2br|escape }}';
		$target = "<?php echo htmlspecialchars(nl2br(htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', false)), \ENT_QUOTES, 'UTF-8', true); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Array join (default joiner is comma)
		$source = '{{ $foo|join }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(implode(', ', \$__Context->foo ?? ''), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(implode(', ', \$__Context->foo ?? '')); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Array join (custom joiner)
		$source = '{{ $foo|join:"!@!" }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(implode(\"!@!\", \$__Context->foo ?? ''), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(implode(\"!@!\", \$__Context->foo ?? '')); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Date conversion (default format)
		$source = '{{ $item->regdate | date }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(getDisplayDateTime(ztime(\$__Context->item->regdate ?? ''), 'Y-m-d H:i:s'), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(getDisplayDateTime(ztime(\$__Context->item->regdate ?? ''), 'Y-m-d H:i:s')); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Date conversion (custom format)
		$source = "{{ \$item->regdate | date:'n/j H:i' }}";
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(getDisplayDateTime(ztime(\$__Context->item->regdate ?? ''), 'n/j H:i'), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(getDisplayDateTime(ztime(\$__Context->item->regdate ?? ''), 'n/j H:i')); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Date conversion (custom format in variable)
		$source = "{{ \$item->regdate | date:\$format }}";
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(getDisplayDateTime(ztime(\$__Context->item->regdate ?? ''), \$__Context->format), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(getDisplayDateTime(ztime(\$__Context->item->regdate ?? ''), \$__Context->format)); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number format
		$source = '{{ $num | format }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(number_format(\$__Context->num ?? ''), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(number_format(\$__Context->num ?? '')); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number format (alternate name)
		$source = '{{ $num | number_format }}';
		$target = "<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(number_format(\$__Context->num ?? ''), \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(number_format(\$__Context->num ?? '')); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number format (custom format)
		$source = '{{ $num | number_format:6 | noescape }}';
		$target = "<?php echo number_format(\$__Context->num ?? '', '6'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number format (custom format in variable)
		$source = '{{ $num | number_format:$digits | noescape }}';
		$target = "<?php echo number_format(\$__Context->num ?? '', \$__Context->digits); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number shorten
		$source = '{{ $num | shorten | noescape }}';
		$target = "<?php echo number_shorten(\$__Context->num ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number shorten (alternate name)
		$source = '{{ $num | number_shorten | noescape }}';
		$target = "<?php echo number_shorten(\$__Context->num ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number shorten (custom format)
		$source = '{{ $num | number_shorten:1 | noescape }}';
		$target = "<?php echo number_shorten(\$__Context->num ?? '', '1'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Link
		$source = '{{ $foo|link }}';
		$target = "<?php echo '<a href=\"' . (htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', false)) . '\">' . (htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', false)) . '</a>'; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Link (custom link text)
		$source = '{{ $foo|link:"Hello World" }}';
		$target = "<?php echo '<a href=\"' . (htmlspecialchars(\"Hello World\", \ENT_QUOTES, 'UTF-8', false)) . '\">' . (htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', false)) . '</a>'; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Link (custom link text in variable)
		$source = '{{ $foo|link:$bar->baz[0] }}';
		$target = "<?php echo '<a href=\"' . (htmlspecialchars(\$__Context->bar->baz[0], \ENT_QUOTES, 'UTF-8', false)) . '\">' . (htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', false)) . '</a>'; ?>";
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testVariableScopeConversion()
	{
		// Local variable
		$source = '{$foo|noescape}';
		$target = "<?php echo \$__Context->foo ?? ''; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Class and array keys
		$source = '{!! ClassName::getInstance()->$foo[$bar] !!}';
		$target = "<?php echo ClassName::getInstance()->{\$__Context->foo}[\$__Context->bar]; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Superglobals
		$source = "{!! \$_SERVER['HTTP_USER_AGENT'] . \$GLOBALS[\$_GET['foo']] !!}";
		$target = "<?php echo \$_SERVER['HTTP_USER_AGENT'] . \$GLOBALS[\$_GET['foo']]; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// $this
		$source = "{!! \$this->func(\$args) !!}";
		$target = "<?php echo \$this->func(\$__Context->args); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// $lang
		$source = "{!! \$lang->cmd_yes !!}";
		$target = "<?php echo \$__Context->lang->cmd_yes; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// $loop
		$source = "{!! \$loop->first !!}";
		$target = "<?php echo end(self::\$_loopvars)->first ?? ''; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Escaped dollar sign
		$source = "{!! \\\$escaped !!}";
		$target = "<?php echo \$escaped; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Escaped and unescaped variables used together in closure
		$source = "{!! (function(\\\$i) use(\$__Context) { return \\\$i * \$j; })(\$k); !!}";
		$target = "<?php echo (function(\$i) use(\$__Context) { return \$i * \$__Context->j; })(\$__Context->k);; ?>";
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testPathConversion()
	{
		// Image
		$source = '<img class="foo" src="foo.jpg" alt="foo" />';
		$target = '<img class="foo" src="' . $this->baseurl . 'tests/_data/template/foo.jpg" alt="foo" />';
		$this->assertEquals($target, $this->_parse($source));

		// <video>
		$source = '<video id="video" src="dir/foo.mp4"></video>';
		$target = '<video id="video" src="' . $this->baseurl . 'tests/_data/template/dir/foo.mp4"></video>';
		$this->assertEquals($target, $this->_parse($source));

		// <video> with poster attribute and <source> inside
		$source = '<video poster="bar.jpg"><source src="../foo.mp4" /></video>';
		$target = '<video poster="' . $this->baseurl . 'tests/_data/template/bar.jpg"><source src="' . $this->baseurl . 'tests/_data/foo.mp4" /></video>';
		$this->assertEquals($target, $this->_parse($source));

		// <audio> with path relative to the Rhymix installation directory
		$source = '<audio controls src="^/assets/foo.ogg" autoplay loop></audio>';
		$target = '<audio controls src="' . $this->baseurl . 'assets/foo.ogg" autoplay loop></audio>';
		$this->assertEquals($target, $this->_parse($source));

		// <input type="image"> with src
		$source = '<input type="image" src="foo/bar.jpg" />';
		$target = '<input type="image" src="' . $this->baseurl . 'tests/_data/template/foo/bar.jpg" />';
		$this->assertEquals($target, $this->_parse($source));

		// Script tag with local path
		$source = '<script src="assets/foo.js" async>';
		$target = '<script src="' . $this->baseurl . 'tests/_data/template/assets/foo.js" async>';
		$this->assertEquals($target, $this->_parse($source));

		// Script tag with external path
		$source = '<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.0/js/bootstrap.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
		$target = '<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.0/js/bootstrap.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
		$this->assertEquals($target, $this->_parse($source));

		// Absolute URL
		$source = '<img src="/foo/bar.jpg" />';
		$target = '<img src="/foo/bar.jpg" />';
		$this->assertEquals($target, $this->_parse($source));

		// External URL
		$source = '<img src="http://example.com/foo/bar.jpg" />';
		$target = '<img src="http://example.com/foo/bar.jpg" />';
		$this->assertEquals($target, $this->_parse($source));

		// data: URL
		$source = '<img src="data:image/png;base64,AAAAAAAAAAA=" />';
		$target = '<img src="data:image/png;base64,AAAAAAAAAAA=" />';
		$this->assertEquals($target, $this->_parse($source));

		// file: URL
		$source = '<img src="file:///C:/inetpub/foobar.jpg" />';
		$target = '<img src="file:///C:/inetpub/foobar.jpg" />';
		$this->assertEquals($target, $this->_parse($source));

		// srcset
		$source = '<img srcset="bar/foo@4x.png 4x, ../foo@2x.png 2x ,./foo.jpg" />';
		$target = '<img srcset="' . $this->baseurl . 'tests/_data/template/bar/foo@4x.png 4x, ' . $this->baseurl . 'tests/_data/foo@2x.png 2x, ' . $this->baseurl . 'tests/_data/template/foo.jpg" />';
		$this->assertEquals($target, $this->_parse($source));

		// url() conversion in style sttribute
		$source = '<div style="background-image: url(img/foo.jpg)"></div>';
		$target = '<div style="background-image: url(' . $this->baseurl . 'tests/_data/template/img/foo.jpg)"></div>';
		$this->assertEquals($target, $this->_parse($source));

		$source = '<div style="border-image: url(\'img/foo.jpg\')"></div>';
		$target = '<div style="border-image: url(\'' . $this->baseurl . 'tests/_data/template/img/foo.jpg\')"></div>';
		$this->assertEquals($target, $this->_parse($source));

		$source = '<div style="mask-image: image(url(foo/bar.svg), blue, linear-gradient(rgb(0 0 0 / 100%), transparent))"></div>';
		$target = '<div style="mask-image: image(url(' . $this->baseurl . 'tests/_data/template/foo/bar.svg), blue, linear-gradient(rgb(0 0 0 / 100%), transparent))"></div>';
		$this->assertEquals($target, $this->_parse($source));

		$source = '<div style="content: url(\'https://foo.com/bar.png\')"></div>';
		$target = '<div style="content: url(\'https://foo.com/bar.png\')"></div>';
		$this->assertEquals($target, $this->_parse($source));

		$source = '<div style="content: url(data:image/png,base64)" other-attribute="cursor: url(img/foo.jpg)"></div>';
		$target = '<div style="content: url(data:image/png,base64)" other-attribute="cursor: url(img/foo.jpg)"></div>';
		$this->assertEquals($target, $this->_parse($source));

		// url() conversion in <style> tag
		$source = '<style> .foo { background-image: url("img/foo.jpg"); } </style>';
		$target = '<style> .foo { background-image: url("' . $this->baseurl . 'tests/_data/template/img/foo.jpg"); } </style>';
		$this->assertEquals($target, $this->_parse($source));

		// No url() conversion in other tags or attributes
		$source = '<other-tag> .foo { list-style-image: url(img/foo.jpg); } </other-tag>';
		$target = '<other-tag> .foo { list-style-image: url(img/foo.jpg); } </other-tag>';
		$this->assertEquals($target, $this->_parse($source));

		$source = '<p class="url(foo.svg)" style="url(../foo.jpg)"> url(img/foo.jpg); } </p>';
		$target = '<p class="url(foo.svg)" style="url(' . $this->baseurl . 'tests/_data/foo.jpg)"> url(img/foo.jpg); } </p>';
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testBlockConditions()
	{
		// @if in comments
		$source = '<!--@if($cond)--><p>Hello World</p><!--@endif-->';
		$target = '<?php if ($__Context->cond): ?><p>Hello World</p><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// @if in its own line, with @elseif and @else
		$source = implode("\n", [
			'@if($foo)',
			'<p>Hello World</p>',
			'@elseif($bar)',
			'<p>Goodbye World</p>',
			'@else',
			'<p>So long and thx 4 all the fish</p>',
			'@endif',
		]);
		$target = implode("\n", [
			'<?php if ($__Context->foo): ?>',
			'<p>Hello World</p>',
			'<?php elseif ($__Context->bar): ?>',
			'<p>Goodbye World</p>',
			'<?php else: ?>',
			'<p>So long and thx 4 all the fish</p>',
			'<?php endif; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// nested @if and @unless with inconsistent spacing before parenthesis
		$source = implode("\n", [
			'@if($cond)',
			'@unless ($cond)',
			'<p>Hello World</p>',
			'@endunless',
			'@endif',
		]);
		$target = implode("\n", [
			'<?php if ($__Context->cond): ?>',
			'<?php if (!($__Context->cond)): ?>',
			'<p>Hello World</p>',
			'<?php endif; ?>',
			'<?php endif; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// nested @if, @unless, and @for with legacy @end
		$source = implode("\n", [
			'@if ($cond)',
			'@for ($i = 0; $i < 10; $i++)',
			'<!--@unless($cond)-->',
			'<p>Hello World</p>',
			'@end',
			'@end',
			'<!--@end-->',
		]);
		$target = implode("\n", [
			'<?php if ($__Context->cond): ?>',
			'<?php for ($__Context->i = 0; $__Context->i < 10; $__Context->i++): ?>',
			'<?php if (!($__Context->cond)): ?>',
			'<p>Hello World</p>',
			'<?php endif; ?>',
			'<?php endfor; ?>',
			'<?php endif; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// @while with legacy @end
		$source = '<!--@while(Context::getFoo()->isBar("baz"))--><p>Hello World</p><!--@end-->';
		$target = '<?php while (Context::getFoo()->isBar("baz")): ?><p>Hello World</p><?php endwhile; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// @switch with @case, @default, @continue, and @break
		$source = implode("\n", [
			'@switch ($str)',
			'@case (1)',
			'<!--@case(2)-->',
			'@continue',
			'@case(3)',
			'@break',
			'@default',
			'@if (42)',
			'<p>Hello World</p>',
			'@end',
			'@end',
		]);
		$target = implode("\n", [
			'<?php switch ($__Context->str): ?>',
			'<?php case 1: ?>',
			'<?php case 2: ?>',
			'<?php continue; ?>',
			'<?php case 3: ?>',
			'<?php break; ?>',
			'<?php default: ?>',
			'<?php if (42): ?>',
			'<p>Hello World</p>',
			'<?php endif; ?>',
			'<?php endswitch; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// @foreach
		$source = implode("\n", [
			'<!--@foreach ($list as $key => $val) -->',
			'<p>Hello World</p>',
			'<!--@endforeach -->',
		]);
		$target = implode("\n", [
			'<?php $__tmp = $__Context->list ?? []; $__loop = $this->_v2_initLoopVar("%uniq", $__tmp); foreach ($__tmp as $__Context->key => $__Context->val): ?>',
			'<p>Hello World</p>',
			'<?php $this->_v2_incrLoopVar($__loop); endforeach; $this->_v2_removeLoopVar($__loop); unset($__loop); ?>',
		]);
		$parsed = $this->_parse($source);
		$tmpvar = preg_match('/(\$__(?:tmp|loop)_)([0-9a-f]{14})/', $parsed, $m) ? $m[2] : '';
		$target = preg_replace(['/(\$__(?:tmp|loop))/', '/%uniq/'], ['$1_' . $tmpvar, $tmpvar], $target);
		$this->assertEquals($target, $parsed);

		// @forelse with @empty
		$source = implode("\n", [
			'@forelse ($list as $key => $val)',
			'<p>Hello World</p>',
			'@empty',
			'<p>Nothing Here!</p>',
			'@end',
		]);
		$target = implode("\n", [
			'<?php $__tmp = $__Context->list ?? []; if($__tmp): $__loop = $this->_v2_initLoopVar("%uniq", $__tmp); foreach ($__tmp as $__Context->key => $__Context->val): ?>',
			'<p>Hello World</p>',
			'<?php $this->_v2_incrLoopVar($__loop); endforeach; $this->_v2_removeLoopVar($__loop); unset($__loop); else: ?>',
			'<p>Nothing Here!</p>',
			'<?php endif; ?>',
		]);
		$parsed = $this->_parse($source);
		$tmpvar = preg_match('/(\$__(?:tmp|loop)_)([0-9a-f]{14})/', $parsed, $m) ? $m[2] : '';
		$target = preg_replace(['/(\$__(?:tmp|loop))/', '/%uniq/'], ['$1_' . $tmpvar, $tmpvar], $target);
		$this->assertEquals($target, $parsed);

		// @once
		$source = implode("\n", [
			'@once',
			'<p>Hello World</p>',
			'@endonce',
		]);
		$target = implode("\n", [
			'<?php if (!isset($GLOBALS[\'tplv2_once\'][\'$UNIQ\'])): ?>',
			'<p>Hello World</p>',
			'<?php $GLOBALS[\'tplv2_once\'][\'$UNIQ\'] = true; endif; ?>',
		]);
		$parsed = $this->_parse($source);
		$tmpvar = preg_match('/\'([0-9a-f]{14})\'/', $parsed, $m) ? $m[1] : '';
		$target = strtr($target, ['$UNIQ' => $tmpvar]);
		$this->assertEquals($target, $parsed);

		// @error
		$source = implode("\n", [
			"@error('email', 'login')",
			'{{ $message }}',
			'@enderror',
		]);
		$target = implode("\n", [
			"<?php if (\$this->_v2_errorExists('email', 'login')): ?>",
			"<?php echo \$this->config->context === 'HTML' ? htmlspecialchars(\$__Context->message ?? '', \ENT_QUOTES, 'UTF-8', false) : \$this->_v2_escape(\$__Context->message ?? ''); ?>",
			'<?php endif; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// @isset and @unset
		$source = '<!--@isset($foo)--><!--@unset($bar)--><p></p><!--@end--><!--@endisset-->';
		$target = '<?php if (isset($__Context->foo)): ?><?php if (!isset($__Context->bar)): ?><p></p><?php endif; ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// @empty
		$source = '<!--@empty ($foo) --><p></p><!--@endempty-->';
		$target = '<?php if (empty($__Context->foo)): ?><p></p><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// @admin
		$source = implode("\n", [
			'@admin',
			'<p>Welcome!</p>',
			'@endadmin',
		]);
		$target = implode("\n", [
			'<?php if ($this->user->isAdmin()): ?>',
			'<p>Welcome!</p>',
			'<?php endif; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// @auth and @guest
		$source = implode("\n", [
			'@auth',
			'@auth(\'manager\')',
			'<p>Welcome back!</p>',
			'@endauth',
			'@end',
			'@guest',
			'<p>Please join!</p>',
			'@endguest',
		]);
		$target = implode("\n", [
			'<?php if ($this->_v2_checkAuth()): ?>',
			'<?php if ($this->_v2_checkAuth(\'manager\')): ?>',
			'<p>Welcome back!</p>',
			'<?php endif; ?>',
			'<?php endif; ?>',
			'<?php if (!$this->user->isMember()): ?>',
			'<p>Please join!</p>',
			'<?php endif; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// @desktop and @mobile
		$source = implode("\n", [
			'@desktop',
			'<p>4K or GTFO!</p>',
			'@end',
			'@mobile',
			'<p>USB C is the way to go~</p>',
			'@endmobile',
		]);
		$target = implode("\n", [
			'<?php if (!$this->_v2_isMobile()): ?>',
			'<p>4K or GTFO!</p>',
			'<?php endif; ?>',
			'<?php if ($this->_v2_isMobile()): ?>',
			'<p>USB C is the way to go~</p>',
			'<?php endif; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// @can and @cannot, @canany
		$source = implode("\n", [
			"@can('foo')",
			'Hello World',
			'@endcan',
			"<!--@cannot('bar') -->",
			"@canany(['foo', 'bar'])",
			'Goodbye World',
			'<!--@endcanany-->',
			'<!--@end-->'
		]);
		$target = implode("\n", [
			'<?php if ($this->_v2_checkCapability(1, \'foo\')): ?>',
			'Hello World',
			'<?php endif; ?>',
			'<?php if ($this->_v2_checkCapability(2, \'bar\')): ?>',
			'<?php if ($this->_v2_checkCapability(3, [\'foo\', \'bar\'])): ?>',
			'Goodbye World',
			'<?php endif; ?>',
			'<?php endif; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// @env
		$source = "@env('foo') FOO @endenv";
		$target = '<?php if (!empty($_ENV[\'foo\'])): ?> FOO <?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testInlineConditions()
	{
		// XE-style pipe with 'if' attribute
		$source = '<input type="text" readonly="readonly"|if="$oDocument->isSecret()" />';
		$target = '<input type="text"<?php if ($__Context->oDocument->isSecret()): ?> readonly="readonly"<?php endif; ?> />';
		$this->assertEquals($target, $this->_parse($source));

		// With boolean (valueless) attribute
		$source = '<input type="text" disabled|if="$oDocument->isSecret()" />';
		$target = '<input type="text"<?php if ($__Context->oDocument->isSecret()): ?> disabled="disabled"<?php endif; ?> />';
		$this->assertEquals($target, $this->_parse($source));

		// Support 'cond' attribute for backward compatibility
		$source = '<option value="1" selected="selected"|cond="$foo">ONE</option>';
		$target = '<option value="1"<?php if ($__Context->foo): ?> selected="selected"<?php endif; ?>>ONE</option>';
		$this->assertEquals($target, $this->_parse($source));

		// Support 'when' and 'unless' attributes
		$source = '<option value="1" selected|when="$foo" disabled|unless="$bar">ONE</option>';
		$target = '<option value="1"<?php if ($__Context->foo): ?> selected="selected"<?php endif; ?><?php if (!($__Context->bar)): ?> disabled="disabled"<?php endif; ?>>ONE</option>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @checked
		$source = '<input type="checkbox" @checked($oDocument->isAccessible() && in_array($this->user->member_srl, [1, 2, 3])) />';
		$target = '<input type="checkbox"<?php if ($__Context->oDocument->isAccessible() && in_array($this->user->member_srl, [1, 2, 3])): ?> checked="checked"<?php endif; ?> />';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @selected
		$source = '<option value="2" @selected($foo)>TWO</option>';
		$target = '<option value="2"<?php if ($__Context->foo): ?> selected="selected"<?php endif; ?>>TWO</option>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @disabled
		$source = '<textarea class="foobar" @disabled(trim($foo) === $bar("baz"))></textarea>';
		$target = '<textarea class="foobar"<?php if (trim($__Context->foo) === $__Context->bar("baz")): ?> disabled="disabled"<?php endif; ?>></textarea>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @readonly and @required
		$source = '<input type="text" @readonly(!!false) @required($member_info->require_title) />';
		$target = '<input type="text"<?php if (!!false): ?> readonly="readonly"<?php endif; ?><?php if ($__Context->member_info->require_title): ?> required="required"<?php endif; ?> />';
		$this->assertEquals($target, $this->_parse($source));

		// @class
		$source = "<span @class(['a-1', 'font-normal' => \$foo, 'text-blue' => false, 'bg-white' => true])></span>";
		$this->assertStringContainsString("\$this->_v2_buildAttribute(", $this->_parse($source));
		$this->assertStringContainsString("\$__Context->foo", $this->_parse($source));

		// @style
		$source = "<span @style(['border-radius: 0.25rem', 'margin: 1rem' => Context::get('bar')])></span>";
		$this->assertStringContainsString("\$this->_v2_buildAttribute(", $this->_parse($source));
		$this->assertStringContainsString("Context::get('bar')]);", $this->_parse($source));
	}

	public function testMiscDirectives()
	{
		// Insert CSRF token
		$source = '<form>@csrf</form>';
		$target = '<form><input type="hidden" name="_rx_csrf_token" value="<?php echo \Rhymix\Framework\Session::getGenericToken(); ?>" /></form>';
		$this->assertEquals($target, $this->_parse($source));

		// JSON with variable
		$source = '@json($var)';
		$target = implode('', [
			'<?php echo $this->config->context === \'JS\' ? ',
			'json_encode($__Context->var, self::$_json_options2) : ',
			'htmlspecialchars(json_encode($__Context->var, self::$_json_options), \ENT_QUOTES, \'UTF-8\', false); ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// JSON with literal array
		$source = '@json(["foo" => 1, "bar" => 2])';
		$target = implode('', [
			'<?php echo $this->config->context === \'JS\' ? ',
			'json_encode(["foo" => 1, "bar" => 2], self::$_json_options2) : ',
			'htmlspecialchars(json_encode(["foo" => 1, "bar" => 2], self::$_json_options), \ENT_QUOTES, \'UTF-8\', false); ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// Lang code with variable as name
		$source = '@lang($var->name)';
		$target = '<?php echo $this->config->context === \'HTML\' ? $this->_v2_lang($__Context->var->name) : $this->_v2_escape($this->_v2_lang($__Context->var->name)); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Lang code with literal name and variable
		$source = "@lang('board.cmd_list_items', \$var)";
		$target = "<?php echo \$this->config->context === 'HTML' ? \$this->_v2_lang('board.cmd_list_items', \$__Context->var) : \$this->_v2_escape(\$this->_v2_lang('board.cmd_list_items', \$__Context->var)); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Lang code with class alias
		$source = "@use('Rhymix\Framework\Lang', 'Lang')\n" . '<p>@lang(Lang::getLang())</p>';
		$target = "\n" . '<p><?php echo $this->config->context === \'HTML\' ? $this->_v2_lang(Rhymix\Framework\Lang::getLang()) : $this->_v2_escape($this->_v2_lang(Rhymix\Framework\Lang::getLang())); ?></p>';
		$this->assertEquals($target, $this->_parse($source));

		// Dump one variable
		$source = '@dump($foo)';
		$target = '<?php ob_start(); var_dump($__Context->foo); $__dump = ob_get_clean(); echo rtrim($__dump); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Dump more than one variable, some literal
		$source = '@dump($foo, Context::get("var"), (object)["foo" => "bar"])';
		$target = '<?php ob_start(); var_dump($__Context->foo, Context::get("var"), (object)["foo" => "bar"]); $__dump = ob_get_clean(); echo rtrim($__dump); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// URL
		$source = "@url(['mid' => 'foo', 'act' => 'dispBoardWrite'])";
		$target = "<?php echo \$this->config->context === 'HTML' ? getUrl(['mid' => 'foo', 'act' => 'dispBoardWrite']) : \$this->_v2_escape(getNotEncodedUrl(['mid' => 'foo', 'act' => 'dispBoardWrite'])); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// URL old-style with variables
		$source = "@url('', 'mid', \$mid, 'act', \$act])";
		$target = "<?php echo \$this->config->context === 'HTML' ? getUrl('', 'mid', \$__Context->mid, 'act', \$__Context->act]) : \$this->_v2_escape(getNotEncodedUrl('', 'mid', \$__Context->mid, 'act', \$__Context->act])); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Widget
		$source = "@widget('login_info', ['skin' => 'default'])";
		$target = "<?php echo \WidgetController::getInstance()->execute('login_info', ['skin' => 'default']); ?>";
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testComments()
	{
		// XE-style comment
		$source = '<div><!--// This is a comment --></div>';
		$target = '<div></div>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style comment
		$source = '<div>{{-- This is a comment --}}</div>';
		$target = '<div></div>';
		$this->assertEquals($target, $this->_parse($source));

		// Not deleted
		$source = '<div><!-- This is a comment --></div>';
		$target = '<div><!-- This is a comment --></div>';
		$this->assertEquals($target, $this->_parse($source));

		// Not deleted
		$source = '<div><!--/* This is a comment */--></div>';
		$target = '<div><!--/* This is a comment */--></div>';
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testVerbatim()
	{
		// Don't convert this expression, but remove the @
		$source = '@{{ $foobar }}';
		$target = '{{ $foobar }}';
		$this->assertEquals($target, $this->_parse($source));

		// Don't convert this expression, but remove the extra @
		$source = implode("\n", [
			'@@if(true)',
			'@@endif',
		]);
		$target = implode("\n", [
			'@if(true)',
			'@endif',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// @verbatim block
		$source = implode("\n", [
			'@verbatim',
			'@if (true)',
			'<p>{{ $foobar }}</p>',
			'<p>{$foobar}</p>',
			'@endif',
			'@endverbatim',
		]);
		$target = implode("\n", [
			'',
			'@if (true)',
			'<p>{{ $foobar }}</p>',
			'<p>{$foobar}</p>',
			'@endif',
			'',
		]);
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testRawPhpCode()
	{
		// Regular PHP tags
		$source = '<?php $foo = 42; ?>';
		$target = '<?php $__Context->foo = 42; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Short PHP tags
		$source = '<? foo($bar); ?>';
		$target = '<?php foo($__Context->bar); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Short PHP echo tags
		$source = '<?=$foo?>';
		$target = '<?php echo $__Context->foo ?>';
		$this->assertEquals($target, $this->_parse($source));

		// XE-style {@ ... } notation
		$source = '{@ $foo = 42; }';
		$target = '<?php $__Context->foo = 42; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @php and @endphp directives
		$source = '@php $foo = 42; @endphp';
		$target = '<?php $__Context->foo = 42; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Turn off context-aware escape within raw PHP blocks
		$source = "@php Context::addHtmlFooter('<script></script>'); @endphp";
		$target = "<?php Context::addHtmlFooter('<script></script>'); ?>";
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testDeprecationMessages()
	{
		// <!--#include()-->
		$source = '<!--#include("foo.html")-->';
		$target = '<?php trigger_error("#include is not supported in template v2", \E_USER_WARNING); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// <!--%import()-->
		$source = '<!--%import("../foo/bar.js")-->';
		$target = '<?php trigger_error("%import is not supported in template v2", \E_USER_WARNING); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// <block> element
		$source = '<block class="foobar">';
		$target = '<block<?php trigger_error("block element is not supported in template v2", \E_USER_WARNING); ?> class="foobar">';
		$this->assertEquals($target, $this->_parse($source));

		// cond
		$source = '<div cond="$foo->isBar()"></div>';
		$target = '<div <?php trigger_error("cond attribute is not supported in template v2", \E_USER_WARNING); ?>></div>';;
		$this->assertEquals($target, $this->_parse($source));

		// cond is OK in includes
		$source = '<include src="foo.html" cond="$bar" />';
		$target = implode(' ', [
			'<?php if(!empty($bar)): ?><?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "foo.html", "html");',
			'$__tpl->setParent($this); if ($this->vars): $__tpl->setVars($this->vars); endif;',
			'echo $__tpl->compile(); ?><?php endif; ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// loop
		$source = '<tr loop="$foo => $bar"></tr>';
		$target = '<tr <?php trigger_error("loop attribute is not supported in template v2", \E_USER_WARNING); ?>></tr>';;
		$this->assertEquals($target, $this->_parse($source));

		// loop is OK in multimedia elements
		$source = '<video autoplay loop="loop"></video>';
		$target = '<video autoplay loop="loop"></video>';
		$this->assertEquals($target, $this->_parse($source));

		// Comprehensive example
		$source = '<block cond="$foo" loop="$arr => $k, $v"></block>';
		$target = implode('', [
			'<block<?php trigger_error("block element is not supported in template v2", \E_USER_WARNING); ?> ',
			'<?php trigger_error("cond attribute is not supported in template v2", \E_USER_WARNING); ?> ',
			'<?php trigger_error("loop attribute is not supported in template v2", \E_USER_WARNING); ?>></block>',
		]);
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testCompileGeneral()
	{
		// General example
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'v2example.html');
		$tmpl->disableCache();

		// Get compiled code
		$compiled_output = $tmpl->compileDirect('./tests/_data/template', 'v2example.html');
		$tmpvar = preg_match('/\$__tmp_([0-9a-f]{14})/', $compiled_output, $m) ? $m[1] : '';
		$compiled_output = strtr($compiled_output, [$tmpvar => 'RANDOM_LOOP_ID']);
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2example.compiled.html', $compiled_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2example.compiled.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($compiled_output)
		);

		// Get final output
		$executed_output = $tmpl->compile();
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2example.executed.html', $executed_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2example.executed.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($executed_output)
		);

		// Get fragment from output
		$fragment_output = $tmpl->getFragment('rhymix');
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2example.fragment.html', $fragment_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2example.fragment.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($fragment_output)
		);

		// Check that resource is loaded
		$list = \Context::getJsFile('body');
		$this->assertStringContainsString('/common/js/plugins/ckeditor/', array_first($list)['file']);
		$list = \Context::getCssFile();
		$this->assertStringContainsString('/tests/_data/template/css/style.scss', array_first($list)['file']);
	}

	public function testCompileContextualEscape()
	{
		// Contextual escape
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'v2contextual.html');
		$tmpl->disableCache();
		$tmpl->setVars([
			'var' => 'Hello <"world"> (\'string\') variable.jpg'
		]);

		$executed_output = $tmpl->compile();
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2contextual.executed.html', $executed_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2contextual.executed.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($executed_output)
		);
	}

	public function testCompileLang()
	{
		// Lang
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'v2lang.html');
		$tmpl->source_type = 'modules';
		$tmpl->source_name = 'document';
		$tmpl->disableCache();

		$executed_output = $tmpl->compile();
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2lang.executed1.html', $executed_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2lang.executed1.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($executed_output)
		);

		$tmpl->source_type = 'modules';
		$tmpl->source_name = 'member';
		$tmpl->disableCache();

		$executed_output = $tmpl->compile();
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2lang.executed2.html', $executed_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2lang.executed2.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($executed_output)
		);
	}

	public function testCompileLoopVariable()
	{
		// Loop variable
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'v2loops.html');
		$tmpl->disableCache();

		$executed_output = $tmpl->compile();
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2loops.executed.html', $executed_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2loops.executed.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($executed_output)
		);
	}

	public function testCompilePushStack()
	{
		// Push stack
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'v2pushstack.html');
		$tmpl->disableCache();

		$executed_output = $tmpl->compile();
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2pushstack.executed.html', $executed_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2pushstack.executed.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($executed_output)
		);
		$this->assertEquals(4, count($tmpl->getStack('cms')));
	}

	public function testCompileValidation()
	{
		// Validation error check
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'v2validation.html');
		$tmpl->disableCache();

		$executed_output = $tmpl->compile();
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2validation.executed.html', $executed_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2validation.executed.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($executed_output)
		);
	}

	public function testCompileVariableScope()
	{
		// Variable scope check
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'v2varscope.html');
		$tmpl->disableCache();

		$executed_output = $tmpl->compile();
		//Rhymix\Framework\Storage::write(\RX_BASEDIR . 'tests/_data/template/v2varscope.executed.html', $executed_output);
		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2varscope.executed.html');
		$this->assertEquals(
			$this->_normalizeWhitespace($expected),
			$this->_normalizeWhitespace($executed_output)
		);

		$list = \Context::getJsFile();
		$this->assertStringContainsString('/tests/_data/template/js/test.js', array_last($list)['file']);
	}

	/**
	 * Utility function to compile an arbitrary string and return the results.
	 *
	 * @param string $source
	 * @param bool $force_v2 Disable version detection
	 * @param bool $remove_context_switches Remove context switches that make code difficult to read
	 * @return string
	 */
	protected function _parse(string $source, bool $force_v2 = true, bool $remove_context_switches = true): string
	{
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'empty.html');
		if ($force_v2)
		{
			$tmpl->config->version = 2;
		}
		$result = $tmpl->parse($source);
		if (str_starts_with($result, $this->prefix))
		{
			$result = substr($result, strlen($this->prefix));
		}

		// Remove context switches.
		if ($remove_context_switches)
		{
			$result = preg_replace('#<\?php \$this->config->context = \'[A-Z]+\'; \?>#', '', $result);
		}

		return $result;
	}

	/**
	 * Utility function to remove empty lines and leading/trailing whitespace.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _normalizeWhitespace(string $content): string
	{
		$content = preg_replace('/<!--#Template(Start|End):.+?-->\n/', '', $content);
		$content = preg_replace('!(action|src)="' . preg_quote($this->baseurl, '!') . '!', '$1="/rhymix/', $content);

		$result = [];
		foreach (explode("\n", $content) as $line)
		{
			$line = trim($line);
			if ($line !== '')
			{
				$result[] = $line;
			}
		}
		return implode("\n", $result);
	}
}
