<?php

/**
 * Test class for QueryArgument.
 */
class QueryArgumentTest extends CubridTest {

    var $xmlPath = "data/";

    function QueryArgumentTest(){
        $this->xmlPath = str_replace('QueryArgumentTest.php', '', str_replace('\\', '/', __FILE__)) . $this->xmlPath;
    }
 }

?>
