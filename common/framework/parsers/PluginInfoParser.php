<?php

namespace Rhymix\Framework\Parsers;

/**
 * Plugin (plugin.xml) parser class.
 */
#[\AllowDynamicProperties]
class PluginInfoParser extends BaseParser
{
	/*
	 * Supported attributes
	 */
	public string $name;
	public string $path;
	public string $title = '';
	public string $description = '';
	public string $license = '';
	public string $version = '';
	public string $date = '';
	public array $author = [];
	public object $config;
	public array $config_groups = [];
	public bool $is_enabled = false;

	/**
	 * Load an XML file.
	 *
	 * @param string $filename
	 * @param string $plugin_name
	 * @param string $lang
	 * @return ?self
	 */
	public static function loadXML(string $filename, string $plugin_name, string $lang = ''): ?self
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return null;
		}

		// Get the current language.
		$lang = $lang ?: (\Context::getLangType() ?: 'en');

		// Initialize the plugin definition.
		$info = new self;
		$info->name = $plugin_name;
		$info->path = './plugins/' . $plugin_name . '/';
		$info->title = self::_getChildrenByLang($xml, 'title', $lang);
		$info->description = self::_getChildrenByLang($xml, 'description', $lang);
		$info->license = trim($xml->license);
		$info->version = trim($xml->version);
		$info->date = ($xml->date === 'RX_CORE') ? '' : date('Ymd', strtotime($xml->date . 'T12:00:00Z'));
		$info->author = [];

		// Get author information.
		foreach ($xml->author as $author)
		{
			$author_info = new \stdClass;
			$author_info->name = self::_getChildrenByLang($author, 'name', $lang);
			$author_info->email_address = trim($author['email_address'] ?? '');
			$author_info->homepage = trim($author['link'] ?? '');
			$info->author[] = $author_info;
		}

		// Get plugin config definition.
		if ($xml->config)
		{
			$info->config = self::_parseConfig($xml->config, $lang);
			foreach ($info->config as $key => $var)
			{
				if ($var->group !== null)
				{
					$info->config_groups[$var->group] = true;
				}
			}
			$info->config_groups = array_keys($info->config_groups);
		}
		else
		{
			$info->config = new \stdClass;
		}

		// Return the complete result.
		return $info;
	}
}
