<?php

namespace Rhymix\Framework\Helpers;

use Rhymix\Framework\Exception;
use Rhymix\Framework\Security;

/**
 * This class uses openssl to perform encryption and decryption in a format
 * that is fully compatible with version 1.x of defuse/php-encryption
 * which we must preserve for backward compatibility.
 *
 * This file is part of Rhymix and is licensed under GPLv2 or later.
 */
class EncryptionHelper
{
    // Default configuration
    const ENCRYPTION_ALGO = 'aes-128';
    const ENCRYPTION_MODE = 'cbc';
    const ENCRYPTION_BLOCK_SIZE = 16;
    const ENCRYPTION_KEY_SIZE = 16;
    const ENCRYPTION_KEY_INFO = 'DefusePHP|KeyForEncryption';
    const ENCRYPTION_MAC_ALGO = 'sha256';
    const ENCRYPTION_MAC_SIZE = 32;
    const ENCRYPTION_MAC_INFO = 'DefusePHP|KeyForAuthentication';

    /**
	 * Encrypt a plaintext using the given key.
	 *
	 * @param string $plaintext
	 * @param string $key
	 * @return string
	 */
    public static function encrypt(string $plaintext, string $key): string
    {
        // Generate subkey for encryption
        $enc_key = self::_hkdf($key, self::ENCRYPTION_KEY_INFO);

        // Generate IV
        $iv = self::_createIV();

        // Encrypt the plaintext
        if (function_exists('openssl_encrypt'))
        {
            $openssl_method = strtoupper(self::ENCRYPTION_ALGO . '-' . self::ENCRYPTION_MODE);
            $ciphertext = openssl_encrypt($plaintext, $openssl_method, $enc_key, OPENSSL_RAW_DATA, $iv);
        }
        elseif (function_exists('mcrypt_encrypt'))
        {
            $plaintext = self::_applyPKCS7Padding($plaintext, self::ENCRYPTION_BLOCK_SIZE);
            $mcrypt_method = str_replace('aes', 'rijndael', self::ENCRYPTION_ALGO);
            $ciphertext = \mcrypt_encrypt($mcrypt_method, $enc_key, $plaintext, self::ENCRYPTION_MODE, $iv);
        }
        else
        {
            throw new Exception('msg_crypto_not_available');
        }

        // Generate MAC
        $mac_key = self::_hkdf($key, self::ENCRYPTION_MAC_INFO);
        $mac = hash_hmac(self::ENCRYPTION_MAC_ALGO, ($iv . $ciphertext), $mac_key, true);

        // Return the MAC, IV, and ciphertext
        return $mac . $iv . $ciphertext;
    }

    /**
	 * Decrypt a ciphertext using the given key.
	 *
	 * @param string $ciphertext
	 * @param string $key
	 * @return string
	 */
    public static function decrypt(string $ciphertext, string $key): string
    {
        // Extract MAC and IV from the remainder of the ciphertext
        $mac = substr($ciphertext, 0, self::ENCRYPTION_MAC_SIZE);
        $iv = substr($ciphertext, self::ENCRYPTION_MAC_SIZE, self::ENCRYPTION_BLOCK_SIZE);
        $ciphertext = substr($ciphertext, self::ENCRYPTION_MAC_SIZE + self::ENCRYPTION_BLOCK_SIZE);

        // Validate MAC
        $mac_key = self::_hkdf($key, self::ENCRYPTION_MAC_INFO);
        $mac_compare = hash_hmac(self::ENCRYPTION_MAC_ALGO, ($iv . $ciphertext), $mac_key, true);
        if (!Security::compareStrings($mac, $mac_compare))
        {
            throw new Exception('msg_invalid_ciphertext');
        }

        // Generate subkey for encryption
        $enc_key = self::_hkdf($key, self::ENCRYPTION_KEY_INFO);

        // Decrypt the ciphertext
        if (function_exists('openssl_decrypt'))
        {
            $openssl_method = strtoupper(self::ENCRYPTION_ALGO . '-' . self::ENCRYPTION_MODE);
            $plaintext = openssl_decrypt($ciphertext, $openssl_method, $enc_key, OPENSSL_RAW_DATA, $iv);
        }
        elseif (function_exists('mcrypt_decrypt'))
        {
            $mcrypt_method = str_replace('aes', 'rijndael', self::ENCRYPTION_ALGO);
            $plaintext = \mcrypt_decrypt($mcrypt_method, $enc_key, $ciphertext, self::ENCRYPTION_MODE, $iv);
            if ($plaintext !== false)
            {
                $plaintext = self::_stripPKCS7Padding($plaintext, self::ENCRYPTION_BLOCK_SIZE);
            }
        }
        else
        {
            throw new Exception('msg_crypto_not_available');
        }

        if ($plaintext === false)
        {
            throw new Exception('msg_invalid_ciphertext');
        }

        // Return the plaintext
        return $plaintext;
    }

    /**
     * Create an IV.
	 *
     * @return string
     */
    protected static function _createIV(): string
    {
        return Security::getRandom(self::ENCRYPTION_BLOCK_SIZE, 'binary');
    }

    /**
     * Apply PKCS#7 padding to a string.
	 *
     * @param string $str The string
     * @param int $block_size The block size
     * @return string
     */
    protected static function _applyPKCS7Padding(string $str, int $block_size): string
    {
        $padding_size = $block_size - (strlen($str) % $block_size);
        if ($padding_size === 0)
		{
			$padding_size = $block_size;
		}

        return $str . str_repeat(chr($padding_size), $padding_size);
    }

    /**
     * Remove PKCS#7 padding from a string.
	 *
     * @param string $str The string
     * @param int $block_size The block size
     * @return string
     */
    protected static function _stripPKCS7Padding(string $str, int $block_size): string
    {
        if (strlen($str) % $block_size !== 0)
		{
			throw new Exception('msg_invalid_ciphertext');
		}

        $padding_size = ord(substr($str, -1));
        if ($padding_size < 1 || $padding_size > $block_size)
		{
			throw new Exception('msg_invalid_ciphertext');
		}
        if (substr($str, (-1 * $padding_size)) !== str_repeat(chr($padding_size), $padding_size))
		{
			throw new Exception('msg_invalid_ciphertext');
		}

        return substr($str, 0, strlen($str) - $padding_size);
    }

    /**
     * HKDF function compatible with defuse/php-encryption v1.
	 *
	 * @param string $key
	 * @param string $info
     * @return string
     */
    protected static function _hkdf(string $key, string $info): string
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
