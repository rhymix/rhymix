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
	public $error = 0;

	/**
	 * Error message. If `success`, it is not an error.
	 * @var string
	 */
	public $message = 'success';

	/**
	 * An additional variable
	 * @var array
	 */
	public $variables = array();

	/**
	 * http status code.
	 * @var int
	 */
	public $httpStatusCode = 200;

	/**
	 * Constructor
	 *
	 * @param int $error Error code
	 * @param string $message Error message
	 * @return void
	 */
	public function __construct($error = 0, $message = 'success')
	{
		$this->setError($error);
		$this->setMessage($message);
		
		if ($error)
		{
			$backtrace = debug_backtrace(false);
			$caller = array_shift($backtrace);
			$nextcaller = array_shift($backtrace);
			if ($nextcaller && $nextcaller['function'] === 'createObject')
			{
				$caller = $nextcaller;
			}
			$location = $caller['file'] . ':' . $caller['line'];
			$this->add('rx_error_location', $location);
		}
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
	public function setError($error = 0)
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
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Setter to set HTTP status code
	 *
	 * @param int $code HTTP status code. Default value is `200` that means successful
	 * @return $this
	 */
	public function setHttpStatusCode($code = 200)
	{
		$this->httpStatusCode = (int)$code;
		return $this;
	}

	/**
	 * Getter to retrieve HTTP status code
	 *
	 * @return int Returns HTTP status code
	 */
	public function getHttpStatusCode()
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
	public function setMessage($message = 'success', $type = null)
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
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * set type of message
	 * @param string $type type of message (error, info, update)
	 * @return $this
	 * */
	public function setMessageType($type)
	{
		$this->variables['message_type'] = strval($type);
		return $this;
	}

	/**
	 * get type of message
	 * @return string $type
	 * */
	public function getMessageType()
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
	public function set($key, $val)
	{
		$this->variables[$key] = $val;
		return $this;
	}

	/**
	 * Alias to set().
	 */
	public function add($key, $val)
	{
		$this->variables[$key] = $val;
		return $this;
	}

	/**
	 * Method to set multiple key/value pairs as additional variables
	 *
	 * @param object|array $vars Either object or array containg key/value pairs to be added
	 * @return $this
	 */
	public function sets($vars)
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
	 * Alias to sets().
	 */
	public function adds($vars)
	{
		return $this->sets($vars);
	}

	/**
	 * Method to retrieve a corresponding value to a given key
	 *
	 * @param string $key
	 * @return mixed Returns value to a given key
	 */
	public function get($key)
	{
		return $this->variables[$key] ?? null;
	}

	/**
	 * Method to retrieve an object containing a key/value pairs
	 *
	 * @return object Returns an object containing key/value pairs
	 */
	public function gets()
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
	public function getVariables()
	{
		return $this->variables;
	}

	/**
	 * Method to retrieve an object of key/value pairs
	 *
	 * @return object
	 */
	public function getObjectVars()
	{
		return (object)($this->variables);
	}

	/**
	 * Method to delete a key
	 *
	 * @return void
	 */
	public function unset($key)
	{
		unset($this->variables[$key]);
	}

	/**
	 * Method to return either true or false depnding on the value in a 'error' variable
	 *
	 * @return bool Retruns true : error isn't 0 or false : otherwise.
	 */
	public function toBool()
	{
		// TODO This method is misleading in that it returns true if error is 0, which should be true in boolean representation.
		return ($this->error == 0);
	}

	/**
	 * Method to return either true or false depnding on the value in a 'error' variable
	 *
	 * @return bool
	 */
	public function toBoolean()
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
