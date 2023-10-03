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
	 * @return string
	 */
	public static function sanitize(string $input, string $type): string
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

			// Unknown filters.
			default:
				throw new Exception('Unknown filter type for sanitize: ' . $type);
		}
	}

	/**
	 * Encrypt a string using AES.
	 *
	 * @param string $plaintext
	 * @param string $key (optional)
	 * @return string
	 */
	public static function encrypt(string $plaintext, ?string $key = null): string
	{
		// Get the encryption key.
		$key = $key ?: config('crypto.encryption_key');
		$key = substr(hash('sha256', $key, true), 0, 16);

		// Encrypt in a format that is compatible with defuse/php-encryption 1.2.x.
		return base64_encode(\CryptoCompat::encrypt($plaintext, $key));
	}

	/**
	 * Decrypt a string using AES.
	 *
	 * @param string $plaintext
	 * @param string $key (optional)
	 * @return string|false
	 */
	public static function decrypt(string $ciphertext, ?string $key = null)
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

		// Decrypt in a format that is compatible with defuse/php-encryption 1.2.x.
		return \CryptoCompat::decrypt($ciphertext, $key);
	}

	/**
	 * Create a digital signature to verify the authenticity of a string.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function createSignature(string $string): string
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
	public static function verifySignature(string $string, string $signature): bool
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
	public static function getRandom(int $length = 32, string $format = 'alnum'): string
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
	public static function getRandomNumber(int $min = 0, int $max = \PHP_INT_MAX): int
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
	 * The code for UUIDv4 is based on https://stackoverflow.com/a/15875555/481206
	 *
	 * @param int $version (4 or 7)
	 * @return string
	 */
	public static function getRandomUUID(int $version = 4): string
	{
		if ($version === 4)
		{
			$randpool = self::getRandom(16, 'binary');
			$randpool[6] = chr(ord($randpool[6]) & 0x0f | 0x40);
			$randpool[8] = chr(ord($randpool[8]) & 0x3f | 0x80);
		}
		elseif ($version === 7)
		{
			$timestamp = microtime(false);
			$timestamp = substr(pack('J', (intval(substr($timestamp, -10), 10) * 1000) + intval(substr($timestamp, 2, 3), 10)), -6);
			$randpool = $timestamp . self::getRandom(10, 'binary');
			$randpool[6] = chr(ord($randpool[6]) & 0x0f | 0x70);
			$randpool[8] = chr(ord($randpool[8]) & 0x3f | 0x80);
		}
		else
		{
			throw new Exception('Invalid UUID version: ' . $version);
		}
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($randpool), 4));
	}

	/**
	 * Compare two strings in constant time.
	 *
	 * @param string $a
	 * @param string $b
	 * @return bool
	 */
	public static function compareStrings(string $a, string $b): bool
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
	public static function checkCSRF(?string $referer = null): bool
	{
		$check_csrf_token = config('security.check_csrf_token') ? true : false;
		if ($token = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : null)
		{
			return Session::verifyToken($token, '', $check_csrf_token);
		}
		elseif ($token = isset($_REQUEST['_rx_csrf_token']) ? $_REQUEST['_rx_csrf_token'] : null)
		{
			return Session::verifyToken($token, '', $check_csrf_token);
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
			if ($referer !== '' && $referer !== 'null' && (!$check_csrf_token || !$is_logged))
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
	public static function checkXXE(?string $xml = null): bool
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
