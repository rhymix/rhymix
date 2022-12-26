<?php

namespace Rhymix\Framework\Parsers;

/**
 * Module info (conf/info.xml) parser class for XE compatibility.
 */
class ModuleInfoParser extends BaseParser
{
	/**
	 * Load an XML file.
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
		
		// Get the current language.
		$lang = \Context::getLangType() ?: 'en';
		
		// Initialize the module definition.
		$info = new \stdClass;
		
		// Get the XML schema version.
		$version = strval($xml['version']) ?: '0.1';
		
		// Parse version 0.2
		if ($version === '0.2')
		{
			$info->title = self::_getChildrenByLang($xml, 'title', $lang);
			$info->description = self::_getChildrenByLang($xml, 'description', $lang);
			$info->version = trim($xml->version);
			$info->homepage = trim($xml->homepage);
			$info->category = trim($xml->category) ?: 'service';
			$info->date = ($xml->date === 'RX_CORE') ? '' : date('Ymd', strtotime($xml->date . 'T12:00:00Z'));
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
		}
		
		// Parse version 0.1
		else
		{
			$info->title = self::_getChildrenByLang($xml, 'title', $lang);
			$info->description = self::_getChildrenByLang($xml->author, 'description', $lang);
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
				$author_info->name = self::_getChildrenByLang($author, 'name', $lang);
				$author_info->email_address = trim($author['email_address']);
				$author_info->homepage = trim($author['link']);
				$info->author[] = $author_info;
			}
		}

		// Add information about actions.
		$action_filename = strtr($filename, ['info.xml' => 'module.xml']);
		if (file_exists($action_filename))
		{
			$action_info = ModuleActionParser::loadXML($action_filename);
			$info->admin_index_act = $action_info->admin_index_act;
			$info->default_index_act = $action_info->default_index_act;
			$info->setup_index_act = $action_info->setup_index_act;
			$info->simple_setup_index_act = $action_info->simple_setup_index_act;
			$info->error_handlers = $action_info->error_handlers ?: [];
		}
		
		// Return the complete result.
		return $info;
	}
}
