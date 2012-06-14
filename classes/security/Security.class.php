<?php
/**
 * - Security class
 * - This class helps to solve security problems.
 * @author NHN (developers@xpressengine.com)
 * @package /classes/security
 * @version 0.1
 */
class Security
{
	/**
	 * Action target variable. If this value is null, the method will use Context variables
	 * @var mixed
	 **/
	var $_targetVar = null;

	/**
	 * @constructor
	 * @param mixed $var Target context
	 * @return void
	 */
	function Security($var = null)
	{
		$this->_targetVar = $var;
	}

	/**
	 * - Convert special characters to HTML entities for the target variables.
	 * - The results of conversion are equivalent to the results of htmlspecialchars() which is a native function of PHP.
	 * @params string $varName. A variable's name to convert to process properties of an object or elements of an array,
	 * separate the owner(object or array) and the item(property or element) using a dot(.)
	 * @return mixed
	 */
	function encodeHTML(/*, $varName1, $varName2, ... */)
	{
		$varNames = func_get_args();
		if(count($varNames) < 0) return false;

		$use_context = is_null($this->_targetVar);
		if(!$use_context) {
			if(!count($varNames) || (!is_object($this->_targetVar) && !is_array($this->_targetVar)) ) return $this->_encodeHTML($this->_targetVar);

			$is_object = is_object($this->_targetVar);
		}

		foreach($varNames as $varName) {
			$varName  = explode('.', $varName);
			$varName0 = array_shift($varName);
			if($use_context) {
				$var = Context::get($varName0);
			} elseif($varName0) {
				$var = $is_object ? $this->_targetVar->{$varName0} : $this->_targetVar[$varName0];
			} else {
				$var = $this->_targetVar;
			}
			$var = $this->_encodeHTML($var, $varName);

			if($var === false) continue;

			if($use_context) {
				Context::set($varName0, $var);
			} elseif($varName0) {
				if($is_object) $this->_targetVar->{$varName0} = $var;
				else $this->_targetVar[$varName0] = $var;
			} else {
				$this->_targetVar = $var;
			}
		}

		if (!$use_context) return $this->_targetVar;
	}

	/**
	 * Convert special characters to HTML entities for the target variables.
	 * @param mixed $var
	 * @param array $name
	 * @return mixed
	 */
	function _encodeHTML($var, $name=array())
	{
		if(is_string($var)) {
			if (!preg_match('/^\$user_lang->/', $var)) $var = htmlspecialchars($var);
			return $var;
		}

		if(!count($name) || (!is_array($var) && !is_object($var)) ) return false;

		$is_object = is_object($var);
		$name0  = array_shift($name);

		if(strlen($name0)) {
			$target = $is_object ? $var->{$name0} : $var[$name0];
			$target = $this->_encodeHTML($target, $name);

			if($target === false) return $var;

			if($is_object) $var->{$name0} = $target;
			else $var[$name0] = $target;

			return $var;
		}

		foreach($var as $key=>$target) {
			$cloned_name = array_slice($name, 0);
			$target = $this->_encodeHTML($target, $name);
			$name   = $cloned_name;

			if($target === false) continue;

			if($is_object) $var->{$key} = $target;
			else $var[$key] = $target;
		}

		return $var;
	}
}

/* End of file : Security.class.php */
