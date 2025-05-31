<?php

namespace Rhymix\Framework\Parsers;

/**
 * Layout (info.xml) parser class for XE compatibility.
 */
class LayoutInfoParser extends BaseParser
{
	/**
	 * Load an XML file.
	 *
	 * @param string $filename
	 * @param string $layout_name
	 * @param string $layout_path
	 * @param string $lang
	 * @return ?object
	 */
	public static function loadXML(string $filename, string $layout_name, string $layout_path, string $lang = ''): ?object
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return null;
		}

		// Get the current language.
		$lang = $lang ?: (\Context::getLangType() ?: 'en');

		// Initialize the layout definition.
		$info = new \stdClass;
		$info->layout = $layout_name;
		$info->type = trim($xml['type'] ?? '');
		$info->path = $layout_path;

		// Get the XML schema version.
		$version = strval($xml['version']) ?: '0.1';

		// Parse version 0.2
		if ($version === '0.2')
		{
			$info->title = self::_getChildrenByLang($xml, 'title', $lang) ?: $layout_name;
			$info->description = self::_getChildrenByLang($xml, 'description', $lang);
			$info->version = trim($xml->version);
			$info->date = ($xml->date === 'RX_CORE') ? '' : date('Ymd', strtotime($xml->date . 'T12:00:00Z'));
			$info->homepage = trim($xml->link);
			$info->license = trim($xml->license);
			$info->license_link = trim($xml->license['link'] ?? '');
			$info->author = array();

			foreach ($xml->author as $author)
			{
				$author_info = new \stdClass;
				$author_info->name = self::_getChildrenByLang($author, 'name', $lang);
				$author_info->email_address = trim($author['email_address'] ?? '');
				$author_info->homepage = trim($author['link'] ?? '');
				$info->author[] = $author_info;
			}
		}

		// Parse version 0.1
		else
		{
			$info->title = self::_getChildrenByLang($xml, 'title', $lang) ?: $layout_name;
			$info->description = self::_getChildrenByLang($xml->author, 'description', $lang);
			$info->version = trim($xml['version'] ?? '');
			$info->date = date('Ymd', strtotime($xml->author['date'] . 'T12:00:00Z'));
			$info->homepage = trim($xml->link);
			$info->license = trim($xml->license);
			$info->license_link = trim($xml->license['link'] ?? '');
			$info->author = array();

			$author_info = new \stdClass;
			$author_info->name = self::_getChildrenByLang($xml->author, 'name', $lang);
			$author_info->email_address = trim($xml->author['email_address']);
			$author_info->homepage = trim($xml->author['link'] ?? '');
			$info->author[] = $author_info;
		}

		// Get extra_vars.
		$info->extra_var_count = 0;
		if ($xml->extra_vars)
		{
			$info->extra_var = self::_getExtraVars($xml->extra_vars, $lang, 'layout', ['layout_path' => $layout_path]);
		}
		else
		{
			$info->extra_var = new \stdClass;
		}

		// Count extra vars.
		$info->extra_var_count = count(get_object_vars($info->extra_var));

		// Get menus.
		$info->menu_count = 0;
		if (isset($xml->menus->menu))
		{
			$info->menu = new \stdClass;
			foreach ($xml->menus->menu as $menu)
			{
				$menu_item = new \stdClass;
				$menu_item->name = trim($menu['name'] ?? '');
				$menu_item->title = self::_getChildrenByLang($menu, 'title', $lang);
				$menu_item->maxdepth = intval($menu['maxdepth'] ?? 0);
				$menu_item->menu_srl = null;
				$menu_item->xml_file = '';
				$menu_item->php_file = '';
				$info->menu->{$menu_item->name} = $menu_item;
				$info->menu_count++;
			}
		}
		else
		{
			$info->menu = null;
		}
		$info->menu_name_list = null;

		// Prepare additional fields that will be filled in later.
		$info->site_srl = 0;
		$info->layout_srl = 0;
		$info->layout_title = '';
		$info->header_script = '';

		// Return the complete result.
		return $info;
	}
}
