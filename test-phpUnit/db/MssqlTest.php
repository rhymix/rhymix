<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

    class MssqlTest extends DBTest {

        protected function setUp() {
            $oContext = &Context::getInstance();

            $db_info->db_type = 'mssql';
            $db_info->db_table_prefix = 'xe';

            $oContext->setDbInfo($db_info);         
        }

        protected function tearDown() {
            unset($GLOBALS['__DB__']);
            XmlQueryParser::setDBParser(null);
        }        
    }
?>
