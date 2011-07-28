<?php
	require(_XE_PATH_ . 'test-phpUnit/config/config.inc.php');

	class CubridDeleteTest extends CubridTest {

		function _test($xml_file, $argsString, $expected){
                    $this->_testQuery($xml_file, $argsString, $expected,  'getDeleteSql');
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