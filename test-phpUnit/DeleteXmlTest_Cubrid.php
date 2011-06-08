<?php
	require('config.inc.php');

	class DeleteXmlTest_Cubrid extends PHPUnit_Framework_TestCase {

		function _test($xml_file, $argsString, $expected){
			$tester = new QueryTester();
			$outputString = $tester->getNewParserOutputString($xml_file, '"', $argsString, 'cubrid');
			$output = eval($outputString);
			
			if(!is_a($output, 'Query')){
				if(!$output->toBool()) $querySql = "Date incorecte! Query-ul nu a putut fi executat.";
			}else {
				//$db = new DBCubrid();
				$db = &DB::getInstance('cubrid');
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
			$expected = 'delete from "xe_action_forward" as "action_forward" 
						where "module" = \'page\' 
							and "type" = \'page\' 
							and "act" = \'tata\'';
			$this->_test($xml_file, $argsString, $expected);			
		}
				
//	$queryTester->test_admin_deleteActionForward();
//	$queryTester->test_module_insertModule();
		
		
	}