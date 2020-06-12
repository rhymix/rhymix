<?php

namespace Rhymix\Framework\Parsers;

/**
 * Module info (conf/info.xml) parser class for XE compatibility.
 */
class ModuleInfoParser
{
	/**
	 * Load an XML file.
	 * 
	 * @param string $filename
	 * @return object
	 */
	public static function loadXML(string $filename): object
	{
		// Load the XML file.
		$xml = simplexml_load_file($filename);
		if ($xml === false)
		{
			return new \stdClass;
		}
		
		// Get the current language.
		$lang = \Context::getLangType();
		
		// Initialize the module definition.
		$info = new \stdClass;
		
		// Get the XML schema version.
		$version = strval($xml['version']) ?: '0.1';
		
		// Parse version 0.2
		if ($version === '0.2')
		{
			$info->title = self::_getElementsByLang($xml, 'title', $lang);
			$info->description = self::_getElementsByLang($xml, 'description', $lang);
			$info->version = trim($xml->version);
			$info->homepage = trim($xml->homepage);
			$info->category = trim($xml->category) ?: 'service';
			$info->date = date('Ymd', strtotime($xml->date . 'T12:00:00Z'));
			$info->license = trim($xml->license);
			$info->license_link = trim($xml->license['link']);
			$info->author = array();
			
			foreach ($xml->author as $author)
			{
				$author_info = new \stdClass;
				$author_info->name = self::_getElementsByLang($author, 'name', $lang);
				$author_info->email_address = trim($author['email_address']);
				$author_info->homepage = trim($author['link']);
				$info->author[] = $author_info;
			}
		}
		
		// Parse version 0.1
		else
		{
			$info->title = self::_getElementsByLang($xml, 'title', $lang);
			$info->description = self::_getElementsByLang($xml->author, 'description', $lang);
			$info->version = trim($xml['version']);
			$info->homepage = trim($xml->homepage);
			$info->category = trim($xml['category']) ?: 'service';
			$info->date = date('Ymd', strtotime($xml->author['date'] . 'T12:00:00Z'));
			$info->license = trim($xml->license);
			$info->license_link = trim($xml->license['link']);
			$info->author = array();
			
			foreach ($xml->author as $author)
			{
				$author_info = new \stdClass;
				$author_info->name = self::_getElementsByLang($author, 'name', $lang);
				$author_info->email_address = trim($author['email_address']);
				$author_info->homepage = trim($author['link']);
				$info->author[] = $author_info;
			}
		}

		// Add information about actions.
		$action_info = ModuleActionParser::loadXML(strtr($filename, ['info.xml' => 'module.xml']));
		$info->admin_index_act = $action_info->admin_index_act;
		$info->default_index_act = $action_info->default_index_act;
		$info->setup_index_act = $action_info->setup_index_act;
		$info->simple_setup_index_act = $action_info->simple_setup_index_act;
		
		// Return the complete result.
		return $info;
	}
	
	/**
	 * Get child elements that match a language.
	 * 
	 * @param SimpleXMLElement $parent
	 * @param string $tag_name
	 * @param string $lang
	 * @return string
	 */
	protected static function _getElementsByLang(\SimpleXMLElement $parent, string $tag_name, string $lang): string
	{
		// If there is a child element that matches the language, return it.
		foreach ($parent->{$tag_name} as $child)
		{
			$attribs = $child->attributes('xml', true);
			if (strval($attribs['lang']) === $lang)
			{
				return trim($child);
			}
		}
		
		// Otherwise, return the first child element.
		foreach ($parent->{$tag_name} as $child)
		{
			return trim($child);
		}
		
		// If there are no child elements, return an empty string.
		return '';
	}
}
