<?php

/**
 * Function library for Rhymix
 * 
 * Copyright (c) Rhymix Developers and Contributors
 */

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
