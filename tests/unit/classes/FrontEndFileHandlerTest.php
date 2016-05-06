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

		$this->specify("js (head)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/js_app.js', 'head'));
			$handler->loadFile(array('./common/js/common.js', 'body'));
			$handler->loadFile(array('./common/js/common.js', 'head'));
			$handler->loadFile(array('./common/js/xml_js_filter.js', 'body'));
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/js/common.js' . $this->_filemtime('common/js/common.js'), 'targetie' => null);
			$this->assertEquals($expected, $handler->getJsFileList());
		});

		$this->specify("js (body)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/xml_js_filter.js', 'head'));
			$expected = array();
			$this->assertEquals($expected, $handler->getJsFileList('body'));
		});

		$this->specify("css and scss", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/css/rhymix.scss'));
			$handler->loadFile(array('./common/css/mobile.css'));
			$result = $handler->getCssFileList(true);
			$this->assertRegexp('/\.rhymix\.scss\.css\?\d+$/', $result[0]['file']);
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
			$this->assertEquals($expected, $handler->getJsFileList());
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
			$this->assertEquals($expected, $handler->getJsFileList());
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
			$this->assertEquals($expected, $handler->getJsFileList());
		});

		$this->specify("target IE (js)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/js_app.js', 'head', 'ie6'));
			$handler->loadFile(array('./common/js/js_app.js', 'head', 'ie7'));
			$handler->loadFile(array('./common/js/js_app.js', 'head', 'ie8'));
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => 'ie6');
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => 'ie7');
			$expected[] = array('file' => '/rhymix/common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'targetie' => 'ie8');
			$this->assertEquals($expected, $handler->getJsFileList());
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
			$this->assertEquals($expected, $handler->getJsFileList());
		});

		$this->specify("external file - schemaless", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('//external.host/js/script.js'));
			$handler->loadFile(array('///external.host/js/script.js'));

			$expected[] = array('file' => '//external.host/js/script.js', 'targetie' => null);
			$this->assertEquals($expected, $handler->getJsFileList());
		});

		$this->specify("target IE (css)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/css/common.css', null, 'ie6'));
			$handler->loadFile(array('./common/css/common.css', null, 'ie7'));
			$handler->loadFile(array('./common/css/common.css', null, 'ie8'));

			$expected[] = array('file' => '/rhymix/common/css/common.css', 'media'=>'all', 'targetie' => 'ie6');
			$expected[] = array('file' => '/rhymix/common/css/common.css','media'=>'all',  'targetie' => 'ie7');
			$expected[] = array('file' => '/rhymix/common/css/common.css', 'media'=>'all', 'targetie' => 'ie8');
			$this->assertEquals($expected, $handler->getCssFileList());
		});

		$this->specify("media", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/css/common.css', 'all'));
			$handler->loadFile(array('./common/css/common.css', 'screen'));
			$handler->loadFile(array('./common/css/common.css', 'handled'));

			$expected[] = array('file' => '/rhymix/common/css/common.css', 'media'=>'all', 'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/css/common.css','media'=>'screen',  'targetie' => null);
			$expected[] = array('file' => '/rhymix/common/css/common.css', 'media'=>'handled', 'targetie' => null);
			$this->assertEquals($expected, $handler->getCssFileList());
		});

		FrontEndFileHandler::$minify = 'all';

		$this->specify("minify (css)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/css/rhymix.scss'));
			$handler->loadFile(array('./common/css/mobile.css'));
			$result = $handler->getCssFileList(true);
			$this->assertRegexp('/\.rhymix\.scss\.min\.css\b/', $result[0]['file']);
			$this->assertEquals('all', $result[0]['media']);
			$this->assertEmpty($result[0]['targetie']);
			$this->assertRegexp('/minified\/common\.css\.mobile\.min\.css\?\d+$/', $result[1]['file']);
			$this->assertEquals('all', $result[1]['media']);
			$this->assertEmpty($result[1]['targetie']);
		});
		
		$this->specify("minify (js)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/common.js', 'head'));
			$result = $handler->getJsFileList('head', true);
			$this->assertRegexp('/minified\/common\.js\.common\.min\.js\?\d+$/', $result[0]['file']);
			$this->assertEmpty($result[0]['targetie']);
		});
		
		FrontEndFileHandler::$minify = 'none';
		
		FrontEndFileHandler::$concat = 'css';
		
		$this->specify("concat (css)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/css/rhymix.scss'));
			$handler->loadFile(array('./common/css/mobile.css'));
			$handler->loadFile(array('http://external.host/style.css'));
			$handler->loadFile(array('./common/css/bootstrap.css', null, 'IE'));
			$handler->loadFile(array('./tests/_data/formatter/concat.source1.css'));
			$handler->loadFile(array('./tests/_data/formatter/concat.source2.css'));
			$handler->loadFile(array('./tests/_data/formatter/concat.target1.css'));
			$handler->loadFile(array('./tests/_data/formatter/concat.target2.css'));
			$result = $handler->getCssFileList(true);
			$this->assertEquals(4, count($result));
			$this->assertRegexp('/combined\/[0-9a-f]+\.css\?\d+$/', $result[0]['file']);
			$this->assertEquals('http://external.host/style.css', $result[1]['file']);
			$this->assertEquals('/rhymix/common/css/bootstrap.css' . $this->_filemtime('common/css/bootstrap.css'), $result[2]['file']);
			$this->assertEquals('IE', $result[2]['targetie']);
			$this->assertRegexp('/combined\/[0-9a-f]+\.css\?\d+$/', $result[3]['file']);
		});
		
		FrontEndFileHandler::$concat = 'js';
		
		$this->specify("concat (js)", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('./common/js/common.js', 'head'));
			$handler->loadFile(array('./common/js/debug.js', 'head'));
			$handler->loadFile(array('./common/js/html5.js', 'head'));
			$handler->loadFile(array('///external.host/js/script.js'));
			$handler->loadFile(array('./tests/_data/formatter/concat.source1.js', 'head', 'lt IE 8'));
			$handler->loadFile(array('./tests/_data/formatter/concat.source2.js', 'head', 'gt IE 7'));
			$handler->loadFile(array('./tests/_data/formatter/concat.target1.js'));
			$handler->loadFile(array('./tests/_data/formatter/concat.target2.js'));
			$result = $handler->getJsFileList('head', true);
			$this->assertEquals(3, count($result));
			$this->assertRegexp('/combined\/[0-9a-f]+\.js\?\d+$/', $result[0]['file']);
			$this->assertEquals('//external.host/js/script.js', $result[1]['file']);
			$this->assertRegexp('/combined\/[0-9a-f]+\.js\?\d+$/', $result[2]['file']);
		});
		
		FrontEndFileHandler::$concat = 'none';
		
		$this->specify("external file", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('http://external.host/css/style1.css'));
			$handler->loadFile(array('https://external.host/css/style2.css'));

			$expected[] = array('file' => 'http://external.host/css/style1.css', 'media'=>'all', 'targetie' => null);
			$expected[] = array('file' => 'https://external.host/css/style2.css', 'media'=>'all', 'targetie' => null);
			$this->assertEquals($expected, $handler->getCssFileList());
		});

		$this->specify("external file - schemaless", function() {
			$handler = new FrontEndFileHandler();
			$handler->loadFile(array('//external.host/css/style.css'));
			$handler->loadFile(array('///external.host/css2/style2.css'));

			$expected[] = array('file' => '//external.host/css/style.css', 'media'=>'all', 'targetie' => null);
			$expected[] = array('file' => '//external.host/css2/style2.css', 'media'=>'all', 'targetie' => null);
			$this->assertEquals($expected, $handler->getCssFileList());
		});


	}
}
