<?php
    class LockTest extends UnitTestCase {
        function testLock() {
            $oController = &getController('module');
            $this->assertNotNull($oController);
            $output = $oController->lock('lockTest', 60);
            $this->assertTrue($output->toBool());
            $deadline = $output->get('deadline'); 
            $output2 = $oController->lock('lockTest', 60);
            $this->assertFalse($output2->toBool());
            $output2 = $oController->unlock('lockTest', $deadline);
            $this->assertTrue($output2->toBool());
            $output2 = $oController->lock('lockTest', 60);
            $this->assertTrue($output2->toBool());
            $output2 = $oController->unlock('lockTest', $output2->get('deadline'));
            $this->assertTrue($output2->toBool());
        }
    }
?>
