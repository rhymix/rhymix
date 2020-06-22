<?php

namespace Rhymix\Framework\Parsers;

use Rhymix\Framework\Storage;

/**
 * DB query parser class for XE compatibility.
 */
class DBQueryParser
{
	/**
	 * Load a query XML file.
	 * 
	 * @param string $filename
	 * @return object|false
	 */
	public static function loadXML(string $filename)
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return false;
		}
	}
}
