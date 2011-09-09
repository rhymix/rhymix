<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

    class MssqlTest extends DBTest {

        protected function setUp() {
            $oContext = &Context::getInstance();

            $db_info->master_db = array('db_type' => 'mssql','db_table_prefix' => 'xe_');
            $db_info->slave_db = array(array('db_type' => 'mssql','db_table_prefix' => 'xe_'));

            $oContext->setDbInfo($db_info);

            DB::getParser(true);
        }

        protected function tearDown() {
            unset($GLOBALS['__DB__']);
        }
    }
?>
