<?php

/**
 * Test class for ConditionWithArgument.
 */
class ConditionWithArgumentTest extends CubridTest {

     /**
     * Checks equal operation
     */
    public function testConditionString_Equal_WithoutPipe_NumericValue() {
        $member_srl_argument = new ConditionArgument('"member_srl"', 20, 'equal');

        $tag = new ConditionWithArgument('"member_srl"', $member_srl_argument, 'equal', null);

        $this->assertEquals(' "member_srl" = 20', $tag->toString());
    }

     /**
     * Checks equal operation
     */
    public function testConditionString_Equal_WithPipe_NumericValue() {
        $member_srl_argument = new ConditionArgument('"member_srl"', 20, 'equal');

        $tag = new ConditionWithArgument('"member_srl"', $member_srl_argument, 'equal', 'and');

        $this->assertEquals('and "member_srl" = 20', $tag->toString());
    }

     /**
     * Checks condition returns nothing when argument is not valid
     */
    public function testConditionString_InvalidArgument() {
        $member_srl_argument = new ConditionArgument('"member_srl"', null, 'equal');
        $member_srl_argument->checkNotNull();

        $tag = new ConditionWithArgument('"member_srl"', $member_srl_argument, 'equal', 'and');

        $this->assertEquals('', $tag->toString());
    }

     /**
     * Checks "in" operation
     */
    public function testConditionString_In_VarcharArray() {
        $member_srl_argument = new ConditionArgument('"member_srl"', array('a', 'b', 'c'), 'in');
        $member_srl_argument->createConditionValue();
        $member_srl_argument->setColumnType('varchar');

        $tag = new ConditionWithArgument('"member_srl"', $member_srl_argument, 'in');

        $this->assertEquals(' "member_srl" in (\'a\',\'b\',\'c\')', $tag->toString());
    }

     /**
     * Checks and operation
     */
    public function testConditionString_And() {
        $tag = new ConditionWithoutArgument('"member_srl"', "20", 'and', null);

        $this->assertEquals(' "member_srl" & 20', $tag->toString());
    }

     /**
     * Checks or operation
     */
    public function testConditionString_Or() {
        $tag = new ConditionWithoutArgument('"member_srl"', "20", 'or', null);

        $this->assertEquals(' "member_srl" | 20', $tag->toString());
    }

     /**
     * Checks xor operation
     */
    public function testConditionString_Xor() {
        $tag = new ConditionWithoutArgument('"member_srl"', "20", 'xor', null);

        $this->assertEquals(' "member_srl" ^ 20', $tag->toString());
    }

     /**
     * Checks not operation
     */
    public function testConditionString_Not() {
        $tag = new ConditionWithoutArgument('"member_srl"', "20", 'not', null);

        $this->assertEquals(' "member_srl" ~ 20', $tag->toString());
    }
}

?>
