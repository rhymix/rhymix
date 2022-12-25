<?php

/**
 * This class uses openssl to perform encryption and decryption in a format
 * that is fully compatible with version 1.x of defuse/php-encryption
 * which we must preserve for backward compatibility.
 * 
 * This file is part of Rhymix and is licensed under GPLv2 or later.
 */
class CryptoCompat
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
    
    // Encrypt method
    public static function encrypt($plaintext, $key)
    {
        // Generate subkey for encryption
        $enc_key = self::_defuseCompatibleHKDF($key, self::ENCRYPTION_KEY_INFO);
        
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
            $ciphertext = mcrypt_encrypt($mcrypt_method, $enc_key, $plaintext, self::ENCRYPTION_MODE, $iv);
        }
        else
        {
            throw new Rhymix\Framework\Exception('msg_crypto_not_available');
        }
        
        // Generate MAC
        $mac_key = self::_defuseCompatibleHKDF($key, self::ENCRYPTION_MAC_INFO);
        $mac = hash_hmac(self::ENCRYPTION_MAC_ALGO, ($iv . $ciphertext), $mac_key, true);
        
        // Return the MAC, IV, and ciphertext
        return $mac . $iv . $ciphertext;
    }

    // Decrypt method
    public static function decrypt($ciphertext, $key)
    {
        // Extract MAC and IV from the remainder of the ciphertext
        $mac = substr($ciphertext, 0, self::ENCRYPTION_MAC_SIZE);
        $iv = substr($ciphertext, self::ENCRYPTION_MAC_SIZE, self::ENCRYPTION_BLOCK_SIZE);
        $ciphertext = substr($ciphertext, self::ENCRYPTION_MAC_SIZE + self::ENCRYPTION_BLOCK_SIZE);
        
        // Validate MAC
        $mac_key = self::_defuseCompatibleHKDF($key, self::ENCRYPTION_MAC_INFO);
        $mac_compare = hash_hmac(self::ENCRYPTION_MAC_ALGO, ($iv . $ciphertext), $mac_key, true);
        if (!Rhymix\Framework\Security::compareStrings($mac, $mac_compare))
        {
            return false;
        }
        
        // Generate subkey for encryption
        $enc_key = self::_defuseCompatibleHKDF($key, self::ENCRYPTION_KEY_INFO);
        
        // Decrypt the ciphertext
        if (function_exists('openssl_decrypt'))
        {
            $openssl_method = strtoupper(self::ENCRYPTION_ALGO . '-' . self::ENCRYPTION_MODE);
            $plaintext = openssl_decrypt($ciphertext, $openssl_method, $enc_key, OPENSSL_RAW_DATA, $iv);
        }
        elseif (function_exists('mcrypt_decrypt'))
        {
            $mcrypt_method = str_replace('aes', 'rijndael', self::ENCRYPTION_ALGO);
            $plaintext = mcrypt_decrypt($mcrypt_method, $enc_key, $ciphertext, self::ENCRYPTION_MODE, $iv);
            if ($plaintext !== false)
            {
                $plaintext = self::_stripPKCS7Padding($plaintext, self::ENCRYPTION_BLOCK_SIZE);
            }
        }
        else
        {
            throw new Rhymix\Framework\Exception('msg_crypto_not_available');
        }
        
        if ($plaintext === false)
        {
            return false;
        }
        
        // Return the plaintext
        return $plaintext;
    }
    
    /**
     * @brief Create an IV
     * @return string
     */
    protected static function _createIV()
    {
        return Rhymix\Framework\Security::getRandom(self::ENCRYPTION_BLOCK_SIZE, 'binary');
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
