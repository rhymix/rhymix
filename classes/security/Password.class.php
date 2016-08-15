<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class Password
{
	public static function registerCustomAlgorithm($name, $regexp, $callback)
	{
		Rhymix\Framework\Password::addAlgorithm($name, $regexp, $callback);
	}

	public function getSupportedAlgorithms()
	{
		return Rhymix\Framework\Password::getSupportedAlgorithms();
	}

	public function getBestAlgorithm()
	{
		return Rhymix\Framework\Password::getBestSupportedAlgorithm();
	}

	public function getCurrentlySelectedAlgorithm()
	{
		return Rhymix\Framework\Password::getDefaultAlgorithm();
	}

	public function getWorkFactor()
	{
		return Rhymix\Framework\Password::getWorkFactor();
	}

	public function createHash($password, $algorithm = null)
	{
		return Rhymix\Framework\Password::hashPassword($password, $algorithm);
	}

	public function checkPassword($password, $hash, $algorithm = null)
	{
		return Rhymix\Framework\Password::checkPassword($password, $hash, $algorithm);
	}

	function checkAlgorithm($hash)
	{
		$algos = Rhymix\Framework\Password::checkAlgorithm($hash);
		return count($algos) ? $algos[0] : false;
	}

	function checkWorkFactor($hash)
	{
		return Rhymix\Framework\Password::checkWorkFactor($hash);
	}

	public function createSecureSalt($length, $format = 'hex')
	{
		return Rhymix\Framework\Security::getRandom($length, $format);
	}

	public function createTemporaryPassword($length = 16)
	{
		return Rhymix\Framework\Password::getRandomPassword($length);
	}
	
	public function createSignature($string)
	{
		return Rhymix\Framework\Security::createSignature($string);
	}
	
	public function checkSignature($string, $signature)
	{
		return Rhymix\Framework\Security::verifySignature($string, $signature);
	}
	
	public function getSecretKey()
	{
		return config('crypto.authentication_key');
	}
	
	public function pbkdf2($password, $salt, $algorithm = 'sha256', $iterations = 8192, $length = 24)
	{
		$hash = Rhymix\Framework\Security::pbkdf2($password, $salt, $algorithm, $iterations, $length);
		$hash = explode(':', $hash);
		return base64_decode($hash[3]);
	}

	public function bcrypt($password, $salt = null)
	{
		return Rhymix\Framework\Security::bcrypt($password, $salt);
	}

	function strcmpConstantTime($a, $b)
	{
		return Rhymix\Framework\Security::compareStrings($a, $b);
	}
}
/* End of file : Password.class.php */
/* Location: ./classes/security/Password.class.php */
