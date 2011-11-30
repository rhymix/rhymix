<?php
	require_once('DBMysql.class.php');
    /**
     * @class DBMysqli
     * @author NHN (developers@xpressengine.com)
     * @brief Class to use MySQL DBMS as mysqli_*
     * @version 0.1
     *
     * mysql handling class
     **/


    class DBMysqli extends DBMysql {

        /**
         * @brief constructor
         **/
        function DBMysqli() {
            $this->_setDBInfo();
            $this->_connect();
        }

        /**
         * @brief Return if it is installable
         **/
        function isSupported() {
            if(!function_exists('mysqli_connect')) return false;
            return true;
        }

		/**
		 * @brief create an instance of this class
		 */
		function create()
		{
			return new DBMysqli;
		}

        /**
         * @brief DB Connection
         **/
        function __connect($connection) {
            // Attempt to connect
            if ($connection["db_port"]) {
                $result = @mysqli_connect($connection["db_hostname"]
                                                        , $connection["db_userid"]
                                                        , $connection["db_password"]
                                                        , $connection["db_database"]
                                                        , $connection["db_port"]);
            } else {
                $result = @mysqli_connect($connection["db_hostname"]
                                                        , $connection["db_userid"]
                                                        , $connection["db_password"]
                                                        , $connection["db_database"]);
            }
			$error = mysqli_connect_errno();
            if($error) {
                $this->setError($error,mysqli_connect_error());
                return;
            }
            mysqli_set_charset($result,'utf8');
            return $result;
        }

        /**
         * @brief DB disconnection
         **/
        function _close($connection) {
            mysqli_close($connection);
        }

        /**
         * @brief Add quotes on the string variables in a query
         **/
        function addQuotes($string) {
            if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)){
                $connection = $this->_getConnection('master');
                $string = mysqli_escape_string($connection,$string);
            }
            return $string;
        }

        /**
         * @brief : Run a query and fetch the result
         *
         * query: run a query and return the result \n
         * fetch: NULL if no value is returned \n
         *        array object if rows are returned \n
         *        object if a row is returned \n
         *         return\n
         **/
        function __query($query, $connection) {
            // Run the query statement
            $result = mysqli_query($connection,$query);
            // Error Check
			$error = mysqli_error($connection);
            if($error){
				$this->setError(mysqli_errno($connection), $error);
			}
            // Return result
            return $result;
        }

		function db_insert_id()
		{
                    $connection = $this->_getConnection('master');
            return  mysqli_insert_id($connection);
		}

		function db_fetch_object(&$result)
		{
			return mysqli_fetch_object($result);
		}
		
		function db_free_result(&$result){
			return mysqli_free_result($result);		
		}		
    }

return new DBMysqli;
?>
