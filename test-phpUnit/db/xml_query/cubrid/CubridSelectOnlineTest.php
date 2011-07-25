<?php

    class CubridSelectOnlineTest extends CubridOnlineTest {

        function test_get_module_by_mid(){
            $args->mid = 'test_4l8ci4vv0n';
            $args->site_srl = 0;
            $output = executeQuery('module.getMidInfo', $args);
            $this->assertNotNull($output);
            $this->assertNotNull($output->data, $output->message);
            $this->assertEquals($output->data->module_srl, 111);
        }

        function test_module_getInfo(){
            $args->site_srl = 0;
            $output = executeQuery('module.getSiteInfo', $args);
            $this->assertTrue(is_a($output, 'Object'));
            $this->assertEquals(0, $output->error, $output->message);
        }

        function test_document_getDocumentList_pagination(){
            $args->sort_index = 'list_order';
            $args->order_type = 'asc';
            $args->page = 1;
            $args->list_count = 30;
            $args->page_count = 10;
            $args->s_member_srl = 4;

            $output = executeQuery('document.getDocumentList', $args);
            $this->assertEquals(0, $output->error, $output->message . PHP_EOL . $output->variables["_query"]);
        }

        function test_syndication_getDocumentList(){
            $args->module_srl = NULL;
            $args->exclude_module_srl = NULL;
            $args->category_srl = NULL;
            $args->sort_index = 'list_order';
            $args->order_type = 'asc';
            $args->page = 5;
            $args->list_count = 30;
            $args->page_count = 10;
            $args->start_date = NULL;
            $args->end_date = NULL;
            $args->member_srl = NULL;
            $output = executeQuery('document.getDocumentList', $args);

            $this->assertTrue(is_int($output->page), $output->message);
        }

        function test_member_getMemberList(){
            $args->is_admin = '';
            $args->is_denied = '';
            $args->sort_index = "list_order";
            $args->sort_order = 'asc';
            $args->list_count = 40;
            $args->page_count = 10;

            $output = executeQuery('member.getMemberList', $args);
            $this->assertEquals(0, $output->error, $output->message);
        }
        }
?>
