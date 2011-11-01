<?php
    class MssqlUpdateOnlineTest extends MssqlOnlineTest {

              function test_counter_updateCounterUnique(){
			$args->regdate = 20110211;

                        $output = executeQuery("counter.updateCounterUnique", $args);
                        $this->assertEquals(0, $output->error, $output->error + ' ' + $output->message);
                }
    }

?>