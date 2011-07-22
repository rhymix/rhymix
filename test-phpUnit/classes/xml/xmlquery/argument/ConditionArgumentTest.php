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
    
    function testZeroValue(){
        $args->site_srl = 0;
        $site_srl_argument = new ConditionArgument('site_srl', $args->site_srl, 'equal');
        $site_srl_argument->checkNotNull();
        $site_srl_argument->createConditionValue();
        if(!$site_srl_argument->isValid()) return $site_srl_argument->getErrorMessage();
        $site_srl_argument->setColumnType('number');        
        
        $condition = new Condition('"sites"."site_srl"',$site_srl_argument,"equal");
        $this->assertEquals(' "sites"."site_srl" = 0', $condition->toString());
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
