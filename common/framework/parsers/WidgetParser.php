<?php

namespace Rhymix\Framework\Parsers;

/**
 * Widget (info.xml) parser class for XE compatibility.
 */
class WidgetParser extends BaseParser
{
	/**
	 * Allowed types
	 *
	 * @var array<string>
	 */
	protected static $extra_vars_allowed_types = [
		'checkbox',
		'color', 'colorpicker',
		'filebox',
		'member_group',
		'menu',
		'mid_list',
		'mid',
		'module_srl_list',
		'number',
		'radio',
		'select-multi-order',
		'select',
		'text',
		'textarea',
	];

	/**
	 * Load an XML file.
	 */
	public static function loadXML(string $filename, string $widget_name, string $lang = ''): ?object
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return null;
		}

		// Get the current language.
		$lang = $lang ?: (\Context::getLangType() ?: 'en');

		$info = new \stdClass;
		$info->widget = $widget_name;
		$info->widget_name = $widget_name;

		// Get basic information.
		$info->title = self::_getChildrenByLang($xml, 'title', $lang);
		$info->description = self::_getChildrenByLang($xml, 'description', $lang);
		$info->version = trim($xml->version ?? '');
		$info->date = ($xml->date == 'RX_CORE') ? 'RX_CORE' : date('Ymd', strtotime($xml->date . 'T12:00:00Z'));
		$info->homepage = trim($xml->homepage ?? '');
		$info->license = trim($xml->license ?? '');
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

		// Get extra_vars.
		if ($xml->extra_vars)
		{
			$info->extra_vars = self::_getExtraVars($xml->extra_vars, $lang, self::$extra_vars_allowed_types);
			// fallback
			$info->extra_var = &$info->extra_vars;
		}

		// Return the complete result.
		return $info;
	}
}
