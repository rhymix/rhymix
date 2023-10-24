<?php

class FileHandlerTest extends \Codeception\Test\Unit
{
   /**
    * @var \UnitTester
    */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
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
        $this->assertEquals(FileHandler::returnBytes('102.48K'), round(1024*102.48));
        $this->assertEquals(FileHandler::returnBytes('1M'), 1024*1024);
        $this->assertEquals(FileHandler::returnBytes('1.12M'), round(1024*1024*1.12));
        $this->assertEquals(FileHandler::returnBytes('1023.99M'), round(1024*1024*1023.99));
        $this->assertEquals(FileHandler::returnBytes('1G'), 1024*1024*1024);
        $this->assertEquals(FileHandler::returnBytes('12.02G'), round(1024*1024*1024*12.02));
    }
}
