<?php

namespace Rhymix\Tests\Unit\Framework;

use Context;
use FrontEndFileHandler;

class ResponseTest extends \Codeception\Test\Unit
{
	public $exampleData = [
		'message' => 'OK',
		'data' => [
			'id' => 123,
			'name' => 'Rhymix',
		],
		'numbers' => [1, 2, 3],
		'list' => ['42' => 'foo', '84' => ['a' => 'bar', 'b' => 'baz']],
	];

	public function testHtmlResponse()
	{
		// Data passed to constructor
		$r = new \Rhymix\Framework\Responses\HTMLResponse(404, ['has_blog' => true]);
		$this->assertEquals(404, $r->getStatusCode());
		$this->assertEquals('text/html', $r->getContentType());
		$this->assertEquals('UTF-8', $r->getCharacterSet());

		// Setting status code and content type
		$r->setStatusCode(500)->setContentType('text/plain')->setCharacterSet('ISO-8859-1');
		$this->assertEquals(500, $r->getStatusCode());
		$this->assertEquals('text/plain', $r->getContentType());
		$this->assertEquals('ISO-8859-1', $r->getCharacterSet());

		// Setting layout and template path
		$r->setTemplate('./tests/_data/template/', 'v1example.html');
		$this->assertEquals('v1example.html', $r->getTemplateFile());

		// Variable setting and unsetting
		$r->has_forum = true;
		unset($r->has_forum);
		$this->assertFalse(isset($r->has_forum));
		$this->assertFalse(isset($r->has_anything));
		$this->assertTrue($r->has_blog);

		// Rendering
		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals('<a href="http://mygony.com">Taggon\'s blog</a><!--#Meta://external.host/js.js-->', $content);
		$this->assertEquals($content, strval($r));

		// Headers
		$headers = $r->getHeaders();
		$this->assertEquals('HTTP/1.1 500 Internal Server Error', $headers[0]);
		$this->assertEquals('Content-Type: text/plain; charset=ISO-8859-1', $headers[1]);

		// Finalization
		$finalized = $r->finalize($content);
		$this->assertStringContainsString('<a href="http://mygony.com">Taggon\'s blog</a>', $finalized);
		$this->assertStringContainsString('//external.host/js.js', $finalized);
		$this->assertStringContainsString('<!DOCTYPE', $finalized);

		// Cleanup
		FrontEndFileHandler::getInstance()->unloadAllFiles();
	}

	public function testCustomResponse()
	{
		$r = new \Rhymix\Framework\Responses\CustomResponse(500);
		$r->setContentType('application/octet-stream');
		$this->assertEquals(500, $r->getStatusCode());
		$this->assertEquals('application/octet-stream', $r->getContentType());

		// Regular string content
		$target_binary = str_repeat(random_bytes(32), 128) . random_bytes(31) . "\x00";
		$r->setContent($target_binary);
		$this->assertEquals($target_binary, $r->getContent());

		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals($target_binary, $content);

		// Stream content, rewinded to the beginning
		$target_binary = str_repeat(random_bytes(32), 1024) . random_bytes(31) . "\x00";
		$stream = fopen('php://memory', 'r+b');
		fwrite($stream, $target_binary);
		$this->assertEquals(32 * 1025, ftell($stream));

		$r = new \Rhymix\Framework\Responses\CustomResponse();
		$r->setStream($stream, true);
		$this->assertEquals($stream, $r->getStream());

		$collected_stream = '';
		foreach ($r->render() as $part)
		{
			$collected_stream .= $part;
		}
		$this->assertEquals($target_binary, $collected_stream);

		// Stream content, played from the current position
		fseek($stream, 4096);
		$r->setStream($stream, false);
		$collected_stream = '';
		foreach ($r->render() as $part)
		{
			$collected_stream .= $part;
		}
		$this->assertEquals(substr($target_binary, 4096), $collected_stream);
	}

	public function testFileResponse()
	{
		$r = new \Rhymix\Framework\Responses\FileResponse();
		$r->setSourcePath(\RX_BASEDIR . 'tests/_data/images/rhymix.png');
		$r->setFilename('다운로드 이미지.png');
		$r->setContentType('image/png');
		$r->forceDownload(true);
		$this->assertEquals(\RX_BASEDIR . 'tests/_data/images/rhymix.png', $r->getSourcePath());
		$this->assertEquals('다운로드 이미지.png', $r->getFilename());
		$this->assertEquals('image/png', $r->getContentType());

		// Full content
		$file_content = file_get_contents($r->getSourcePath());
		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals($file_content, $content);

		$headers = $r->getHeaders();
		$this->assertEquals('Content-Type: image/png', $headers[0]);
		$this->assertEquals('Content-Disposition: attachment; filename="' . rawurlencode($r->getFilename()) . '"', $headers[1]);
		$this->assertEquals('Content-Length: ' . filesize($r->getSourcePath()), $headers[2]);

		// Partial content
		$r->setFilename('');
		$r->setRange(1024, 9245);
		$r->forceDownload(false);
		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals(substr($file_content, 1024, 9245 - 1024 + 1), $content);

		$headers = $r->getHeaders();
		$this->assertEquals('HTTP/1.1 206 Partial Content', $headers[0]);
		$this->assertEquals('Content-Type: image/png', $headers[1]);
		$this->assertEquals('Content-Disposition: inline', $headers[2]);
		$this->assertEquals('Content-Range: bytes 1024-9245/' . filesize($r->getSourcePath()), $headers[3]);
		$this->assertEquals('Content-Length: ' . (9245 - 1024 + 1), $headers[4]);
	}

	public function testJsonResponse()
	{
		$r = new \Rhymix\Framework\Responses\JSONResponse();
		$r->setStatusCode(429);
		$this->assertEquals(429, $r->getStatusCode());
		$this->assertEquals('application/json', $r->getContentType());
		$this->assertFalse(isset($r->status));

		$r->setVars($this->exampleData);
		$this->assertEquals(['id' => 123, 'name' => 'Rhymix'], $r->data);

		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals($this->exampleData, json_decode($content, true));
		$this->assertEquals($content, strval($r));

		$headers = $r->getHeaders();
		$this->assertEquals('HTTP/1.1 429 Too Many Requests', $headers[0]);
		$this->assertEquals('Content-Type: application/json', $headers[1]);
	}

	public function testRawTemplateResponse()
	{
		$r = new \Rhymix\Framework\Responses\RawTemplateResponse();
		$this->assertEquals(200, $r->getStatusCode());
		$this->assertEquals('', $r->getContentType());
		$this->assertEquals('', $r->getCharacterSet());
		$r->setContentType('text/plain');
		$this->assertEquals('text/plain', $r->getContentType());

		$r->setTemplate('./tests/_data/template/', 'v1example.html');
		$this->assertEquals('v1example.html', $r->getTemplateFile());

		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals('<!--#Meta://external.host/js.js-->', $content);
		$this->assertEquals($content, strval($r));
	}

	public function testRedirectResponse()
	{
		$r = new \Rhymix\Framework\Responses\RedirectResponse(301);
		$this->assertEquals(301, $r->getStatusCode());
		$r->setStatusCode(308);
		$this->assertEquals(308, $r->getStatusCode());

		$r->setRedirectUrl('https://rhymix.org/');
		$this->assertEquals('https://rhymix.org/', $r->getRedirectUrl());

		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals('', $content);

		$headers = $r->getHeaders();
		$this->assertEquals('HTTP/1.1 308 Permanent Redirect', $headers[0]);
		$this->assertEquals('Location: https://rhymix.org/', $headers[1]);
	}

	public function testLegacyJsonResponse()
	{
		$r = new \Rhymix\Framework\Responses\LegacyJSONResponse();
		$this->assertEquals('application/json', $r->getContentType());
		$this->assertEquals(200, $r->getStatusCode());

		$r->setVars($this->exampleData);
		$this->assertEquals([42, 84], array_keys($r->list));

		// Backward compatible behavior for numeric arrays
		$target_json = '{"error":0,"message":"OK","data":{"id":123,"name":"Rhymix"},"numbers":[1,2,3],"list":["foo",{"a":"bar","b":"baz"}]}' . "\n";
		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals($target_json, $content);

		// Alternate encoding of numeric arrays for XMLRPC requests
		$request_method = Context::getRequestMethod();
		Context::setRequestMethod('XMLRPC');

		$target_json = '{"error":0,"message":"OK","data":{"id":123,"name":"Rhymix"},"numbers":{"item":[1,2,3]},"list":{"item":["foo",{"a":"bar","b":"baz"}]}}' . "\n";
		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals($target_json, $content);

		Context::setRequestMethod($request_method);
	}

	public function testLegacyXmlResponse()
	{
		$r = new \Rhymix\Framework\Responses\LegacyXMLResponse();
		$this->assertEquals('text/xml', $r->getContentType());
		$this->assertEquals(200, $r->getStatusCode());

		$r->setVars($this->exampleData);
		$this->assertEquals([42, 84], array_keys($r->list));

		$target_xml = <<<XML
			<?xml version="1.0" encoding="UTF-8"?>
			<response>
				<error>0</error>
				<message>OK</message>
				<data>
					<id>123</id>
					<name>Rhymix</name>
				</data>
				<numbers>
					<item>1</item>
					<item>2</item>
					<item>3</item>
				</numbers>
				<list>
					<item>foo</item>
					<item>
						<a>bar</a>
						<b>baz</b>
					</item>
				</list>
			</response>
		XML;

		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals(preg_replace('/\s+/', '', $target_xml), preg_replace('/\s+/', '', $content));
		$this->assertEquals($content, strval($r));

		$headers = $r->getHeaders();
		$this->assertEquals('Content-Type: text/xml; charset=UTF-8', $headers[0]);
	}

	public function testLegacyCallbackResponse()
	{
		$r = new \Rhymix\Framework\Responses\LegacyCallbackResponse();
		$this->assertEquals('text/html', $r->getContentType());
		$this->assertEquals(200, $r->getStatusCode());

		Context::getInstance()->js_callback_func = 'window.parent.Rhymix.onAjaxResponse';
		$r->setVars(['error' => -1, 'message' => 'Epic Fail']);

		$target_html = <<<HTML
			<!DOCTYPE html>
			<html>
			<head><title></title></head>
			<body>
			<script>
			//<![CDATA[
			window.parent.Rhymix.onAjaxResponse({"error":-1,"message":"Epic Fail"});
			//]]>
			</script>
			</body>
			</html>
		HTML;

		$target_html = preg_replace('/\t+/', '', $target_html);
		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals($target_html, $content);
	}

	public function testLegacyRedirectResponse()
	{
		$r = new \Rhymix\Framework\Responses\LegacyRedirectResponse();
		$this->assertEquals('text/html', $r->getContentType());
		$this->assertEquals(200, $r->getStatusCode());

		// Error case
		$r->setVars(['error' => -1, 'message' => 'Epic Fail']);

		$target_html = <<<HTML
			<!DOCTYPE html>
			<html>
			<head><title></title></head>
			<body>
			<script>
			alert("Epic Fail");
			</script>
			</body>
			</html>
		HTML;

		$target_html = preg_replace('/\t+/', '', $target_html);
		$content = implode('', iterator_to_array($r->render()));
		$this->assertEquals($target_html, $content);

		// Success case
		$r->setVars(['error' => 0, 'redirect_url' => 'https://rhymix.org']);

		$target_html = <<<HTML
			<!DOCTYPE html>
			<html>
			<head><title></title></head>
			<body>
			<script>
				if (opener) {
					opener.location.href = "https:\/\/rhymix.org";
				} else {
					parent.location.href = "https:\/\/rhymix.org";
				}
			</script>
			</body>
			</html>
		HTML;

		$target_html = preg_replace('/\t+/', '', $target_html);
		$content = implode('', iterator_to_array($r->render()));
		$content = preg_replace('/  /', '', $content);
		$this->assertEquals($target_html, $content);

		// Response code is always 200
		$this->assertEquals(200, $r->getStatusCode());
	}
}
