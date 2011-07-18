<?php

/**
 * Test class for ConditionArgument.
 */
class ConditionArgumentTest extends CubridTest {

    function testIn(){
        $args->document_srl = 1234;
        $document_srl_argument = new ConditionArgument('document_srl', $args->document_srl, 'in');
        $document_srl_argument->checkNotNull();
        $document_srl_argument->createConditionValue();
        if(!$document_srl_argument->isValid()) return $document_srl_argument->getErrorMessage();
        $document_srl_argument->setColumnType('number');
        
        $condition = new Condition('"extra_vars"."document_srl"',$document_srl_argument,"in", 'and');
        $this->assertEquals('and "extra_vars"."document_srl" in (1234)', $condition->toString());
    }
    /**
     * @todo Implement testCreateConditionValue().
     */
    public function testCreateConditionValue() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetType().
     */
    public function testGetType() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testSetColumnType().
     */
    public function testSetColumnType() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

}

?>
