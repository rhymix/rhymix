<?php
	require(_XE_PATH_ . 'test-phpUnit/config/config.inc.php');

	class CubridUpdateTest extends CubridTest {

                function _test($xml_file, $argsString, $expected){
                    $this->_testQuery($xml_file, $argsString, $expected, 'getUpdateSql');
		}

		function test_module_updateModule(){
			$xml_file = _XE_PATH_ . "modules/module/queries/updateModule.xml";
			$argsString = ' $args->module_category_srl = 0;
							$args->browser_title = "test";
							$args->layout_srl = 0;
							$args->mlayout_srl = 0;
							$args->module = "page";
							$args->mid = "test";
							$args->use_mobile = "";
							$args->site_srl = 0;
							$args->module_srl = 47374;';
			$expected = 'UPDATE "xe_modules"
						 SET "module" = \'page\'
						 	, "mid" = \'test\'
						 	, "browser_title" = \'test\'
						 	, "description" = \'\'
						 	, "is_default" = \'N\'
						 	, "open_rss" = \'Y\'
						 	, "header_text" = \'\'
						 	, "footer_text" = \'\'
						 	, "use_mobile" = \'n\'
						WHERE  "site_srl" = 0
							AND "module_srl" = 47374';
			$this->_test($xml_file, $argsString, $expected);
		}
		function test_member_updateLastLogin(){
			$xml_file = _XE_PATH_ . "modules/member/queries/updateLastLogin.xml";
			$argsString = ' $args->member_srl = 4;
							$args->last_login = "20110607120549";';
			$expected = 'UPDATE "xe_member" SET "member_srl" = 4, "last_login" = \'20110607120549\'  WHERE  "member_srl" = 4';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_module_updatePoint(){
			$xml_file = _XE_PATH_ . "modules/point/queries/updatePoint.xml";
			$argsString = ' $args->member_srl = 4;
							$args->point = 105;';
			$expected = 'UPDATE "xe_point" SET "point" = 105  WHERE  "member_srl" = 4';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_module_updateCounterUnique(){
			$xml_file = _XE_PATH_ . "modules/counter/queries/updateCounterUnique.xml";
			$argsString = '$args->regdate = 20110607;
							';
			$expected = 'UPDATE "xe_counter_status" SET "unique_visitor" = "unique_visitor" + 1,
						"pageview" = "pageview" + 1  WHERE  "regdate" = 20110607 ';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_module_updateMenu(){
			$xml_file = _XE_PATH_ . "modules/menu/queries/updateMenu.xml";
			$argsString = '$args->menu_srl = 204;
						$args->title = "test_menu";
						';
			$expected = 'UPDATE "xe_menu" SET "title" = \'test_menu\'  WHERE  "menu_srl" = 204';
			$this->_test($xml_file, $argsString, $expected);
		}


		function test_menu_updateMenuItemNode(){
			$xml_file = _XE_PATH_ . "modules/menu/queries/updateMenuItemNode.xml";
			$argsString = '$args->parent_srl = 0;
                            $args->menu_srl = 237423;
                            $args->listorder = -8;
                            $args->menu_item_srl = 237431;';
			$expected = 'UPDATE "xe_menu_item" SET "parent_srl" = 0, "listorder" = -8 WHERE  "menu_item_srl" = 237431';
			$this->_test($xml_file, $argsString, $expected);
		}

//	$queryTester->test_admin_deleteActionForward();
//	$queryTester->test_module_insertModule();


	}