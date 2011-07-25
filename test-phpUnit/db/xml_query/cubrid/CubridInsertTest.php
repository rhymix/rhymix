<?php
	require(_XE_PATH_ . 'test-phpUnit/config/config.inc.php');

	class CubridInsertTest extends CubridTest {

		function _test($xml_file, $argsString, $expected){
                    $this->_testQuery($xml_file, $argsString, $expected, 'getInsertSql');
		}


                /**
                 * Note: this test can fail when comaparing regdate from the $args with
                 * regdate from the expected string - a few seconds difference
                 */
		function test_module_insertModule(){
			$xml_file = _XE_PATH_ . "modules/module/queries/insertModule.xml";
			$argsString = ' $args->module_category_srl = 0;
							$args->browser_title = "test";
							$args->layout_srl = 0;
							$args->mlayout_srl = 0;
							$args->module = "page";
							$args->mid = "test";
							$args->site_srl = 0;
							$args->module_srl = 47374;';
			$expected = 'insert into "xe_modules"
							("site_srl"
							, "module_srl"
							, "module_category_srl"
							, "mid"
							, "browser_title"
							, "layout_srl"
							, "module"
							, "is_default"
							, "open_rss"
							, "regdate"
							, "mlayout_srl"
							, "use_mobile")
							values
							(0
							, 47374
							, 0
							, \'test\'
							, \'test\'
							, 0
							, \'page\'
							, \'n\'
							, \'y\'
							, \''.date("YmdHis").'\'
							, 0
							, \'n\')';
			$this->_test($xml_file, $argsString, $expected);
		}

                function test_module_insertSiteTodayStatus(){
			//\''.date("YmdHis").'\'
			$xml_file = _XE_PATH_ . "modules/counter/queries/insertTodayStatus.xml";
			$argsString = ' $args->regdate = 0;
							$args->unique_visitor = 0;
							$args->pageview = 0;';
			$expected = 'insert into "xe_counter_status"
						 	("regdate"
							, "unique_visitor"
							, "pageview")
							values
							('.date("YmdHis").'
							, 0
							, 0)';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_module_insertCounterLog(){
			$xml_file = _XE_PATH_ . "modules/counter/queries/insertCounterLog.xml";
			$argsString = ' $args->site_srl = 0;
							$args->regdate = "20110607120619";
							$args->ipaddress = "127.0.0.1";
							$args->user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.77 Safari/534.24";';
			$expected = 'insert into "xe_counter_log"
 						("site_srl", "regdate", "ipaddress", "user_agent")
 						VALUES (0, \'20110607120619\', \'127.0.0.1\', \'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.77 Safari/534.24\')
 						';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_module_insertMember(){
			$xml_file = _XE_PATH_ . "modules/member/queries/insertMember.xml";
			$argsString = ' $args->member_srl = 203;
							$args->user_id = "cacao";
							$args->email_address = "teta@ar.ro";
							$args->password = "23e5484cb88f3c07bcce2920a5e6a2a7";
							$args->email_id = "teta";
							$args->email_host = "ar.ro";
							$args->user_name = "trident";
							$args->nick_name = "aloha";
							$args->homepage = "http://jkgjfk./ww";
							$args->allow_mailing = "Y";
							$args->allow_message = "Y";
							$args->denied = "N";
							$args->regdate = "20110607121952";
							$args->change_password_date = "20110607121952";
							$args->last_login = "20110607121952";
							$args->is_admin = "N";
							$args->extra_vars = "O:8:\"stdClass\":2:{s:4:\"body\";s:0:\"\";s:7:\"_filter\";s:6:\"insert\";}";
							$args->list_order = -203;
							';
			$expected = 'INSERT INTO "xe_member"
 						("member_srl", "user_id", "email_address", "password", "email_id", "email_host", "user_name", "nick_name",
  						"homepage", "allow_mailing", "allow_message", "denied", "regdate", "change_password_date",
   						"last_login", "is_admin", "extra_vars", "list_order")
 						VALUES (203, \'cacao\', \'teta@ar.ro\', \'23e5484cb88f3c07bcce2920a5e6a2a7\', \'teta\', \'ar.ro\', \'trident\',
 						\'aloha\', \'http://jkgjfk./ww\', \'Y\', \'Y\', \'N\', \'20110607121952\', \'20110607121952\',
 						\'20110607121952\', \'N\', \'O:8:"stdClass":2:{s:4:"body";s:0:"";s:7:"_filter";s:6:"insert";}\', -203)';
			$this->_test($xml_file, $argsString, $expected);
		}

		function test_module_insertModuleExtraVars(){
			$xml_file = _XE_PATH_ . "modules/module/queries/insertModuleExtraVars.xml";
			$argsString = ' $args->module_srl = 202;
							$args->name = "_filter";
							$args->value = "insert_page";
							';
			$expected = 'INSERT INTO "xe_module_extra_vars"
 						("module_srl", "name", "value")
 						VALUES (202, \'_filter\', \'insert_page\')
 						';
			$this->_test($xml_file, $argsString, $expected);
		}

	}