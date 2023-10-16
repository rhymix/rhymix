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

	}

	public function testPathConversion()
	{

	}

	public function testBlockConditions()
	{

	}

	public function testInlineConditions()
	{

	}

	public function testMiscDirectives()
	{

	}

	public function testComments()
	{

	}

	public function testVerbatim()
	{

	}

	public function testRawPhpCode()
	{

	}

	public function testAutoEscape()
	{

	}

	public function testCurlyBracesAndVars()
	{

	}

	public function testCompile()
	{

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
