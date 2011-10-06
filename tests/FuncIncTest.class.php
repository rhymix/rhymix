<?php

define('__XE__', 1);
define('_XE_PATH_', realpath(dirname(__FILE__).'/../'));
require _XE_PATH_.'/config/func.inc.php';

class FuncIncTest extends PHPUnit_Framework_TestCase
{
	static public function xssProvider()
	{
		return array(
			// remove iframe
			array(
				'<div class="frame"><iframe src="path/to/file.html"></iframe><p><a href="#iframe">IFrame</a></p></div>',
				'<div class="frame">&lt;iframe src="path/to/file.html">&lt;/iframe><p><a href="#iframe">IFrame</a></p></div>'
			),
			// expression
			array(
				'<div class="dummy" style="xss:expr/*XSS*/ession(alert(\'XSS\'))">',
				'<div class="dummy">'
			),
			// no quotes and no semicolon - http://ha.ckers.org/xss.html
			array(
				'<img src=javascript:alert(\'xss\')>',
				'<img>'
			),
			// embedded encoded tab to break up XSS - http://ha.ckers.org/xss.html
			array(
				'<IMG SRC="jav&#x09;ascript:alert(\'XSS\');">',
				'<img>'
			),
			// issue 178
			array(
				"<img src=\"invalid\"\nonerror=\"alert(1)\" />",
				'<img src="invalid" />'
			)
		);
	}

	/**
	 * @dataProvider xssProvider
	 */
	public function testXSS($source, $expected)
	{
		$result = removeHackTag($source);
		$this->assertEquals($result, $expected);
	}
}
