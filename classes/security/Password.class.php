<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class Password
{
	public static function registerCustomAlgorithm($name, $regexp, $callback)
	{
		Rhymix\Framework\Password::addAlgorithm($name, $regexp, $callback);
	}

	public static function getSupportedAlgorithms()
	{
		return Rhymix\Framework\Password::getSupportedAlgorithms();
	}

	public static function getBestAlgorithm()
	{
		return Rhymix\Framework\Password::getBestSupportedAlgorithm();
	}

	public static function getCurrentlySelectedAlgorithm()
	{
		return Rhymix\Framework\Password::getDefaultAlgorithm();
	}

	public static function getWorkFactor()
	{
		return Rhymix\Framework\Password::getWorkFactor();
	}

	public static function createHash($password, $algorithm = null)
	{
		return Rhymix\Framework\Password::hashPassword($password, $algorithm);
	}

	public static function checkPassword($password, $hash, $algorithm = null)
	{
		return Rhymix\Framework\Password::checkPassword($password, $hash, $algorithm);
	}

	public static function checkAlgorithm($hash)
	{
		$algos = Rhymix\Framework\Password::checkAlgorithm($hash);
		return count($algos) ? $algos[0] : false;
	}

	public static function checkWorkFactor($hash)
	{
		return Rhymix\Framework\Password::checkWorkFactor($hash);
	}

	public static function createSecureSalt($length, $format = 'hex')
	{
		return Rhymix\Framework\Security::getRandom($length, $format);
	}

	public static function createTemporaryPassword($length = 16)
	{
		return Rhymix\Framework\Password::getRandomPassword($length);
	}
	
	public static function createSignature($string)
	{
		return Rhymix\Framework\Security::createSignature($string);
	}
	
	public static function checkSignature($string, $signature)
	{
		return Rhymix\Framework\Security::verifySignature($string, $signature);
	}
	
	public static function getSecretKey()
	{
		return config('crypto.authentication_key');
	}
	
	public static function pbkdf2($password, $salt, $algorithm = 'sha256', $iterations = 8192, $length = 24)
	{
		$hash = Rhymix\Framework\Security::pbkdf2($password, $salt, $algorithm, $iterations, $length);
		$hash = explode(':', $hash);
		return base64_decode($hash[3]);
	}

	public static function bcrypt($password, $salt = null)
	{
		return Rhymix\Framework\Security::bcrypt($password, $salt);
	}

	public static function strcmpConstantTime($a, $b)
	{
		return Rhymix\Framework\Security::compareStrings($a, $b);
	}
}
/* End of file : Password.class.php */
/* Location: ./classes/security/Password.class.php */
