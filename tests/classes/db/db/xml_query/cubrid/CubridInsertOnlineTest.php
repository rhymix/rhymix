<?php

	class CubridInsertOnlineTest extends CubridOnlineTest {

              /**
                 * Note: this test can fail when comaparing regdate from the $args with
                 * regdate from the expected string - a few seconds difference
                 */
		function test_module_insertModule_escapeContent(){
			$xml_file = _XE_PATH_ . "modules/module/queries/insertModule.xml";

                        $args->module_category_srl = 0;
                        $args->browser_title = "test";
                        $args->layout_srl = 0;
                        $args->mlayout_srl = 0;
                        $args->module = "page";
                        $args->mid = "test";
                        $args->site_srl = 0;
                        $args->module_srl = 47374;
                        $args->content = "hello \' moto";

                        $output = executeQuery('module.insertModule', $args);

                        $this->assertTrue(!$output->error, $output->message);
		}

                function test_document_insertDocument_defaultVarcharValue(){
                    $args->module_srl = 102;
                    $args->content = '<p>yuhuuuuu</p>';
                    $args->document_srl = 9200;
                    $args->is_secret = 'N';
                    $args->allow_comment = 'N';
                    $args->lock_comment = 'N';
                    $args->allow_trackback = 'N';
                    $args->notify_message = 'N';
                    $args->ipaddress = '127.0.0.1';
                    $args->extra_vars = 'N;';
                    $args->readed_count = 0;
                    $args->list_order = -9201;
                    $args->update_order = -9201;
                    $args->member_srl = 4;
                    $args->user_id = 'admin';
                    $args->user_name = 'admin';
                    $args->nick_name = 'admin';
                    $args->email_address = 'admin@admin.admin';
                    $args->homepage = '';
                    $args->title = 'yuhuu';
                    $args->lang_code;
                    $output = executeQuery('document.insertDocument', $args);

                    $this->assertNotEquals(-225, $output->error);
                    $this->assertNotEquals('Missing value for attribute "homepage" with the NOT NULL constraint.', $output->message);
                }

              function test_communication_addFriendGroup(){
			$args->member_srl = 202;
                        $args->title = "Grup";

                        $output = executeQuery("communication.addFriendGroup", $args);
                        $this->assertEquals(0, $output->error, $output->message);

                }

             function test_communication_addFriendGroup_NullId(){
			$args->member_srl = 202;
                        $args->title = "Grup";
                        $args->friend_group_srl = trim(null);

                        $output = executeQuery("communication.addFriendGroup", $args);
                        $this->assertEquals(0, $output->error, $output->message);

             }

                protected function tearDown() {
                    $db = &DB::getInstance();
                    $db->_query("DELETE FROM xe_modules WHERE module_srl = 47374");
                    $db->_query("DELETE FROM xe_documents WHERE document_srl = 9200");
                    $db->_query("DELETE FROM xe_member_friend_group WHERE member_srl = 202");
                    $db->close();

                    parent::tearDown();
                }


	}