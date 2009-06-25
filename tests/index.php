<?php
    define('__ZBXE__', true);
    require_once('../config/config.inc.php');

    if(__ENABLE_PHPUNIT_TEST__!=1) exit();

    require_once('simpletest/autorun.php');

    class AllTests extends TestSuite {
        function AllTests() {
            $oContext = &Context::getInstance();
            $oContext->init();

            $this->TestSuite('Classes Test');
            $this->addFile(dirname(__FILE__).'/classes/context/Context.test.php');

            $this->TestSuite('Module Test');
            $this->addFile(dirname(__FILE__).'/modules/module/module.test.php');
            $this->addFile(dirname(__FILE__).'/modules/module/opage.test.php');
        }
    }

?>
