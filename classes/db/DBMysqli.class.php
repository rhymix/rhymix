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
        function _connect() {
            // Ignore if no DB information exists
            if(!$this->hostname || !$this->userid || !$this->password || !$this->database) return;
            // Attempt to connect
			if($this->port){
	            $this->fd = @mysqli_connect($this->hostname, $this->userid, $this->password, $this->database, $this->port);
			}else{
	            $this->fd = @mysqli_connect($this->hostname, $this->userid, $this->password, $this->database);
			}
			$error = mysqli_connect_errno();
            if($error) {
                $this->setError($error,mysqli_connect_error());
                return;
            }
			mysqli_set_charset($this->fd,'utf8');
            // Check connections
            $this->is_connected = true;
			$this->password = md5($this->password);
        }

        /**
         * @brief DB disconnection
         **/
        function close() {
            if(!$this->isConnected()) return;
            mysqli_close($this->fd);
        }

        /**
         * @brief Add quotes on the string variables in a query
         **/
        function addQuotes($string) {
            if(version_compare(PHP_VERSION, "5.9.0", "<") && get_magic_quotes_gpc()) $string = stripslashes(str_replace("\\","\\\\",$string));
            if(!is_numeric($string)) $string = mysqli_escape_string($this->fd,$string);
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
        function _query($query) {
            if(!$this->isConnected()) return;
            // Notify to start a query execution
            $this->actStart($query);
            // Run the query statement
            $result = mysqli_query($this->fd,$query);
            // Error Check
			$error = mysqli_error($this->fd);
            if($error){
				$this->setError(mysqli_errno($this->fd), $error);
			}

            // Notify to complete a query execution
            $this->actFinish();
            // Return result
            return $result;
        }

		function db_insert_id()
		{
            return  mysqli_insert_id($this->fd);
		}

		function db_fetch_object(&$result)
		{
			return mysqli_fetch_object($result);
		}
    }

return new DBMysqli;
?>
