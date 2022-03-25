<?php

class FnValidatorTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test custom functional validator implementations.
	 *
	 * @param JBBCode\validators\FnValidator $validator
	 * @dataProvider validatorProvider
	 */
	public function testValidator($validator)
	{
		$this->assertTrue($validator->validate('1234567890'));
		$this->assertFalse($validator->validate('QWERTZUIOP'));
	}

	/**
	 * Provide custom numeric string validator implementations.
	 *
	 */
	public function validatorProvider()
	{
		return array(
			array(new JBBCode\validators\FnValidator('is_numeric')),
			array(new JBBCode\validators\FnValidator(function ($input) {
				return is_numeric($input);
			})),
		);
	}
}
