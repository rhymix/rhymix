<?php

    /**
     * @brief Mock database base class
     *
     * Used to load mock classes instead of actual ones,
     * so that connect methods can be skipped
     */
    class MockDb extends DB {

        function &getParser($force = false){
            static $dbParser = null;
            if(!$dbParser || $force) {
                $oDB = &MockDb::getInstance();
                $dbParser = $oDB->getParser();
                DB::getParser(true);
            }

            return $dbParser;
        }

        function &getInstance(){
            $db_type = Context::getDBType();

            if(!isset($GLOBALS['__DB__'])) $GLOBALS['__DB__'] = array();
            if(!isset($GLOBALS['__DB__'][$db_type])) {
                switch($db_type){
                    case 'mssql' :
                                    $GLOBALS['__DB__'][$db_type] = new MockDBMssql; break;
                    case 'mysql' :
                                    $GLOBALS['__DB__'][$db_type] = new MockDBMysql; break;
                    case 'cubrid' :
                                    $GLOBALS['__DB__'][$db_type] = new MockDBCubrid; break;
                }
            }

            return $GLOBALS['__DB__'][$db_type];
        }
    }

    /**
     * @brief Mock up for MS SQL class
     *
     * Overrides default constructor in order to skip connect method
     */
    class MockDBMssql extends DBMssql {
        function MockDBMssql(){
            $this->_setDBInfo();
        }
    }

    /**
     * @brief Mock up for CUBRID class
     *
     * Overrides default constructor in order to skip connect method
     */
    class MockDBCubrid extends DBCubrid {
        function MockDBCubrid(){
            $this->_setDBInfo();
        }
    }

    /**
     * @brief Mock up for Mysql class
     *
     * Overri des default constructor in order to skip connect method.
     */
    class MockDBMysql extends DBMysql {
        function MockDBMysql(){
            $this->_setDBInfo();
        }

        /**
         * Overrides mysql_real_escape_string, that returns null when no connection is present
         */
        function addQuotes($string){
            return $string;
        }
    }

?>