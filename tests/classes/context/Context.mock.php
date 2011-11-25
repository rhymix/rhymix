<?php

if(!defined('FOLLOW_REQUEST_SSL')) define('FOLLOW_REQUEST_SSL',0);
if(!defined('ENFORCE_SSL')) define('ENFORCE_SSL',1);
if(!defined('RELEASE_SSL')) define('RELEASE_SSL',2);
if(!defined('MOCK_CONTEXT')) define('MOCK_CONTEXT', 1);

class Context
{
	public static $mock_vars = array();
	private static $useCdn = 'N';
	private static $requestUrl = 'http://www.test.com';

	public function gets() {
		$args = func_get_args();
		$output = new stdClass;

		foreach($args as $name) {
			$output->{$name} = self::$mock_vars[$name];
		}

		return $output;
	}

	public function get($name) {
		return array_key_exists($name, self::$mock_vars)?self::$mock_vars[$name]:'';
	}

	public function getRequestVars() {
		return self::$mock_vars;
	}

	public function set($name, $value) {
		self::$mock_vars[$name] = $value;
	}

	public function getLangType() {
		return 'en';
	}

	public function getLang($str) {
		return $str;
	}

	public function truncate() {
		self::$mock_vars = array();
	}

	public static function setUseCdn($useCdn)
	{
		self::$useCdn = $useCdn != 'Y' ? 'N':'Y';
	}

	public static function getDBInfo() {
		$dbInfo = new stdClass();
		$dbInfo->use_cdn = self::$useCdn;

		return $dbInfo;
	}


	public static function setRequestUrl($url)
	{
		self::$requestUrl= $url;
	}

	public static function getRequestUrl() {
		return self::$requestUrl;
	}
}

/* End of file Context.mock.php */
/* Location: ./tests/classes/context/Context.mock.php */
