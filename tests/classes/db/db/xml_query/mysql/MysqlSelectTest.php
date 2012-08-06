<?php

class MysqlSelectTest extends MysqlTest {

	function _test($xml_file, $argsString, $expected, $columnList = NULL) {
		$this->_testQuery($xml_file, $argsString, $expected, 'getSelectSql', $columnList);
	}

	function testConditionWithVarAndColumnDefaultValue_WithoutArgument() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/resource.getLatestItem.xml";
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

	function testConditionWithVarAndColumnDefaultValue_WithArgument() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/resource.getLatestItem.xml";
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

	function testSubstring() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/substring.xml";
		$argsString = '$args->var_start_mmdd = "1102"; ';
		$expected = 'select * from `xe_member` as `member` where substr(`extra_vars_t1`.`value`,5,4) >= 1102';
		$this->_test($xml_file, $argsString, $expected);
	}

	function testResource_getLatestItemList() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/resource.getLatestItemList.xml";
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

	function test_Syndication_getGrantedModules() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/syndication.getGrantedModules.xml";
		$argsString = '';
		$expected = 'select `module_srl`
                                        from `xe_module_grants` as `module_grants`
                                        where `name` in (\'access\',\'view\',\'list\')
                                            and (`group_srl` >= 1 or `group_srl` = -1 or `group_srl` = -2) group by `module_srl`';
		$this->_test($xml_file, $argsString, $expected);
	}

	function test_Like_Clause() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/opage.getOpageList.like.xml";
		$argsString = '$args->s_mid = "test";';
		$expected = 'select *
                                        from `xe_modules` as `modules`
                                        where `module` = \'opage\'
                                            and (`mid` like \'%test%\')
                                        order by `module_srl` desc
                                        limit 0, 20';
		$this->_test($xml_file, $argsString, $expected);
	}

	function test_NotLike_Clause() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/opage.getOpageList.notlike.xml";
		$argsString = '$args->s_mid = "test";';
		$expected = 'select *
                                        from `xe_modules` as `modules`
                                        where `module` = \'opage\'
                                            and (`mid` not like \'%test%\')
                                        order by `module_srl` desc
                                        limit 0, 20';
		$this->_test($xml_file, $argsString, $expected);
	}

	function test_NotLikeTail_Clause() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/opage.getOpageList.notliketail.xml";
		$argsString = '$args->s_mid = "test";';
		$expected = 'select *
                                        from `xe_modules` as `modules`
                                        where `module` = \'opage\'
                                            and (`mid` not like \'%test\')
                                        order by `module_srl` desc
                                        limit 0, 20';
		$this->_test($xml_file, $argsString, $expected);
	}

	function test_NotLikePrefix_Clause() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/opage.getOpageList.notlikeprefix.xml";
		$argsString = '$args->s_mid = "test";';
		$expected = 'select *
                                        from `xe_modules` as `modules`
                                        where `module` = \'opage\'
                                            and (`mid` not like \'test%\')
                                        order by `module_srl` desc
                                        limit 0, 20';
		$this->_test($xml_file, $argsString, $expected);
	}

	function test_WidgetsNewestDocument_getNewestDocuments() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/widgets.newest_document.getNewestDocuments.xml";
		$argsString = '$args->module_srl = "566036,3777868";';
		$expected = 'select `modules`.`site_srl` as `site_srl`
                                        , `modules`.`mid` as `mid`
                                        , `documents`.*
                                     from `xe_modules` as `modules`
                                        , `xe_documents` as `documents`
                                     where (
										`documents`.`module_srl` not in (0) 
                                        and `documents`.`module_srl` in (566036,3777868)
                                        and `modules`.`module_srl` = `documents`.`module_srl`)
                                        and `documents`.`list_order` <= 2100000000
                                     order by `documents`.`list_order` asc
                                     limit 20';

		$this->_test($xml_file, $argsString, $expected);
	}

	function test_homepage_getNewestComments() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/homepage.getNewestComments.xml";
		$argsString = ';';
		$expected = 'select `sites`.`domain` as `domain`
                                                , `comments`.*
                                             from `xe_homepages` as `homepages`
                                             , `xe_sites` as `sites`
                                             , `xe_comments` as `comments`
                                             , `xe_modules` as `modules`
                                                left join `xe_module_grants` as `module_grants`
                                                    on `module_grants`.`module_srl` = `modules`.`module_srl`
                                                        and `module_grants`.`name` = \'access\'
                                                        and `module_grants`.`group_srl` not in (0,-1)
                                                where (`homepages`.`site_srl` = `sites`.`site_srl`
                                                    and `homepages`.`site_srl` = `modules`.`site_srl`
                                                    and `comments`.`module_srl` = `modules`.`module_srl`
                                                    and `module_grants`.`group_srl` is null)
                                                    and `comments`.`list_order` <= 2100000000
                                                order by `comments`.`list_order` asc limit 0, 5';
		$this->_test($xml_file, $argsString, $expected);
	}

	function test_distinct_outer_join() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/distinct_outer_join.xml";
		$argsString = '$args->site_srl = 0;';
		$expected = 'select distinct `modules`.`module_srl` as `module_site_srl` 
												from `xe_sites` as `sites` 
													left join `xe_modules` as `modules` on `modules`.`module_srl` = `sites`.`index_module_srl` 
												where `sites`.`site_srl` = 0';
		$this->_test($xml_file, $argsString, $expected);
	}

	function test_getDocumentListWithinComment() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/document.getDocumentListWithinComment.xml";
		$argsString = '$args->module_srl = 19778968; 
										$args->s_comment = "dfsds";
										$args->statusList = "PUBLIC, SECRET";
										';
		$expected = 'select `documents`.`document_srl`, `documents`.`list_order` 
										from `xe_documents` as `documents`
											, `xe_comments` as `comments` 
										where (`documents`.`module_srl` in (19778968) 
													and `documents`.`document_srl` = `comments`.`document_srl` 
													and `documents`.`status` in (\'public\',\'secret\') 
													and `comments`.`content` like \'%dfsds%\') 
													and `comments`.`list_order` <= 2100000000 
										group by `documents`.`document_srl` 
										order by `comments`.`list_order` asc 
										limit 0, 20';
		$this->_test($xml_file, $argsString, $expected);
	}

	/**
	 * Issue 2064
	 * Queries should support both notin and not_in as valid operations
	 */
	function test_not_in() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/document.getNewestDocuments.xml";
		$argsString = '$args->module_srl = "345709,345710,345711,345728,345707,345670,345667,49113,16551,345679,50394,350665,345680,381846,381852,381917,345708,349028,345666,17173,49117,345671,345714,345665,349893,345696,345713,351967,330919,345685,16754,349027,348787,345672,350239,345697,345674,291882,345678,345729,345675,345721,345676,381867,294605,381864,345673,355113,353624,345681,345683,345668,345677,12424,158716,47498,101835,273679,142558,13818,12311,8723,78670,18919,365075,13833,14293,15891,27823,14291,177818,81000,11788,18918,13859,14102,14136,255783,134367,385619,317170,330312";
										$args->sort_index = "documents.list_order";
										$args->order_type = "asc";
										$args->list_count = 10;
										';
		$expected = 'select `modules`.`site_srl` as `site_srl`, `modules`.`mid` as `mid`, `documents`.* from `xe_modules` as `modules`, `xe_documents` as `documents` where (`documents`.`module_srl` not in (0) and `documents`.`module_srl` in (345709,345710,345711,345728,345707,345670,345667,49113,16551,345679,50394,350665,345680,381846,381852,381917,345708,349028,345666,17173,49117,345671,345714,345665,349893,345696,345713,351967,330919,345685,16754,349027,348787,345672,350239,345697,345674,291882,345678,345729,345675,345721,345676,381867,294605,381864,345673,355113,353624,345681,345683,345668,345677,12424,158716,47498,101835,273679,142558,13818,12311,8723,78670,18919,365075,13833,14293,15891,27823,14291,177818,81000,11788,18918,13859,14102,14136,255783,134367,385619,317170,330312) and `modules`.`module_srl` = `documents`.`module_srl`) and `documents`.`list_order` <= 2100000000 order by `documents`.`list_order` asc limit 10';
		$this->_test($xml_file, $argsString, $expected);
	}

	/**
	 * Issue 2064
	 * Query condition should be ignored if operation is invalid
	 */
	function test_invalid_condition_operation() {
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/document.getNewestDocumentsInvalid.xml";
		$argsString = '$args->module_srl = "345709,345710,345711,345728,345707,345670,345667,49113,16551,345679,50394,350665,345680,381846,381852,381917,345708,349028,345666,17173,49117,345671,345714,345665,349893,345696,345713,351967,330919,345685,16754,349027,348787,345672,350239,345697,345674,291882,345678,345729,345675,345721,345676,381867,294605,381864,345673,355113,353624,345681,345683,345668,345677,12424,158716,47498,101835,273679,142558,13818,12311,8723,78670,18919,365075,13833,14293,15891,27823,14291,177818,81000,11788,18918,13859,14102,14136,255783,134367,385619,317170,330312";
										$args->sort_index = "documents.list_order";
										$args->order_type = "asc";
										$args->list_count = 10;
										';
		$expected = 'select `modules`.`site_srl` as `site_srl`, `modules`.`mid` as `mid`, `documents`.* from `xe_modules` as `modules`, `xe_documents` as `documents` where (`documents`.`module_srl` in (345709,345710,345711,345728,345707,345670,345667,49113,16551,345679,50394,350665,345680,381846,381852,381917,345708,349028,345666,17173,49117,345671,345714,345665,349893,345696,345713,351967,330919,345685,16754,349027,348787,345672,350239,345697,345674,291882,345678,345729,345675,345721,345676,381867,294605,381864,345673,355113,353624,345681,345683,345668,345677,12424,158716,47498,101835,273679,142558,13818,12311,8723,78670,18919,365075,13833,14293,15891,27823,14291,177818,81000,11788,18918,13859,14102,14136,255783,134367,385619,317170,330312) and `modules`.`module_srl` = `documents`.`module_srl`) and `documents`.`list_order` <= 2100000000 order by `documents`.`list_order` asc limit 10';
		$this->_test($xml_file, $argsString, $expected);
	}

	/**
	 * Issue 2114
	 * 'Null' operation is ignored
	 */
	function test_null()
	{
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/page.pageTypeNullCheck.xml";
		$argsString = '	';
		$expected = 'select `m`.`module_srl`, `m`.`mid`, `ev`.`value`
						from `xe_modules` as `m`
							left join `xe_module_extra_vars` as `ev`
											on `ev`.`name` = \'page_type\'
											and `m`.`module_srl` = `ev`.`module_srl`
						where `m`.`module` = \'page\'
							and `ev`.`value` is null
						';
		$this->_test($xml_file, $argsString, $expected);
	}

	/**
	 * Issue 2114
	 * 'Notnull' operation is ignored
	 */
	function test_notnull()
	{
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/page.pageTypeNotNullCheck.xml";
		$argsString = '	';
		$expected = 'select `m`.`module_srl`, `m`.`mid`, `ev`.`value`
						from `xe_modules` as `m`
							left join `xe_module_extra_vars` as `ev`
											on `ev`.`name` = \'page_type\'
											and `m`.`module_srl` = `ev`.`module_srl`
						where `m`.`module` = \'page\'
							and `ev`.`value` is not null
						';
		$this->_test($xml_file, $argsString, $expected);
	}

	function testLikeWithDot()
	{
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/likewithdot.xml";
		$argsString = '';
		$expected = 'select *
						from `xe_layouts` as `layouts`
							where `site_srl` = 0
								and `layout_type` = \'p\'
								and `layout` like \'%.%\'';
		$this->_test($xml_file, $argsString, $expected);

	}

	/**
	 * Issue 2213
	 * http://code.google.com/p/xe-core/issues/detail?id=2213
	 *
	 * @author Corina Udrescu (dev@xpressengine.org)
	 */
	function testSumInCondition()
	{
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/sumInCondition.xml";
		$argsString = '';
		$expected = 'select *
					 from `xe_test11` as `a`
					 where `site_srl` = 0
					 	and `price` <= `a`.`pa_1`+`a`.`pa_2`';
		$this->_test($xml_file, $argsString, $expected);

	}
}