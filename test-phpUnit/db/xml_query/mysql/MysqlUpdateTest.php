<?php
	require(_XE_PATH_ . 'test-phpUnit/config/config.inc.php');

	class MysqlUpdateTest extends MysqlTest {

                function _test($xml_file, $argsString, $expected, $columnList = null){
                    $this->_testQuery($xml_file, $argsString, $expected, 'getUpdateSql', $columnList);
		}

                function test_document_updateDocumentStatus(){
                        $xml_file = _XE_PATH_ . "modules/document/queries/updateDocumentStatus.xml";
			$argsString = '$args->is_secret = \'Y\';
                                       $args->status = \'SECRET\';
                        ';
                        $expected = 'update `xe_documents` set `status` = \'secret\' where `is_secret` = \'y\'';
                        $this->_test($xml_file, $argsString, $expected);
                }


	}