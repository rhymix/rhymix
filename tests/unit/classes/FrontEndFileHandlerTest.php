<?php

class FrontEndFileHandlerTest extends \Codeception\TestCase\Test
{
	use \Codeception\Specify;

	private function _filemtime($file)
	{
		return '?' . date('YmdHis', filemtime(_XE_PATH_ . $file));
	}

	public function testFrontEndFileHandler()
	{
		$handler = new FrontEndFileHandler();
		HTMLDisplayHandler::$reservedCSS = '/xxx$/';
		HTMLDisplayHandler::$reservedJS = '/xxx$/';
		FrontEndFileHandler::$minify = 'none';
		FrontEndFileHandler::$concat = 'none';

		$this->specify("js(head)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/js_app.js', 'head'));
			$handler->loadFile(array('./common/js/common.js', 'body'));
			$handler->loadFile(array('./common/js/common.js', 'head'));
			$handler->loadFile(array('./common/js/xml_js_filter.js', 'body'));
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/common.js' . $this->_filemtime('common/js/common.js'), 'targetie' => null);
			$this->assertEquals($handler->getJsFileList(), $expected);
		});

		$this->specify("js(body)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/xml_js_filter.js', 'head'));
			$expected = array();
			$this->assertEquals($handler->getJsFileList('body'), $expected);
		});

		$this->specify("css", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/css/rhymix.scss'));
			$handler->loadFile(array('./common/css/mobile.css'));
			$result = $handler->getCssFileList();
			$this->assertRegexp('/\.rhymix\.scss\.css\b/', $result[0]['file']);
			$this->assertEquals('all', $result[0]['media']);
			$this->assertEmpty($result[0]['targetie']);
			$this->assertEquals('/rhymix/common/css/mobile.css' . $this->_filemtime('common/css/mobile.css'), $result[1]['file']);
			$this->assertEquals('all', $result[1]['media']);
			$this->assertEmpty($result[1]['targetie']);
		});

		$this->specify("order (duplicate)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/common.js' . $this->_filemtime('common/js/common.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/xml_handler.js' . $this->_filemtime('common/js/xml_handler.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/xml_js_filter.js' . $this->_filemtime('common/js/xml_js_filter.js'), 'targetie' => null);
			$this->assertEquals($handler->getJsFileList(), $expected);
		});

		$this->specify("order (redefine)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', 1));
			$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/common.js' . $this->_filemtime('common/js/common.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/xml_js_filter.js' . $this->_filemtime('common/js/xml_js_filter.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/xml_handler.js' . $this->_filemtime('common/js/xml_handler.js'), 'targetie' => null);
			$this->assertEquals($handler->getJsFileList(), $expected);
		});

		$this->specify("unload", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
			$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
			$handler->unloadFile('./common/js/js_app.js', '', 'all');
			$expected[] = array('file' => '/rhymix/common/js/common.js' . $this->_filemtime('common/js/common.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/xml_handler.js' . $this->_filemtime('common/js/xml_handler.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/xml_js_filter.js' . $this->_filemtime('common/js/xml_js_filter.js'), 'targetie' => null);
			$this->assertEquals($handler->getJsFileList(), $expected);
		});

		$this->specify("target IE(js)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/js_app.js', 'head', 'ie6'));
			$handler->loadFile(array('./common/js/js_app.js', 'head', 'ie7'));
			$handler->loadFile(array('./common/js/js_app.js', 'head', 'ie8'));
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => 'ie6');
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => 'ie7');
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => 'ie8');
			$this->assertEquals($handler->getJsFileList(), $expected);
		});

		$this->specify("external file - schemaless", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('http://external.host/js/script.js'));
			$handler->loadFile(array('https://external.host/js/script.js'));
			$handler->loadFile(array('//external.host/js/script1.js'));
			$handler->loadFile(array('///external.host/js/script2.js'));

			$expected[] = array('file' => 'http://external.host/js/script.js', 'targetie' => null);
			$expected[] = array('file' => 'https://external.host/js/script.js', 'targetie' => null);
			$expected[] = array('file' => '//external.host/js/script1.js', 'targetie' => null);
			$expected[] = array('file' => '//external.host/js/script2.js', 'targetie' => null);
			$this->assertEquals($handler->getJsFileList(), $expected);
		});

		$this->specify("external file - schemaless", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('//external.host/js/script.js'));
			$handler->loadFile(array('///external.host/js/script.js'));

			$expected[] = array('file' => '//external.host/js/script.js', 'targetie' => null);
			$this->assertEquals($handler->getJsFileList(), $expected);
		});

		$this->specify("target IE(css)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/css/common.css', null, 'ie6'));
			$handler->loadFile(array('./common/css/common.css', null, 'ie7'));
			$handler->loadFile(array('./common/css/common.css', null, 'ie8'));

			$expected[] = array('file' => '/rhymix/common/css/common.css', 'media'=>'all', 'targetie' => 'ie6');
			$expected[] = array('file' => '/rhymix/common/css/common.css','media'=>'all',  'targetie' => 'ie7');
			$expected[] = array('file' => '/rhymix/common/css/common.css', 'media'=>'all', 'targetie' => 'ie8');
			$this->assertEquals($handler->getCssFileList(), $expected);
		});

		$this->specify("media", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/css/common.css', 'all'));
			$handler->loadFile(array('./common/css/common.css', 'screen'));
			$handler->loadFile(array('./common/css/common.css', 'handled'));

			$expected[] = array('file' => '/rhymix/common/css/common.css', 'media'=>'all', 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/css/common.css','media'=>'screen',  'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/css/common.css', 'media'=>'handled', 'targetie' => null);
			$this->assertEquals($handler->getCssFileList(), $expected);
		});

		FrontEndFileHandler::$minify = 'all';

		$this->specify("minify", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/css/rhymix.scss'));
			$handler->loadFile(array('./common/css/mobile.css'));
			$result = $handler->getCssFileList();
			$this->assertRegexp('/\.rhymix\.scss\.min\.css\b/', $result[0]['file']);
			$this->assertEquals('all', $result[0]['media']);
			$this->assertEmpty($result[0]['targetie']);
			$this->assertRegexp('/minified\/common\.css\.mobile\.min\.css\b/', $result[1]['file']);
			$this->assertEquals('all', $result[1]['media']);
			$this->assertEmpty($result[1]['targetie']);
		});

		$this->specify("external file", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('http://external.host/css/style1.css'));
			$handler->loadFile(array('https://external.host/css/style2.css'));

			$expected[] = array('file' => 'http://external.host/css/style1.css', 'media'=>'all', 'targetie' => null);
			$expected[] = array('file' => 'https://external.host/css/style2.css', 'media'=>'all', 'targetie' => null);
			$this->assertEquals($handler->getCssFileList(), $expected);
		});

		$this->specify("external file - schemaless", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('//external.host/css/style.css'));
			$handler->loadFile(array('///external.host/css2/style2.css'));

			$expected[] = array('file' => '//external.host/css/style.css', 'media'=>'all', 'targetie' => null);
			$expected[] = array('file' => '//external.host/css2/style2.css', 'media'=>'all', 'targetie' => null);
			$this->assertEquals($handler->getCssFileList(), $expected);
		});


	}
}
