<?php

    /**
     * Base class for tests for MSSQL SQL syntax
     */

    class MssqlOnlineTest extends PHPUnit_Framework_TestCase {

        protected $backupGlobals = FALSE;
        protected $backupStaticAttributes = FALSE;
        protected $preserveGlobalState = FALSE;

        /**
         * Prepare runtime context - tell DB class that current DB is MSSQL
         */
        protected function setUp() {
            $oContext = &Context::getInstance();

            $db_info->master_db = array('db_type' => 'mssql'
                                                ,'db_port' => '3306'
                                                ,'db_hostname' => 'PHENOMII\SQL2008EXPRESS'
                                                ,'db_userid' => 'dba'
                                                ,'db_password' => 'arniarules'
                                                ,'db_database' => 'xe-15-db'
                                                ,'db_table_prefix' => 'xe_');
            $db_info->slave_db = array(array('db_type' => 'mssql'
                                                ,'db_port' => '3306'
                                                ,'db_hostname' => 'PHENOMII\SQL2008EXPRESS'
                                                ,'db_userid' => 'dba'
                                                ,'db_password' => 'arniarules'
                                                ,'db_database' => 'xe-15-db'
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
