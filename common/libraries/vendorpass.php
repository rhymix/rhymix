<?php

// PHP implementation of several vendor-specific password hashing functions.

class VendorPass
{
    // MySQL's OLD_PASSWORD() function.
    // Minor modification of the code written by Dustin Fineout, 10/9/2009
    // Source: http://stackoverflow.com/questions/260236/mysql-hashing-function-implementation
    
    public static function mysql_old_password($password)
    {
        $password = strval($password);
        $length = strlen($password);
        $nr1 = 0x50305735; $nr2 = 0x12345671; $add = 7; $tmp = null;
        for ($i = 0; $i < $length; $i++) {
            $byte = substr($password, $i, 1);
            if ($byte === ' ' || $byte === "\t") continue;
            $tmp = ord($byte);
            $nr1 ^= (($nr1 << 8) & 0x7FFFFFFF) + ((($nr1 & 63) + $add) * $tmp);
            $nr2 += (($nr2 << 8) & 0x7FFFFFFF) ^ $nr1;
            $add += $tmp;
        }
        return sprintf("%08x%08x", $nr1 & 0x7FFFFFFF, $nr2 & 0x7FFFFFFF);
    }
    
    // MySQL's PASSWORD() function.
    
    public static function mysql_new_password($password)
    {
        return '*' . strtoupper(sha1(sha1($password, true)));
    }
    
    // MS SQL Server's PWDENCRYPT() function.
    
    public static function mssql_pwdencrypt($password, $salt = null)
    {
        if ($salt !== null && strlen($salt) === 54)
        {
            $salt = substr($salt, 6, 8);
        }
        else
        {
            $salt = strtoupper(str_pad(dechex(mt_rand(0, 65535)), 4, '0') .
                str_pad(dechex(mt_rand(0, 65535)), 4, '0'));
        }
        $password = mb_convert_encoding($password, 'UTF-16LE', 'UTF-8');
        return '0x0100' . strtoupper($salt . sha1($password . pack('H*', $salt)));
    }
    
    // Drupal's SHA512-based password hashing algorithm.
    
    public static function drupal($password, $salt = null)
    {
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        if ($salt !== null && strlen($salt) > 12)
        {
            $iterations = intval(strpos($itoa64, substr($salt, 3, 1)));
            $salt = substr($salt, 4, 8);
        }
        else
        {
            $iterations = 15;
            $salt = Rhymix\Framework\Security::getRandom(8, 'hex');
        }
        $count = 1 << $iterations;
        $hash = hash('sha512', $salt . $password, true);
        do
        {
            $hash = hash('sha512', $hash . $password, true);
        } while (--$count);
        $hash = self::drupal_base64($hash, strlen($hash), $itoa64);
        return substr('$S$' . $itoa64[$iterations] . $salt . $hash, 0, 55);
    }
    
    // Drupal's own Base64 implementation.
    
    protected static function drupal_base64($input, $count, $chars)
    {
        $output = '';
        $i = 0;
        do
        {
            $value = ord($input[$i++]);
            $output .= $chars[$value & 0x3f];
            if ($i < $count) $value |= ord($input[$i]) << 8;
            $output .= $chars[($value >> 6) & 0x3f];
            if ($i++ >= $count) break;
            if ($i < $count) $value |= ord($input[$i]) << 16;
            $output .= $chars[($value >> 12) & 0x3f];
            if ($i++ >= $count) break;
            $output .= $chars[($value >> 18) & 0x3f];
        } while ($i < $count);
        return $output;
    }
    
    // Joomla's MD5-based password hashing algorithm.
    
    public static function joomla($password, $salt = null)
    {
        if ($salt !== null && strlen($salt) > 33)
        {
            $salt = substr($salt, 33);
        }
        else
        {
            $salt = Rhymix\Framework\Security::getRandom(32, 'hex');
        }
        return md5($password . $salt) . ':' . $salt;
    }
    
    // KimsQ Rb's algorithms.
    
    public static function kimsqrb($password, $salt = null)
    {
        if (preg_match('/(\$[1-4])\$([0-9]{14})$/', $salt, $matches))
        {
            $date = '$' . $matches[2];
            $fakesalt = substr(base64_encode(substr($date, 1) . 'salt'), 0, 22);
            switch ($matches[1])
            {
                case '$1': return self::password_hash($password, 1, ['cost' =>10, 'salt' => $fakesalt]) . '$1' . $date;
                case '$2': return hash('sha512', $password . $fakesalt) . '$2' . $date;
                case '$3': return hash('sha256', $password . $fakesalt) . '$3' . $date;
                case '$4': return md5(sha1(md5($password . $fakesalt))) . '$4' . $date;
            }
        }
        
        $date = '$' . date('YmdHis');
        $fakesalt = substr(base64_encode(substr($date, 1) . 'salt'), 0, 22);
        return self::password_hash($password, 1, ['cost' =>10, 'salt' => $fakesalt]) . '$1' . $date;
    }
    
    // Bcrypt wrapper for PHP 5.4.
    
    public static function password_hash($password, $algo = 1, $options = [])
    {
        if (!isset($options['salt']) || !preg_match('/^[0-9a-zA-Z\.\/]{22,}$/', $options['salt']))
        {
            $options['salt'] = Rhymix\Framework\Security::getRandom(22, 'alnum');
        }
        if (!isset($options['cost']) || $options['cost'] < 4 || $options['cost'] > 31)
        {
            $options['cost'] = 10;
        }
        
        $salt = '$2y$' . sprintf('%02d', $options['cost']) . '$' . $options['salt'];
        return @crypt($password, $salt);
    }
}
