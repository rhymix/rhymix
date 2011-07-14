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
            var_dump($output);
        }
    }
?>
