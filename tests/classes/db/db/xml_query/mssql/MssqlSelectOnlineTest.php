<?php
    class MssqlSelectOnlineTest extends MssqlOnlineTest {

            function test_syndication_getGrantedModule(){
                    $args->module_srl = 67;
                    $output = executeQuery("syndication.getGrantedModule", $args);
                    $this->assertEquals(0, $output->error, $output->error + ' ' + $output->message);
            }
    }

?>