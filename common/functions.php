<?php

/**
 * Function library for Rhymix
 * 
 * Copyright (c) Rhymix Developers and Contributors
 */

/**
 * Get or set system configuration.
 * 
 * @param string $key
 * @param string $value (optional)
 * @return mixed
 */
function config($key, $value = null)
{
	if ($value === null)
	{
		return Rhymix\Framework\Config::get($key);
	}
	else
	{
		Rhymix\Framework\Config::set($key, $value);
	}
}

/**
 * Get or set lang variable.
 *
 * @param string $code Lang variable name
 * @param string $value `$code`s value
 * @return mixed
 */
function lang($code, $value = null)
{
	if (!isset($GLOBALS['lang']) || !$GLOBALS['lang'] instanceof Rhymix\Framework\Lang)
	{
		$GLOBALS['lang'] = Rhymix\Framework\Lang::getInstance(Context::getLangType() ?: config('locale.default_lang') ?: 'ko');
		$GLOBALS['lang']->loadDirectory(RX_BASEDIR . 'common/lang', 'common');
	}
	
	if ($value === null)
	{
		return $GLOBALS['lang']->get($code);
	}
	else
	{
		$GLOBALS['lang']->set($code, $value);
	}
}

/**
 * Get the first value of an array.
 * 
 * @param array $array The input array
 * @return mixed
 */
function array_first(array $array)
{
	return reset($array);
}

/**
 * Get the first key of an array.
 * 
 * @param array $array The input array
 * @return mixed
 */
function array_first_key(array $array)
{
	reset($array);
	return key($array);
}

/**
 * Get the last value of an array.
 * 
 * @param array $array The input array
 * @return mixed
 */
function array_last(array $array)
{
	return end($array);
}

/**
 * Get the last key of an array.
 * 
 * @param array $array The input array
 * @return mixed
 */
function array_last_key(array $array)
{
	end($array);
	return key($array);
}

/**
 * Escape all keys and values in a multi-dimensional array.
 * 
 * @param array $array The array to escape
 * @param bool $double_escape Set this to false to skip symbols that are already escaped (default: true)
 * @return array
 */
function array_escape(array $array, $double_escape = true)
{
	$flags = ENT_QUOTES | ENT_SUBSTITUTE;
	$result = array();
	foreach ($array as $key => $value)
	{
		if (is_array($value))
		{
			$result[htmlspecialchars($key, $flags, 'UTF-8', $double_escape)] = array_escape($value, $double_escape, $flags);
		}
		elseif (is_object($value))
		{
			$result[htmlspecialchars($key, $flags, 'UTF-8', $double_escape)] = (object)array_escape(get_object_vars($value), $double_escape, $flags);
		}
		else
		{
			$result[htmlspecialchars($key, $flags, 'UTF-8', $double_escape)] = htmlspecialchars($value, $flags, 'UTF-8', $double_escape);
		}
	}
	return $result;
}

/**
 * Flatten a multi-dimensional array into a one-dimensional array.
 * Based on util.php <https://github.com/brandonwamboldt/utilphp>
 * Contributed by Theodore R. Smith of PHP Experts, Inc. <http://www.phpexperts.pro/>
 *
 * @param array $array The array to flatten
 * @param bool $preserve_keys Whether or not to preserve array keys (default: true)
 * @return array
 */
function array_flatten(array $array, $preserve_keys = true)
{
	$result = array();
	array_walk_recursive($array, function($value, $key) use(&$result, $preserve_keys) {
		if ($preserve_keys && !is_int($key))
		{
			$result[$key] = $value;
		}
		else
		{
			$result[] = $value;
		}
	});
	return $result;
}

/**
 * Get the base name of a class name (without namespaces).
 * Based on Laravel helper function <http://laravel.com/docs/5.0/helpers>
 * 
 * @param string|object $class The class name
 * @return string
 */
function class_basename($class)
{
	return basename(str_replace('\\', '/', is_object($class) ? get_class($class) : $class));
}

/**
 * Clean a path to remove ./, ../, trailing slashes, etc.
 * 
 * This function is an alias to Rhymix\Framework\Filters\FilenameFilter::cleanPath().
 * 
 * @param string $path
 * @return string
 */
function clean_path($path)
{
	return Rhymix\Framework\Filters\FilenameFilter::cleanPath($path);
}

/**
 * This function is a shortcut to htmlspecialchars().
 * 
 * @param string $str The string to escape
 * @param bool $double_escape Set this to false to skip symbols that are already escaped (default: true)
 * @return string
 */
function escape($str, $double_escape = true)
{
	$flags = ENT_QUOTES | ENT_SUBSTITUTE;
	return htmlspecialchars($str, $flags, 'UTF-8', $double_escape);
}

/**
 * This function escapes a string to be used in a CSS property.
 * 
 * @param string $str The string to escape
 * @return string
 */
function escape_css($str)
{
	return preg_replace('/[^a-zA-Z0-9_.#\/-]/', '', $str);
}

/**
 * This function escapes a string to be used in a JavaScript string literal.
 * 
 * @param string $str The string to escape
 * @return string
 */
function escape_js($str)
{
	$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
	$str = json_encode((string)$str, $flags);
	return substr($str, 1, strlen($str) - 2);
}

/**
 * This function escapes a string to be used in a 'single-quoted' PHP string literal.
 * Null bytes are removed.
 * 
 * @param string $str The string to escape
 * @return string
 */
function escape_sqstr($str)
{
	return str_replace(array('\\0', '\\"'), array('', '"'), addslashes($str));
}

/**
 * This function escapes a string to be used in a "double-quoted" PHP string literal.
 * Null bytes are removed.
 * 
 * @param string $str The string to escape
 * @return string
 */
function escape_dqstr($str)
{
	return str_replace(array('\\0', "\\'", '$'), array('', "'", '\\$'), addslashes($str));
}

/**
 * This function splits a string into an array, but allows the delimter to be escaped.
 * For example, 'A|B\|C|D' will be split into 'A', 'B|C', and 'D'
 * because the bar between B and C is escaped.
 * 
 * @param string $delimiter The delimiter
 * @param string $str The string to split
 * @param int $limit The maximum number of items to return, 0 for unlimited (default: 0)
 * @param string $escape_char The escape character (default: backslash)
 * @return array
 */
function explode_with_escape($delimiter, $str, $limit = 0, $escape_char = '\\')
{
	if ($limit < 1) $limit = null;
	$result = array();
	$split = preg_split('/(?<!' . preg_quote($escape_char, '/') . ')' . preg_quote($delimiter, '/') . '/', $str, $limit);
	foreach ($split as $piece)
	{
		if (trim($piece) !== '')
		{
			$result[] = trim(str_replace($escape_char . $delimiter, $delimiter, $piece));
		}
	}
	return $result;
}

/**
 * This function returns true if $haystack starts with $needle, and false otherwise.
 * 
 * @param string $needle The needle
 * @param string $haystack The haystack
 * @param bool $case_sensitive Whether the search should be case-sensitive (default: true)
 * @return bool
 */
function starts_with($needle, $haystack, $case_sensitive = true)
{
	if (strlen($needle) > strlen($haystack)) return false;
	if ($case_sensitive)
	{
		return !strncmp($needle, $haystack, strlen($needle));
	}
	else
	{
		return !strncasecmp($needle, $haystack, strlen($needle));
	}
}

/**
 * This function returns true if $haystack ends with $needle, and false otherwise.
 * 
 * @param string $needle The needle
 * @param string $haystack The haystack
 * @param bool $case_sensitive Whether the search should be case-sensitive (default: true)
 * @return bool
 */
function ends_with($needle, $haystack, $case_sensitive = true)
{
	if (strlen($needle) > strlen($haystack)) return false;
	if ($case_sensitive)
	{
		return (substr($haystack, -strlen($needle)) === $needle);
	}
	else
	{
		return (strtolower(substr($haystack, -strlen($needle))) === strtolower($needle));
	}
}

/**
 * This function returns true if $haystack contains $needle, and false otherwise.
 * 
 * @param string $needle The needle
 * @param string $haystack The haystack
 * @param bool $case_sensitive Whether the search should be case-sensitive (default: true)
 * @return bool
 */
function contains($needle, $haystack, $case_sensitive = true)
{
	return $case_sensitive ? (strpos($haystack, $needle) !== false) : (stripos($haystack, $needle) !== false);
}

/**
 * This function returns true if $needle is between $min and $max, and false otherwise.
 * Non-numeric values are compared according to PHP defaults.
 * 
 * @param mixed $needle The needle
 * @param mixed $min The minimum value
 * @param mixed $max The maximum value
 * @param bool $exclusive Set this to true to exclude endpoints (default: false)
 * @return bool
 */
function is_between($needle, $min, $max, $exclusive = false)
{
	if ($exclusive)
	{
		return ($needle > $min && $needle < $max);
	}
	else
	{
		return ($needle >= $min && $needle <= $max);
	}
}

/**
 * This function restricts $input to be between $min and $max.
 * All values less than $min are converted to $min, and all values greater than $max are converted to $max.
 * Non-numeric values are compared according to PHP defaults.
 * 
 * @param mixed $input The value to convert
 * @param mixed $min The minimum value
 * @param mixed $max The maximum value
 * @return mixed
 */
function force_range($input, $min, $max)
{
	if ($input < $min) $input = $min;
	if ($input > $max) $input = $max;
	return $input;
}

/**
 * This function encodes a string with base64, using a URL-safe character set.
 * 
 * The choice of alternative characters is defined in RFC 4648 Section 5.
 * Do not use substitute other characters such as the dot (.) or the tilde (~).
 * 
 * @param string $str The string to encode
 * @return string
 */
function base64_encode_urlsafe($str)
{
	return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
}

/**
 * This function decodes a string with base64, using a URL-safe character set.
 * 
 * @param string $str The string to decode
 * @return string
 */
function base64_decode_urlsafe($str)
{
	return @base64_decode(str_pad(strtr($str, '-_', '+/'), ceil(strlen($str) / 4) * 4, '=', STR_PAD_RIGHT));
}

/**
 * This function shortens a number using common suffixes.
 *
 * @param int $number The number to shorten
 * @param int $significant_digits The number of significant digits to retain
 * @return string
 */
function number_shorten($number, $significant_digits = 2)
{
	$length = strlen(abs(intval($number)));
	switch ($length)
	{
		case 0: case 1: case 2: case 3: return strval(intval($number));
		case 4: return number_format($number / 1000, max(0, $significant_digits - 1)) . 'K';
		case 5: return number_format($number / 1000, max(0, $significant_digits - 2)) . 'K';
		case 6: return number_format($number / 1000, max(0, $significant_digits - 3)) . 'K';
		case 7: return number_format($number / 1000000, max(0, $significant_digits - 1)) . 'M';
		case 8: return number_format($number / 1000000, max(0, $significant_digits - 2)) . 'M';
		case 9: return number_format($number / 1000000, max(0, $significant_digits - 3)) . 'M';
		case 10: return number_format($number / 1000000000, max(0, $significant_digits - 1)) . 'G';
		case 11: return number_format($number / 1000000000, max(0, $significant_digits - 2)) . 'G';
		case 12: return number_format($number / 1000000000, max(0, $significant_digits - 3)) . 'G';
		case 13: return number_format($number / 1000000000000, max(0, $significant_digits - 1)) . 'T';
		case 14: return number_format($number / 1000000000000, max(0, $significant_digits - 2)) . 'T';
		case 15: return number_format($number / 1000000000000, max(0, $significant_digits - 3)) . 'T';
		default: return floor($number / 1000000000000) . 'T';
	}
}

/**
 * Convert a server-side path to a URL.
 * 
 * This function is an alias to Rhymix\Framework\URL::fromServerPath().
 * It returns false if the path cannot be converted.
 * 
 * @param string $path
 * @return string|false
 */
function path2url($path)
{
	return Rhymix\Framework\URL::fromServerPath($path);
}

/**
 * Convert a URL to a server-side path.
 * 
 * This function is an alias to Rhymix\Framework\URL::toServerPath().
 * It returns false if the URL cannot be converted.
 * 
 * @param string $url
 * @return string|false
 */
function url2path($url)
{
	return Rhymix\Framework\URL::toServerPath($url);
}

/**
 * Convert hexadecimal color codes to an array of R, G, B values.
 * This function can handle both 6-digit and 3-digit notations, optionally prefixed with '#'.
 * If the color code is illegal, this function will return all nulls.
 * 
 * @param string $hex The color to convert
 * @return array
 */
function hex2rgb($hex)
{
	$hex = ltrim($hex, '#');
	if (strlen($hex) == 3)
	{
		$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
		$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
		$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
	}
	elseif (strlen($hex) == 6)
	{
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
	}
	else
	{
		$r = $g = $b = null;
	}
	return array($r, $g, $b);
}

/**
 * Convert an array of R, G, B values to hexadecimal color codes.
 * If the RGB values are illegal, this function will return #000000.
 * 
 * @param array $rgb The color to convert
 * @param bool $hash_prefix Whether or not to prefix the result with '#' (default: true)
 * @return string
 */
function rgb2hex(array $rgb, $hash_prefix = true)
{
	if (!isset($rgb[0]) || !isset($rgb[1]) || !isset($rgb[2]) || $rgb[0] > 255 || $rgb[1] > 255 || $rgb[2] > 255)
	{
		return '#000000';
	}
	$hex = $hash_prefix ? '#' : '';
	$hex .= str_pad(dechex(max(0, $rgb[0])), 2, '0', STR_PAD_LEFT);
	$hex .= str_pad(dechex(max(0, $rgb[1])), 2, '0', STR_PAD_LEFT);
	$hex .= str_pad(dechex(max(0, $rgb[2])), 2, '0', STR_PAD_LEFT);
	return $hex;
}

/**
 * This function includes another file in a clean scope.
 * This is useful if the included file tries to define global variables.
 * 
 * @param string $filename The name of the file to include
 * @return mixed
 */
function include_in_clean_scope($filename)
{
	return (include $filename);
}

/**
 * This function includes another file while ignoring all errors inside of it.
 * 
 * @param string $filename The name of the file to include
 * @return mixed
 */
function include_and_ignore_errors($filename)
{
	error_reporting(0);
	$result = (include $filename);
	error_reporting(~0);
	return $result;
}

/**
 * This function includes another file while ignoring all output.
 * 
 * @param string $filename The name of the file to include
 * @return mixed
 */
function include_and_ignore_output($filename)
{
	ob_start();
	$result = (include $filename);
	ob_end_clean();
	return $result;
}

/**
 * Converts any value to either true or false.
 * Based on util.php <https://github.com/brandonwamboldt/utilphp>
 * 
 * @param string $input The input value
 * @return bool
 */
function tobool($input)
{
	if (is_scalar($input))
	{
		if (preg_match('/^(?:1|[ty].*|on|ok.*|oui|si|vrai|aye)$/i', $input)) return true;
		if (preg_match('/^(?:0|[fn].*|off|out)$/i', $input)) return false;
	}
	return (bool)$input;
}

/**
 * Counts members of an array or an object.
 * 
 * @param mixed $array_or_object
 * @return int
 */
function countobj($array_or_object)
{
	if (is_array($array_or_object) || $array_or_object instanceof Countable)
	{
		return count($array_or_object);
	}
	elseif (is_object($array_or_object))
	{
		return count(get_object_vars($array_or_object));
	}
	else
	{
		return $array_or_object ? 1 : 0;
	}
}

/**
 * Checks if the given string contains valid UTF-8.
 * 
 * @param string $str The input string
 * @return bool
 */
function utf8_check($str)
{
	if (function_exists('mb_check_encoding'))
	{
		return mb_check_encoding($str, 'UTF-8');
	}
	else
	{
		return ($str === @iconv('UTF-8', 'UTF-8', $str));
	}
}

/**
 * Remove BOM and invalid UTF-8 sequences from text content.
 * 
 * @param string $str
 * @return string
 */
function utf8_clean($str)
{
	// Check if the input is a valid UTF-8 string.
    if (!utf8_check($str))
    {
        $str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
	}
	
	// Normalize the text content.
	if (class_exists('Normalizer'))
	{
		$str = Normalizer::normalize($str, Normalizer::FORM_C);
	}
	
	// Remove BOM.
	$str = preg_replace('/\xEF\xBB\xBF/', '', $str);
	
	// Remove Hangul Filler and RLO character.
	$str = preg_replace('/(?:&#(?:8238|x202e|12644|x3164);|\xE3\x85\xA4|\xE2\x80\xAE)/i', '', $str);
	
	// Remove excessively long sequences (more than 3) of combining diacritical marks.
	$str = preg_replace('/(\pM{3})\pM+/u', '$1', $str);
    
    return $str;
}

/**
 * Encode UTF-8 characters outside of the Basic Multilingual Plane in the &#xxxxxx format.
 * This allows emoticons and other characters to be stored in MySQL without utf8mb4 support.
 * 
 * @param $str The string to encode
 * @return string
 */
function utf8_mbencode($str)
{
	return preg_replace_callback('/[\xF0-\xF7][\x80-\xBF]{3}/', function($m) {
		$bytes = array(ord($m[0][0]), ord($m[0][1]), ord($m[0][2]), ord($m[0][3]));
		$codepoint = ((0x07 & $bytes[0]) << 18) + ((0x3F & $bytes[1]) << 12) + ((0x3F & $bytes[2]) << 6) + (0x3F & $bytes[3]);
		return '&#x' . dechex($codepoint) . ';';
	}, $str);
}

/**
 * This function replaces all whitespace characters with a single regular space (0x20).
 * Unicode whitespace characters are also replaced.
 * 
 * @param string $str The input string
 * @param bool $multiline Set this to true to permit newlines inside the string (default: false)
 * @return string
 */
function utf8_normalize_spaces($str, $multiline = false)
{
	return $multiline ? preg_replace('/((?!\x0A)[\pZ\pC])+/u', ' ', $str) : preg_replace('/[\pZ\pC]+/u', ' ', $str);
}

/**
 * This function trims all space from the beginning and end of a string.
 * Unicode whitespace characters are also trimmed.
 * 
 * @param string $str The input string
 * @return string
 */
function utf8_trim($str)
{
	return preg_replace('/^[\s\pZ\pC]+|[\s\pZ\pC]+$/u', '', $str);
}

/**
 * Check if a string contains HTML content.
 * This function checks whether a string seems to contain HTML.
 * It checks for tags like <p>, <div>, <br> at the beginning and end of lines.
 * 
 * @param string $str The input string
 * @return bool
 */
function is_html_content($str)
{
	$str = preg_replace('![\r\n]+!', "\n", utf8_trim(utf8_clean($str)));
	$line_count = substr_count($str, "\n") + 1;
	$p_tag_count = preg_match_all('!(?:^<(?:p|div|h[1-6])(?:>|\s*[a-z])|</(?:p|div|h[1-6])>$)!im', $str);
	if ($p_tag_count > 4 || ($p_tag_count > 0 && $p_tag_count >= $line_count * 2))
	{
		return true;
	}
	$br_tag_count = preg_match_all('!<br\s?/?>$!im', $str);
	if ($br_tag_count > 4 || ($br_tag_count > 0 && $br_tag_count >= $line_count - 1))
	{
		return true;
	}
	$span_tag_count = preg_match_all('!<span\s+style="[^"<>]*?">.+?</span>!i', $str);
	if ($span_tag_count >= 1)
	{
		return true;
	}
	return false;
}

/**
 * Check if HTML content is empty.
 * This function checks whether any printable characters remain
 * after removing all tags except images, videos, iframes, etc.
 * 
 * @param string $str The input string
 * @return bool
 */
function is_empty_html_content($str)
{
	$str = strip_tags($str, '<img><audio><video><iframe><object><embed>');
	$str = utf8_trim(utf8_clean(html_entity_decode($str, ENT_QUOTES, 'UTF-8')));
	return $str === '';
}

/**
 * Check if there is 'is_countable' function (PHP 7.3)
 * If there is no 'is_countable' function, define it to check for Countable objects.
 * 
 * cf. https://wiki.php.net/rfc/is-countable
 * 
 * @param string $str The input string
 * @return bool
**/
if (!function_exists('is_countable'))
{
    function is_countable($var)
	{
        return is_array($var) || $var instanceof Countable;
    }
}
