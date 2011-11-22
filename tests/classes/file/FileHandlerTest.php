<?php

if(!defined('__XE__')) require dirname(__FILE__).'/../../Bootstrap.php';

require_once _XE_PATH_.'classes/file/FileHandler.class.php'; 

class FileHandlerTest extends PHPUnit_Framework_TestCase
{
	public function testGetRealPath()
	{
		$this->assertEquals(FileHandler::getRealPath(__FILE__), __FILE__);
		$this->assertEquals(FileHandler::getRealPath('./tests/classes/file/FileHandlerTest.php'), __FILE__);
	}

	public function testFileMethods()
	{
		$mock  = dirname(__FILE__).'/mock.txt';
		$mock2 = dirname(__FILE__).'/mock2.txt';
		touch($mock);

		// copy file
		$this->assertTrue(is_readable($mock));
		FileHandler::copyFile($mock, $mock2);
		$this->assertTrue(is_readable($mock2));

		// remove file
		$this->assertTrue(FileHandler::removeFile($mock2));
		$this->assertFalse(is_readable($mock2));
		$this->assertFalse(FileHandler::removeFile($mock2));

		// rename file
		$this->assertTrue(FileHandler::rename($mock, $mock2));
		$this->assertFalse(is_readable($mock));
		$this->assertTrue(is_readable($mock2));
		$this->assertFalse(FileHandler::rename($mock, $mock2));

		// move file
		$this->assertTrue(FileHandler::rename($mock2, $mock));
		$this->assertTrue(is_readable($mock));
		$this->assertFalse(is_readable($mock2));
		$this->assertTrue(touch($mock2) && is_readable($mock2));
		$this->assertTrue(FileHandler::moveFile($mock, $mock2));
		$this->assertFalse(is_readable($mock));
		$this->assertTrue(is_readable($mock2));

		// remove file
		$this->assertFalse(FileHandler::removeFile($mock));
		$this->assertTrue(FileHandler::removeFile($mock2));
		$this->assertFalse(is_readable($mock));
		$this->assertFalse(is_readable($mock2));
	}

	public function testFileSize()
	{
		// file size
		$this->assertEquals(FileHandler::filesize(0), '0Byte');
		$this->assertEquals(FileHandler::filesize(1), '1Byte');
		$this->assertEquals(FileHandler::filesize(386), '386Bytes');
		$this->assertEquals(FileHandler::filesize(1023), '1023Bytes');
		$this->assertEquals(FileHandler::filesize(1024), '1.0KB');
		$this->assertEquals(FileHandler::filesize(2480), '2.4KB');
		$this->assertEquals(FileHandler::filesize(1024*1024-1), '1024.0KB');
		$this->assertEquals(FileHandler::filesize(1024*1024), '1.00MB');
		$this->assertEquals(FileHandler::filesize(3*1024*1024+210*1024), '3.21MB');

		// return bytes
		$this->assertEquals(FileHandler::returnBytes('0B'), 0);
		$this->assertEquals(FileHandler::returnBytes('1024B'), 1024);
		$this->assertEquals(FileHandler::returnBytes('1K'), 1024);
		$this->assertEquals(FileHandler::returnBytes('102.48K'), 1024*102.48);
		$this->assertEquals(FileHandler::returnBytes('1M'), 1024*1024);
		$this->assertEquals(FileHandler::returnBytes('1.12M'), 1024*1024*1.12);
		$this->assertEquals(FileHandler::returnBytes('1023.99M'), 1024*1024*1023.99);
		$this->assertEquals(FileHandler::returnBytes('1G'), 1024*1024*1024);
		$this->assertEquals(FileHandler::returnBytes('12.02G'), 1024*1024*1024*12.02);
	}
}

/* End of file FileHandlerTest.php */
/* Location: ./tests/classes/file/FileHandlerTest.php */
