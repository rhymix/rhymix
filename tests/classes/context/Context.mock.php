<?php

if(!defined('FOLLOW_REQUEST_SSL')) define('FOLLOW_REQUEST_SSL',0);
if(!defined('ENFORCE_SSL')) define('ENFORCE_SSL',1);
if(!defined('RELEASE_SSL')) define('RELEASE_SSL',2);

class Context
{
	public static $mock_vars = array();

	public function gets() {
		$args = func_get_args();
		$output = new stdClass;

		foreach($args as $name) {
			$output->{$name} = Context::$mock_vars[$name];
		}

		return $output;
	}

	public function get($name) {
		return array_key_exists($name, Context::$mock_vars)?Context::$mock_vars[$name]:'';
	}

	public function getRequestVars() {
		return Context::$mock_vars;
	}

	public function set($name, $value) {
		Context::$mock_vars[$name] = $value;
	}

	public function getLangType() {
		return 'en';
	}

	public function getLang($str) {
		return $str;
	}

	public function truncate() {
		Context::$mock_vars = array();
	}

	public function getDBInfo() {
		global $use_cdn;

		$dbInfo = new stdClass;
		$dbInfo->use_cdn = $use_cdn;

		return $dbInfo;
	}

	public function getRequestUrl() {
		global $request_url;
		return $request_url;
	}
}

/* End of file Context.mock.php */
/* Location: ./tests/classes/context/Context.mock.php */
