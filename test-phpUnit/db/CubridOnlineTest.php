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

            $db_info->db_type = 'cubrid';
            $db_info->db_port = '33000';
            $db_info->db_hostname = '10.0.0.206';
            $db_info->db_userid = 'dba';
            $db_info->db_password = 'arniarules';
            $db_info->db_database = 'xe15QA';
            $db_info->db_table_prefix = 'xe';        

            $oContext->setDbInfo($db_info);        
            
            // remove cache dir
            $tmp_cache_list = FileHandler::readDir('./files','/(^cache_[0-9]+)/');
            if($tmp_cache_list){
                    foreach($tmp_cache_list as $tmp_dir){
                            if($tmp_dir) FileHandler::removeDir('./files/'.$tmp_dir);
                    }
            }            
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
