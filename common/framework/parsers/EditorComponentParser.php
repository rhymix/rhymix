<?php

namespace Rhymix\Framework\Parsers;

/**
 * Editor component (info.xml) parser class for XE compatibility.
 */
class EditorComponentParser extends BaseParser
{
	/**
	 * Load an XML file.
	 * 
	 * @param string $filename
	 * @param string $component_name
	 * @param string $lang
	 * @return object|false
	 */
	public static function loadXML(string $filename, string $component_name, string $lang = '')
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return false;
		}
		
		// Get the current language.
		$lang = $lang ?: (\Context::getLangType() ?: 'en');
		
		// Initialize the module definition.
		$info = new \stdClass;
		$info->component_name = $component_name;
		
		// Get basic information.
		$info->title = self::_getChildrenByLang($xml, 'title', $lang);
		$info->description = self::_getChildrenByLang($xml, 'description', $lang);
		$info->version = trim($xml->version);
		$info->date = date('Ymd', strtotime($xml->date . 'T12:00:00Z'));
		$info->homepage = trim($xml->homepage);
		$info->license = trim($xml->license);
		$info->license_link = trim($xml->license['link']);
		$info->author = array();
		
		foreach ($xml->author as $author)
		{
			$author_info = new \stdClass;
			$author_info->name = self::_getChildrenByLang($author, 'name', $lang);
			$author_info->email_address = trim($author['email_address']);
			$author_info->homepage = trim($author['link']);
			$info->author[] = $author_info;
		}
		
		// Get extra_vars.
		if ($xml->extra_vars)
		{
			$info->extra_vars = self::_getExtraVars($xml->extra_vars, $lang);
		}
		
		// Return the complete result.
		return $info;
	}
}
