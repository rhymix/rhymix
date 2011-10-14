<?php
	require(_XE_PATH_ . 'test-phpUnit/config/config.inc.php');

	class SqliteDeleteTest extends SqliteTest {

                function _test($xml_file, $argsString, $expected, $columnList = null){
                    $this->_testQuery($xml_file, $argsString, $expected, 'getDeleteSql', $columnList);
		}

                function testDeleteIsGeneratedWithoutAlias(){
			$xml_file = _TEST_PATH_ . "db/xml_query/sqlite/data/module.deleteModuleConfig.xml";
			$argsString = '$args->module = "comment"; $args->site_srl = 0; ';
			$expected = 'delete from "xe_module_config"
                                        where "module" = \'comment\' and "site_srl" = 0';
			$this->_test($xml_file, $argsString, $expected);
                }

	}