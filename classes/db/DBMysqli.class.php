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
			if($this->use_prepared_statements == 'Y')
			{			
				// 1. Prepare query
				$stmt = mysqli_prepare($connection, $query);
				if($stmt){
					$types = '';
					$params = array();
					$this->_prepareQueryParameters($types, $params);
					
					if(!empty($params))
					{
						$args[0] = $stmt;
						$args[1] = $types; 
						
						$i = 2;
						foreach($params as $key => $param) {
							$copy[$key] = $param;
							$args[$i++] = &$copy[$key];
						}

						// 2. Bind parameters
						$status = call_user_func_array('mysqli_stmt_bind_param',$args);   
						if(!$status)
							$this->setError(-1, "Invalid arguments: $query" . mysqli_error($connection) . PHP_EOL . print_r($args, true));						
					}
					
					// 3. Execute query
					$status = mysqli_stmt_execute($stmt);
					
					if(!$status)
						$this->setError(-1, "Prepared statement failed: $query" . mysqli_error($connection) . PHP_EOL . print_r($args, true));
					
					// Return stmt for other processing - like retrieving resultset (_fetch)
					return $stmt;
					// mysqli_stmt_close($stmt);
				}
				
			}
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
		
		function _prepareQueryParameters(&$types, &$params){
			$types = '';
			$params = array();
			if(!$this->param) return;
			
			foreach($this->param as $k => $o){
				$value = $o->getUnescapedValue();
				$type = $o->getType();
				
				// Skip column names -> this should be concatenated to query string
				if($o->isColumnName()) continue;
				
				switch($type)
				{
					case 'number' : 
									$type = 'i'; 
									break;
					case 'varchar' : 
									$type = 's'; 
									break;
					default: 
									$type = 's';
				}				
				
				if(is_array($value)) 
				{
					foreach($value as $v)
					{
						$params[] = $v;
						$types .= $type;
					}
				}
				else {
					$params[] = $value;
					$types .= $type;
				}
				
				
				
			}			
		}
		
       /**
         * @brief Fetch results
         **/
        function _fetch($result, $arrayIndexEndValue = NULL) {
			if($this->use_prepared_statements != 'Y'){
				return parent::_fetch($result, $arrayIndexEndValue);
			}
			$output = array();
            if(!$this->isConnected() || $this->isError() || !$result) return $output;
			
			// Prepared stements: bind result variable and fetch data
			$stmt = $result;
			$meta = mysqli_stmt_result_metadata($stmt);
			$fields = mysqli_fetch_fields($meta);
			
			foreach($fields as $field) 
			{
				if(isset($resultArray[$field->name])) // When joined tables are used and the same column name appears twice, we should add it separately, otherwise bind_result fails
					$field->name = 'repeat_' . $field->name;				

				// Array passed needs to contain references, not values
				$row[$field->name] = "";
				$resultArray[$field->name] = &$row[$field->name];
			}
			$resultArray = array_merge(array($stmt), $resultArray);

			call_user_func_array('mysqli_stmt_bind_result', $resultArray);

			$rows = array();
			while(mysqli_stmt_fetch($stmt))
			{
				$resultObject = new stdClass();
				
				foreach($resultArray as $key => $value)
				{
					if($key === 0) continue; // Skip stmt object
					if(strpos($key, 'repeat_')) $key = substr($key, 6);
					$resultObject->$key = $value;
				}
				
				$rows[] = $resultObject;
			}
				
			mysqli_stmt_close($stmt);			

			if($arrayIndexEndValue)
			{
				foreach($rows as $row)
				{
					$output[$arrayIndexEndValue--] = $row;
				}
			}
			else 
			{
				$output = $rows;
			}
			
            if(count($output)==1){
            	if(isset($arrayIndexEndValue)) return $output;
            	else return $output[0];
            }
			
            return $output;
        }		
		
		function _executeInsertAct($queryObject, $with_values = false){
			if($this->use_prepared_statements != 'Y')
			{
				return parent::_executeInsertAct($queryObject);
			}
			$this->param = $queryObject->getArguments();
			$result = parent::_executeInsertAct($queryObject, $with_values);
			unset($this->param);
			return $result;
		}
		
		function _executeUpdateAct($queryObject, $with_values = false) {
			if($this->use_prepared_statements != 'Y')
			{
				return parent::_executeUpdateAct($queryObject);
			}			
			$this->param = $queryObject->getArguments();
			$result = parent::_executeUpdateAct($queryObject, $with_values);
			unset($this->param);
			return $result;
		}
		
		function _executeDeleteAct($queryObject, $with_values = false) {
			if($this->use_prepared_statements != 'Y')
			{
				return parent::_executeDeleteAct($queryObject);
			}				
			$this->param = $queryObject->getArguments();
			$result = parent::_executeDeleteAct($queryObject, $with_values);
			unset($this->param);
			return $result;
		}		
		
		function _executeSelectAct($queryObject, $connection = null, $with_values = false) {
			if($this->use_prepared_statements != 'Y')
			{
				return parent::_executeSelectAct($queryObject, $connection);
			}				
			$this->param = $queryObject->getArguments();
			$result = parent::_executeSelectAct($queryObject, $connection, $with_values);		
			unset($this->param);
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
