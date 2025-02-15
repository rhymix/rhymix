<?php

class FrontEndFileHandlerTest extends \Codeception\Test\Unit
{
	private $baseurl;
	private $reservedCSS;
	private $reservedJS;
	private function _filemtime($file)
	{
		return '?t=' . filemtime(_XE_PATH_ . $file);
	}

	public function _before()
	{
		$this->baseurl = '/' . basename(dirname(dirname(dirname(__DIR__)))) . '/';
		$this->reservedCSS = HTMLDisplayHandler::$reservedCSS;
		$this->reservedJS = HTMLDisplayHandler::$reservedJS;
		HTMLDisplayHandler::$reservedCSS = '/xxx$/';
		HTMLDisplayHandler::$reservedJS = '/xxx$/';
		FrontEndFileHandler::$minify = 'none';
		FrontEndFileHandler::$concat = 'none';
	}

	public function _after()
	{
		HTMLDisplayHandler::$reservedCSS = $this->reservedCSS;
		HTMLDisplayHandler::$reservedJS = $this->reservedJS;
	}

	public function _failed()
	{
		HTMLDisplayHandler::$reservedCSS = $this->reservedCSS;
		HTMLDisplayHandler::$reservedJS = $this->reservedJS;
	}

	public function testJsHead()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/js_app.js', 'head'));
		$handler->loadFile(array('./common/js/common.js', 'body'));
		$handler->loadFile(array('./common/js/common.js', 'head'));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'body'));
		$expected[] = array('file' => $this->baseurl . 'common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'attrs' => '');
		$expected[] = array('file' => $this->baseurl . 'common/js/common.js' . $this->_filemtime('common/js/common.js'), 'attrs' => '');
		$this->assertEquals($expected, $handler->getJsFileList());
	}

	public function testJsBody()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/xml_handler.js', 'body'));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'head'));
		$expected[] = array('file' => $this->baseurl . 'common/js/xml_handler.js' . $this->_filemtime('common/js/xml_handler.js'), 'attrs' => '');
		$this->assertEquals($expected, $handler->getJsFileList('body'));
	}

	public function testDefaultScss()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/css/rhymix.scss'));
		$result = $handler->getCssFileList(true);
		$this->assertRegexp('/\.rhymix\.scss\.css\?t=\d+$/', $result[0]['file']);
		$this->assertEquals('all', $result[0]['media']);
		$this->assertTrue(empty($result[0]['targetie']));
	}

	public function testDuplicateOrder()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
		$expected[] = array('file' => $this->baseurl . 'common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'attrs' => '');
		$expected[] = array('file' => $this->baseurl . 'common/js/common.js' . $this->_filemtime('common/js/common.js'), 'attrs' => '');
		$expected[] = array('file' => $this->baseurl . 'common/js/xml_handler.js' . $this->_filemtime('common/js/xml_handler.js'), 'attrs' => '');
		$expected[] = array('file' => $this->baseurl . 'common/js/xml_js_filter.js' . $this->_filemtime('common/js/xml_js_filter.js'), 'attrs' => '');
		$this->assertEquals($expected, $handler->getJsFileList());
	}

	public function testRedefineOrder()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', 1));
		$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
		$expected[] = array('file' => $this->baseurl . 'common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'attrs' => '');
		$expected[] = array('file' => $this->baseurl . 'common/js/common.js' . $this->_filemtime('common/js/common.js'), 'attrs' => '');
		$expected[] = array('file' => $this->baseurl . 'common/js/xml_js_filter.js' . $this->_filemtime('common/js/xml_js_filter.js'), 'attrs' => '');
		$expected[] = array('file' => $this->baseurl . 'common/js/xml_handler.js' . $this->_filemtime('common/js/xml_handler.js'), 'attrs' => '');
		$this->assertEquals($expected, $handler->getJsFileList());
	}

	public function testUnload()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/js_app.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/common.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_handler.js', 'head', '', -100000));
		$handler->loadFile(array('./common/js/xml_js_filter.js', 'head', '', -100000));
		$handler->unloadFile('./common/js/js_app.js', '', 'all');
		$expected[] = array('file' => $this->baseurl . 'common/js/common.js' . $this->_filemtime('common/js/common.js'), 'attrs' => '');
		$expected[] = array('file' => $this->baseurl . 'common/js/xml_handler.js' . $this->_filemtime('common/js/xml_handler.js'), 'attrs' => '');
		$expected[] = array('file' => $this->baseurl . 'common/js/xml_js_filter.js' . $this->_filemtime('common/js/xml_js_filter.js'), 'attrs' => '');
		$this->assertEquals($expected, $handler->getJsFileList());
	}

	public function testJsModule()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/js_app.js', 'module'));
		$handler->loadFile(array('./common/js/common.js', 'module'));
		$expected[] = array('file' => $this->baseurl . 'common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'), 'attrs' => ' type="module"');
		$expected[] = array('file' => $this->baseurl . 'common/js/common.js' . $this->_filemtime('common/js/common.js'), 'attrs' => ' type="module"');
		$this->assertEquals($expected, $handler->getJsFileList());
	}

	public function testExternalFile1()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('http://external.host/js/script.js'));
		$handler->loadFile(array('https://external.host/js/script.js'));
		$handler->loadFile(array('//external.host/js/script1.js'));
		$handler->loadFile(array('///external.host/js/script2.js'));

		$expected[] = array('file' => 'http://external.host/js/script.js', 'attrs' => '');
		$expected[] = array('file' => 'https://external.host/js/script.js', 'attrs' => '');
		$expected[] = array('file' => '//external.host/js/script1.js', 'attrs' => '');
		$expected[] = array('file' => '//external.host/js/script2.js', 'attrs' => '');
		$this->assertEquals($expected, $handler->getJsFileList());
	}

	public function testExternalFile2()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('//external.host/js/script.js'));
		$handler->loadFile(array('///external.host/js/script.js'));

		$expected[] = array('file' => '//external.host/js/script.js', 'attrs' => '');
		$this->assertEquals($expected, $handler->getJsFileList());
	}

	public function testExternalFile3()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('http://external.host/css/style1.css'));
		$handler->loadFile(array('https://external.host/css/style2.css'));
		$handler->loadFile(array('https://external.host/css/style3.css?foo=bar&t=123'));

		$expected[] = array('file' => 'http://external.host/css/style1.css', 'media'=>'all');
		$expected[] = array('file' => 'https://external.host/css/style2.css', 'media'=>'all');
		$expected[] = array('file' => 'https://external.host/css/style3.css?foo=bar&t=123', 'media'=>'all');
		$this->assertEquals($expected, $handler->getCssFileList());
	}

	public function testExternalFile4()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('//external.host/css/style.css'));
		$handler->loadFile(array('///external.host/css2/style2.css'));
		$handler->loadFile(array('//external.host/css/style3.css?foo=bar&t=123'));

		$expected[] = array('file' => '//external.host/css/style.css', 'media'=>'all');
		$expected[] = array('file' => '//external.host/css2/style2.css', 'media'=>'all');
		$expected[] = array('file' => '//external.host/css/style3.css?foo=bar&t=123', 'media'=>'all');
		$this->assertEquals($expected, $handler->getCssFileList());
	}

	public function testExternalFile5()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('https://fonts.googleapis.com/css?family=Montserrat&display=swap'));
		$handler->loadFile(array('//fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap'));

		$expected[] = array('file' => 'https://fonts.googleapis.com/css?family=Montserrat&display=swap', 'media'=>'all');
		$expected[] = array('file' => '//fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap', 'media'=>'all');
		$this->assertEquals($expected, $handler->getCssFileList());
	}

	public function testPathConversion()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/xeicon/xeicon.min.css'));
		$result = $handler->getCssFileList();
		$this->assertEquals($this->baseurl . 'common/css/xeicon/xeicon.min.css' . $this->_filemtime('common/css/xeicon/xeicon.min.css'), $result[0]['file']);
		$this->assertEquals('all', $result[0]['media']);
		$this->assertTrue(empty($result[0]['targetie']));
	}

	public function testTargetie1()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/js_app.js', 'head', 'ie6'));
		$handler->loadFile(array('./common/js/js_app.js', 'head', 'ie7'));
		$handler->loadFile(array('./common/js/js_app.js', 'head', 'ie8'));

		// All targetie attributes should be ignored since Rhymix 2.1
		// Since the 3 loadFile() are otherwise the same, only 1 will remain.
		$expected[] = array(
			'file' => $this->baseurl . 'common/js/js_app.js' . $this->_filemtime('common/js/js_app.js'),
			'attrs' => '',
		);
		$this->assertEquals($expected, $handler->getJsFileList());
	}

	public function testTargetie2()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/css/common.css', null, 'ie6'));
		$handler->loadFile(array('./common/css/common.css', null, 'ie7'));
		$handler->loadFile(array('./common/css/common.css', null, 'ie8'));

		// All targetie attributes should be ignored since Rhymix 2.1
		// Since the 3 loadFile() are otherwise the same, only 1 will remain.
		$expected[] = array('file' => $this->baseurl . 'common/css/common.css', 'media' => 'all');
		$this->assertEquals($expected, $handler->getCssFileList());
	}

	public function testMedia()
	{
		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/css/common.css', 'screen'));
		$handler->loadFile(array('./common/css/common.css', 'print'));
		$handler->loadFile(array('./common/css/common.css', 'handheld'));
		$handler->loadFile(array('./common/css/common.css', true));

		$expected[] = array('file' => $this->baseurl . 'common/css/common.css', 'media'=>'screen');
		$expected[] = array('file' => $this->baseurl . 'common/css/common.css', 'media'=>'print');
		$expected[] = array('file' => $this->baseurl . 'common/css/common.css', 'media'=>'handheld');
		$expected[] = array('file' => $this->baseurl . 'common/css/common.css', 'media'=>'all');
		$this->assertEquals($expected, $handler->getCssFileList());
	}

	public function testMinify()
	{
		FrontEndFileHandler::$minify = 'all';

		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/css/rhymix.scss'));
		$result = $handler->getCssFileList(true);
		$this->assertRegexp('/\.rhymix\.scss\.min\.css\b/', $result[0]['file']);
		$this->assertEquals('all', $result[0]['media']);
		$this->assertTrue(empty($result[0]['targetie']));

		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/common.js', 'head'));
		$result = $handler->getJsFileList('head', true);
		$this->assertRegexp('/minified\/common\.js\.common\.min\.js\?t=\d+$/', $result[0]['file']);
		$this->assertTrue(empty($result[0]['targetie']));

		FrontEndFileHandler::$minify = 'none';
	}

	public function testConcat()
	{
		FrontEndFileHandler::$concat = 'css';

		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/css/rhymix.scss'));
		$handler->loadFile(array('./common/css/bootstrap-responsive.css'));
		$handler->loadFile(array('http://external.host/style.css'));
		$handler->loadFile(array('./common/css/bootstrap.css', null, 'IE'));
		$handler->loadFile(array('./tests/_data/formatter/concat.source1.css'));
		$handler->loadFile(array('./tests/_data/formatter/concat.source2.css'));
		$handler->loadFile(array('./tests/_data/formatter/concat.target1.css'));
		$handler->loadFile(array('./tests/_data/formatter/concat.target2.css'));
		$result = $handler->getCssFileList(true);
		$this->assertEquals(3, count($result));
		$this->assertRegexp('/combined\/[0-9a-f]+\.css\?t=\d+$/', $result[0]['file']);
		//$this->assertEquals($this->baseurl . 'common/css/bootstrap.css' . $this->_filemtime('common/css/bootstrap.css'), $result[1]['file']);
		//$this->assertEquals('IE', $result[1]['targetie']);
		$this->assertEquals('http://external.host/style.css', $result[1]['file']);
		$this->assertRegexp('/combined\/[0-9a-f]+\.css\?t=\d+$/', $result[2]['file']);

		FrontEndFileHandler::$concat = 'js';

		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/js/common.js', 'head'));
		$handler->loadFile(array('./common/js/debug.js', 'head'));
		$handler->loadFile(array('///external.host/js/script.js'));
		$handler->loadFile(array('./tests/_data/formatter/concat.source1.js', 'head', 'lt IE 8'));
		$handler->loadFile(array('./tests/_data/formatter/concat.source2.js', 'head', 'gt IE 7'));
		$handler->loadFile(array('./tests/_data/formatter/concat.target1.js'));
		$handler->loadFile(array('./tests/_data/formatter/concat.target2.js'));
		$result = $handler->getJsFileList('head', true);
		$this->assertEquals(3, count($result));
		$this->assertRegexp('/combined\/[0-9a-f]+\.js\?t=\d+$/', $result[0]['file']);
		$this->assertEquals('//external.host/js/script.js', $result[1]['file']);
		$this->assertRegexp('/combined\/[0-9a-f]+\.js\?t=\d+$/', $result[2]['file']);

		FrontEndFileHandler::$concat = 'none';
	}

	public function testBlockedScripts()
	{
		HTMLDisplayHandler::$reservedCSS = $this->reservedCSS;
		HTMLDisplayHandler::$reservedJS = $this->reservedJS;

		$handler = new FrontEndFileHandler();
		$handler->loadFile(array('./common/css/xe.min.css'));
		$handler->loadFile(array('./common/js/common.js'));
		$handler->loadFile(array('./common/js/xe.js'));
		$handler->loadFile(array('./common/js/xe.min.js'));
		$handler->loadFile(array('./common/js/xml2json.js'));
		$handler->loadFile(array('./common/js/jquery.js'));
		$handler->loadFile(array('./common/js/jquery-1.x.min.js'));
		$handler->loadFile(array('./common/js/jquery-2.0.0.js'));
		$handler->loadFile(array('./common/js/jQuery.min.js'));
		$handler->loadFile(array('http://code.jquery.com/jquery-latest.js'));
		$result = $handler->getCssFileList();
		$this->assertEquals(0, count($result));
		$result = $handler->getJsFileList();
		$this->assertEquals(1, count($result));
		$this->assertEquals($this->baseurl . 'common/js/xml2json.js' . $this->_filemtime('common/js/xml2json.js'), $result[0]['file']);
	}
}
