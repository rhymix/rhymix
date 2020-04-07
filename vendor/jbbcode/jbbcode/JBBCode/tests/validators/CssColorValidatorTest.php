<?php

class CssColorValidatorTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var JBBCode\validators\CssColorValidator
     */
    private $_validator;

    protected function setUp()
    {
        $this->_validator = new JBBCode\validators\CssColorValidator();
    }

    /**
     * @param string $color
     * @dataProvider validColorProvider
     */
    public function testValidColors($color)
    {
        $this->assertTrue($this->_validator->validate($color));
    }

    public function validColorProvider()
    {
        return array(
            array('red'),
            array('yellow'),
            array('LightGoldenRodYellow'),
            array('#000'),
            array('#00ff00'),
            array('rgba(255, 0, 0, 0.5)'),
            array('rgba(50, 50, 50, 0.0)'),
        );
    }
}
