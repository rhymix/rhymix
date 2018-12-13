<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Every module inherits from BaseObject class.
 *
 * @author NAVER (developers@xpressengine.com)
 */
class BaseObject
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
	var $httpStatusCode = 200;

	/**
	 * Constructor
	 *
	 * @param int $error Error code
	 * @param string $message Error message
	 * @return void
	 */
	function __construct($error = 0, $message = 'success')
	{
		$this->setError($error);
		$this->setMessage($message);
	}
	
	/**
	 * Set state for var_export()
	 * 
	 * @param array $vars
	 * @return object
	 */
	public static function __set_state(array $vars)
	{
		$instance = new static;
		foreach ($vars as $key => $val)
		{
			$instance->{$key} = $val;
		}
		return $instance;
	}

	/**
	 * Setter to set error code or message
	 *
	 * @param int|strong $error error code or message
	 * @return $this
	 */
	function setError($error = 0)
	{
		// If the first argument is an integer, treat it as an error code. Otherwise, treat it as an error message.
		$args = func_get_args();
		if(strval(intval($error)) === strval($error))
		{
			$this->error = intval($error);
			array_shift($args);
		}
		else
		{
			$this->error = -1;
		}
		
		// Convert the error message into the correct language and interpolate any other variables into it.
		if(count($args))
		{
			$this->message = lang(array_shift($args));
			if(count($args))
			{
				$this->message = vsprintf($this->message, $args);
			}
		}
		
		return $this;
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
	 * @return $this
	 */
	function setHttpStatusCode($code = 200)
	{
		$this->httpStatusCode = (int) $code;
		return $this;
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
	 * @param string $type type of message (error, info, update)
	 * @return $this
	 */
	function setMessage($message = 'success', $type = null)
	{
		$this->message = lang($message);
		if($type !== null)
		{
			$this->setMessageType($type);
		}
		return $this;
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
	 * set type of message
	 * @param string $type type of message (error, info, update)
	 * @return $this
	 * */
	function setMessageType($type)
	{
		$this->add('message_type', $type);
		return $this;
	}

	/**
	 * get type of message
	 * @return string $type
	 * */
	function getMessageType()
	{
		$type = $this->get('message_type');
		$typeList = array('error' => 1, 'info' => 1, 'update' => 1);
		if(!isset($typeList[$type]))
		{
			$type = $this->getError() ? 'error' : 'info';
		}
		return $type;
	}

	/**
	 * Setter to set a key/value pair as an additional variable
	 *
	 * @param string $key A variable name
	 * @param mixed $val A value for the variable
	 * @return $this
	 */
	function add($key, $val)
	{
		$this->variables[$key] = $val;
		return $this;
	}

	/**
	 * Method to set multiple key/value pairs as an additional variables
	 *
	 * @param object|array $vars Either object or array containg key/value pairs to be added
	 * @return $this
	 */
	function adds($vars)
	{
		if(is_object($vars))
		{
			$vars = get_object_vars($vars);
		}
		if(is_array($vars))
		{
			foreach($vars as $key => $val)
			{
				$this->variables[$key] = $val;
			}
		}
		return $this;
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
	 * @return object Returns an object containing key/value pairs
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
	 * @return object
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

/**
 * Alias to Object for backward compatibility.
 */
if (version_compare(PHP_VERSION, '7.2', '<'))
{
	class_alias('BaseObject', 'Object');
}

/* End of file Object.class.php */
/* Location: ./classes/object/Object.class.php */