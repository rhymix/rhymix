<?php

    /**
     * Base class for tests for CUBRID SQL syntax
     */

    class CubridTest extends DBTest {

        /**
         * Prepare runtime context - tell DB class that current DB is CUBRID
         */
        protected function setUp() {
            $oContext = &Context::getInstance();

            $db_info->master_db = array('db_type' => 'cubrid','db_table_prefix' => 'xe_');
            $db_info->slave_db = array(array('db_type' => 'cubrid','db_table_prefix' => 'xe_'));

            $oContext->setDbInfo($db_info);
            DB::getParser(true);
        }

        /**
         * Free resources - reset static DB and QueryParser
         */
        protected function tearDown() {
            unset($GLOBALS['__DB__']);
        }
    }
?>
