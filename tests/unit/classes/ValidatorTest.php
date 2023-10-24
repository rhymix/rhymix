<?php

class ValidatorTest extends \Codeception\Test\Unit
{
    public function _before()
    {
        $ob_level = ob_get_level();
    	while (ob_get_level() > $ob_level)
    	{
    		ob_end_clean();
    	}
    }

	public function _after()
	{
		Rhymix\Framework\Storage::deleteDirectory(__DIR__ . '/validator/ruleset', true);
	}

	public function _failed()
	{
        Rhymix\Framework\Storage::deleteDirectory(__DIR__ . '/validator/ruleset', true);
    }

    public function testRequired()
    {
        $vd = new Validator();
        $vd->addFilter('userid', array('required'=>'true'));

        // given data
        $this->assertFalse( $vd->validate(array('no-userid'=>'hello')) );
        $this->assertTrue( $vd->validate(array('userid'=>'myuserid')) );
        $this->assertFalse( $vd->validate(array('userid'=>'')) );

        // context data
        $this->assertFalse( $vd->validate() );
        Context::set('userid', '', true);
        $this->assertFalse( $vd->validate() );
        Context::set('userid', 'myuserid', true);
        $this->assertTrue( $vd->validate() );
        $vd->removeFilter('userid');
        $this->assertTrue( $vd->validate() );
    }

    public function testNamePattern()
    {
        $vd = new Validator();
        $vd->addFilter('^user_', array('length'=>'5:'));

        Context::set('user_123', 'abcd', true);
        Context::set('user_456', '123', true);
        $this->assertFalse( $vd->validate() );

        Context::set('user_123', 'abcdefg', true);
        $this->assertFalse( $vd->validate() );

        Context::set('user_456', '123456', true);
        $this->assertTrue( $vd->validate() );
    }

    public function testEqualTo()
    {
        $vd = new Validator();
        $vd->addFilter('pass1', array('equalto'=>'pass2'));

        Context::set('pass1', 'MyPassword', true);
        $this->assertFalse( $vd->validate() );
        Context::set('pass2', 'WorngPassword', true);
        $this->assertFalse( $vd->validate() );
        Context::set('pass2', 'MyPassword', true);
        $this->assertTrue( $vd->validate() );
    }

    public function testArrayTrim()
    {
        $vd = new Validator();

        $arr = array('red'=>'apple', 'yellow'=>'banana ', 'green'=>' papaya ');
        $this->assertEquals($vd->arrayTrim($arr), array('red'=>'apple', 'yellow'=>'banana', 'green'=>'papaya'));
        $this->assertEquals($vd->arrayTrim(' string '), 'string');
    }

    public function testLength()
    {
        $vd = new Validator();

        $vd->addFilter('field1', array('length'=>'3:'));
        $this->assertFalse( $vd->validate(array('field1'=>'ab')) );
        $this->assertTrue( $vd->validate(array('field1'=>'abc')) );
        $this->assertTrue( $vd->validate(array('field1'=>'abcd')) );
    }

    public function testCustomRule()
    {
        // regex
        $vd = new Validator();
        $customRules['regex_rule']['type'] = 'regex';
        $customRules['regex_rule']['test'] = '/^[a-z]+$/';
        $vd->addRule($customRules);
        $vd->addFilter('regex_field', array('rule' => 'regex_rule'));

        $this->assertTrue($vd->validate(array('regex_field' => 'abc')));
        $this->assertFalse($vd->validate(array('regex_field' => 'ABC')));

        // enum
        $vd = new Validator();
        $customRules['enum_rule']['type'] = 'enum';
        $customRules['enum_rule']['test'] = 'a,b,c';
        $vd->addRule($customRules);
        $vd->addFilter('enum_field', array('rule' => 'enum_rule'));

        $this->assertTrue($vd->validate(array('enum_field' => 'a')));
        $this->assertFalse($vd->validate(array('enum_field' => 'd')));

        // enum with custom delimiter
        $vd = new Validator();
        $customRules['enum_rule2']['type'] = 'enum';
        $customRules['enum_rule2']['test'] = 'a@b@c';
        $customRules['enum_rule2']['delim'] = '@';
        $vd->addRule($customRules);
        $vd->addFilter('enum_field2', array('rule' => 'enum_rule2'));

        $this->assertTrue($vd->validate(array('enum_field2' => 'a')));
        $this->assertFalse($vd->validate(array('enum_field2' => 'd')));

        // expr
        $vd = new Validator();
        $customRules['expr_rule']['type'] = 'expr';
        $customRules['expr_rule']['test'] = '$$ &lt; 10';
        $vd->addRule($customRules);
        $vd->addFilter('expr_field', array('rule' => 'expr_rule'));

        $this->assertTrue($vd->validate(array('expr_field' => '5')));
        $this->assertFalse($vd->validate(array('expr_field' => '15')));
    }

    public function testCustomRuleXml()
    {
        $vd = new Validator(__DIR__ . '/validator/customrule.xml');

        $this->assertTrue($vd->validate(array('regex_field' => 'abc')));
        $this->assertFalse($vd->validate(array('regex_field' => 'ABC')));

        $this->assertTrue($vd->validate(array('enum_field' => 'a')));
        $this->assertFalse($vd->validate(array('enum_field' => 'd')));

        $this->assertTrue($vd->validate(array('enum_field2' => 'a')));
        $this->assertFalse($vd->validate(array('enum_field2' => 'd')));

        $this->assertTrue($vd->validate(array('expr_field' => '5')));
        $this->assertFalse($vd->validate(array('expr_field' => '15')));
    }

    public function testCondition()
    {
        $vd = new Validator();
        $data = array('greeting1'=>'hello');

        // No condition
        $vd->addFilter('greeting1', array('required'=>'true'));
        $this->assertTrue($vd->validate($data));

        // Now greeting2 being mandatory if greeting1 is 'Hello'
        $vd->addFilter('greeting2', array('if'=>array('test'=>'$greeting1 == "Hello"', 'attr'=>'required', 'value'=>'true')));

        // Because greeting1 is 'hello', including lowercase 'h', greeting2 isn't required yet.
        $this->assertTrue($vd->validate($data));

        // Change the value of greeting1. Greeting2 is required now
        $data['greeting1'] = 'Hello';
        $this->assertFalse($vd->validate($data));

        $data['greeting2'] = 'World';
        $this->assertTrue($vd->validate($data));
    }

    public function testConditionXml()
    {

        $vd = new Validator(__DIR__ . '/validator/condition.xml');
        $data = array('greeting1'=>'hello');

        $this->assertTrue($vd->validate($data));

        // Change the value of greeting1. Greeting2 is required now
        $data['greeting1'] = 'Hello';
        $this->assertFalse($vd->validate($data));

        $data['greeting2'] = 'World';
        $this->assertTrue($vd->validate($data));

        // javascript
        $vd->setCacheDir(__DIR__ . '/validator');
        $js = $vd->getJsPath();
        $this->assertEquals(trim(file_get_contents(__DIR__ . '/validator/condition.en.js')), trim(file_get_contents($js)));
    }
}

