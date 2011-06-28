<?php
	require(_XE_PATH_ . 'test-phpUnit/config.inc.php');
        require(_XE_PATH_ . 'test-phpUnit/db/xml_query/cubrid/config.cubrid.inc.php');

	class CubridDeleteTest extends PHPUnit_Framework_TestCase {

		function _test($xml_file, $argsString, $expected){
			$tester = new QueryTester();
			$outputString = $tester->getNewParserOutputString($xml_file, '"', $argsString);
                        $output = eval($outputString);
			
			if(!is_a($output, 'Query')){
				if(!$output->toBool()) $querySql = "Date incorecte! Query-ul nu a putut fi executat.";
			}else {
				$db = &DB::getInstance();
                                var_dump($db);
				$querySql = $db->getDeleteSql($output);
	
				// Remove whitespaces, tabs and all
				$querySql = Helper::cleanQuery($querySql);
				$expected = Helper::cleanQuery($expected);
			}

			// Test
			$this->assertEquals($expected, $querySql);
		}
			
		function test_module_deleteActionForward(){					
			$xml_file = _XE_PATH_ . "modules/module/queries/deleteActionForward.xml";
			$argsString = '$args->module = "page";
							$args->type = "page";
							$args->act = "tata";';
			$expected = 'delete "action_forward" from "xe_action_forward" as "action_forward" 
						where "module" = \'page\' 
							and "type" = \'page\' 
							and "act" = \'tata\'';
			$this->_test($xml_file, $argsString, $expected);			
		}
	}