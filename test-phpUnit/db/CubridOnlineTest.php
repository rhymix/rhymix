<?php

    /**
     * Base class for tests for CUBRID SQL syntax
     */

    class CubridOnlineTest extends PHPUnit_Framework_TestCase {

        protected $backupGlobals = FALSE;
        protected $backupStaticAttributes = FALSE;
        protected $preserveGlobalState = FALSE;

        /**
         * Prepare runtime context - tell DB class that current DB is CUBRID
         */
        protected function setUp() {
            $oContext = &Context::getInstance();

            $db_info->master_db = array('db_type' => 'cubrid'
                                                ,'db_port' => '33000'
                                                ,'db_hostname' => '10.0.0.206'
                                                ,'db_userid' => 'dba'
                                                ,'db_password' => 'arniarules'
                                                ,'db_database' => 'xe15QA'
                                                ,'db_table_prefix' => 'xe_');
            $db_info->slave_db = array(array('db_type' => 'cubrid'
                                                ,'db_port' => '33000'
                                                ,'db_hostname' => '10.0.0.206'
                                                ,'db_userid' => 'dba'
                                                ,'db_password' => 'arniarules'
                                                ,'db_database' => 'xe15QA'
                                                ,'db_table_prefix' => 'xe_'));
            $oContext->setDbInfo($db_info);

            // remove cache dir
            FileHandler::removeDir( _XE_PATH_ . 'files/cache');

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
