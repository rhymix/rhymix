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
		$this->assertEquals($this->prefix . "\n" . $target, $this->_parse($source), false);

		$source = '@version(2)' . "\n" . '<div>@php func_get_args(); @endphp</div>';
		$target = '<div><?php func_get_args(); ?></div>';
		$this->assertEquals($this->prefix . "\n" . $target, $this->_parse($source), false);

		// Extension is .blade.php and config is not declared
		$source = '<input @disabled(foo())>';
		$target = '<input<?php if (foo()): ?> disabled="disabled"<?php endif; ?>>';
		$this->assertEquals($this->prefix . $target, $this->_parse($source));

		// Extension is .blade.php but version is incorrectly declared: will be parsed as v1
		$source = '@version(1)' . "\n" . '<input @disabled(foo())>';
		$target = '<input @disabled(foo())>';
		$this->assertStringContainsString($target, $this->_parse($source));
	}

	public function testClassAliases()
	{

	}

	public function testInclude()
	{

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
		$filename = $force_v2 ? 'v2example.blade.php' : 'no_file.html';
		$tmpl = new \Rhymix\Framework\Template('./tests/_data/template', $filename);
		$result = $tmpl->parse($source);
		return $result;
	}
}
