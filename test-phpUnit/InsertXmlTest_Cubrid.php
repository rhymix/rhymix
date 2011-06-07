<?php
	require('config.inc.php');

	class InsertXmlTest_Cubrid extends PHPUnit_Framework_TestCase {

		function _test($xml_file, $argsString, $expected){
			$tester = new QueryTester();
			echo $xml_file . $argsString;
			$outputString = $tester->getNewParserOutputString($xml_file, '"', $argsString);
			$output = eval($outputString);

			if(!is_a($output, 'Query')){
				if(!$output->toBool()) $querySql = "Date incorecte! Query-ul nu a putut fi executat.";
			}else {
				$db = new DBCubrid();
				$querySql = $db->getInsertSql($output);
	
				// Remove whitespaces, tabs and all
				$querySql = Helper::cleanQuery($querySql);
				$expected = Helper::cleanQuery($expected);
			}

			// Test
			$this->assertEquals($expected, $querySql);
		}
			
		function test_module_insertModule(){					
			$xml_file = _XE_PATH_ . "modules/module/queries/insertModule.xml";
			$argsString = ' $args->module_category_srl = 0; 
							$args->browser_title = "test";
							$args->layout_srl = 0;
							$args->mlayout_srl = 0;
							$args->module = "page";
							$args->mid = "test";
							$args->site_srl = 0;
							$args->module_srl = 47374;';
			$expected = 'insert into "xe_modules" 
							("site_srl"
							, "module_srl"
							, "module_category_srl"
							, "mid"
							, "browser_title"
							, "layout_srl"
							, "module"
							, "is_default"
							, "open_rss"
							, "regdate"
							, "mlayout_srl"
							, "use_mobile") 
							values 
							(0
							, 47374
							, 0
							, \'test\'
							, \'test\'
							, 0
							, \'page\'
							, \'n\'
							, \'y\'
							, \''.date("YmdHis").'\'
							, 0
							, \'n\')';
			$this->_test($xml_file, $argsString, $expected);			
		}
				
//	$queryTester->test_admin_deleteActionForward();
//	$queryTester->test_module_insertModule();
		
		
	}