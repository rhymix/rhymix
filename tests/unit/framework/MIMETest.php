<?php

class MIMETest extends \Codeception\TestCase\Test
{
	public function testMIME()
	{
		$this->assertEquals('audio/ogg', Rhymix\Framework\MIME::getTypeByExtension('ogg'));
		$this->assertEquals('image/gif', Rhymix\Framework\MIME::getTypeByExtension('gif'));
		$this->assertEquals('text/html', Rhymix\Framework\MIME::getTypeByExtension('htm'));
		
		$this->assertEquals('application/msword', Rhymix\Framework\MIME::getTypeByFilename('attachment.doc'));
		$this->assertEquals('application/pdf', Rhymix\Framework\MIME::getTypeByFilename('라이믹스.pdf'));
		$this->assertEquals('application/postscript', Rhymix\Framework\MIME::getTypeByFilename('MyGraphics.v2.eps'));
		$this->assertEquals('application/vnd.ms-excel', Mail::returnMIMEType('MySpreadsheet.xls'));
		$this->assertEquals('application/octet-stream', Mail::returnMIMEType('Untitled File'));
		
		$this->assertEquals('odt', Rhymix\Framework\MIME::getExtensionByType('application/vnd.oasis.opendocument.text'));
		$this->assertEquals('jpg', Rhymix\Framework\MIME::getExtensionByType('image/jpeg'));
		$this->assertEquals('mpeg', Rhymix\Framework\MIME::getExtensionByType('video/mpeg'));
		$this->assertFalse(Rhymix\Framework\MIME::getExtensionByType('application/octet-stream'));
	}
}
