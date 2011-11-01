<?php

	class MssqlSelectTest extends MssqlTest {

		function _test($xml_file, $argsString, $expected, $expectedArgs = NULL){
                    $this->_testPreparedQuery($xml_file, $argsString, $expected, 'getSelectSql', $expectedArgs);
		}

		function testSelectStar(){
			$xml_file = _XE_PATH_ . "modules/module/queries/getAdminId.xml";
			$argsString = '$args->module_srl = 10;';
			$expected = 'SELECT * FROM [xe_module_admins] as [module_admins] , [xe_member] as [member] WHERE [module_srl] = ? and [member].[member_srl] = [module_admins].[member_srl]';
			$this->_test($xml_file, $argsString, $expected, array(10));
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
			$expected = 'SELECT * FROM [xe_module_categories] as [module_categories] ORDER BY [title] asc';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_module_getDefaultModules(){
			$xml_file = _XE_PATH_ . "modules/module/queries/getDefaultModules.xml";
			$argsString = '$args->site_srl = 0;';
			$expected = 'SELECT [modules].[site_srl]
							, [modules].[module]
							, [modules].[mid]
							, [modules].[browser_title]
							, [module_categories].[title] as [category]
							, [modules].[module_srl]
						FROM [xe_modules] as [modules]
							left join [xe_module_categories] as [module_categories]
								on [module_categories].[module_category_srl] = [modules].[module_category_srl]
						WHERE [modules].[site_srl] = ?
						ORDER BY [modules].[module] asc, [module_categories].[title] asc, [modules].[mid] asc';
			$this->_test($xml_file, $argsString, $expected, array(0));
		}

		function test_module_getSiteInfo(){
			$xml_file = _XE_PATH_ . "modules/module/queries/getSiteInfo.xml";
			$argsString = '$args->site_srl = 0;';
			$expected = 'SELECT [modules].[site_srl] as [module_site_srl]
							, [modules].[module_srl] as [module_srl]
							, [modules].[module] as [module]
							, [modules].[module_category_srl] as [module_category_srl]
							, [modules].[layout_srl] as [layout_srl]
							, [modules].[mlayout_srl] as [mlayout_srl]
							, [modules].[use_mobile] as [use_mobile]
							, [modules].[menu_srl] as [menu_srl]
							, [modules].[mid] as [mid]
							, [modules].[skin] as [skin]
							, [modules].[mskin] as [mskin]
							, [modules].[browser_title] as [browser_title]
							, [modules].[description] as [description]
							, [modules].[is_default] as [is_default]
							, [modules].[content] as [content]
							, [modules].[mcontent] as [mcontent]
							, [modules].[open_rss] as [open_rss]
							, [modules].[header_text] as [header_text]
							, [modules].[footer_text] as [footer_text]
							, [modules].[regdate] as [regdate]
							, [sites].[site_srl] as [site_srl]
							, [sites].[domain] as [domain]
							, [sites].[index_module_srl] as [index_module_srl]
							, [sites].[default_language] as [default_language]
						FROM [xe_sites] as [sites]
							left join [xe_modules] as [modules] on  [modules].[module_srl] = [sites].[index_module_srl]
						WHERE  [sites].[site_srl] = ?   ';
			$this->_test($xml_file, $argsString, $expected, array(0));
		}

		function test_addon_getAddonInfo(){
			$xml_file = _XE_PATH_ . "modules/addon/queries/getAddonInfo.xml";
			$argsString = '$args->addon = "captcha";';
			$expected = 'SELECT *
						FROM [xe_addons] as [addons]
						WHERE  [addon] = ? ';
			$this->_test($xml_file, $argsString, $expected, array("'captcha'"));
		}

		function test_addon_getAddons(){
			$xml_file = _XE_PATH_ . "modules/addon/queries/getAddons.xml";
			$argsString = '';
			$expected = 'SELECT *
						FROM [xe_addons] as [addons]
						ORDER BY [addon] asc';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_admin_getCommentCount(){
			$xml_file = _XE_PATH_ . "modules/admin/queries/getCommentCount.xml";
			$argsString = '';
			$expected = 'SELECT count(*) as [count]
						FROM [xe_comments] as [comments]';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_admin_getCommentDeclaredStatus(){
			$xml_file = _XE_PATH_ . "modules/admin/queries/getCommentDeclaredStatus.xml";
			$argsString = '$args->date = "20110411";';
			$expected = 'SELECT TOP 2 substr([regdate],1,8) as [date], count(*) as [count]
				FROM [xe_comment_declared_log] as [comment_declared_log]
				WHERE  [regdate] >= ?
				GROUP BY substr([regdate],1,8)
				ORDER BY substr([regdate],1,8) asc';
			$this->_test($xml_file, $argsString, $expected, array("'20110411'"));
		}

		function test_member_getAutoLogin(){
			$xml_file = _XE_PATH_ . "modules/member/queries/getAutoLogin.xml";
			$argsString = '$args->autologin_key = 10;';
			$expected = 'SELECT [member].[user_id] as [user_id]
							, [member].[password] as [password]
							, [member_autologin].[autologin_key] as [autologin_key]
						FROM [xe_member] as [member] , [xe_member_autologin] as [member_autologin]
						WHERE  [member_autologin].[autologin_key] = ?
							and [member].[member_srl] = [member_autologin].[member_srl]';
			$this->_test($xml_file, $argsString, $expected, array("'10'"));
		}

                /**
                 * Query fails because XML is wrong - title column does not exist
                 * in xe_modules. Maybe the developer ment "browser_title"
                 */
		function test_opage_getOpageList(){
			$xml_file = _TEST_PATH_ . "db/xml_query/mssql/data/opage.getOpageList.xml";
			$argsString = '$args->s_title = "yuhuu";
					$args->module = \'opage\';';
			$expected = 'SELECT TOP 20 *
						FROM [xe_modules] as [modules]
						WHERE  [module] = \'opage\' and ([title] like ?)
						ORDER BY [module_srl] desc';
			$this->_test($xml_file, $argsString, $expected, array("'%yuhuu%'"));
		}

                function test_module_getExtraVars(){
                        $xml_file = _XE_PATH_ . "modules/module/queries/getModuleExtraVars.xml";
			$argsString = '$args->module_srl = 25;';
			$expected = 'SELECT *  FROM [xe_module_extra_vars] as [module_extra_vars] WHERE  [module_srl] in (?)';
			$this->_test($xml_file, $argsString, $expected, array(array(25)));
                }

                function test_module_getModuleSites(){
                        $xml_file = _XE_PATH_ . "modules/module/queries/getModuleSites.xml";
                        $argsString = '$args->module_srls = "67, 65";';
			$expected = 'SELECT [modules].[module_srl] as [module_srl], [sites].[domain] as [domain]  FROM [xe_modules] as [modules] , [xe_sites] as [sites]   WHERE  [modules].[module_srl] in (?,?) and [sites].[site_srl] = [modules].[site_srl]';
			$this->_test($xml_file, $argsString, $expected, array(array(67, 65)));
                }

                function test_syndication_getGrantedModule(){
                        $xml_file = _TEST_PATH_ . "db/xml_query/mssql/data/syndication.getGrantedModule.xml";
			$argsString = '$args->module_srl = 67;';
			$expected = 'select count(*) as [count]
                                        from [xe_module_grants] as [module_grants]
                                        where [module_srl] = ?
                                            and [name] in (\'access\',\'view\',\'list\')
                                            and ([group_srl] >= 1
                                                    or [group_srl] = -1
                                                    or [group_srl] = -2)';
			$this->_test($xml_file, $argsString, $expected, array(67));
                }
	}