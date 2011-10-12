<?php
	require(_XE_PATH_ . 'test-phpUnit/config/config.inc.php');

	class MysqlSelectTest extends MysqlTest {

                function _test($xml_file, $argsString, $expected, $columnList = null){
                    $this->_testQuery($xml_file, $argsString, $expected, 'getSelectSql', $columnList);
		}

                function testConditionWithVarAndColumnDefaultValue_WithoutArgument(){
			$xml_file = _XE_PATH_ . "modules/resource/queries/getLatestItem.xml";
			$argsString = '$args->item_srl = "";';
			$expected = 'select `package`.`module_srl` as `module_srl`
                                            , `package`.`status` as `status`
                                            , `package`.`category_srl` as `category_srl`
                                            , `package`.`member_srl` as `member_srl`
                                            , `package`.`package_srl` as `package_srl`
                                            , `package`.`path` as `path`
                                            , `package`.`license` as `license`
                                            , `package`.`title` as `title`
                                            , `package`.`homepage` as `homepage`
                                            , `package`.`description` as `package_description`
                                            , `package`.`voter` as `package_voter`
                                            , `package`.`voted` as `package_voted`
                                            , `package`.`downloaded` as `package_downloaded`
                                            , `package`.`regdate` as `package_regdate`
                                            , `package`.`last_update` as `package_last_update`
                                            , `member`.`nick_name` as `nick_name`
                                            , `member`.`user_id` as `user_id`
                                            , `item`.`item_srl` as `item_srl`
                                            , `item`.`document_srl` as `document_srl`
                                            , `item`.`file_srl` as `item_file_srl`
                                            , `item`.`screenshot_url` as `item_screenshot_url`
                                            , `item`.`version` as `item_version`
                                            , `item`.`voter` as `item_voter`
                                            , `item`.`voted` as `item_voted`
                                            , `item`.`downloaded` as `item_downloaded`
                                            , `item`.`regdate` as `item_regdate`
                                     from `xe_resource_packages` as `package`
                                        , `xe_member` as `member`
                                        , `xe_resource_items` as `item`
                                     where `package`.`member_srl` = `member`.`member_srl`
                                        and `item`.`item_srl` = `package`.`latest_item_srl`';
			$this->_test($xml_file, $argsString, $expected);
		}

		function testConditionWithVarAndColumnDefaultValue_WithArgument(){
			$xml_file = _XE_PATH_ . "modules/resource/queries/getLatestItem.xml";
			$argsString = '$args->item_srl = "10";';
			$expected = 'select `package`.`module_srl` as `module_srl`
                                            , `package`.`status` as `status`
                                            , `package`.`category_srl` as `category_srl`
                                            , `package`.`member_srl` as `member_srl`
                                            , `package`.`package_srl` as `package_srl`
                                            , `package`.`path` as `path`
                                            , `package`.`license` as `license`
                                            , `package`.`title` as `title`
                                            , `package`.`homepage` as `homepage`
                                            , `package`.`description` as `package_description`
                                            , `package`.`voter` as `package_voter`
                                            , `package`.`voted` as `package_voted`
                                            , `package`.`downloaded` as `package_downloaded`
                                            , `package`.`regdate` as `package_regdate`
                                            , `package`.`last_update` as `package_last_update`
                                            , `member`.`nick_name` as `nick_name`
                                            , `member`.`user_id` as `user_id`
                                            , `item`.`item_srl` as `item_srl`
                                            , `item`.`document_srl` as `document_srl`
                                            , `item`.`file_srl` as `item_file_srl`
                                            , `item`.`screenshot_url` as `item_screenshot_url`
                                            , `item`.`version` as `item_version`
                                            , `item`.`voter` as `item_voter`
                                            , `item`.`voted` as `item_voted`
                                            , `item`.`downloaded` as `item_downloaded`
                                            , `item`.`regdate` as `item_regdate`
                                     from `xe_resource_packages` as `package`
                                        , `xe_member` as `member`
                                        , `xe_resource_items` as `item`
                                     where `package`.`member_srl` = `member`.`member_srl`
                                        and `item`.`item_srl` = 10';
			$this->_test($xml_file, $argsString, $expected);
		}

                function testSubstring(){
			$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/substring.xml";
			$argsString = '$args->var_start_mmdd = "1102"; ';
			$expected = 'select * from `xe_member` as `member` where substr(`extra_vars_t1`.`value`,5,4) >= 1102';
			$this->_test($xml_file, $argsString, $expected);
                }

                function testResource_getLatestItemList(){
			$xml_file = _XE_PATH_ . "modules/resource/queries/getLatestItemList.xml";
			$argsString = '';
			$expected = 'select `package`.`module_srl` as `module_srl`
                                            , `package`.`status` as `status`
                                            , `package`.`category_srl` as `category_srl`
                                            , `package`.`member_srl` as `member_srl`
                                            , `package`.`package_srl` as `package_srl`
                                            , `package`.`path` as `path`
                                            , `package`.`license` as `license`
                                            , `package`.`title` as `title`
                                            , `package`.`homepage` as `homepage`
                                            , `package`.`description` as `package_description`
                                            , `package`.`voter` as `package_voter`
                                            , `package`.`voted` as `package_voted`
                                            , `package`.`downloaded` as `package_downloaded`
                                            , `package`.`regdate` as `package_regdate`
                                            , `package`.`last_update` as `package_last_update`
                                            , `member`.`nick_name` as `nick_name`
                                            , `member`.`user_id` as `user_id`
                                            , `item`.`item_srl` as `item_srl`
                                            , `item`.`document_srl` as `item_document_srl`
                                            , `item`.`file_srl` as `item_file_srl`
                                            , `item`.`screenshot_url` as `item_screenshot_url`
                                            , `item`.`version` as `item_version`
                                            , `item`.`voter` as `item_voter`
                                            , `item`.`voted` as `item_voted`
                                            , `item`.`downloaded` as `item_downloaded`
                                            , `item`.`regdate` as `item_regdate`
                                            , `files`.`source_filename` as `source_filename`
                                            , `files`.`sid` as `sid`
                                       from `xe_resource_packages` as `package`
                                            , `xe_member` as `member`
                                            , `xe_resource_items` as `item`
                                            , `xe_files` as `files`
                                       where (`package`.`status` = \'accepted\'
                                                and `package`.`member_srl` = `member`.`member_srl`
                                                and `item`.`item_srl` = `package`.`latest_item_srl`
                                                and `package`.`update_order` <= 0
                                                and `files`.`file_srl` = `item`.`file_srl`)
                                             and `package`.`update_order` <= 2100000000
                                       order by `package`.`update_order` asc
                                       limit 0, 20';
			$this->_test($xml_file, $argsString, $expected);
		}

                function test_Syndication_getGrantedModules(){
			$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/syndication.getGrantedModules.xml";
			$argsString = '';
			$expected = 'select `module_srl`
                                        from `xe_module_grants` as `module_grants`
                                        where `name` in (\'access\',\'view\',\'list\')
                                            and (`group_srl` >= 1 or `group_srl` = -1 or `group_srl` = -2) group by `module_srl`';
			$this->_test($xml_file, $argsString, $expected);
                }
	}