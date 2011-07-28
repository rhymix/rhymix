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

            $db_info->db_type = 'cubrid';
            $db_info->db_table_prefix = 'xe';

            $oContext->setDbInfo($db_info);         
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
