<?php
    define('__ZBXE__', true);
    require_once('../config/config.inc.php');
    require_once('simpletest/autorun.php');

    class AllTests extends TestSuite {
        function AllTests() {
            $oContext = &Context::getInstance();
            $oContext->init();
            
            $this->TestSuite('XpressEngine Test');
            $this->addFile(dirname(__FILE__).'/classes/context/Context.test.php');
            $this->addFile(dirname(__FILE__).'/modules/module/module.test.php');
        }
    }

?>
