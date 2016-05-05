<?php

class FormatterTest extends \Codeception\TestCase\Test
{
	public function testText2HTML()
	{
		$text = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/text2html.source.txt');
		$html1 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/text2html.target1.html');
		$html2 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/text2html.target2.html');
		$html3 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/text2html.target3.html');
		
		$this->assertEquals($html1, Rhymix\Framework\Formatter::text2html($text));
		$this->assertEquals($html2, Rhymix\Framework\Formatter::text2html($text, Rhymix\Framework\Formatter::TEXT_NEWLINE_AS_P));
		$this->assertEquals($html3, Rhymix\Framework\Formatter::text2html($text, Rhymix\Framework\Formatter::TEXT_DOUBLE_NEWLINE_AS_P));
	}
	
	public function testHTML2Text()
	{
		$html = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/html2text.source.html');
		$text = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/html2text.target.txt');
		
		$this->assertEquals($text, Rhymix\Framework\Formatter::html2text($html));
	}
	
	public function testMarkdown2HTML()
	{
		$markdown = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/markdown2html.source.md');
		$html1 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/markdown2html.target1.html');
		$html2 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/markdown2html.target2.html');
		$html3 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/markdown2html.target3.html');
		
		$this->assertEquals($html1, Rhymix\Framework\Formatter::markdown2html($markdown));
		$this->assertEquals($html2, Rhymix\Framework\Formatter::markdown2html($markdown, Rhymix\Framework\Formatter::MD_NEWLINE_AS_BR));
		$this->assertEquals($html3, Rhymix\Framework\Formatter::markdown2html($markdown, Rhymix\Framework\Formatter::MD_NEWLINE_AS_BR | Rhymix\Framework\Formatter::MD_ENABLE_EXTRA));
	}
	
	public function testHTML2Markdown()
	{
		$html = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/html2markdown.source.html');
		$markdown = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/html2markdown.target.md');
		
		$this->assertEquals($markdown, Rhymix\Framework\Formatter::html2markdown($html));
	}
	
	public function testBBCode()
	{
		$bbcode = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/bbcode.source.txt');
		$html = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/bbcode.target.html');
		
		$this->assertEquals($html, Rhymix\Framework\Formatter::bbcode($bbcode));
	}
	
	public function testApplySmartQuotes()
	{
		$before = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/smartypants.source.html');
		$after = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/smartypants.target.html');
		
		$this->assertEquals($after, Rhymix\Framework\Formatter::applySmartQuotes($before));
	}
	
	public function testCompileLESS()
	{
		$sources = array(
			\RX_BASEDIR . 'tests/_data/formatter/less.source1.less',
			\RX_BASEDIR . 'tests/_data/formatter/less.source2.less',
		);
		$variables = array(
			'foo' => '#123456',
			'bar' => '320px',
		);
		
		$real_target1 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/less.target1.css');
		$real_target2 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/less.target2.css');
		$test_target1 = \RX_BASEDIR . 'tests/_output/less.target1.css';
		$test_target2 = \RX_BASEDIR . 'tests/_output/less.target2.css';
		
		$this->assertTrue(Rhymix\Framework\Formatter::compileLESS($sources, $test_target1, $variables));
		$this->assertEquals($real_target1, file_get_contents($test_target1));
		$this->assertTrue(Rhymix\Framework\Formatter::compileLESS($sources, $test_target2, $variables, true));
		$this->assertEquals($real_target2, file_get_contents($test_target2));
		
		unlink($test_target1);
		unlink($test_target2);
	}
	
	public function testCompileSCSS()
	{
		$sources = array(
			\RX_BASEDIR . 'tests/_data/formatter/scss.source1.scss',
			\RX_BASEDIR . 'tests/_data/formatter/scss.source2.scss',
		);
		$variables = array(
			'foo' => '#123456',
			'bar' => '320px',
		);
		
		$real_target1 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/scss.target1.css');
		$real_target2 = file_get_contents(\RX_BASEDIR . 'tests/_data/formatter/scss.target2.css');
		$test_target1 = \RX_BASEDIR . 'tests/_output/scss.target1.css';
		$test_target2 = \RX_BASEDIR . 'tests/_output/scss.target2.css';
		
		$this->assertTrue(Rhymix\Framework\Formatter::compileSCSS($sources, $test_target1, $variables));
		$this->assertEquals($real_target1, file_get_contents($test_target1));
		$this->assertTrue(Rhymix\Framework\Formatter::compileSCSS($sources, $test_target2, $variables, true));
		$this->assertEquals($real_target2, file_get_contents($test_target2));
		
		unlink($test_target1);
		unlink($test_target2);
	}
	
	public function testMinifyCSS()
	{
		$source = \RX_BASEDIR . 'tests/_data/formatter/minify.source.css';
		$real_target = \RX_BASEDIR . 'tests/_data/formatter/minify.target.css';
		$test_target = \RX_BASEDIR . 'tests/_output/minify.target.css';
		
		$this->assertTrue(Rhymix\Framework\Formatter::minifyCSS($source, $test_target));
		$this->assertEquals(file_get_contents($real_target), file_get_contents($test_target));
		
		unlink($test_target);
	}
	
	public function testMinifyJS()
	{
		$source = \RX_BASEDIR . 'tests/_data/formatter/minify.source.js';
		$real_target = \RX_BASEDIR . 'tests/_data/formatter/minify.target.js';
		$test_target = \RX_BASEDIR . 'tests/_output/minify.target.js';
		
		$this->assertTrue(Rhymix\Framework\Formatter::minifyJS($source, $test_target));
		$this->assertEquals(file_get_contents($real_target), file_get_contents($test_target));
		
		unlink($test_target);
	}
	
	public function testConcatCSS()
	{
		$source1 = \RX_BASEDIR . 'tests/_data/formatter/concat.source1.css';
		$source2 = \RX_BASEDIR . 'tests/_data/formatter/concat.source2.css';
		$real_target1 = \RX_BASEDIR . 'tests/_data/formatter/concat.target1.css';
		$real_target2 = \RX_BASEDIR . 'tests/_data/formatter/concat.target2.css';
		$test_target = \RX_BASEDIR . 'tests/_output/concat.target.css';
		
		$test_without_media_query = Rhymix\Framework\Formatter::concatCSS(array($source1, $source2), $test_target);
		$this->assertEquals(trim(file_get_contents($real_target1)), trim($test_without_media_query));
		
		$test_with_media_query = Rhymix\Framework\Formatter::concatCSS(array(array($source1, 'screen and (max-width: 640px)'), $source2), $test_target);
		$this->assertEquals(trim(file_get_contents($real_target2)), trim($test_with_media_query));
	}
	
	public function testConcatJS()
	{
		$source1 = \RX_BASEDIR . 'tests/_data/formatter/concat.source1.js';
		$source2 = \RX_BASEDIR . 'tests/_data/formatter/concat.source2.js';
		$real_target1 = \RX_BASEDIR . 'tests/_data/formatter/concat.target1.js';
		$real_target2 = \RX_BASEDIR . 'tests/_data/formatter/concat.target2.js';
		$test_target = \RX_BASEDIR . 'tests/_output/concat.target.js';
		
		$test_without_targetie = Rhymix\Framework\Formatter::concatJS(array($source1, $source2), $test_target);
		$this->assertEquals(trim(file_get_contents($real_target1)), trim($test_without_targetie));
		
		$test_with_targetie = Rhymix\Framework\Formatter::concatJS(array($source1, array($source2, '(gte IE 6) & (lte IE 8)')), $test_target);
		$this->assertEquals(trim(file_get_contents($real_target2)), trim($test_with_targetie));
	}
	
	public function testConvertIECondition()
	{
		$this->assertEquals('window.navigator.userAgent.match(/MSIE\s/)', Rhymix\Framework\Formatter::convertIECondition('IE'));
		$this->assertEquals('!window.navigator.userAgent.match(/MSIE\s/)', Rhymix\Framework\Formatter::convertIECondition('!IE'));
		$this->assertEquals('!window.navigator.userAgent.match(/MSIE\s/)', Rhymix\Framework\Formatter::convertIECondition('!(IE)'));
		$this->assertEquals('true && false', Rhymix\Framework\Formatter::convertIECondition('true&false'));
		$this->assertEquals('false', Rhymix\Framework\Formatter::convertIECondition('gobbledygook'));
		
		$source = 'gt IE 7';
		$target = '(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] > 7)';
		$this->assertEquals($target, Rhymix\Framework\Formatter::convertIECondition($source));
		
		$source = 'lte IE 8';
		$target = '(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] <= 8)';
		$this->assertEquals($target, Rhymix\Framework\Formatter::convertIECondition($source));
		
		$source = '(gte IE 6) & (lt IE 8)';
		$target = '(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] >= 6) && (/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] < 8)';
		$this->assertEquals($target, Rhymix\Framework\Formatter::convertIECondition($source));
		
		$source = '!(gt IE 9)';
		$target = '!(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] > 9)';
		$this->assertEquals($target, Rhymix\Framework\Formatter::convertIECondition($source));
		
		$source = '!lt IE 8|lt IE 6';
		$target = '!(/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] < 8) || (/MSIE (\d+)/.exec(window.navigator.userAgent) && /MSIE (\d+)/.exec(window.navigator.userAgent)[1] < 6)';
		$this->assertEquals($target, Rhymix\Framework\Formatter::convertIECondition($source));
	}
}
