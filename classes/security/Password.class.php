<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * This class can be used to hash passwords using various algorithms and check their validity.
 * It is fully compatible with previous defaults, while also supporting bcrypt and pbkdf2.
 *
 * @file Password.class.php
 * @author Kijin Sung (kijin@kijinsung.com)
 * @package /classes/security
 * @version 1.1
 */
class Password
{
	/**
	 * @brief Return the list of hashing algorithms supported by this server
	 * @return array
	 */
	public function getSupportedAlgorithms()
	{
		$retval = array();
		if(function_exists('hash_hmac') && in_array('sha256', hash_algos()))
		{
			$retval['pbkdf2'] = 'pbkdf2';
		}
		if(version_compare(PHP_VERSION, '5.3.7', '>=') && defined('CRYPT_BLOWFISH'))
		{
			$retval['bcrypt'] = 'bcrypt';
		}
		$retval['md5'] = 'md5';
		return $retval;
	}

	/**
	 * @brief Return the best hashing algorithm supported by this server
	 * @return string
	 */
	public function getBestAlgorithm()
	{
		$algos = $this->getSupportedAlgorithms();
		return key($algos);
	}

	/**
	 * @brief Return the currently selected hashing algorithm
	 * @return string
	 */
	public function getCurrentlySelectedAlgorithm()
	{
		if(function_exists('getModel'))
		{
			$config = getModel('member')->getMemberConfig();
			$algorithm = $config->password_hashing_algorithm;
			if(strval($algorithm) === '')
			{
				$algorithm = 'md5';  // Historical default for XE
			}
		}
		else
		{
			$algorithm = 'md5';
		}
		return $algorithm;
	}

	/**
	 * @brief Return the currently configured work factor for bcrypt and other adjustable algorithms
	 * @return int
	 */
	public function getWorkFactor()
	{
		if(function_exists('getModel'))
		{
			$config = getModel('member')->getMemberConfig();
			$work_factor = $config->password_hashing_work_factor;
			if(!$work_factor || $work_factor < 4 || $work_factor > 31)
			{
				$work_factor = 8;  // Reasonable default
			}
		}
		else
		{
			$work_factor = 8;
		}
		return $work_factor;
	}

	/**
	 * @brief Create a hash using the specified algorithm
	 * @param string $password The password
	 * @param string $algorithm The algorithm (optional)
	 * @return string
	 */
	public function createHash($password, $algorithm = null)
	{
		if($algorithm === null)
		{
			$algorithm = $this->getCurrentlySelectedAlgorithm();
		}
		if(!array_key_exists($algorithm, $this->getSupportedAlgorithms()))
		{
			return false;
		}

		$password = trim($password);

		switch($algorithm)
		{
			case 'md5':
				return md5($password);

			case 'pbkdf2':
				$iterations = pow(2, $this->getWorkFactor() + 5);
				$salt = $this->createSecureSalt(12, 'alnum');
				$hash = base64_encode($this->pbkdf2($password, $salt, 'sha256', $iterations, 24));
				return 'sha256:'.sprintf('%07d', $iterations).':'.$salt.':'.$hash;

			case 'bcrypt':
				return $this->bcrypt($password);

			default:
				return false;
		}
	}

	/**
	 * @brief Check if a password matches a hash
	 * @param string $password The password
	 * @param string $hash The hash
	 * @param string $algorithm The algorithm (optional)
	 * @return bool
	 */
	public function checkPassword($password, $hash, $algorithm = null)
	{
		if($algorithm === null)
		{
			$algorithm = $this->checkAlgorithm($hash);
		}

		$password = trim($password);

		switch($algorithm)
		{
			case 'md5':
				return md5($password) === $hash || md5(sha1(md5($password))) === $hash;

			case 'mysql_old_password':
				return (class_exists('Context') && substr(Context::getDBType(), 0, 5) === 'mysql') ?
					DB::getInstance()->isValidOldPassword($password, $hash) : false;

			case 'mysql_password':
				return $hash[0] === '*' && substr($hash, 1) === strtoupper(sha1(sha1($password, true)));

			case 'pbkdf2':
				$hash = explode(':', $hash);
				$hash[3] = base64_decode($hash[3]);
				$hash_to_compare = $this->pbkdf2($password, $hash[2], $hash[0], intval($hash[1], 10), strlen($hash[3]));
				return $this->strcmpConstantTime($hash_to_compare, $hash[3]);

			case 'bcrypt':
				$hash_to_compare = $this->bcrypt($password, $hash);
				return $this->strcmpConstantTime($hash_to_compare, $hash);

			default:
				return false;
		}
	}

	/**
	 * @brief Check the algorithm used to create a hash
	 * @param string $hash The hash
	 * @return string
	 */
	function checkAlgorithm($hash)
	{
		if(preg_match('/^\$2[axy]\$([0-9]{2})\$/', $hash, $matches))
		{
			return 'bcrypt';
		}
		elseif(preg_match('/^sha[0-9]+:([0-9]+):/', $hash, $matches))
		{
			return 'pbkdf2';
		}
		elseif(strlen($hash) === 32 && ctype_xdigit($hash))
		{
			return 'md5';
		}
		elseif(strlen($hash) === 16 && ctype_xdigit($hash))
		{
			return 'mysql_old_password';
		}
		elseif(strlen($hash) === 41 && $hash[0] === '*')
		{
			return 'mysql_password';
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief Check the work factor of a hash
	 * @param string $hash The hash
	 * @return int
	 */
	function checkWorkFactor($hash)
	{
		if(preg_match('/^\$2[axy]\$([0-9]{2})\$/', $hash, $matches))
		{
			return intval($matches[1], 10);
		}
		elseif(preg_match('/^sha[0-9]+:([0-9]+):/', $hash, $matches))
		{
			return max(0, round(log($matches[1], 2)) - 5);
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief Generate a cryptographically secure random string to use as a salt
	 * @param int $length The number of bytes to return
	 * @param string $format hex or alnum
	 * @return string
	 */
	public function createSecureSalt($length, $format = 'hex')
	{
		// Find out how many bytes of entropy we really need
		switch($format)
		{
			case 'hex':
				$entropy_required_bytes = ceil($length / 2);
				break;
			case 'alnum':
			case 'printable':
				$entropy_required_bytes = ceil($length * 3 / 4);
				break;
			default:
				$entropy_required_bytes = $length;
		}

		// Cap entropy to 256 bits from any one source, because anything more is meaningless
		$entropy_capped_bytes = min(32, $entropy_required_bytes);

		// Find and use the most secure way to generate a random string
		$is_windows = (defined('PHP_OS') && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
		if(function_exists('openssl_random_pseudo_bytes') && (!$is_windows || version_compare(PHP_VERSION, '5.4', '>=')))
		{
			$entropy = openssl_random_pseudo_bytes($entropy_capped_bytes);
		}
		elseif(function_exists('mcrypt_create_iv') && (!$is_windows || version_compare(PHP_VERSION, '5.3.7', '>=')))
		{
			$entropy = mcrypt_create_iv($entropy_capped_bytes, MCRYPT_DEV_URANDOM);
		}
		elseif(function_exists('mcrypt_create_iv') && $is_windows)
		{
			$entropy = mcrypt_create_iv($entropy_capped_bytes, MCRYPT_RAND);
		}
		elseif(!$is_windows && @is_readable('/dev/urandom'))
		{
			$fp = fopen('/dev/urandom', 'rb');
			$entropy = fread($fp, $entropy_capped_bytes);
			fclose($fp);
		}
		else
		{
			$entropy = '';
			for($i = 0; $i < $entropy_capped_bytes; $i += 2)
			{
				$entropy .= pack('S', rand(0, 65536) ^ mt_rand(0, 65535));
			}
		}

		// Mixing (see RFC 4086 section 5)
		$output = '';
		for($i = 0; $i < $entropy_required_bytes; $i += 32)
		{
			$output .= hash('sha256', $entropy . $i . rand(), true);
		}

		// Encode and return the random string
		switch($format)
		{
			case 'hex':
				return substr(bin2hex($output), 0, $length);
			case 'binary':
				return substr($output, 0, $length);
			case 'printable':
				$salt = '';
				for($i = 0; $i < $length; $i++)
				{
					$salt .= chr(33 + (crc32(sha1($i . $output)) % 94));
				}
				return $salt;
			case 'alnum':
			default:
				$salt = substr(base64_encode($output), 0, $length);
				$replacements = chr(rand(65, 90)) . chr(rand(97, 122)) . rand(0, 9);
				return strtr($salt, '+/=', $replacements);
		}
	}

	/**
	 * @brief Generate a temporary password using the secure salt generator
	 * @param int $length The number of bytes to return
	 * @return string
	 */
	public function createTemporaryPassword($length = 16)
	{
		while(true)
		{
			$source = base64_encode($this->createSecureSalt(64, 'binary'));
			$source = strtr($source, 'iIoOjl10/', '@#$%&*-!?');
			$source_length = strlen($source);
			for($i = 0; $i < $source_length - $length; $i++)
			{
				$candidate = substr($source, $i, $length);
				if(preg_match('/[a-z]/', $candidate) && preg_match('/[A-Z]/', $candidate) &&
					preg_match('/[0-9]/', $candidate) && preg_match('/[^a-zA-Z0-9]/', $candidate))
				{
					return $candidate;
				}
			}
		}
	}

	/**
	 * @brief Generate the PBKDF2 hash of a string using a salt
	 * @param string $password The password
	 * @param string $salt The salt
	 * @param string $algorithm The algorithm (optional, default is sha256)
	 * @param int $iterations Iteration count (optional, default is 8192)
	 * @param int $length The length of the hash (optional, default is 32)
	 * @return string
	 */
	public function pbkdf2($password, $salt, $algorithm = 'sha256', $iterations = 8192, $length = 24)
	{
		if(function_exists('hash_pbkdf2'))
		{
			return hash_pbkdf2($algorithm, $password, $salt, $iterations, $length, true);
		}
		else
		{
			$output = '';
			$block_count = ceil($length / strlen(hash($algorithm, '', true)));  // key length divided by the length of one hash
			for($i = 1; $i <= $block_count; $i++)
			{
				$last = $salt . pack('N', $i);  // $i encoded as 4 bytes, big endian
				$last = $xorsum = hash_hmac($algorithm, $last, $password, true);  // first iteration
				for($j = 1; $j < $iterations; $j++)  // The other $count - 1 iterations
				{
					$xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
				}
				$output .= $xorsum;
			}
			return substr($output, 0, $length);
		}
	}

	/**
	 * @brief Generate the bcrypt hash of a string using a salt
	 * @param string $password The password
	 * @param string $salt The salt (optional, auto-generated if empty)
	 * @return string
	 */
	public function bcrypt($password, $salt = null)
	{
		if($salt === null)
		{
			$salt = '$2y$'.sprintf('%02d', $this->getWorkFactor()).'$'.$this->createSecureSalt(22, 'alnum');
		}
		return crypt($password, $salt);
	}

	/**
	 * @brief Compare two strings in constant time
	 * @param string $a The first string
	 * @param string $b The second string
	 * @return bool
	 */
	function strcmpConstantTime($a, $b)
	{
		$diff = strlen($a) ^ strlen($b);
		$maxlen = min(strlen($a), strlen($b));
		for($i = 0; $i < $maxlen; $i++)
		{
			$diff |= ord($a[$i]) ^ ord($b[$i]);
		}
		return $diff === 0;
	}
}
/* End of file : Password.class.php */
/* Location: ./classes/security/Password.class.php */
