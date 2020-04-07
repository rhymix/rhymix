<?php

class UrlValidatorTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var JBBCode\validators\UrlValidator
     */
    private $_validator;

    protected function setUp()
    {
        $this->_validator = new JBBCode\validators\UrlValidator();
    }

    /**
     * @param string $url
     * @dataProvider invalidUrlProvider
     */
    public function testInvalidUrl($url)
    {
        $this->assertFalse($this->_validator->validate($url));
    }

    /**
     * @param string $url
     * @dataProvider validUrlProvider
     */
    public function testValidUrl($url)
    {
        $this->assertTrue($this->_validator->validate($url));
    }

    public function invalidUrlProvider()
    {
        return array(
            array('#yolo#swag'),
            array('giehtiehwtaw352353%3'),
        );
    }

    public function validUrlProvider()
    {
        return array(
            array('http://google.com'),
            array('http://jbbcode.com/docs'),
            array('https://www.maps.google.com'),
        );
    }
}
