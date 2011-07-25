<?php

    /**
     * Base class for tests for MSSQL SQL syntax
     */

    class MssqlOnlineTest extends PHPUnit_Framework_TestCase {

        protected $backupGlobals = FALSE;
        protected $backupStaticAttributes = FALSE;
        protected $preserveGlobalState = FALSE;

        /**
         * Prepare runtime context - tell DB class that current DB is CUBRID
         */
        protected function setUp() {
            $oContext = &Context::getInstance();

            $db_info->db_type = 'mssql';
            $db_info->db_port = '3306';
            $db_info->db_hostname = 'PHENOMII\SQL2008EXPRESS';
            $db_info->db_userid = 'dba';
            $db_info->db_password = 'arniarules';
            $db_info->db_database = 'xe-15-db';
            $db_info->db_table_prefix = 'xe';

            $oContext->setDbInfo($db_info);

            // remove cache dir
            FileHandler::removeDir( _XE_PATH_ . 'files/cache');
        }

        /**
         * Free resources - reset static DB and QueryParser
         */
        protected function tearDown() {
            unset($GLOBALS['__DB__']);
            XmlQueryParser::setDBParser(null);
        }
    }
?>
