<?php
	require('config.inc.php');

	class SelectXmlTest_Mssql extends PHPUnit_Framework_TestCase {

		function _test($xml_file, $argsString, $expected, $expectedArgs = NULL){
			$tester = new QueryTester();
			$outputString = $tester->getNewParserOutputString($xml_file, '[', $argsString, 'mssql');
			//echo $outputString;
			$output = eval($outputString);
			
			if(!is_a($output, 'Query')){
				if(!$output->toBool()) $querySql = "Date incorecte! Query-ul nu a putut fi executat.";
			}else {
				$db = &DB::getInstance('mssql');
				$querySql = $db->getSelectSql($output);
				$queryArguments = $output->getArguments();
				
				// Remove whitespaces, tabs and all
				$querySql = Helper::cleanQuery($querySql);
				$expected = Helper::cleanQuery($expected);
			}

			// Test
			$this->assertEquals($expected, $querySql);
			
			// Test query arguments
			$argCount = count($expectedArgs);
	        for($i = 0; $i < $argCount; $i++){
	        		//echo "$i: $expectedArgs[$i] vs $queryArguments[$i]->getValue()";
	        		$this->assertEquals($expectedArgs[$i], $queryArguments[$i]->getValue());
	        }
		}
		
		function testSelectStar(){
			$xml_file = _XE_PATH_ . "modules/module/queries/getAdminId.xml";
			$argsString = '$args->module_srl = 10;';
			$expected = 'SELECT * FROM [xe_module_admins] as [module_admins] , [xe_member] as [member] WHERE [module_srl] = ? and [member].[member_srl] = [module_admins].[member_srl]';
			$this->_test($xml_file, $argsString, $expected, array(10));
		}
		
		function testRquiredParameter(){
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
			$argsString = '';
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
		
		function test_opage_getOpageList(){
			$xml_file = _XE_PATH_ . "modules/opage/queries/getOpageList.xml";
			$argsString = '$args->s_title = "yuhuu";
							$args->module = \'opage\';';
			$expected = 'SELECT TOP 20 * 
						FROM [xe_modules] as [modules]
						WHERE  [module] = ? and ([browser_title] like ?)   
						ORDER BY [module_srl] desc';
			$this->_test($xml_file, $argsString, $expected, array("'opage'", "'%yuhuu%'"));			
		}
		
		// TODO Something fishy about this query - to be investigated
		/*
		function test_syndication_getGrantedModules(){
			$xml_file = _XE_PATH_ . "modules/syndication/queries/getGrantedModules.xml";
			$argsString = '$args->module_srl = 12;
						   $args->name = array(\'access\',\'view\',\'list\');';
			$expected = 'select "module_srl" 
						 from "xe_module_grants" as "module_grants" 
						 where "name" in (?) 
						 	and ("group_srl" >= -2 
						 			or "group_srl" = -2 
						 			or "group_srl" = -2) 
						 group by "module_srl"';
			$this->_test($xml_file, $argsString, $expected);			
		}
		*/
	}