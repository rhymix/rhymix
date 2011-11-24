<?php

class DBTest extends PHPUnit_Framework_TestCase {

        function _testQuery($xml_file, $argsString, $expected, $methodName, $columnList = null){
                echo PHP_EOL . ' ----------------------------------- ' .PHP_EOL;
                echo $xml_file;
                echo PHP_EOL . ' ----------------------------------- ' .PHP_EOL;

                $tester = new QueryTester();
                $outputString = $tester->getNewParserOutputString($xml_file, $argsString);
                echo $outputString;
                $output = eval($outputString);

                if(!is_a($output, 'Query')){
                        if(!$output->toBool()) $querySql = "Date incorecte! Query-ul nu a putut fi executat.";
                }else {
                        $db = &DB::getInstance();
                        if($columnList) $output->setColumnList($columnList);
                        $querySql = $db->{$methodName}($output);

                        // Remove whitespaces, tabs and all
                        $querySql = Helper::cleanString($querySql);
                        $expected = Helper::cleanString($expected);
                }
                $this->assertEquals($expected, $querySql);
        }

        function _testPreparedQuery($xml_file, $argsString, $expected, $methodName, $expectedArgs = NULL){
                echo PHP_EOL . ' ----------------------------------- ' .PHP_EOL;
                echo $xml_file;
                echo PHP_EOL . ' ----------------------------------- ' .PHP_EOL;

                $tester = new QueryTester();
                $outputString = $tester->getNewParserOutputString($xml_file, $argsString);
                echo $outputString;
                $output = eval($outputString);

                if(!is_a($output, 'Query')){
                        if(!$output->toBool()) $querySql = "Date incorecte! Query-ul nu a putut fi executat.";
                }else {
                        $db = &DB::getInstance();
                        $querySql = $db->{$methodName}($output, false);
                        $queryArguments = $output->getArguments();

                        // Remove whitespaces, tabs and all
                        $querySql = Helper::cleanString($querySql);
                        $expected = Helper::cleanString($expected);
                }

                // Test
                $this->assertEquals($expected, $querySql);

                // Test query arguments
                $argCount = count($expectedArgs);
                for($i = 0; $i < $argCount; $i++){
                    $this->assertEquals($expectedArgs[$i], $queryArguments[$i]->getEscapedValue());
                }
        }

        function _testCachedOutput($expected, $actual){
            $expected = Helper::cleanString($expected);
            $actual = Helper::cleanString($actual);

            $this->assertEquals($expected, $actual);

        }
    }

?>
