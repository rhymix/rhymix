<?php

namespace Rhymix\Framework;

/**
 * The security class.
 */
class Security
{
	/**
	 * Sanitize a variable.
	 * 
	 * @param string $input
	 * @param string $type
	 * @return string|false
	 */
	public static function sanitize($input, $type)
	{
		switch ($type)
		{
			// Escape HTML special characters.
			case 'escape':
				if (!utf8_check($input)) return false;
				return escape($input);
			
			// Strip all HTML tags.
			case 'strip':
				if (!utf8_check($input)) return false;
				return escape(strip_tags($input));
			
			// Clean up HTML content to prevent XSS attacks.
			case 'html':
				if (!utf8_check($input)) return false;
				return Filters\HTMLFilter::clean($input);
			
			// Clean up the input to be used as a safe filename.
			case 'filename':
				if (!utf8_check($input)) return false;
				return Filters\FilenameFilter::clean($input);
			
			// Unknown filters return false.
			default: return false;
		}
	}
	
	/**
	 * Encrypt a string using AES.
	 * 
	 * @param string $plaintext
	 * @param string $key (optional)
	 * @param bool $force_compat (optional)
	 * @return string|false
	 */
	public static function encrypt($plaintext, $key = null, $force_compat = false)
	{
		// Get the encryption key.
		$key = $key ?: config('crypto.encryption_key');
		$key = substr(hash('sha256', $key, true), 0, 16);
		
		// Use defuse/php-encryption if possible.
		if (!$force_compat && function_exists('openssl_encrypt'))
		{
			return base64_encode(\Crypto::Encrypt($plaintext, $key));
		}
		
		// Otherwise, use the CryptoCompat class.
		if (function_exists('mcrypt_encrypt'))
		{
			return base64_encode(\CryptoCompat::encrypt($plaintext, $key));
		}
		else
		{
			throw new Exception('msg_crypto_not_available');
		}
	}
	
	/**
	 * Decrypt a string using AES.
	 * 
	 * @param string $plaintext
	 * @param string $key (optional)
	 * @param bool $force_compat (optional)
	 * @return string|false
	 */
	public static function decrypt($ciphertext, $key = null, $force_compat = false)
	{
		// Get the encryption key.
		$key = $key ?: config('crypto.encryption_key');
		$key = substr(hash('sha256', $key, true), 0, 16);
		
		// Check whether the ciphertext is valid.
		$ciphertext = @base64_decode($ciphertext);
		if (strlen($ciphertext) < 48)
		{
			return false;
		}
		
		// Use defuse/php-encryption if possible.
		if (!$force_compat && function_exists('openssl_decrypt'))
		{
			try
			{
				return \Crypto::Decrypt($ciphertext, $key);
			}
			catch (\InvalidCiphertextException $e)
			{
				return false;
			}
		}
		
		// Otherwise, use the CryptoCompat class.
		if (function_exists('mcrypt_decrypt'))
		{
			return \CryptoCompat::decrypt($ciphertext, $key);
		}
		else
		{
			throw new Exception('msg_crypto_not_available');
		}
	}
	
	/**
	 * Create a digital signature to verify the authenticity of a string.
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function createSignature($string)
	{
		$key = config('crypto.authentication_key');
		$salt = self::getRandom(8, 'alnum');
		$hash = substr(base64_encode(hash_hmac('sha256', hash_hmac('sha256', $string, $salt), $key, true)), 0, 32);
		return $salt . strtr($hash, '+/', '-_');
	}
	
	/**
	 * Check whether a signature is valid.
	 * 
	 * @param string $string
	 * @param string $signature
	 * @return bool
	 */
	public static function verifySignature($string, $signature)
	{
		if(strlen($signature) !== 40)
		{
			return false;
		}
		
		$key = config('crypto.authentication_key');
		$salt = substr($signature, 0, 8);
		$hash = substr(base64_encode(hash_hmac('sha256', hash_hmac('sha256', $string, $salt), $key, true)), 0, 32);
		return self::compareStrings(substr($signature, 8), strtr($hash, '+/', '-_'));
	}
	
	/**
	 * Generate a cryptographically secure random string.
	 * 
	 * @param int $length
	 * @param string $format
	 * @return string
	 */
	public static function getRandom($length = 32, $format = 'alnum')
	{
		// Find out how many bytes of entropy we really need.
		switch($format)
		{
			case 'binary':
				$entropy_required_bytes = $length;
				break;
			case 'hex':
				$entropy_required_bytes = ceil($length / 2);
				break;
			case 'alnum':
			case 'printable':
			default:
				$entropy_required_bytes = ceil($length * 3 / 4);
				break;
		}
		
		// Cap entropy to 256 bits from any one source, because anything more is meaningless.
		$entropy_capped_bytes = min(32, $entropy_required_bytes);
		$entropy = false;
		
		// Find and use the most secure way to generate a random string.
		if(function_exists('random_bytes'))
		{
			try
			{
				$entropy = random_bytes($entropy_capped_bytes);
			}
			catch (\Exception $e)
			{
				$entropy = false;
			}
		}
		
		// Use other good sources of entropy if random_bytes() is not available.
		if ($entropy === false)
		{
			if(function_exists('openssl_random_pseudo_bytes'))
			{
				$entropy = openssl_random_pseudo_bytes($entropy_capped_bytes);
			}
			elseif(function_exists('mcrypt_create_iv') && !\RX_WINDOWS)
			{
				$entropy = mcrypt_create_iv($entropy_capped_bytes, \MCRYPT_DEV_URANDOM);
			}
			elseif(function_exists('mcrypt_create_iv') && \RX_WINDOWS)
			{
				$entropy = mcrypt_create_iv($entropy_capped_bytes, \MCRYPT_RAND);
			}
			elseif(!\RX_WINDOWS && @is_readable('/dev/urandom'))
			{
				$fp = fopen('/dev/urandom', 'rb');
				if (function_exists('stream_set_read_buffer'))  // This function does not exist in HHVM.
				{
					stream_set_read_buffer($fp, 0);  // Prevent reading several KB of unnecessary data from urandom.
				}
				$entropy = fread($fp, $entropy_capped_bytes);
				fclose($fp);
			}
		}
		
		// Use built-in source of entropy if an error occurs while using other functions.
		if($entropy === false || strlen($entropy) < $entropy_capped_bytes)
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
		
		// Encode and return the random string.
		switch($format)
		{
			case 'binary':
				return substr($output, 0, $length);
			case 'printable':
				$salt = '';
				for($i = 0; $i < $length; $i++)
				{
					$salt .= chr(33 + (crc32(sha1($i . $output)) % 94));
				}
				return $salt;
			case 'hex':
				return substr(bin2hex($output), 0, $length);
			case 'alnum':
			default:
				$salt = substr(base64_encode($output), 0, $length);
				$replacements = chr(rand(65, 90)) . chr(rand(97, 122)) . rand(0, 9);
				return strtr($salt, '+/=', $replacements);
		}
	}
	
	/**
	 * Generate a cryptographically secure random number between $min and $max.
	 * 
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	public static function getRandomNumber($min = 0, $max = 0x7fffffff)
	{
		if (function_exists('random_int'))
		{
			return random_int($min, $max);
		}
		else
		{
			$bytes_required = min(4, ceil(log($max - $min, 2) / 8) + 1);
			$bytes = self::getRandom($bytes_required, 'binary');
			$offset = abs(hexdec(bin2hex($bytes)) % ($max - $min + 1));
			return intval($min + $offset);
		}
	}
	
	/**
	 * Generate a random UUID.
	 * 
	 * @return string
	 */
	public static function getRandomUUID()
	{
		$randpool = self::getRandom(16, 'binary');
		$randpool[6] = chr(ord($randpool[6]) & 0x0f | 0x40);
		$randpool[8] = chr(ord($randpool[8]) & 0x3f | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($randpool), 4));
	}
	
	/**
	 * Compare two strings in constant time.
	 * 
	 * @param string $a
	 * @param string $b
	 * @return bool
	 */
	public static function compareStrings($a, $b)
	{
		if(function_exists('hash_equals'))
		{
			return hash_equals($a, $b);
		}
		
		$diff = strlen($a) ^ strlen($b);
		$maxlen = min(strlen($a), strlen($b));
		for($i = 0; $i < $maxlen; $i++)
		{
			$diff |= ord($a[$i]) ^ ord($b[$i]);
		}
		return $diff === 0;
	}
	
	/**
	 * Check if the current request seems to be a CSRF attack.
	 * 
	 * This method returns true if the request seems to be innocent,
	 * and false if it seems to be a CSRF attack.
	 * 
	 * @param string $referer (optional)
	 * @return bool
	 */
	public static function checkCSRF($referer = null)
	{
		if ($token = $_SERVER['HTTP_X_CSRF_TOKEN'])
		{
			return Session::verifyToken($token);
		}
		elseif ($token = $_REQUEST['_rx_csrf_token'])
		{
			return Session::verifyToken($token);
		}
		elseif ($_SERVER['REQUEST_METHOD'] === 'GET')
		{
			return false;
		}
		else
		{
			$is_logged = Session::getMemberSrl();
			if ($is_logged)
			{
				trigger_error('CSRF token missing in POST request: ' . (\Context::get('act') ?: '(no act)'), \E_USER_WARNING);
			}
			
			if (!$referer)
			{
				$referer = strval(($_SERVER['HTTP_ORIGIN'] ?? '') ?: ($_SERVER['HTTP_REFERER'] ?? ''));
			}
			if ($referer !== '' && $referer !== 'null' && (!config('security.check_csrf_token') || !$is_logged))
			{
				return URL::isInternalURL($referer);
			}
			else
			{
				return false;
			}
		}
	}
	
	/**
	 * Check if the current request seems to be an XXE (XML external entity) attack.
	 * 
	 * This method returns true if the request seems to be innocent,
	 * and false if it seems to be an XXE attack.
	 * This is the opposite of XE's Security::detectingXEE() method.
	 * The name has also been changed to the more accurate acronym XXE.
	 * 
	 * @param string $xml (optional)
	 * @return bool
	 */
	public static function checkXXE($xml = null)
	{
		// Stop if there is no XML content.
		if (!$xml)
		{
			return true;
		}
		
		// Reject entity tags.
		if (strpos($xml, '<!ENTITY') !== false)
		{
			return false;
		}
		
		// Check if there is no content after the xml tag.
		$header = preg_replace('/<\?xml.*?\?'.'>/s', '', substr($xml, 0, 100), 1);
		if (($xml = trim(substr_replace($xml, $header, 0, 100))) === '')
		{
			return false;
		}
		
		// Check if there is no content after the DTD.
		$header = preg_replace('/^<!DOCTYPE[^>]*+>/i', '', substr($xml, 0, 200), 1);
		if (($xml = trim(substr_replace($xml, $header, 0, 200))) === '')
		{
			return false;
		}
		
		// Check that the root tag is valid.
		if (!preg_match('/^<(methodCall|methodResponse|fault)/', $xml))
		{
			return false;
		}
		
		return true;
	}
}
