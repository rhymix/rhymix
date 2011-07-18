<?php

    class CubridSelectOnlineTest extends CubridOnlineTest {
        
        function test_get_module_by_mid(){
            $args->mid = 'test_4l8ci4vv0n';
            $args->site_srl = 0;
            $output = executeQuery('module.getMidInfo', $args);
            $this->assertNotNull($output);
            $this->assertNotNull($output->data);
            $this->assertEquals($output->data->module_srl, 111);   
        }
        
        function test_module_getInfo(){
            $args->site_srl = 0;
            $output = executeQuery('module.getSiteInfo', $args);
            $this->assertTrue(is_a($output, 'Object'));
            $this->assertEquals(0, $output->error);
        }
        
        function test_document_getDocumentList_pagination(){
            $args->sort_index = 'list_order';
            $args->order_type = 'asc';
            $args->page = 1;
            $args->list_count = 30;
            $args->page_count = 10;
            $args->s_member_srl = 4;
            
            $output = executeQuery('document.getDocumentList', $args);
            $this->assertEquals(0, $output->error, $output->message);            
        }
    }
?>
