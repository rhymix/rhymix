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
                        $expected = 'update `xe_documents` as `documents` set `status` = \'secret\' where `is_secret` = \'y\'';
                        $this->_test($xml_file, $argsString, $expected);
                }

                /**
                 * Issue 388 - Query cache error related table alias
                 * http://code.google.com/p/xe-core/issues/detail?id=388
                 */
                function test_importer_updateDocumentSync(){
			$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/importer.updateDocumentSync.xml";
			$argsString = '';
			$expected = 'UPDATE `xe_documents` as `documents`, `xe_member` as `member`
                            SET `documents`.`member_srl` = `member`.`member_srl`
                            WHERE `documents`.`user_id` = `member`.`user_id`
                        ';
			$this->_test($xml_file, $argsString, $expected);
                }

                function test_document_updateItemDownloadedCount(){
			$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/document.updateItemDownloadedCount.xml";
			$argsString = '$args->module_srl = 10; $args->package_srl = 11; $args->item_srl = 12;';
			$expected = 'update `xe_resource_items` as `resource_items`
                                        set `downloaded` = `downloaded` + 1
                                        where `module_srl` = 10
                                            and `package_srl` = 11
                                            and `item_srl` = 12
                        ';
			$this->_test($xml_file, $argsString, $expected);
                }

                function test_menu_updateMenuItemListorder(){
			$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/menu.updateMenuItemListorder.xml";
			$argsString = '$args->menu_srl = 10; $args->parent_srl = 11; $args->listorder = 12;';
			$expected = 'update `xe_menu_item` as `menu_item`
                                        set `listorder` = `listorder` - 1
                                        where `menu_srl` = 10
                                            and `parent_srl` = 11
                                            and `listorder` <= 12';
			$this->_test($xml_file, $argsString, $expected);
                }

                function test_communication_setMessageReaded(){
			$xml_file = _XE_PATH_ . "modules/communication/queries/setMessageReaded.xml";
			$argsString = '$args->message_srl = 339321; $args->related_srl = 339321;';
			$expected = 'update `xe_member_message` as `member_message`
                                        set `readed` = \'y\'
                                            , `readed_date` = ' . date("YmdHis") . '
                                        where `message_srl` = 339321 or `related_srl` = 339321';
			$this->_test($xml_file, $argsString, $expected);
                }

                function test_session_updateSession(){
			$xml_file = _XE_PATH_ . "modules/session/queries/updateSession.xml";
			$argsString = '$args->session_key = 339321; $args->val = "yuhuu";';
			$expected = 'update `xe_session` as `session`
                                        set `member_srl` = 0, `val` = \'yuhuu\'
                                        , `last_update` = ' . date("YmdHis") . '
                                       where `session_key` = \'339321\'';
			$this->_test($xml_file, $argsString, $expected);
                }
	}