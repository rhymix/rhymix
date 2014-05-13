<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

if(!defined('__XE__'))
	exit();

/**
 * @file ./addons/blogapi/blogapi.func.php
 * @author NAVER (developers@xpressengine.com)
 * @brief Function collections for the implementation of blogapi
 * */
// Error messages
function getXmlRpcFailure($error, $message)
{
	return
			sprintf(
					"<methodResponse>\n<fault><value><struct>\n<member>\n<name>faultCode</name>\n<value><int>%d</int></value>\n</member>\n<member>\n<name>faultString</name>\n<value><string>%s</string></value>\n</member>\n</struct></value></fault>\n</methodResponse>\n", $error, htmlspecialchars($message, ENT_COMPAT | ENT_HTML401, 'UTF-8', false)
	);
}

// Display results
function getXmlRpcResponse($params)
{
	$buff = '<?xml version="1.0" encoding="utf-8"?>' . "\n<methodResponse><params>";
	$buff .= _getEncodedVal($params);
	$buff .= "</params>\n</methodResponse>\n";

	return $buff;
}

// Encoding
function _getEncodedVal($val, $is_sub_set = false)
{
	if(preg_match('/^\<\!\[CDATA\[/',$val))
	{
		$buff = sprintf("<value>%s</value>", $val);
	}
	elseif(is_int($val))
	{
		$buff = sprintf("<value><i4>%d</i4></value>", $val);
	}
	elseif(is_string($val) && preg_match('/^([0-9]+)T([0-9\:]+)$/', $val))
	{
		$buff = sprintf("<value><dateTime.iso8601>%s</dateTime.iso8601></value>\n", $val);
	}
	elseif(is_double($val))
	{
		$buff = sprintf("<value><double>%f</double></value>", $val);
	}
	elseif(is_bool($val))
	{
		$buff = sprintf("<value><boolean>%d</boolean></value>", $val ? 1 : 0);
	}
	elseif(is_object($val))
	{
		$values = get_object_vars($val);
		$val_count = count($values);
		$buff = "<value><struct>";
		foreach($values as $k => $v)
		{
			$buff .= sprintf("<member>\n<name>%s</name>\n%s</member>\n", htmlspecialchars($k, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), _getEncodedVal($v, true));
		}
		$buff .= "</struct></value>\n";
	}
	elseif(is_array($val))
	{
		$val_count = count($val);
		$buff = "<value><array>\n<data>";
		for($i = 0; $i < $val_count; $i++)
		{
			$buff .= _getEncodedVal($val[$i], true);
		}
		$buff .= "</data>\n</array></value>";
	}
	else
	{
		$buff = sprintf("<value><string>%s</string></value>\n", $val);
	}
	if(!$is_sub_set)
	{
		return sprintf("<param>\n%s</param>", $buff);
	}
	return $buff;
}

// Display the result
function printContent($content)
{
	header("Content-Type: text/xml; charset=UTF-8");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	print $content;
	Context::close();
	exit();
}

/* End of file blogapi.func.php */
/* Location: ./addons/blogapi/blogapi.func.php */
