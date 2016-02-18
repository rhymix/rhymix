<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Every modules inherits from Object class. It includes error, message, and other variables for communicatin purpose.
 *
 * @author NAVER (developers@xpressengine.com)
 */
class Object
{

	/**
	 * Error code. If `0`, it is not an error.
	 * @var int
	 */
	var $error = 0;

	/**
	 * Error message. If `success`, it is not an error.
	 * @var string
	 */
	var $message = 'success';

	/**
	 * An additional variable
	 * @var array
	 */
	var $variables = array();

	/**
	 * http status code.
	 * @var int
	 */
	var $httpStatusCode = NULL;

	/**
	 * Constructor
	 *
	 * @param int $error Error code
	 * @param string $message Error message
	 * @return void
	 */
	function Object($error = 0, $message = 'success')
	{
		$this->setError($error);
		$this->setMessage($message);
	}

	/**
	 * Setter to set error code
	 *
	 * @param int $error error code
	 * @return void
	 */
	function setError($error = 0)
	{
		$this->error = $error;
	}

	/**
	 * Getter to retrieve error code
	 *
	 * @return int Returns an error code
	 */
	function getError()
	{
		return $this->error;
	}

	/**
	 * Setter to set HTTP status code
	 *
	 * @param int $code HTTP status code. Default value is `200` that means successful
	 * @return void
	 */
	function setHttpStatusCode($code = '200')
	{
		$this->httpStatusCode = $code;
	}

	/**
	 * Getter to retrieve HTTP status code
	 *
	 * @return int Returns HTTP status code
	 */
	function getHttpStatusCode()
	{
		return $this->httpStatusCode;
	}

	/**
	 * Setter to set set the error message
	 *
	 * @param string $message Error message
	 * @return bool Alaways returns true.
	 */
	function setMessage($message = 'success', $type = NULL)
	{
		if($str = Context::getLang($message))
		{
			$this->message = $str;
		}
		else
		{
			$this->message = $message;
		}

		// TODO This method always returns True. We'd better remove it
		return TRUE;
	}

	/**
	 * Getter to retrieve an error message
	 *
	 * @return string Returns message
	 */
	function getMessage()
	{
		return $this->message;
	}

	/**
	 * Setter to set a key/value pair as an additional variable
	 *
	 * @param string $key A variable name
	 * @param mixed $val A value for the variable
	 * @return void
	 */
	function add($key, $val)
	{
		$this->variables[$key] = $val;
	}

	/**
	 * Method to set multiple key/value pairs as an additional variables
	 *
	 * @param Object|array $object Either object or array containg key/value pairs to be added
	 * @return void
	 */
	function adds($object)
	{
		if(is_object($object))
		{
			$object = get_object_vars($object);
		}

		if(is_array($object))
		{
			foreach($object as $key => $val)
			{
				$this->variables[$key] = $val;
			}
		}
	}

	/**
	 * Method to retrieve a corresponding value to a given key
	 *
	 * @param string $key
	 * @return string Returns value to a given key
	 */
	function get($key)
	{
		return $this->variables[$key];
	}

	/**
	 * Method to retrieve an object containing a key/value pairs
	 *
	 * @return Object Returns an object containing key/value pairs
	 */
	function gets()
	{
		$args = func_get_args();
		$output = new stdClass();
		foreach($args as $arg)
		{
			$output->{$arg} = $this->get($arg);
		}
		return $output;
	}

	/**
	 * Method to retrieve an array of key/value pairs
	 *
	 * @return array
	 */
	function getVariables()
	{
		return $this->variables;
	}

	/**
	 * Method to retrieve an object of key/value pairs
	 *
	 * @return Object
	 */
	function getObjectVars()
	{
		$output = new stdClass();
		foreach($this->variables as $key => $val)
		{
			$output->{$key} = $val;
		}
		return $output;
	}

	/**
	 * Method to return either true or false depnding on the value in a 'error' variable
	 *
	 * @return bool Retruns true : error isn't 0 or false : otherwise.
	 */
	function toBool()
	{
		// TODO This method is misleading in that it returns true if error is 0, which should be true in boolean representation.
		return ($this->error == 0);
	}

	/**
	 * Method to return either true or false depnding on the value in a 'error' variable
	 *
	 * @return bool
	 */
	function toBoolean()
	{
		return $this->toBool();
	}

}
/* End of file Object.class.php */
/* Location: ./classes/object/Object.class.php */
