<?php

define('__DEBUG__', 1);
$xe_path = realpath(dirname(__FILE__).'/../../../');
require "{$xe_path}/classes/handler/Handler.class.php";
require "{$xe_path}/classes/frontendfile/FrontEndFileHandler.class.php";

error_reporting(E_ALL & ~E_NOTICE);
$_SERVER['SCRIPT_NAME'] = '/xe/index.php';

class FrontEndFileHandlerTest extends PHPUnit_Framework_TestCase
{
	public function testHandler()
	{
		global $request_url, $use_cdn;

		$request_url = 'http://test.com';
		$use_cdn = 'Y';

		$handler = new FrontEndFileHandler();

		// js(head)
		$handler->loadFile(array('./common/js/jquery.js'));
		$handler->loadFile(array('./common/js/js_app.js'));
		$handler->loadFile(array('./common/js/common.js'));
		$handler->loadFile(array('./common/js/xml_handler.js'));
		$handler->loadFile(array('./common/js/xml_js_filter.js'));
		
		$expected[] = array('file' => '/xe/common/js/jquery.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/js_app.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/common.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_handler.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_js_filter.js', 'targetie' => '');
		$this->assertEquals($handler->getJsFileList(), $expected);

		unset($handler);
		unset($expected);
		$handler = new FrontEndFileHandler();

		// js(body)
		$handler->loadFile(array('./common/js/jquery.js', 'body'));
		$handler->loadFile(array('./common/js/js_app.js', 'body'));
		$handler->loadFile(array('./common/js/common.js', 'body'));
		$handler->loadFile(array('./common/js/xml_handler.js', 'body'));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'body'));
		
		$expected[] = array('file' => '/xe/common/js/jquery.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/js_app.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/common.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_handler.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_js_filter.js', 'targetie' => '');
		$this->assertEquals($handler->getJsFileList('body'), $expected);

		unset($handler);
		unset($expected);
		$handler = new FrontEndFileHandler();

		// css
		$handler->loadFile(array('./common/css/xe.css'));
		$handler->loadFile(array('./common/css/common.css'));
		
		$expected[] = array('file' => '/xe/common/css/xe.css', 'media' => 'all', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/css/common.css', 'media' => 'all', 'targetie' => '');
		$this->assertEquals($handler->getCssFileList(), $expected);

		unset($handler);
		unset($expected);
		$handler = new FrontEndFileHandler();

		// order (duplicate)
		$handler->loadFile(array('./common/js/jquery.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/jquery.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));

		$expected[] = array('file' => '/xe/common/js/jquery.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/js_app.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/common.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_handler.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_js_filter.js', 'targetie' => '');
		$this->assertEquals($handler->getJsFileList(), $expected);

		// order (redefine)
		unset($handler);
		unset($expected);
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/jquery.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/jquery.js', 'head', '', 1));

		$expected[] = array('file' => '/xe/common/js/js_app.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/common.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_handler.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_js_filter.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/jquery.js', 'targetie' => '');
		$this->assertEquals($handler->getJsFileList(), $expected);

		// unload
		unset($handler);
		unset($expected);
		$handler = new FrontEndFileHandler();

		$handler->loadFile(array('./common/js/jquery.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
		$handler->unloadFile('./common/js/jquery.js', '', 'all');

		$expected[] = array('file' => '/xe/common/js/js_app.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/common.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_handler.js', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/js/xml_js_filter.js', 'targetie' => '');
		$this->assertEquals($handler->getJsFileList(), $expected);

		// target IE(js)
		unset($handler);
		unset($expected);
		$handler = new FrontEndFileHandler();

		$handler->loadFile(array('./common/js/jquery.js', 'head', 'ie6'));
		$handler->loadFile(array('./common/js/jquery.js', 'head', 'ie7'));
		$handler->loadFile(array('./common/js/jquery.js', 'head', 'ie8'));

		$expected[] = array('file' => '/xe/common/js/jquery.js', 'targetie' => 'ie6');
		$expected[] = array('file' => '/xe/common/js/jquery.js', 'targetie' => 'ie7');
		$expected[] = array('file' => '/xe/common/js/jquery.js', 'targetie' => 'ie8');
		$this->assertEquals($handler->getJsFileList(), $expected);

		// target IE(css)
		unset($handler);
		unset($expected);
		$handler = new FrontEndFileHandler();

		$handler->loadFile(array('./common/css/common.css', null, 'ie6'));
		$handler->loadFile(array('./common/css/common.css', null, 'ie7'));
		$handler->loadFile(array('./common/css/common.css', null, 'ie8'));

		$expected[] = array('file' => '/xe/common/css/common.css', 'media'=>'all', 'targetie' => 'ie6');
		$expected[] = array('file' => '/xe/common/css/common.css','media'=>'all',  'targetie' => 'ie7');
		$expected[] = array('file' => '/xe/common/css/common.css', 'media'=>'all', 'targetie' => 'ie8');
		$this->assertEquals($handler->getCssFileList(), $expected);

		// media
		unset($handler);
		unset($expected);
		$handler = new FrontEndFileHandler();

		$handler->loadFile(array('./common/css/common.css', 'all'));
		$handler->loadFile(array('./common/css/common.css', 'screen'));
		$handler->loadFile(array('./common/css/common.css', 'handled'));

		$expected[] = array('file' => '/xe/common/css/common.css', 'media'=>'all', 'targetie' => '');
		$expected[] = array('file' => '/xe/common/css/common.css','media'=>'screen',  'targetie' => '');
		$expected[] = array('file' => '/xe/common/css/common.css', 'media'=>'handled', 'targetie' => '');
		$this->assertEquals($handler->getCssFileList(), $expected);

		// CDN
		unset($handler);
		unset($expected);
		$handler = new FrontEndFileHandler();

		$handler->loadFile(array('./common/css/common.css'), true, 'http://static.xpressengine.com/core/', 'v');

		$expected[] = array('file' => 'http://static.xpressengine.com/core/v/common/css/common.css', 'media'=>'all', 'targetie' => '');
		$this->assertEquals($handler->getCssFileList(), $expected);

		// CDN(no cdn setting)
		unset($handler);
		unset($expected);
		$use_cdn = 'N';
		$handler = new FrontEndFileHandler();

		$handler->loadFile(array('./common/css/common.css'), true, 'http://static.xpressengine.com/core/', 'v');

		$expected[] = array('file' => '/xe/common/css/common.css', 'media'=>'all', 'targetie' => '');
		$this->assertEquals($handler->getCssFileList(), $expected);

		// CDN(use ssl)
		unset($handler);
		unset($expected);
		$use_cdn = 'Y';
		$request_url = 'https://test.com';
		$handler = new FrontEndFileHandler();

		$handler->loadFile(array('./common/css/common.css'), true, 'http://static.xpressengine.com/core/', 'v');

		$expected[] = array('file' => '/xe/common/css/common.css', 'media'=>'all', 'targetie' => '');
		$this->assertEquals($handler->getCssFileList(), $expected);

		// external file
		unset($handler);
		unset($expected);
		$use_cdn = 'Y';
		$request_url = 'http://test.com';
		$handler = new FrontEndFileHandler();

		$handler->loadFile(array('http://external.com/css/style2.css'));
		$handler->loadFile(array('http://external.com/css/style.css'), true, 'http://static.xpressengine.com/core/', 'v');

		$expected[] = array('file' => 'http://external.com/css/style2.css', 'media'=>'all', 'targetie' => '');
		$expected[] = array('file' => 'http://external.com/css/style.css', 'media'=>'all', 'targetie' => '');
		$this->assertEquals($handler->getCssFileList(), $expected);

	}
}

$mock_vars = array();

class Context
{
	public function gets() {
		global $mock_vars;

		$args = func_get_args();
		$output = new stdClass;

		foreach($args as $name) {
			$output->{$name} = $mock_vars[$name];
		}

		return $output;
	}

	public function get($name) {
		global $mock_vars;
		return array_key_exists($name, $mock_vars)?$mock_vars[$name]:'';
	}

	public function set($name, $value) {
		global $mock_vars;

		$mock_vars[$name] = $value;
	}

	public function getRequestUrl() {
		global $request_url;
		return $request_url;
	}
	public function getDBInfo() {
		global $use_cdn;
		$dbInfo->use_cdn = $use_cdn;
		return $dbInfo;
	}
}

function debugPrint(){}
