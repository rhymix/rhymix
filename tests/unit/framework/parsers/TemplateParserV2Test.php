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
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "foobar", "blade.php"); echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Legacy 'target' attribute
		$source = '<include target="subdir/foobar" />';
		$target = '<?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "subdir/foobar", "blade.php"); echo $__tpl->compile(); ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Conditional include
		$source = '<include src="../up/foobar" if="$cond" />';
		$target = '<?php if(!empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "../up/foobar", "blade.php"); echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Conditional include with legacy 'cond' attribute
		$source = '<include target="legacy/cond.statement.html" cond="$cond" />';
		$target = '<?php if(!empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template($this->relative_dirname, "legacy/cond.statement.html", "blade.php"); echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Path relative to Rhymix installation directory
		$source = '<include src="^/modules/foobar/views/baz" when="$cond" />';
		$target = '<?php if(!empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template("modules/foobar/views", "baz", "blade.php"); echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

		// Unless
		$source = '<include src="^/modules/foobar/views/baz" unless="$cond" />';
		$target = '<?php if(empty($cond)): ?><?php $__tpl = new \Rhymix\Framework\Template("modules/foobar/views", "baz", "blade.php"); echo $__tpl->compile(); ?><?php endif; ?>';
		$this->assertEquals($target, $this->_parse($source));

	}

	public function testAssetLoading()
	{

	}

	public function testEchoStatements()
	{

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

	public function _parse($source, $force_v2 = true)
	{
		$filename = $force_v2 ? 'v2example.blade.php' : 'empty.html';
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', $filename);
		$result = $tmpl->parse($source);
		if (str_starts_with($result, $this->prefix))
		{
			$result = substr($result, strlen($this->prefix));
		}
		return $result;
	}
}
