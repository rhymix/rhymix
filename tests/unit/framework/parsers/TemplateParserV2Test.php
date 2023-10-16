<?php

class TemplateParserV2Test extends \Codeception\Test\Unit
{
	private $prefix = '<?php if (!defined("RX_VERSION")) exit(); ?><?php $this->config->version = 2; ?>';
	private $baseurl;

	public function _before()
	{
		$this->baseurl = '/' . basename(dirname(dirname(dirname(dirname(__DIR__))))) . '/';
	}

	public function testVersion()
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
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "foobar", "html"); echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Legacy 'target' attribute
		$source = '<include target="subdir/foobar" />';
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "subdir/foobar", "html"); echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Conditional include
		$source = '<include src="../up/foobar" if="$cond" />';
		$target = '<?php if(!empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "../up/foobar", "html"); echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Conditional include with legacy 'cond' attribute
		$source = '<include target="legacy/cond.statement.html" cond="$cond" />';
		$target = '<?php if(!empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "legacy/cond.statement.html", "html"); echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Path relative to Rhymix installation directory
		$source = '<include src="^/modules/foobar/views/baz" when="$cond" />';
		$target = '<?php if(!empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template("modules/foobar/views", "baz", "html"); echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Unless
		$source = '<include src="^/modules/foobar/views/baz" unless="$cond" />';
		$target = '<?php if(empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template("modules/foobar/views", "baz", "html"); echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// With variables
		$source = '<include src="foobar" vars="$vars" />';
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "foobar", "html"); $__tpl->setVars($__Context->vars); echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// With array literal passed as variables
		$source = '<include src="foobar" vars="[\'foo\' => \'bar\']" />';
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "foobar", "html"); $__tpl->setVars([\'foo\' => \'bar\']); echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @include
		$source = "@include ('foobar')";
		$target = implode(' ', [
			'<?php (function($__dir, $__path, $__vars = null) {',
			'$__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html");',
			'if ($__vars) $__tpl->setVars($__vars);',
			'echo $__tpl->compile(); })($this->relative_dirname, \'foobar\'); ?>'
		]);
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @include with variable in filename
		$source = "@include(\$var)";
		$target = implode(' ', [
			'<?php (function($__dir, $__path, $__vars = null) {',
			'$__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html");',
			'if ($__vars) $__tpl->setVars($__vars);',
			'echo $__tpl->compile(); })($this->relative_dirname, $__Context->var); ?>'
		]);
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @include with path relative to Rhymix installation directory
		$source = '@include ("^/common/js/plugins/foobar/baz.blade.php")';
		$target = implode(' ', [
			'<?php (function($__dir, $__path, $__vars = null) {',
			'$__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html");',
			'if ($__vars) $__tpl->setVars($__vars);',
			'echo $__tpl->compile(); })("common/js/plugins/foobar", "baz.blade.php"); ?>'
		]);
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @includeIf with variables
		$source = "@includeIf('dir/foobar', \$vars)";
		$target = implode(' ', [
			'<?php (function($__dir, $__path, $__vars = null) {',
			'$__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html");',
			'if (!$__tpl->exists()) return;',
			'if ($__vars) $__tpl->setVars($__vars);',
			'echo $__tpl->compile(); })($this->relative_dirname, \'dir/foobar\', $__Context->vars); ?>'
		]);
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @includeWhen
		$source = "@includeWhen(\$foo->isBar(), '../../foobar.html', \$vars)";
		$target = implode(' ', [
			'<?php (function($__type, $__dir, $__cond, $__path, $__vars = null) {',
			'if ($__type === "includeWhen" && !$__cond) return;',
			'if ($__type === "includeUnless" && $__cond) return;',
			'$__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html");',
			'if ($__vars) $__tpl->setVars($__vars);',
			'echo $__tpl->compile(); })("includeWhen", $this->relative_dirname, $__Context->foo->isBar(), \'../../foobar.html\', $__Context->vars); ?>'
		]);
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @includeUnless with path relative to Rhymix installation directory
		$source = "@includeUnless (false, '^common/tpl/foobar.html', \$vars)";
		$target = implode(' ', [
			'<?php (function($__type, $__dir, $__cond, $__path, $__vars = null) {',
			'if ($__type === "includeWhen" && !$__cond) return;',
			'if ($__type === "includeUnless" && $__cond) return;',
			'$__tpl = new \Rhymix\Framework\Template($__dir, $__path, "html");',
			'if ($__vars) $__tpl->setVars($__vars);',
			'echo $__tpl->compile(); })("includeUnless", "common/tpl", false, \'foobar.html\', $__Context->vars); ?>'
		]);
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testAssetLoading()
	{
		// CSS, SCSS, LESS with media and variables
		$source = '<load src="assets/hello.scss" media="print" vars="$foo" />';
		$target = "<?php \Context::loadFile(['./tests/_data/template/assets/hello.scss', 'print', '', '', \$__Context->foo]); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '<load target="../hello.css" media="screen and (max-width: 800px)" />';
		$target = "<?php \Context::loadFile(['./tests/_data/hello.css', 'screen and (max-width: 800px)', '', '', []]); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// JS with type and index
		$source = '<load src="assets/hello.js" type="head" />';
		$target = "<?php \Context::loadFile(['./tests/_data/template/assets/hello.js', 'head', '', '']); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '<load target="assets/../otherdir/hello.js" type="body" index="20" />';
		$target = "<?php \Context::loadFile(['./tests/_data/template/otherdir/hello.js', 'body', '', 20]); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// External script
		$source = '<load src="//cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js" />';
		$target = "<?php \Context::loadFile(['//cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js', '', 'tests', '']); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// External webfont
		$source = '<load src="https://fonts.googleapis.com/css2?family=Roboto&display=swap" />';
		$target = "<?php \Context::loadFile(['https://fonts.googleapis.com/css2?family=Roboto&display=swap', '', 'tests', '', []]); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Path relative to Rhymix installation directory
		$source = '<load src="^/common/js/foobar.js" />';
		$target = "<?php \Context::loadFile(['./common/js/foobar.js', '', '', '']); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// JS plugin
		$source = '<load src="^/common/js/plugins/ckeditor/" />';
		$target = "<?php \Context::loadJavascriptPlugin('ckeditor'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Lang file
		$source = '<load src="^/modules/member/lang" />';
		$target = "<?php \Context::loadLang('./modules/member/lang'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '<load src="^/modules/legacy_module/lang/lang.xml" />';
		$target = "<?php \Context::loadLang('./modules/legacy_module/lang'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style SCSS with media and variables
		$source = "@load('assets/hello.scss', 'print', \$vars)";
		$target = "<?php \Context::loadFile(['./tests/_data/template/assets/hello.scss', 'print', '', '', \$__Context->vars]); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = "@load ('../hello.css', 'screen')";
		$target = "<?php \Context::loadFile(['./tests/_data/hello.css', 'screen', '', '', []]); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style JS with type and index
		$source = "@load('assets/hello.js', 'body', 10)";
		$target = "<?php \Context::loadFile(['./tests/_data/template/assets/hello.js', 'body', '', 10]); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = "@load ('assets/hello.js', 'head')";
		$target = "<?php \Context::loadFile(['./tests/_data/template/assets/hello.js', 'head', '', '']); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = "@load ('assets/hello.js')";
		$target = "<?php \Context::loadFile(['./tests/_data/template/assets/hello.js', '', '', '']); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style external script
		$source = "@load ('//cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js')";
		$target = "<?php \Context::loadFile(['//cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js', '', 'tests', '']); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style external webfont
		$source = "@load('https://fonts.googleapis.com/css2?family=Roboto&display=swap')";
		$target = "<?php \Context::loadFile(['https://fonts.googleapis.com/css2?family=Roboto&display=swap', '', 'tests', '', []]); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style path relative to Rhymix installation directory
		$source = '@load ("^/common/js/foobar.js")';
		$target = "<?php \Context::loadFile(['./common/js/foobar.js', '', '', '']); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style JS plugin
		$source = "@load('^/common/js/plugins/ckeditor/')";
		$target = "<?php \Context::loadJavascriptPlugin('ckeditor'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style lang file
		$source = "@load('^/modules/member/lang')";
		$target = "<?php \Context::loadLang('./modules/member/lang'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		$source = '@load("^/modules/legacy_module/lang/lang.xml")';
		$target = "<?php \Context::loadLang('./modules/legacy_module/lang'); ?>";
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testEchoStatements()
	{
		// Basic usage of XE-style single braces
		$source = '{$var}';
		$target = "<?php echo htmlspecialchars(\$__Context->var ?? '', \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Single braces with space at beginning will not be parsed
		$source = '{ $var}';
		$target = '{ $var}';
		$this->assertEquals($target, $this->_parse($source));

		// Single braces with space at end are OK
		$source = '{$var  }';
		$target = "<?php echo htmlspecialchars(\$__Context->var ?? '', \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Correct handling of object property and array access
		$source = '{Context::getRequestVars()->$foo[$bar]}';
		$target = "<?php echo htmlspecialchars(Context::getRequestVars()->{\$__Context->foo}[\$__Context->bar], \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Basic usage of Blade-style double braces
		$source = '{{ $var }}';
		$target = "<?php echo htmlspecialchars(\$__Context->var ?? '', \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Double braces without spaces are OK
		$source = '{{$var}}';
		$target = "<?php echo htmlspecialchars(\$__Context->var ?? '', \ENT_QUOTES, 'UTF-8', false); ?>";
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
		$source = '{{ implode("|", array_map(function(\$i) { return \$i + 1; }, $list) }}';
		$target = "<?php echo htmlspecialchars(implode(\"|\", array_map(function(\$i) { return \$i + 1; }, \$__Context->list), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Multiline echo statement
		$source = '{{ $foo ?' . "\n" . '  date($foo) :' . "\n" . '  toBool($bar) }}';
		$target = "<?php echo htmlspecialchars(\$__Context->foo ?\n  date(\$__Context->foo) :\n  toBool(\$__Context->bar), \ENT_QUOTES, 'UTF-8', false); ?>";
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

		// Autolang
		$source = '{{ $foo|autolang }}';
		$target = "<?php echo (preg_match('/^$(?:user_)?lang->\w+$/', \$__Context->foo ?? '') ? (\$__Context->foo ?? '') : htmlspecialchars(\$__Context->foo ?? '', \ENT_QUOTES, 'UTF-8', false)); ?>";
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
			"(json_encode(\$__Context->foo ?? '', \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES)) : ",
			"htmlspecialchars(json_encode(\$__Context->foo ?? '', \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES), \ENT_QUOTES, 'UTF-8', false); ?>",
		]);
		$this->assertEquals($target, $this->_parse($source));

		// strip_tags
		$source = '{{ $foo|strip }}';
		$target = "<?php echo htmlspecialchars(strip_tags(\$__Context->foo ?? ''), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// strip_tags (alternate name)
		$source = '{{ $foo|upper|strip_tags }}';
		$target = "<?php echo htmlspecialchars(strip_tags(strtoupper(\$__Context->foo ?? '')), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Trim
		$source = '{{ $foo|trim|noescape }}';
		$target = "<?php echo trim(\$__Context->foo ?? ''); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// URL encode
		$source = '{{ $foo|trim|urlencode }}';
		$target = "<?php echo htmlspecialchars(rawurlencode(trim(\$__Context->foo ?? '')), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Lowercase
		$source = '{{ $foo|trim|lower }}';
		$target = "<?php echo htmlspecialchars(strtolower(trim(\$__Context->foo ?? '')), \ENT_QUOTES, 'UTF-8', false); ?>";
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
		$target = "<?php echo htmlspecialchars(implode(', ', \$__Context->foo ?? ''), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Array join (custom joiner)
		$source = '{{ $foo|join:"!@!" }}';
		$target = "<?php echo htmlspecialchars(implode(\"!@!\", \$__Context->foo ?? ''), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Date conversion (default format)
		$source = '{{ $item->regdate | date }}';
		$target = "<?php echo htmlspecialchars(getDisplayDateTime(ztime(\$__Context->item->regdate ?? ''), 'Y-m-d H:i:s'), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Date conversion (custom format)
		$source = "{{ \$item->regdate | date:'n/j H:i' }}";
		$target = "<?php echo htmlspecialchars(getDisplayDateTime(ztime(\$__Context->item->regdate ?? ''), 'n/j H:i'), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Date conversion (custom format in variable)
		$source = "{{ \$item->regdate | date:\$format }}";
		$target = "<?php echo htmlspecialchars(getDisplayDateTime(ztime(\$__Context->item->regdate ?? ''), \$__Context->format), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number format
		$source = '{{ $num | format }}';
		$target = "<?php echo htmlspecialchars(number_format(\$__Context->num ?? ''), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number format (alternate name)
		$source = '{{ $num | number_format }}';
		$target = "<?php echo htmlspecialchars(number_format(\$__Context->num ?? ''), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number format (custom format)
		$source = '{{ $num | number_format:6 }}';
		$target = "<?php echo htmlspecialchars(number_format(\$__Context->num ?? '', '6'), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number format (custom format in variable)
		$source = '{{ $num | number_format:$digits }}';
		$target = "<?php echo htmlspecialchars(number_format(\$__Context->num ?? '', \$__Context->digits), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number shorten
		$source = '{{ $num | shorten }}';
		$target = "<?php echo htmlspecialchars(number_shorten(\$__Context->num ?? ''), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number shorten (alternate name)
		$source = '{{ $num | number_shorten }}';
		$target = "<?php echo htmlspecialchars(number_shorten(\$__Context->num ?? ''), \ENT_QUOTES, 'UTF-8', false); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Number shorten (custom format)
		$source = '{{ $num | number_shorten:1 }}';
		$target = "<?php echo htmlspecialchars(number_shorten(\$__Context->num ?? '', '1'), \ENT_QUOTES, 'UTF-8', false); ?>";
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
		$target = "<?php echo \$__Context->lang->cmd_yes ?? ''; ?>";
		$this->assertEquals($target, $this->_parse($source));

		// $loop
		$source = "{!! \$loop->first !!}";
		$target = "<?php echo \$loop->first ?? ''; ?>";
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
		$target = '<script src="' . $this->baseurl . 'tests/_data/template/assets/foo.js" async<?php $this->config->context = "JS"; ?>>';
		$this->assertEquals($target, $this->_parse($source));

		// Script tag with external path
		$source = '<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.0/js/bootstrap.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
		$target = '<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.0/js/bootstrap.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"<?php $this->config->context = "JS"; ?>><?php $this->config->context = "HTML"; ?></script>';
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
			'<?php $__tmp = $__Context->list ?? []; foreach ($__tmp as $__Context->key => $__Context->val): ?>',
			'<p>Hello World</p>',
			'<?php endforeach; ?>',
		]);
		$parsed = $this->_parse($source);
		$tmpvar = preg_match('/(\$__tmp_[0-9a-f]{14})/', $parsed, $m) ? $m[1] : '';
		$target = strtr($target, ['$__tmp' => $tmpvar]);
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
			'<?php $__tmp = $__Context->list ?? []; if($__tmp): foreach ($__tmp as $__Context->key => $__Context->val): ?>',
			'<p>Hello World</p>',
			'<?php endforeach; else: ?>',
			'<p>Nothing Here!</p>',
			'<?php endif; ?>',
		]);
		$parsed = $this->_parse($source);
		$tmpvar = preg_match('/(\$__tmp_[0-9a-f]{14})/', $parsed, $m) ? $m[1] : '';
		$target = strtr($target, ['$__tmp' => $tmpvar]);
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

		// @auth, @member and @guest
		$source = implode("\n", [
			'@auth',
			'@member',
			'<p>Welcome back!</p>',
			'@endmember',
			'@end',
			'@guest',
			'<p>Please join!</p>',
			'@endguest',
		]);
		$target = implode("\n", [
			'<?php if ($this->user->isMember()): ?>',
			'<?php if ($this->user->isMember()): ?>',
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
			'<?php if (!$__Context->m): ?>',
			'<p>4K or GTFO!</p>',
			'<?php endif; ?>',
			'<?php if ($__Context->m): ?>',
			'<p>USB C is the way to go~</p>',
			'<?php endif; ?>',
		]);
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
			'json_encode($__Context->var, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) : ',
			'htmlspecialchars(json_encode($__Context->var, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES), \ENT_QUOTES, \'UTF-8\', false); ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// JSON with literal array
		$source = '@json(["foo" => 1, "bar" => 2])';
		$target = implode('', [
			'<?php echo $this->config->context === \'JS\' ? ',
			'json_encode(["foo" => 1, "bar" => 2], \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) : ',
			'htmlspecialchars(json_encode(["foo" => 1, "bar" => 2], \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES), \ENT_QUOTES, \'UTF-8\', false); ?>',
		]);
		$this->assertEquals($target, $this->_parse($source));

		// Lang code with variable
		$source = '@lang($var->name)';
		$target = '<?php echo $this->config->context === \'JS\' ? escape_js(lang($__Context->var->name)) : lang($__Context->var->name); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Lang code with literal name
		$source = "@lang('board.cmd_list_items')";
		$target = "<?php echo \$this->config->context === 'JS' ? escape_js(lang('board.cmd_list_items')) : lang('board.cmd_list_items'); ?>";
		$this->assertEquals($target, $this->_parse($source));

		// Lang code with class alias
		$source = "@use('Rhymix\Framework\Lang', 'Lang')\n" . '<p>@lang(Lang::getLang())</p>';
		$target = "\n" . '<p><?php echo $this->config->context === \'JS\' ? escape_js(lang(Rhymix\Framework\Lang::getLang())) : lang(Rhymix\Framework\Lang::getLang()); ?></p>';
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
			'@endif',
			'@endverbatim',
		]);
		$target = implode("\n", [
			'',
			'@if (true)',
			'<p>{{ $foobar }}</p>',
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

		// XE-style {@ ... } notation
		$source = '{@ $foo = 42; }';
		$target = '<?php $__Context->foo = 42; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Blade-style @php and @endphp directives
		$source = '@php $foo = 42; @endphp';
		$target = '<?php $__Context->foo = 42; ?>';
		$this->assertEquals($target, $this->_parse($source));
	}

	public function testCompile()
	{
		Context::init();
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'v2example.html');

		$compiled_output = $tmpl->compileDirect('./tests/_data/template', 'v2example.html');
		$tmpvar = preg_match('/(\$__tmp_[0-9a-f]{14})/', $compiled_output, $m) ? $m[1] : '';
		//file_put_contents(\RX_BASEDIR . 'tests/_data/template/v2result1.php', $compiled_output);

		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2result1.php');
		$expected = preg_replace('/(\$__tmp_[0-9a-f]{14})/', $tmpvar, $expected);
		$this->assertEquals($expected, $compiled_output);

		$executed_output = $tmpl->compile();
		$executed_output = preg_replace('/<!--#Template(Start|End):.+?-->\n/', '', $executed_output);
		$tmpvar = preg_match('/(\$__tmp_[0-9a-f]{14})/', $executed_output, $m) ? $m[1] : '';
		//file_put_contents(\RX_BASEDIR . 'tests/_data/template/v2result2.php', $executed_output);

		$expected = file_get_contents(\RX_BASEDIR . 'tests/_data/template/v2result2.php');
		$expected = preg_replace('/(\$__tmp_[0-9a-f]{14})/', $tmpvar, $expected);
		$this->assertEquals($expected, $executed_output);
	}

	protected function _parse($source, $force_v2 = true)
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
		return $result;
	}
}
