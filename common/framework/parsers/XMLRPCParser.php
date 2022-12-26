<?php

namespace Rhymix\Framework\Parsers;

/**
 * XMLRPC request parser class for XE compatibility.
 */
class XMLRPCParser
{
	/**
	 * Load an XML file.
	 * 
	 * @param string $content
	 * @return object|false
	 */
	public static function parse(string $content)
	{
		// Load the XML content.
		$xml = simplexml_load_string($content);
		if ($xml === false)
		{
			return false;
		}
		
		// Loop over the list of parameters.
		$result = self::_parseArray($xml->params);
		
		// Return the complete result.
		return $result;
	}
	
	/**
	 * Process an array of parameters.
	 * 
	 * @param \SimpleXMLElement $parent
	 * @return array
	 */
	protected static function _parseArray(\SimpleXMLElement $parent): array
	{
		$result = array();
		foreach ($parent->children() ?: [] as $tag)
		{
			$key = $tag->getName();
			if (strval($tag['type']) === 'array')
			{
				$result[$key] = self::_parseArray($tag);
			}
			else
			{
				$result[$key] = strval($tag);
			}
		}
		return $result;
	}
}
