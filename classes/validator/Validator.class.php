<?php
/**
 * Validator class
 */
class Validator
{
	/**
	 * @constructor
	 */
	function Validator($xml_path){
	}

	/**
	 * Set root cache directory
	 * @param[in] string $cache_dir Root cache directory
	 */
	function setCacheDir($cache_dir){
	}

	/**
	 * Set target fields to be checked.
	 * The keys of array represents filed's name, its values represents field's vale.
	 * @param[in] array $fields Target fields
	 */
	function setFields($fields){
	}

	/**
	 * Validate the fields. If the fields aren't passed, validation will be execute on the Context variables.
	 * @param[in] (optional) array $fields
	 * @return bool True if it is valid, FALSE otherwise.
	 */
	function validate($fields){
	}

	/**
	 * Returns the last error message
	 * @return string error message
	 */
	function getLastError(){
	}

	/**
	 * Add a new rule
	 * @param[in] string $name rule name
	 * @param[in] string $rule
	 */
	function addRule($name, $rule){
	}

	/**
	 * Remove a rule
	 * @param[in] string $name rule name
	 */
	function removeRule($name){
	}

	/**
	 * Find whether the field is valid with the rule
	 * @param[in] string $name rule name
	 * @param[in] string $field field name
	 * @return bool TRUE if the field is valid, FALSE otherwise.
	 */
	function applyRule($name, $field){
	}

	/**
	 * Returns compiled javascript file path. The path begins from XE root directory.
	 * @return string Compiled JavaScript file path
	 */
	function getJsPath(){
	}
}

/* End of file Validator.class.php */
/* Location: ./classes/validator/Validator.class.php */
