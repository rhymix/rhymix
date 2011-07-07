<?php

/**
 * Test class for ConditionTag.
 */
class ConditionTagTest extends CubridTest {

    var $xmlPath = "data/";
    
    function ConditionTagTest(){
        $this->xmlPath = str_replace('ConditionTagTest.php', '', str_replace('\\', '/', __FILE__)) . $this->xmlPath;
    }
    
    /**
     * Tests a simple <condition> tag:
     * <condition operation="equal" column="user_id" var="user_id" />
     */
    function testConditionStringWithArgument(){
            $xml_file = $this->xmlPath . "condition1.xml";
            $xml_obj = Helper::getXmlObject($xml_file);
            $tag = new ConditionTag($xml_obj->condition);

            $expected = "new Condition('\"user_id\"',\$user_id_argument,\"equal\")";
            $actual = $tag->getConditionString();
            $this->assertEquals($expected, $actual);            
            
            $arguments = $tag->getArguments();
            $this->assertEquals(1, count($arguments));
    }	    
    
    /**
     * Tests a condition tag for joins - that uses no argument
     * <condition operation="equal" column="comments.user_id" default="member.user_id" filter="userid" />
     */
    function testConditionStringWithoutArgument(){
            $xml_file = $this->xmlPath . "condition3.xml";
            $xml_obj = Helper::getXmlObject($xml_file);
            $tag = new ConditionTag($xml_obj->condition);

            $expected = "new Condition('\"comments\".\"user_id\"','\"member\".\"user_id\"',\"equal\")";
            $actual = $tag->getConditionString();
            $this->assertEquals($expected, $actual);            
            
            $arguments = $tag->getArguments();
            $this->assertEquals(0, count($arguments));            
    }	
    
    
    /**
     * Tests a <condition> tag with pipe:
     * <condition operation="equal" column="type" var="type" notnull="notnull" pipe="and" />
     */
    function testConditionStringWithPipe(){
            $xml_file = $this->xmlPath . "condition2.xml";
            $xml_obj = Helper::getXmlObject($xml_file);
            $tag = new ConditionTag($xml_obj->condition);

            $expected = "new Condition('\"type\"',\$type_argument,\"equal\", 'and')";
            $actual = $tag->getConditionString();
            $this->assertEquals($expected, $actual);            
            
            $arguments = $tag->getArguments();
            $this->assertEquals(1, count($arguments));
    }	        

}

?>
