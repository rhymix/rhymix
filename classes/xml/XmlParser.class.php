<?php

/**
 * XML Parser class from XE
 *
 * Renamed because of conflict with built-in XMLParser class in PHP 8+
 *
 * @deprecated
 */
class XeXmlParser
{
	/**
	 * Load an XML file.
	 *
	 * @deprecated
	 * @param string $filename
	 * @return ?object
	 */
	public static function loadXmlFile($filename): ?object
	{
		$filename = strval($filename);
		if (file_exists($filename))
		{
			return Rhymix\Framework\Parsers\XEXMLParser::loadXMLFile($filename);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Load an XML string.
	 *
	 * @deprecated
	 * @param string $$input
	 * @return ?object
	 */
	function parse($input = ''): ?object
	{
		$input = strval($input !== '' ? $input : $GLOBALS['HTTP_RAW_POST_DATA']);
		return Rhymix\Framework\Parsers\XEXMLParser::loadXMLString($input);
	}
}

/**
 * Alias to XmlParser for backward compatibility.
 */
if (!class_exists('XmlParser', false))
{
	class_alias('XeXmlParser', 'XmlParser');
}
