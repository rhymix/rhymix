<?php

namespace Rhymix\Framework\Parsers;

/**
 * Theme (theme.xml) parser class.
 */
#[\AllowDynamicProperties]
class ThemeInfoParser extends BaseParser
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
	public array $thumbnails = [];
	public array $provides = [];
	public object $config;
	public array $config_groups = [];

	/**
	 * Load the main XML file of a theme.
	 *
	 * @param string $filename
	 * @param string $theme_name
	 * @param string $lang
	 * @return ?self
	 */
	public static function loadXML(string $filename, string $theme_name, string $lang = ''): ?self
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return null;
		}

		// Get the current language.
		$lang = $lang ?: (\Context::getLangType() ?: 'en');

		// Initialize the theme definition.
		$info = new self;
		$info->name = $theme_name;
		$info->path = './themes/' . $theme_name . '/';
		$info->title = self::_getChildrenByLang($xml, 'title', $lang);
		$info->description = self::_getChildrenByLang($xml, 'description', $lang);
		$info->license = trim($xml->license);
		$info->version = trim($xml->version);
		$info->date = ($xml->date === 'RX_CORE') ? '' : date('Ymd', strtotime($xml->date . 'T12:00:00Z'));
		$info->config = new \stdClass;

		// Get author information.
		foreach ($xml->author as $author)
		{
			$author_info = new \stdClass;
			$author_info->name = self::_getChildrenByLang($author, 'name', $lang);
			$author_info->email_address = trim($author['email_address'] ?? '');
			$author_info->homepage = trim($author['link'] ?? '');
			$info->author[] = $author_info;
		}

		// Get thumbnail information.
		if ($xml->thumbnails)
		{
			foreach ($xml->thumbnails->thumbnail as $thumbnail)
			{
				$thumbnail_info = new \stdClass;
				$thumbnail_info->type = trim($thumbnail['type'] ?? 'any');
				$thumbnail_info->path = trim($thumbnail['path'] ?? '', './');
				if ($thumbnail_info->path !== '')
				{
					$info->thumbnails[] = $thumbnail_info;
				}
			}
		}

		// Get details about the layouts and skins provided by this theme.
		if ($xml->provides)
		{
			foreach ($xml->provides->children() as $provide)
			{
				// Check the type.
				$type = strtolower(str_replace('Skin', '_skin', $provide->getName()));
				if (!in_array($type, ['layout', 'module_skin', 'widget_skin']))
				{
					continue;
				}

				// Parse the provide item.
				$item = new \stdClass;
				$item->name = '';
				$item->type = $type;
				$item->path = trim($provide['path'] ?? '', './') . '/';
				if ($type === 'module_skin')
				{
					$item->module = trim($provide['for'] ?? '');
				}
				if ($type === 'widget_skin')
				{
					$item->widget = trim($provide['for'] ?? '');
				}
				$item->title = self::_getChildrenByLang($provide, 'title', $lang);
				$item->description = self::_getChildrenByLang($provide, 'description', $lang);

				// Generate a unique name for this item, especially if there are multiple skins for the same module or widget.
				$base_name = 'theme:' . $theme_name . ':' . ($item->module ?? ($item->widget ?? $item->type));
				$name = $base_name;
				$seq = 2;
				while (isset($info->provides[$name]))
				{
					$name = $base_name . $seq++;
				}
				$item->name = $name;
				$info->provides[$name] = $item;
			}
		}

		// Get theme config definition.
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

		// Return the complete result.
		return $info;
	}

	/**
	 * Find the best matching thumbnail for the type.
	 *
	 * @param string $type
	 * @return ?string
	 */
	public function getThumbnail(string $type = 'any'): ?string
	{
		foreach ($this->thumbnails as $thumbnail)
		{
			if ($thumbnail->type === $type)
			{
				return $this->path . $thumbnail->path;
			}
		}
		foreach ($this->thumbnails as $thumbnail)
		{
			if ($thumbnail->type === 'any')
			{
				return $this->path . $thumbnail->path;
			}
		}

		return null;
	}

	/**
	 * Load the XML file of a layout or skin provided by this theme.
	 *
	 * @param string $name
	 * @param string $lang
	 * @return ?object
	 */
	public function loadSubConfig(string $name, string $lang = ''): ?object
	{
		// Check if the sub config exists.
		if (!str_starts_with($name, 'theme:'))
		{
			$name = 'theme:' . $this->name . ':' . $name;
		}
		if (!isset($this->provides[$name]))
		{
			return null;
		}

		// Load the XML file.
		$type = $this->provides[$name]->type;
		$filename = \RX_BASEDIR . $this->path . $this->provides[$name]->path . ($type === 'layout' ? 'layout.xml' : 'skin.xml');
		if (!file_exists($filename) || !is_readable($filename))
		{
			return null;
		}
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return null;
		}

		// Get the current language.
		$lang = $lang ?: (\Context::getLangType() ?: 'en');

		// Clone the provide item to add more attributes.
		$info = clone $this->provides[$name];
		$info->config = new \stdClass;
		$info->config_groups = [];

		// Add config definitions.
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

		// Add menu definitions.
		if ($type === 'layout')
		{
			$info->menus = new \stdClass;
			if (isset($xml->menus) && isset($xml->menus->menu))
			{
				foreach ($xml->menus->menu as $menu)
				{
					$menu_item = new \stdClass;
					$menu_item->name = trim($menu['name'] ?? '');
					$menu_item->title = self::_getChildrenByLang($menu, 'title', $lang);
					$info->menus->{$menu_item->name} = $menu_item;
				}
			}
		}

		return $info;
	}
}
