<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * This class makes encryption and digital signing easy to use in XE.
 * 
 * The encryption methods use AES-128, and is fully compatible with
 * https://github.com/defuse/php-encryption
 * except that it uses base64-encoded keys and ciphertexts.
 * 
 * The digital signature methods is based on the same SHA-256 based
 * key derivation function used by the encryption methods.
 * 
 * A key is automatically generated and saved to the files/config directory
 * when first invoked. The same key will be used for all subsequent
 * method calls that do not specify a different key.
 * The key must be a binary string exactly 16 bytes long.
 * 
 * @file Crypto.class.php
 * @author Kijin Sung (kijin@kijinsung.com)
 * @package /classes/security
 * @version 1.0
 */
class Crypto
{
	/**
	 * @brief Default configuration
	 */
	const ENCRYPTION_ALGO = 'aes-128';
	const ENCRYPTION_MODE = 'cbc';
	const ENCRYPTION_BLOCK_SIZE = 16;
	const ENCRYPTION_KEY_SIZE = 16;
	const ENCRYPTION_KEY_INFO = 'DefusePHP|KeyForEncryption';
	const ENCRYPTION_MAC_ALGO = 'sha256';
	const ENCRYPTION_MAC_SIZE = 32;
	const ENCRYPTION_MAC_INFO = 'DefusePHP|KeyForAuthentication';
	const SIGNATURE_ALGO = 'sha256';
	const SIGNATURE_SIZE = '32';
	
	/**
	 * @brief The default key
	 */
	protected static $_default_key = null;

	/**
	 * @brief The currently selected extension
	 */
	protected static $_extension = null;

	/**
	 * @brief If this is true, encryption and signature are only valid in current session
	 */
	protected $_current_session_only = false;

	/**
	 * @brief Constructor
	 */
	public function __construct()
	{
		if(function_exists('openssl_encrypt'))
		{
			self::$_extension = 'openssl';
		}
		elseif(function_exists('mcrypt_encrypt'))
		{
			self::$_extension = 'mcrypt';
		}
		else
		{
			throw new Exception('Crypto class requires openssl or mcrypt extension.');
		}
	}

	/**
	 * @brief Check if cryptography is supported on this server
	 * @return bool
	 */
	public static function isSupported()
	{
		return (function_exists('openssl_encrypt') || function_exists('mcrypt_encrypt'));
	}

	/**
	 * @brief Make encryption and signature only valid in current session
	 * @return void
	 */
	public function currentSessionOnly()
	{
		$this->_current_session_only = true;
	}

	/**
	 * @brief Encrypt a string
	 * @param string $plaintext The string to encrypt
	 * @param string $key Optional key. If empty, default key will be used.
	 * @return string
	 */
	public function encrypt($plaintext, $key = null)
	{
		if($key === null || $key === '')
		{
			$key = $this->_getSessionKey();
		}

		// Generate subkey for encryption
		$enc_key = self::_defuseCompatibleHKDF($key, self::ENCRYPTION_KEY_INFO);

		// Generate IV
		$iv = self::_createIV();

		// Encrypt the plaintext
		if(self::$_extension === 'openssl')
		{
			$openssl_method = self::ENCRYPTION_ALGO . '-' . self::ENCRYPTION_MODE;
			$ciphertext = openssl_encrypt($plaintext, $openssl_method, $enc_key, OPENSSL_RAW_DATA, $iv);
		}
		else
		{
			$mcrypt_method = str_replace('aes', 'rijndael', self::ENCRYPTION_ALGO);
			$plaintext = self::_applyPKCS7Padding($plaintext, self::ENCRYPTION_BLOCK_SIZE);
			$ciphertext = mcrypt_encrypt($mcrypt_method, $enc_key, $plaintext, self::ENCRYPTION_MODE, $iv);
		}

		// Generate MAC
		$mac_key = self::_defuseCompatibleHKDF($key, self::ENCRYPTION_MAC_INFO);
		$mac = hash_hmac(self::ENCRYPTION_MAC_ALGO, ($iv . $ciphertext), $mac_key, true);

		// Return the MAC, IV, and ciphertext as a base64 encoded string
		return base64_encode($mac . $iv . $ciphertext);
	}

	/**
	 * @brief Decrypt a string
	 * @param string $ciphertext The string to decrypt
	 * @param string $key Optional key. If empty, default key will be used.
	 * @return string
	 */
	public function decrypt($ciphertext, $key = null)
	{
		if($key === null || $key === '')
		{
			$key = $this->_getSessionKey();
		}
		
		// Base64 decode the ciphertext and check the length
		$ciphertext = @base64_decode($ciphertext);
		if(strlen($ciphertext) < (self::ENCRYPTION_MAC_SIZE + (self::ENCRYPTION_BLOCK_SIZE * 2)))
		{
			return false;
		}

		// Extract MAC and IV from the remainder of the ciphertext
		$mac = substr($ciphertext, 0, self::ENCRYPTION_MAC_SIZE);
		$iv = substr($ciphertext, self::ENCRYPTION_MAC_SIZE, self::ENCRYPTION_BLOCK_SIZE);
		$ciphertext = substr($ciphertext, self::ENCRYPTION_MAC_SIZE + self::ENCRYPTION_BLOCK_SIZE);

		// Validate MAC
		$mac_key = self::_defuseCompatibleHKDF($key, self::ENCRYPTION_MAC_INFO);
		$mac_compare = hash_hmac(self::ENCRYPTION_MAC_ALGO, ($iv . $ciphertext), $mac_key, true);
		$oPassword = new Password();
		if(!$oPassword->strcmpConstantTime($mac, $mac_compare))
		{
			return false;
		}

		// Generate subkey for encryption
		$enc_key = self::_defuseCompatibleHKDF($key, self::ENCRYPTION_KEY_INFO);

		// Decrypt the ciphertext
		if (self::$_extension === 'openssl')
		{
			$openssl_method = self::ENCRYPTION_ALGO . '-' . self::ENCRYPTION_MODE;
			$plaintext = openssl_decrypt($ciphertext, $openssl_method, $enc_key, OPENSSL_RAW_DATA, $iv);
		}
		else
		{
			$mcrypt_method = str_replace('aes', 'rijndael', self::ENCRYPTION_ALGO);
			$plaintext = @mcrypt_decrypt($mcrypt_method, $enc_key, $ciphertext, self::ENCRYPTION_MODE, $iv);
			if($plaintext === false)
			{
				return false;
			}
			$plaintext = self::_stripPKCS7Padding($plaintext, self::ENCRYPTION_BLOCK_SIZE);
			if($plaintext === false)
			{
				return false;
			}
		}

		// Return the plaintext
		return $plaintext;
	}

	/**
	 * @brief Create a digital signature of a string
	 * @param string $plaintext The string to sign
	 * @param string $key Optional key. If empty, default key will be used.
	 * @return string
	 */
	public function createSignature($plaintext, $key = null)
	{
		if($key === null || $key === '')
		{
			$key = $this->_getSessionKey();
		}

		// Generate a signature using HMAC
		return bin2hex(self::_defuseCompatibleHKDF($plaintext, $key));
	}

	/**
	 * @brief Verify a digital signature
	 * @param string $signature The signature to verify
	 * @param string $plaintext The string to verify
	 * @param string $key Optional key. If empty, default key will be used.
	 * @return bool
	 */
	public function verifySignature($signature, $plaintext, $key = null)
	{
		if($key === null || $key === '')
		{
			$key = $this->_getSessionKey();
		}

		// Verify the signature using HMAC
		$oPassword = new Password();
		$compare = bin2hex(self::_defuseCompatibleHKDF($plaintext, $key));
		return $oPassword->strcmpConstantTime($signature, $compare);
	}

	/**
	 * @brief Get the default key applicable to this instance
	 * @return string
	 */
	protected function _getSessionKey()
	{
		if($this->_current_session_only)
		{
			if(!isset($_SESSION['XE_CRYPTO_SESSKEY']))
			{
				$_SESSION['XE_CRYPTO_SESSKEY'] = self::_createSecureKey();
			}
			$session_key = base64_decode($_SESSION['XE_CRYPTO_SESSKEY']);
			return strval(self::_getDefaultKey()) ^ strval($session_key);
		}
		else
		{
			return strval(self::_getDefaultKey());
		}
	}

	/**
	 * @brief Get the default key
	 * @return string
	 */
	protected static function _getDefaultKey()
	{
		if(self::$_default_key !== null)
		{
			return base64_decode(self::$_default_key);
		}
		else
		{
			$file_name = _XE_PATH_ . 'files/config/crypto.config.php';
			if(file_exists($file_name) && is_readable($file_name))
			{
				$key = (include $file_name);
			}
			if(!isset($key) || !is_string($key))
			{
				$key = self::_createSecureKey();
				self::_setDefaultKey($key);
			}
			return base64_decode(self::$_default_key = $key);
		}
	}

	/**
	 * @brief Set the default key
	 * @param string $key The default key
	 * @return void
	 */
	protected static function _setDefaultKey($key)
	{
		self::$_default_key = $key = trim($key);
		$file_name = _XE_PATH_ . 'files/config/crypto.config.php';
		$file_content = '<?php return ' . var_export($key, true) . ';' . PHP_EOL;
		FileHandler::writeFile($file_name, $file_content);
	}

	/**
	 * @brief Create a secure key
	 * @return string
	 */
	protected static function _createSecureKey()
	{
		$oPassword = new Password();
		return base64_encode($oPassword->createSecureSalt(ENCRYPTION_KEY_SIZE, 'binary'));
	}

	/**
	 * @brief Create an IV
	 * @return string
	 */
	protected static function _createIV()
	{
		$oPassword = new Password();
		return $oPassword->createSecureSalt(self::ENCRYPTION_BLOCK_SIZE, 'binary');
	}

	
	/**
	 * @brief Apply PKCS#7 padding to a string
	 * @param string $str The string
	 * @param int $block_size The block size
	 * @return string
	 */
	protected static function _applyPKCS7Padding($str, $block_size)
	{
		$padding_size = $block_size - (strlen($str) % $block_size);
		if ($padding_size === 0) $padding_size = $block_size;
		return $str . str_repeat(chr($padding_size), $padding_size);
	}
	
	/**
	 * @brief Remove PKCS#7 padding from a string
	 * @param string $str The string
	 * @param int $block_size The block size
	 * @return string
	 */
	protected static function _stripPKCS7Padding($str, $block_size)
	{
		if (strlen($str) % $block_size !== 0) return false;
		$padding_size = ord(substr($str, -1));
		if ($padding_size < 1 || $padding_size > $block_size) return false;
		if (substr($str, (-1 * $padding_size)) !== str_repeat(chr($padding_size), $padding_size)) return false;
		return substr($str, 0, strlen($str) - $padding_size);
	}

	/**
	 * @brief HKDF function compatible with defuse/php-encryption
	 * @return string
	 */
	protected static function _defuseCompatibleHKDF($key, $info)
	{
		$salt = str_repeat("\x00", self::ENCRYPTION_MAC_SIZE);
		$prk = hash_hmac(self::ENCRYPTION_MAC_ALGO, $key, $salt, true);
		$t = $last_block = '';
		for ($block_index = 1; strlen($t) < self::ENCRYPTION_KEY_SIZE; $block_index++)
		{
			$t .= $last_block = hash_hmac(self::ENCRYPTION_MAC_ALGO, ($last_block . $info . chr($block_index)), $prk, true);
		}
		return substr($t, 0, self::ENCRYPTION_KEY_SIZE);
	}
}
/* End of file : Crypto.class.php */
/* Location: ./classes/security/Crypto.class.php */
