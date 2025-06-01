<?php

namespace Rhymix\Framework\Parsers;

/**
 * Skin (info.xml) parser class for XE compatibility.
 */
class SkinInfoParser extends BaseParser
{
	/**
	 * Load an XML file.
	 *
	 * @param string $filename
	 * @param string $skin_name
	 * @param string $skin_path
	 * @param string $lang
	 * @return ?object
	 */
	public static function loadXML(string $filename, string $skin_name, string $skin_path, string $lang = ''): ?object
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
		$info->skin = $skin_name;
		$info->path = $skin_path;

		// Get the XML schema version.
		$version = strval($xml['version']) ?: '0.1';

		// Parse version 0.2
		if ($version === '0.2')
		{
			$info->title = self::_getChildrenByLang($xml, 'title', $lang) ?: $skin_name;
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
			$info->title = self::_getChildrenByLang($xml, 'title', $lang) ?: $skin_name;
			$info->description = self::_getChildrenByLang($xml->maker, 'description', $lang);
			$info->version = trim($xml['version'] ?? '');
			$info->date = date('Ymd', strtotime($xml->maker['date'] . 'T12:00:00Z'));
			$info->homepage = trim($xml->link);
			$info->license = trim($xml->license);
			$info->license_link = trim($xml->license['link'] ?? '');
			$info->author = array();

			$author_info = new \stdClass;
			$author_info->name = self::_getChildrenByLang($xml->maker, 'name', $lang);
			$author_info->email_address = trim($xml->maker['email_address']);
			$author_info->homepage = trim($xml->maker['link'] ?? '');
			$info->author[] = $author_info;
		}

		// Get extra_vars.
		if ($xml->extra_vars)
		{
			$info->extra_vars = get_object_vars(self::_getExtraVars($xml->extra_vars, $lang, 'skin', ['version' => $version]));
		}
		else
		{
			$info->extra_vars = [];
		}

		// Get colorsets.
		if ($xml->colorset && $xml->colorset->color)
		{
			$info->colorset = [];
			foreach ($xml->colorset->color as $color)
			{
				$color_item = new \stdClass;
				$color_item->name = trim($color['name'] ?? '');
				$color_item->title = self::_getChildrenByLang($color, 'title', $lang);
				$screenshot = trim($color['src'] ?? '');
				if ($screenshot)
				{
					$screenshot = $info->path . $screenshot;
				}
				$color_item->screenshot = $screenshot;
				$info->colorset[] = $color_item;
			}
		}

		// Get thumbnail path.
		if (file_exists($info->path . 'thumbnail.png'))
		{
			$info->thumbnail = $info->path . 'thumbnail.png';
		}
		else
		{
			$info->thumbnail = '';
		}

		// Return the complete result.
		return $info;
	}
}
