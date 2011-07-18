<?php

/**
 * Test class for QueryArgument.
 */
class QueryArgumentTest extends CubridTest {

    var $xmlPath = "data/";
    
    function QueryArgumentClass(){
        $this->xmlPath = str_replace('QueryArgumentTest.php', '', str_replace('\\', '/', __FILE__)) . $this->xmlPath;
    }
    
    function testNotNullConditionArgument(){
            $xml_file = $this->xmlPath . "condition1.xml";
            $xml_obj = Helper::getXmlObject($xml_file);
            $tag = new QueryArgument($xml_obj->condition);

            $this->assertEquals("member_srl", $tag->getArgumentName());            
            $this->assertEquals("member_srl", $tag->getColumnName());
            $this->assertEquals(true, $tag->isConditionArgument());
                        
            $actual = Helper::cleanString($tag->toString());
            $expected = Helper::cleanString('$member_srl_argument = new ConditionArgument(\'member_srl\', $args->member_srl, \'equal\');
                            $member_srl_argument->checkNotNull();
                            $member_srl_argument->createConditionValue();
                            if(!$member_srl_argument->isValid()) return $member_srl_argument->getErrorMessage();');
            $this->assertEquals($expected, $actual);
    }
    
 

}

?>
