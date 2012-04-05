<?php

	class CubridSelectTest extends CubridTest {

                function _test($xml_file, $argsString, $expected, $columnList = null){
                    $this->_testQuery($xml_file, $argsString, $expected, 'getSelectSql', $columnList);
		}

		function testSelectStar(){
			$xml_file = _XE_PATH_ . "modules/module/queries/getAdminId.xml";
			$argsString = '$args->module_srl = 10;';
			$expected = 'SELECT * FROM "xe_module_admins" as "module_admins" , "xe_member" as "member" WHERE "module_srl" = 10 and "member"."member_srl" = "module_admins"."member_srl"';
			$this->_test($xml_file, $argsString, $expected);
		}

		function testRequiredParameter(){
			$xml_file = _XE_PATH_ . "modules/module/queries/getAdminId.xml";
			$argsString = '';
			$expected = 'Date incorecte! Query-ul nu a putut fi executat.';
			$this->_test($xml_file, $argsString, $expected);
		}

		function testWithoutCategoriesTag(){
			$xml_file = _XE_PATH_ . "modules/module/queries/getModuleCategories.xml";
			$argsString = '';
			$expected = 'SELECT * FROM "xe_module_categories" as "module_categories" ORDER BY "title" asc';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_module_getDefaultModules(){
			$xml_file = _XE_PATH_ . "modules/module/queries/getDefaultModules.xml";
			$argsString = '$args->site_srl = 0;';
			$expected = 'SELECT "modules"."site_srl"
							, "modules"."module"
							, "modules"."mid"
							, "modules"."browser_title"
							, "module_categories"."title" as "category"
							, "modules"."module_srl"
						FROM "xe_modules" as "modules"
							left join "xe_module_categories" as "module_categories"
								on "module_categories"."module_category_srl" = "modules"."module_category_srl"
						WHERE "modules"."site_srl" = 0
						ORDER BY "modules"."module" asc, "module_categories"."title" asc, "modules"."mid" asc';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_module_getSiteInfo(){
			$xml_file = _XE_PATH_ . "modules/module/queries/getSiteInfo.xml";
			$argsString = '$args->site_srl = 0;';
			$expected = 'SELECT "modules"."site_srl" as "module_site_srl"
							, "modules"."module_srl" as "module_srl"
							, "modules"."module" as "module"
							, "modules"."module_category_srl" as "module_category_srl"
							, "modules"."layout_srl" as "layout_srl"
							, "modules"."mlayout_srl" as "mlayout_srl"
							, "modules"."use_mobile" as "use_mobile"
							, "modules"."menu_srl" as "menu_srl"
							, "modules"."mid" as "mid"
							, "modules"."skin" as "skin"
							, "modules"."mskin" as "mskin"
							, "modules"."browser_title" as "browser_title"
							, "modules"."description" as "description"
							, "modules"."is_default" as "is_default"
							, "modules"."content" as "content"
							, "modules"."mcontent" as "mcontent"
							, "modules"."open_rss" as "open_rss"
							, "modules"."header_text" as "header_text"
							, "modules"."footer_text" as "footer_text"
							, "modules"."regdate" as "regdate"
							, "sites"."site_srl" as "site_srl"
							, "sites"."domain" as "domain"
							, "sites"."index_module_srl" as "index_module_srl"
							, "sites"."default_language" as "default_language"
						FROM "xe_sites" as "sites"
							left join "xe_modules" as "modules" on  "modules"."module_srl" = "sites"."index_module_srl"
						WHERE  "sites"."site_srl" = 0   ';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_addon_getAddonInfo(){
			$xml_file = _XE_PATH_ . "modules/addon/queries/getAddonInfo.xml";
			$argsString = '$args->addon = "captcha";';
			$expected = 'SELECT *
						FROM "xe_addons" as "addons"
						WHERE  "addon" = \'captcha\' ';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_addon_getAddons(){
			$xml_file = _XE_PATH_ . "modules/addon/queries/getAddons.xml";
			$argsString = '';
			$expected = 'SELECT *
						FROM "xe_addons" as "addons"
						ORDER BY "addon" asc';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_admin_getCommentCount(){
			$xml_file = _XE_PATH_ . "modules/admin/queries/getCommentCount.xml";
			$argsString = '';
			$expected = 'SELECT count(*) as "count"
						FROM "xe_comments" as "comments"';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_admin_getCommentDeclaredStatus(){
			$xml_file = _XE_PATH_ . "modules/admin/queries/getCommentDeclaredStatus.xml";
			$argsString = '$args->date = "20110411";';
			$expected = 'SELECT substr("regdate",1,8) as "date", count(*) as "count"
				FROM "xe_comment_declared_log" as "comment_declared_log"
				WHERE  "regdate" >= \'20110411\'
				GROUP BY substr("regdate",1,8)
				ORDER BY substr("regdate",1,8) asc limit 2';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_member_getAutoLogin(){
			$xml_file = _XE_PATH_ . "modules/member/queries/getAutoLogin.xml";
			$argsString = '$args->autologin_key = 10;';
			$expected = 'SELECT "member"."user_id" as "user_id"
							, "member"."password" as "password"
							, "member_autologin"."autologin_key" as "autologin_key"
						FROM "xe_member" as "member" , "xe_member_autologin" as "member_autologin"
						WHERE  "member_autologin"."autologin_key" = \'10\'
							and "member"."member_srl" = "member_autologin"."member_srl"';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_opage_getOpageList(){
			$xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/opage.getOpageList.xml";
			$argsString = '$args->s_title = "yuhuu";
							$args->module = \'opage\';';
			$expected = 'SELECT *
						FROM "xe_modules" as "modules"
						WHERE  "module" = \'opage\' and ("title" like \'%yuhuu%\')
						ORDER BY "module_srl" desc
						LIMIT 0, 20';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_syndication_getGrantedModules(){
			$xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/syndication.getGrantedModules.xml";
			$argsString = '$args->module_srl = 12;
                                       $args->name = array(\'access\',\'view\',\'list\');';
			$expected = 'select "module_srl"
						 from "xe_module_grants" as "module_grants"
						 where "name" in (\'access\',\'view\',\'list\')
						 	and ("group_srl" >= 1
						 			or "group_srl" = -1
						 			or "group_srl" = -2)
						 group by "module_srl"';
			$this->_test($xml_file, $argsString, $expected);
		}

                function test_document_getDocumentList(){
                    $xml_file = _XE_PATH_ . "modules/document/queries/getDocumentList.xml";
                    $argsString = '$args->sort_index = \'list_order\';
                                    $args->order_type = \'asc\';
                                    $args->page = 1;
                                    $args->list_count = 30;
                                    $args->page_count = 10;
                                    $args->s_member_srl = 4;';
                    $expected = 'select *
                                    from "xe_documents" as "documents"
                                    where ("member_srl" = 4)
                                        and "list_order" <= 2100000000
                                    order by "list_order" asc
                                    limit 0, 30';
                    $this->_test($xml_file, $argsString, $expected);


                }

                /**
                 * Test column list
                 */
                function test_session_getSession(){
                    $xml_file = _XE_PATH_ . "modules/session/queries/getSession.xml";
                    $argsString = '$args->session_key = \'session_key\';';
                    $columnList = array('session_key', 'cur_mid', 'val');

                    $expected = 'select "session_key", "cur_mid", "val"
                                 from "xe_session" as "session"
                                 where "session_key" = \'session_key\'';

                    $this->_test($xml_file, $argsString, $expected, $columnList);
                }

                function test_module_getModuleInfoByDocument(){
                    $xml_file = _XE_PATH_ . "modules/module/queries/getModuleInfoByDocument.xml";
                    $argsString = '$args->document_srl = 10;';
                    $expected = 'SELECT "modules".*
                        FROM "xe_modules" as "modules"
                                , "xe_documents" as "documents"
                        WHERE  "documents"."document_srl" = 10
                                and "modules"."module_srl" = "documents"."module_srl"';
                    $this->_test($xml_file, $argsString, $expected);
                }

                function test_member_getMemberList(){
                    $xml_file = _XE_PATH_ . "modules/member/queries/getMemberList.xml";
                    $argsString = '$args->is_admin = \'\';
                                    $args->is_denied = \'\';
                                    $args->sort_index = "list_order";
                                    $args->sort_order = \'asc\';
                                    $args->list_count = 40;
                                    $args->page_count = 10;';
                    $expected = 'select *
                                    from "xe_member" as "member"
                                    where "list_order" <= 2100000000
                                 order by "list_order" asc
                                 limit 0, 40';
                    $this->_test($xml_file, $argsString, $expected);
                }

                /**
                 * Tests "not in" query condition
                 * Query argument is a single value - not in (12)
                 */
                function test_module_getModules_Notin_Single_Value(){
                    $xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/syndication.getModules.xml";
                    $argsString = '$args->except_module_srls = 12;';
                    $expected = 'select "modules"."site_srl" as "site_srl"
                                    , "modules"."module_srl" as "module_srl"
                                    , "sites"."domain" as "domain"
                                    , "modules"."mid" as "mid"
                                    , "modules"."module" as "module"
                                    , "modules"."browser_title" as "browser_title"
                                    , "modules"."description" as "description"
                                from "xe_sites" as "sites"
                                    , "xe_modules" as "modules"
                                        left join "xe_syndication_except_modules" as "except_modules"
                                            on "modules"."module_srl" = "except_modules"."module_srl"
                                where "modules"."module_srl" not in (12)
                                    and "sites"."site_srl" = "modules"."site_srl"
                                    and "except_modules"."module_srl" is null';
                    $this->_test($xml_file, $argsString, $expected);
                }

                function test_module_getModules_Notin_Multiple_Value_String(){
                    $xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/syndication.getModules.xml";
                    $argsString = '$args->except_module_srls = "12, 13, 14";';
                    $expected = 'select "modules"."site_srl" as "site_srl"
                                    , "modules"."module_srl" as "module_srl"
                                    , "sites"."domain" as "domain"
                                    , "modules"."mid" as "mid"
                                    , "modules"."module" as "module"
                                    , "modules"."browser_title" as "browser_title"
                                    , "modules"."description" as "description"
                                from "xe_sites" as "sites"
                                    , "xe_modules" as "modules"
                                        left join "xe_syndication_except_modules" as "except_modules"
                                            on "modules"."module_srl" = "except_modules"."module_srl"
                                where "modules"."module_srl" not in (12,13,14)
                                    and "sites"."site_srl" = "modules"."site_srl"
                                    and "except_modules"."module_srl" is null';
                    $this->_test($xml_file, $argsString, $expected);
                }

                function test_module_getModules_Notin_Multiple_Value_Array(){
                    $xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/syndication.getModules.xml";
                    $argsString = '$args->except_module_srls = array(12, 13, 14);';
                    $expected = 'select "modules"."site_srl" as "site_srl"
                                    , "modules"."module_srl" as "module_srl"
                                    , "sites"."domain" as "domain"
                                    , "modules"."mid" as "mid"
                                    , "modules"."module" as "module"
                                    , "modules"."browser_title" as "browser_title"
                                    , "modules"."description" as "description"
                                from "xe_sites" as "sites"
                                    , "xe_modules" as "modules"
                                        left join "xe_syndication_except_modules" as "except_modules"
                                            on "modules"."module_srl" = "except_modules"."module_srl"
                                where "modules"."module_srl" not in (12,13,14)
                                    and "sites"."site_srl" = "modules"."site_srl"
                                    and "except_modules"."module_srl" is null';
                    $this->_test($xml_file, $argsString, $expected);
                }

               function test_module_getModules_In_Single_Value(){
                    $xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/syndication.getModules.xml";
                    $argsString = '$args->module_srls = 12;';
                    $expected = 'select "modules"."site_srl" as "site_srl"
                                    , "modules"."module_srl" as "module_srl"
                                    , "sites"."domain" as "domain"
                                    , "modules"."mid" as "mid"
                                    , "modules"."module" as "module"
                                    , "modules"."browser_title" as "browser_title"
                                    , "modules"."description" as "description"
                                from "xe_sites" as "sites"
                                    , "xe_modules" as "modules"
                                        left join "xe_syndication_except_modules" as "except_modules"
                                            on "modules"."module_srl" = "except_modules"."module_srl"
                                where "modules"."module_srl" in (12)
                                    and "sites"."site_srl" = "modules"."site_srl"
                                    and "except_modules"."module_srl" is null';
                    $this->_test($xml_file, $argsString, $expected);
                }

                function test_module_getModules_In_Multiple_Value_String(){
                    $xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/syndication.getModules.xml";
                    $argsString = '$args->module_srls = "12, 13, 14";';
                    $expected = 'select "modules"."site_srl" as "site_srl"
                                    , "modules"."module_srl" as "module_srl"
                                    , "sites"."domain" as "domain"
                                    , "modules"."mid" as "mid"
                                    , "modules"."module" as "module"
                                    , "modules"."browser_title" as "browser_title"
                                    , "modules"."description" as "description"
                                from "xe_sites" as "sites"
                                    , "xe_modules" as "modules"
                                        left join "xe_syndication_except_modules" as "except_modules"
                                            on "modules"."module_srl" = "except_modules"."module_srl"
                                where "modules"."module_srl" in (12,13,14)
                                    and "sites"."site_srl" = "modules"."site_srl"
                                    and "except_modules"."module_srl" is null';
                    $this->_test($xml_file, $argsString, $expected);
                }

                function test_module_getModules_In_Multiple_Value_Array(){
                    $xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/syndication.getModules.xml";
                    $argsString = '$args->module_srls = array(12, 13, 14);';
                    $expected = 'select "modules"."site_srl" as "site_srl"
                                    , "modules"."module_srl" as "module_srl"
                                    , "sites"."domain" as "domain"
                                    , "modules"."mid" as "mid"
                                    , "modules"."module" as "module"
                                    , "modules"."browser_title" as "browser_title"
                                    , "modules"."description" as "description"
                                from "xe_sites" as "sites"
                                    , "xe_modules" as "modules"
                                        left join "xe_syndication_except_modules" as "except_modules"
                                            on "modules"."module_srl" = "except_modules"."module_srl"
                                where "modules"."module_srl" in (12,13,14)
                                    and "sites"."site_srl" = "modules"."site_srl"
                                    and "except_modules"."module_srl" is null';
                    $this->_test($xml_file, $argsString, $expected);
                }

                function test_module_getModuleSrlByMid_In_Multiple_Value_Array_Strings(){
                    $xml_file = _XE_PATH_ . "modules/module/queries/getModuleSrlByMid.xml";
                    $argsString = '$args->mid = "\'mid1\', \'mid2\'";';
                    $expected = 'select "module_srl" from "xe_modules" as "modules" where "mid" in (\'mid1\',\'mid2\')';
                    $this->_test($xml_file, $argsString, $expected);
                }


               function test_file_getFileList_In_Empty_Array_Value(){
                    $xml_file = _XE_PATH_ . "modules/file/queries/getFileList.xml";
                    $argsString = '$args->exclude_module_srl = 12; $args->s_module_srl = array(); ';
                    $expected = 'select "files".*
                                    from "xe_files" as "files"
                                        left join "xe_member" as "member" on "files"."member_srl" = "member"."member_srl"
                                    where "files"."module_srl" not in (12)
                                    order by "files"."file_srl" desc
                                    limit 0, 20';
                    $this->_test($xml_file, $argsString, $expected);
                }

               function test_file_getFileList_Not_In_Empty_String_Value(){
                    $xml_file = _XE_PATH_ . "modules/file/queries/getFileList.xml";
                    $argsString = '$args->exclude_module_srl = ""; $args->s_module_srl = array(12); ';
                    $expected = 'select "files".*
                                    from "xe_files" as "files"
                                        left join "xe_member" as "member" on "files"."member_srl" = "member"."member_srl"
                                    where "files"."module_srl" in (12)
                                    order by "files"."file_srl" desc
                                    limit 0, 20';
                    $this->_test($xml_file, $argsString, $expected);
                }

                function test_document_getDeclaredList_In_Query(){
                    $xml_file = _XE_PATH_ . "modules/document/queries/getDeclaredList.xml";
                    $argsString = "\$args->list_count = 30;
                                    \$args->page_count = 10;
                                    \$args->sort_index = 'document_declared.declared_count';
                                    \$args->order_type = 'desc';";
                    $expected = 'select * from "xe_documents" as "documents"
                                    , "xe_document_declared" as "document_declared"
                                    where "documents"."document_srl"
                                        in ("document_declared"."document_srl")
                                    order by "document_declared"."declared_count" desc
                                    limit 0, 30';
                    $this->_test($xml_file, $argsString, $expected);
                }

                function test_getExpiredSession_curdate(){
                    $xml_file = _XE_PATH_ . "modules/session/queries/getExpiredSessions.xml";
                    $argsString = '';
                    $expected = 'select "session_key"
                                        from "xe_session" as "session"
                                        where "expired" <= \'' . date("YmdHis") . '\'';
                    $this->_test($xml_file, $argsString, $expected);
                }
				
                function test_rlike_1(){
                    $xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/rlike1.xml";
                    $argsString = '$args->title = "aaa";';
                    $expected = 'select * from "xe_modules" as "modules" where "title" rlike \'aaa\'';
					define('__CUBRID_VERSION__', '8.4.1');
                    $this->_test($xml_file, $argsString, $expected);
                }				
				
	
		function test_resource_getLatestItem(){
			$xml_file = _TEST_PATH_ . "db/xml_query/cubrid/data/resource.getLatestItem.xml";
			$expected = 'SELECT "package"."module_srl" as "module_srl"
							, "package"."status" as "status"
							, "package"."category_srl" as "category_srl"
							, "package"."member_srl" as "member_srl"
							, "package"."package_srl" as "package_srl"
							, "package"."path" as "path"
							, "package"."license" as "license"
							, "package"."title" as "title"
							, "package"."homepage" as "homepage"
							, "package"."description" as "package_description"
							, "package"."voter" as "package_voter"
							, "package"."voted" as "package_voted"
							, "package"."downloaded" as "package_downloaded"
							, "package"."regdate" as "package_regdate"
							, "package"."last_update" as "package_last_update"
							, "member"."nick_name" as "nick_name"
							, "member"."user_id" as "user_id"
							, "item"."item_srl" as "item_srl"
							, "item"."document_srl" as "document_srl"
							, "item"."file_srl" as "item_file_srl"
							, "item"."screenshot_url" as "item_screenshot_url"
							, "item"."version" as "item_version"
							, "item"."voter" as "item_voter"
							, "item"."voted" as "item_voted"
							, "item"."downloaded" as "item_downloaded"
							, "item"."regdate" as "item_regdate"  
						FROM "xe_resource_packages" as "package" 
							, "xe_member" as "member" 
							, "xe_resource_items" as "item"   
						WHERE "package"."package_srl" = ? 
							and "package"."member_srl" = "member"."member_srl" 
							and "item"."item_srl" = "package"."latest_item_srl"';
			$argsString = '$args->package_srl = 18325662;';
			$expectedArgs = array(18325662);
			$this->_testPreparedQuery($xml_file, $argsString, $expected, 'getSelectSql', $expectedArgs);
		}				

	}