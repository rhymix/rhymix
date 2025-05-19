<?php

namespace Rhymix\Framework\Parsers;

/**
 * Widget Style (info.xml) parser class for XE compatibility.
 */
class WidgetStyleInfoParser extends BaseParser
{
	/**
	 * Load an XML file.
	 *
	 * @param string $filename
	 * @param string $widgetstyle_name
	 * @param string $lang
	 * @return ?object
	 */
	public static function loadXML(string $filename, string $widgetstyle_name, string $lang = ''): ?object
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return null;
		}

		// Get the current language.
		$lang = $lang ?: (\Context::getLangType() ?: 'en');

		// Initialize the widget definition.
		$info = new \stdClass;
		$info->widgetStyle = $widgetstyle_name;
		$info->path = sprintf('./widgetstyles/%s/', $widgetstyle_name);

		// Parse common fields.
		$info->title = self::_getChildrenByLang($xml, 'title', $lang);
		$info->description = self::_getChildrenByLang($xml, 'description', $lang);
		$info->version = trim($xml->version);
		$info->date = ($xml->date === 'RX_CORE') ? '' : date('Ymd', strtotime($xml->date . 'T12:00:00Z'));
		$info->homepage = trim($xml->link);
		$info->license = trim($xml->license);
		$info->license_link = trim($xml->license['link'] ?? '');

		// Parse the preview image.
		$preview_filename = trim($xml->preview ?? 'preview.jpg');
		$preview_path = sprintf('%s%s', $info->path, $preview_filename);
		if (file_exists($preview_path))
		{
			$info->preview = $preview_path;
		}
		else
		{
			$info->preview = null;
		}

		// Parse the author list.
		$info->author = array();
		foreach ($xml->author as $author)
		{
			$author_info = new \stdClass;
			$author_info->name = self::_getChildrenByLang($author, 'name', $lang);
			$author_info->email_address = trim($author['email_address'] ?? '');
			$author_info->homepage = trim($author['link'] ?? '');
			$info->author[] = $author_info;
		}

		// Get extra_vars.
		if ($xml->extra_vars)
		{
			$info->extra_var = self::_getExtraVars($xml->extra_vars, $lang, 'widget');
		}
		else
		{
			$info->extra_var = new \stdClass;
		}

		// Count extra vars.
		$info->extra_var_count = count(get_object_vars($info->extra_var));

		// Return the complete result.
		return $info;
	}
}
