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
	public static function loadXML($filename)
	{
		// Load the XML file.
		$xml = simplexml_load_file($filename);
		if ($xml === false)
		{
			return false;
		}
	}
}
