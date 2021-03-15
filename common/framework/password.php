<?php

namespace Rhymix\Framework;

/**
 * The password class.
 */
class Password
{
	/**
	 * Regular expressions to detect various hashing algorithms.
	 */
	protected static $_algorithm_callbacks = array();
	protected static $_algorithm_signatures = array(
		'bcrypt' => '/^\$2[a-z]\$[0-9]{2}\$/',
		'pbkdf2' => '/^[a-z0-9]+:[0-9]+:/',
		'md5' => '/^[0-9a-f]{32}$/',
		'md5,sha1,md5' => '/^[0-9a-f]{32}$/',
		'sha1' => '/^[0-9a-f]{40}$/',
		'sha256' => '/^[0-9a-f]{64}$/',
		'sha384' => '/^[0-9a-f]{96}$/',
		'sha512' => '/^[0-9a-f]{128}$/',
		'ripemd160' => '/^[0-9a-f]{40}$/',
		'whirlpool' => '/^[0-9a-f]{128}$/',
		'mssql_pwdencrypt' => '/^0x0100[0-9A-F]{48}$/',
		'mysql_old_password' => '/^[0-9a-f]{16}$/',
		'mysql_new_password' => '/^\*[0-9A-F]{40}$/',
		'portable' => '/^\$P\$/',
		'drupal' => '/^\$S\$/',
		'joomla' => '/^[0-9a-f]{32}:[0-9a-zA-Z\.\+\/\=]{32}$/',
		'kimsqrb' => '/\$[1-4]\$[0-9]{14}$/',
		'crypt' => '/^([0-9a-zA-Z\.\/]{13}$|_[0-9a-zA-Z\.\/]{19}$|\$[156]\$)/',
	);
	
	/**
	 * Add a custom algorithm.
	 * 
	 * @param string $name
	 * @param string $signature
	 * @param callable $callback
	 * @return void
	 */
	public static function addAlgorithm($name, $signature, $callback)
	{
		self::$_algorithm_signatures[$name] = $signature;
		self::$_algorithm_callbacks[$name] = $callback;
	}
	
	/**
	 * Check if the given sequence of algorithms is valid.
	 * 
	 * @param array|string $algos
	 * @return bool
	 */
	public static function isValidAlgorithm($algos)
	{
		$hash_algos = hash_algos();
		$algos = is_array($algos) ? $algos : explode(',', $algos);
		foreach ($algos as $algo)
		{
			if (array_key_exists($algo, self::$_algorithm_signatures))
			{
				continue;
			}
			if (in_array($algo, $hash_algos))
			{
				continue;
			}
			return false;
		}
		return true;
	}
	
	/**
	 * Get the list of hashing algorithms supported by this server.
	 * 
	 * @return array
	 */
	public static function getSupportedAlgorithms()
	{
		$retval = array();
		if (defined('\CRYPT_BLOWFISH'))
		{
			$retval['bcrypt'] = 'bcrypt';
		}
		if (in_array('sha512', hash_algos()))
		{
			$retval['pbkdf2'] = 'pbkdf2';
		}
		$retval['sha512'] = 'sha512';
		$retval['sha256'] = 'sha256';
		$retval['sha1'] = 'sha1';
		$retval['md5'] = 'md5';
		return $retval;
	}

	/**
	 * Get the best hashing algorithm supported by this server.
	 * 
	 * @return string
	 */
	public static function getBestSupportedAlgorithm()
	{
		$algos = self::getSupportedAlgorithms();
		return key($algos);
	}

	/**
	 * Get the current default hashing algorithm.
	 * 
	 * @return string
	 */
	public static function getDefaultAlgorithm()
	{
		if (class_exists('\MemberModel'))
		{
			$config = @\MemberModel::getInstance()->getMemberConfig();
			$algorithm = $config->password_hashing_algorithm ?? '';
			if (strval($algorithm) === '')
			{
				$algorithm = 'md5';
			}
		}
		else
		{
			$algorithm = 'md5';
		}
		return $algorithm;
	}

	/**
	 * Get the currently configured work factor for bcrypt and other adjustable algorithms.
	 * 
	 * @return int
	 */
	public static function getWorkFactor()
	{
		if (class_exists('\MemberModel'))
		{
			$config = @\MemberModel::getInstance()->getMemberConfig();
			$work_factor = $config->password_hashing_work_factor ?? 10;
			if (!$work_factor || $work_factor < 4 || $work_factor > 31)
			{
				$work_factor = 10;
			}
		}
		else
		{
			$work_factor = 10;
		}
		
		return $work_factor;
	}
	
	/**
	 * Generate a reasonably strong random password.
	 * 
	 * @param int $length
	 * @return string
	 */
	public static function getRandomPassword($length = 16)
	{
		while(true)
		{
			$source = base64_encode(Security::getRandom(64, 'binary'));
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
	 * Hash a password.
	 * 
	 * To use multiple algorithms in series, provide them as an array.
	 * Salted algorithms such as bcrypt, pbkdf2, or portable must be used last.
	 * On error, false will be returned.
	 * 
	 * @param string $password
	 * @param string|array $algos (optional)
	 * @param string $salt (optional)
	 * @return string|false
	 */
	public static function hashPassword($password, $algos = null, $salt = null)
	{
		// If the algorithm is null, use the default algorithm.
		if ($algos === null)
		{
			$algos = self::getDefaultAlgorithm();
		}
		
		// Initialize the chain of hashes.
		$algos = array_map('strtolower', array_map('trim', is_array($algos) ? $algos : explode(',', $algos)));
		$hashchain = preg_replace('/\\s+/', ' ', trim($password));
		
		// Apply the given algorithms one by one.
		foreach ($algos as $algo)
		{
			switch ($algo)
			{
				// bcrypt (must be used last)
				case 'bcrypt':
					$hashchain = self::bcrypt($hashchain, $salt, self::getWorkFactor());
					if ($hashchain[0] === '*') return false;
					return $hashchain;
				
				// PBKDF2 (must be used last)
				case 'pbkdf2':
					if ($salt === null)
					{
						$salt = Security::getRandom(12, 'alnum');
						$hash_algorithm = 'sha512';
						$iterations = intval(pow(2, self::getWorkFactor() + 5)) ?: 16384;
						$key_length = 24;
					}
					else
					{
						$parts = explode(':', $salt);
						$salt = $parts[2];
						$hash_algorithm = $parts[0];
						$iterations = intval($parts[1], 10);
						$key_length = strlen(base64_decode($parts[3]));
					}
					$iterations_padding = ($salt === null || !isset($parts[1])) ? 7 : strlen($parts[1]);
					return self::pbkdf2($hashchain, $salt, $hash_algorithm, $iterations, $key_length, $iterations_padding);
				
				// phpass portable algorithm (must be used last)
				case 'portable':
					$phpass = new \Hautelook\Phpass\PasswordHash(self::getWorkFactor(), true);
					if ($salt === null)
					{
						$hashchain = $phpass->HashPassword($hashchain);
						return $hashchain;
					}
					else
					{
						$match = $phpass->CheckPassword($hashchain, $salt);
						return $match ? $salt : false;
					}
				
				// Drupal's SHA-512 based algorithm (must be used last)
				case 'drupal':
					$hashchain = \VendorPass::drupal($password, $salt);
					return $hashchain;
				
				// Joomla's MD5 based algorithm (must be used last)
				case 'joomla':
					$hashchain = \VendorPass::joomla($password, $salt);
					return $hashchain;
				
				// KimsQ Rb algorithms (must be used last)
				case 'kimsqrb':
					$hashchain = \VendorPass::kimsqrb($password, $salt);
					return $hashchain;
				
				// crypt() function (must be used last)
				case 'crypt':
					if ($salt === null) $salt = Security::getRandom(2, 'alnum');
					$hashchain = crypt($hashchain, $salt);
					return $hashchain;
				
				// MS SQL's PWDENCRYPT() function (must be used last)
				case 'mssql_pwdencrypt':
					$hashchain = \VendorPass::mssql_pwdencrypt($hashchain, $salt);
					return $hashchain;
				
				// MySQL's old PASSWORD() function.
				case 'mysql_old_password':
					$hashchain = \VendorPass::mysql_old_password($hashchain);
					break;
				
				// MySQL's new PASSWORD() function.
				case 'mysql_new_password':
					$hashchain = \VendorPass::mysql_new_password($hashchain);
					break;
				
				// A dummy algorithm that does nothing.
				case 'null':
					break;
				
				// All other algorithms will be passed to hash() or treated as a function name.
				default:
					if (isset(self::$_algorithm_callbacks[$algo]))
					{
						$callback = self::$_algorithm_callbacks[$algo];
						$hashchain = $callback($hashchain, $salt);
					}
					elseif (in_array($algo, hash_algos()))
					{
						$hashchain = hash($algo, $hashchain);
					}
					elseif (function_exists($algo))
					{
						$hashchain = $algo($hashchain, $salt);
					}
					else
					{
						return false;
					}
			}
		}
		
		return $hashchain;
	}
	
	/**
	 * Check a password against a hash.
	 * 
	 * This method returns true if the password is correct, and false otherwise.
	 * If the algorithm is not specified, it will be guessed from the format of the hash.
	 * 
	 * @param string $password
	 * @param string $hash
	 * @param array|string $algos
	 * @return bool
	 */
	public static function checkPassword($password, $hash, $algos = null)
	{
		if ($algos === null)
		{
			$algos = self::checkAlgorithm($hash);
			foreach ($algos as $algo)
			{
				if (Security::compareStrings($hash, self::hashPassword($password, $algo, $hash)))
				{
					return true;
				}
			}
			return false;
		}
		else
		{
			return Security::compareStrings($hash, self::hashPassword($password, $algos, $hash));
		}
	}
	
	/**
	 * Guess which algorithm(s) were used to generate the given hash.
	 * 
	 * If there are multiple possibilities, all of them will be returned in an array.
	 * 
	 * @param string $hash
	 * @return array
	 */
	public static function checkAlgorithm($hash)
	{
		$candidates = array();
		foreach (self::$_algorithm_signatures as $name => $signature)
		{
			if (preg_match($signature, $hash)) $candidates[] = $name;
		}
		return $candidates;
	}
	
	/**
	 * Check the work factor of a hash.
	 * 
	 * @param string $hash
	 * @return int
	 */
	public static function checkWorkFactor($hash)
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
			return 0;
		}
	}
	
	/**
	 * Generate the bcrypt hash of a string.
	 * 
	 * @param string $password
	 * @param string $salt (optional)
	 * @param int $work_factor (optional)
	 * @return string
	 */
	public static function bcrypt($password, $salt = null, $work_factor = 10)
	{
		if ($salt === null)
		{
			$salt = '$2y$' . sprintf('%02d', $work_factor) . '$' . Security::getRandom(22, 'alnum');
		}
		
		return crypt($password, $salt);
	}
	
	/**
	 * Generate the PBKDF2 hash of a string.
	 * 
	 * @param string $password
	 * @param string $salt (optional)
	 * @param string $algorithm (optional)
	 * @param int $iterations (optional)
	 * @param int $length (optional)
	 * @param int $iterations_padding (optional)
	 * @return string
	 */
	public static function pbkdf2($password, $salt = null, $algorithm = 'sha512', $iterations = 16384, $length = 24, $iterations_padding = 7)
	{
		if ($salt === null)
		{
			$salt = Security::getRandom(12, 'alnum');
		}
		
		if (function_exists('hash_pbkdf2'))
		{
			$hash = hash_pbkdf2($algorithm, $password, $salt, $iterations, $length, true);
		}
		else
		{
			$output = '';
			$block_count = ceil($length / strlen(hash($algorithm, '', true)));  // key length divided by the length of one hash
			for ($i = 1; $i <= $block_count; $i++)
			{
				$last = $salt . pack('N', $i);  // $i encoded as 4 bytes, big endian
				$last = $xorsum = hash_hmac($algorithm, $last, $password, true);  // first iteration
				for ($j = 1; $j < $iterations; $j++)  // The other $count - 1 iterations
				{
					$xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
				}
				$output .= $xorsum;
			}
			$hash = substr($output, 0, $length);
		}
		
		return $algorithm . ':' . str_pad($iterations, $iterations_padding, '0', STR_PAD_LEFT) . ':' . $salt . ':' . base64_encode($hash);
	}
	
	/**
	 * Count the amount of entropy that a password contains.
	 * 
	 * @param string $password
	 * @return int
	 */
	public static function countEntropyBits($password)
	{
		// An empty string has no entropy.
		
		if ($password === '') return 0;
		
		// Common character sets and the number of possible mutations.
		
		static $entropy_per_char = array(
			'/^[0-9]+$/' => 10,
			'/^[a-z]+$/' => 26,
			'/^[A-Z]+$/' => 26,
			'/^[a-z0-9]+$/' => 36,
			'/^[A-Z0-9]+$/' => 36,
			'/^[a-zA-Z]+$/' => 52,
			'/^[a-zA-Z0-9]+$/' => 62,
			'/^[a-zA-Z0-9_-]+$/' => 64,
			'/^[\\x20-\\x7e]+$/' => 95,
			'/^[\\x00-\\x7f]+$/' => 128,
		);
		
		foreach ($entropy_per_char as $regex => $entropy)
		{
			if (preg_match($regex, $password))
			{
				return log(pow($entropy, strlen($password)), 2);
			}
		}
		
		return strlen($password) * 8;
	}
}
