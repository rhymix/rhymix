<?php
	require(_XE_PATH_ . 'test-phpUnit/config/config.inc.php');

	class SqliteUpdateTest extends SqliteTest {

                function _test($xml_file, $argsString, $expected, $columnList = null){
                    $this->_testQuery($xml_file, $argsString, $expected, 'getUpdateSql', $columnList);
		}

                function testUpdateIsGeneratedWithoutAlias(){
			$xml_file = _TEST_PATH_ . "db/xml_query/sqlite/data/member.updateLastLogin.xml";
			$argsString = '$args->member_srl = 4;
                                       $args->last_login = \'20111014184010\';
                            ';
			$expected = 'UPDATE  "xe_member"
                                        SET "member_srl" = 4
                                            , "last_login" = \'20111014184010\'
                                     WHERE "member_srl" = 4';
			$this->_test($xml_file, $argsString, $expected);
                }

	}