<?php

namespace Rhymix\Modules\Layout\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Storage;
use Rhymix\Framework\Helpers\DBResultHelper;
use Rhymix\Framework\Parsers\ThemeInfoParser;
use Rhymix\Modules\Extravar\Models\Value as ExtraValue;

class Theme
{
	/**
	 * Default design config cache
	 */
	protected static ?object $_default_design_config = null;

	/**
	 * Get installed theme list
	 *
	 * @return array
	 */
	public static function getInstalledThemeList(): array
	{
		$themes = [];

		// Read the list of theme directories and get their information.
		$subdirs = Storage::readDirectory(\RX_BASEDIR . 'themes', false, true, false);
		foreach ($subdirs as $theme_name)
		{
			$info = self::getThemeInfo($theme_name);
			if (!$info)
			{
				continue;
			}
			$themes[$theme_name] = $info;
		}

		return $themes;
	}

	/**
	 * Get theme info
	 *
	 * @param string $theme_name
	 * @return ?ThemeInfoParser
	 */
	public static function getThemeInfo(string $theme_name): ?ThemeInfoParser
	{
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $theme_name))
		{
			return null;
		}

		$xml_file = \RX_BASEDIR . 'themes/' . $theme_name . '/theme.xml';
		if (!Storage::exists($xml_file) || !Storage::isReadable($xml_file))
		{
			return null;
		}

		$cache_key = 'theme:info:' . $theme_name . ':' . filemtime($xml_file);
		$info = Cache::get($cache_key);
		if ($info)
		{
			return $info;
		}

		$info = ThemeInfoParser::loadXML($xml_file, $theme_name);
		Cache::set($cache_key, $info, 0, true);
		return $info;
	}

	/**
	 * Get theme config
	 *
	 * @param string $theme_name
	 * @return ?object
	 */
	public static function getThemeConfig(string $theme_name): ?object
	{
		$cache_key = 'theme:config:' . $theme_name;
		$config = Cache::get($cache_key);
		if ($config)
		{
			return $config;
		}

		$output = executeQuery('layout.getThemeConfig', ['theme_name' => $theme_name]);
		if ($output->toBool() && $output->data && !empty($output->data->config))
		{
			$config = json_decode($output->data->config);
			Cache::set($cache_key, $config, 0, true);
			return $config;
		}
		else
		{
			return null;
		}

	}

	/**
	 * Get the default configuration of a specific theme.
	 *
	 * @param string $theme_name
	 * @param string $sub_name
	 * @return ?object
	 */
	public static function getDefaultConfig(string $theme_name, string $sub_name = 'theme'): ?object
	{
		$info = self::getThemeInfo($theme_name);
		if (!$info)
		{
			return null;
		}

		if ($sub_name === 'theme')
		{
			$config = $info->config;
		}
		else
		{
			$config = $info->loadSubConfig($sub_name);
		}

		$default_config = new \stdClass();
		foreach ($config as $key => $var)
		{
			if (isset(ExtraValue::ARRAY_TYPES[$var->type]) && !in_array($var->type, ['radio', 'select']))
			{
				$default_config->{$var->name} = $var->default === null ? [] : [strval($var->default)];
			}
			else
			{
				$default_config->{$var->name} = $var->default === null ? '' : strval($var->default);
			}
		}
		return $default_config;
	}

	/**
	 * Save the configuration for a specific theme.
	 *
	 * @param string $theme_name
	 * @param object $config
	 * @return DBResultHelper
	 */
	public static function insertThemeConfig(string $theme_name, object $config): DBResultHelper
	{
		$output = executeQuery('layout.insertThemeConfig', [
			'theme_srl' => getNextSequence(),
			'theme_name' => $theme_name,
			'config' => json_encode($config, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES),
		]);

		Cache::delete('theme:config:' . $theme_name);
		return $output;
	}

	/**
	 * Update the configuration for a specific theme.
	 *
	 * @param string $theme_name
	 * @param ?object $config
	 * @return DBResultHelper
	 */
	public static function updateThemeConfig(string $theme_name, ?object $config): DBResultHelper
	{
		$output = executeQuery('layout.updateThemeConfig', [
			'theme_name' => $theme_name,
			'config' => $config ? json_encode($config, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) : null,
		]);

		Cache::delete('theme:config:' . $theme_name);
		return $output;
	}

	/**
	 * Delete the configuration for a specific theme.
	 *
	 * @param string $theme_name
	 * @return DBResultHelper
	 */
	public static function deleteThemeConfig(string $theme_name): DBResultHelper
	{
		$output = executeQuery('layout.deleteThemeConfig', [
			'theme_name' => $theme_name,
		]);

		Cache::delete('theme:config:' . $theme_name);
		return $output;
	}

	/**
	 * Get the current site's default design configuration.
	 *
	 * @return object
	 */
	public static function getDefaultDesignConfig(): object
	{
		if (isset(self::$_default_design_config))
		{
			return self::$_default_design_config;
		}

		include \RX_BASEDIR . 'files/site_design/design_0.php';
		if (!isset($designInfo) || !is_object($designInfo))
		{
			$designInfo = new \stdClass;
		}
		if (!isset($designInfo->theme) || !is_string($designInfo->theme))
		{
			$designInfo->theme = '';
		}

		self::$_default_design_config = $designInfo;
		return $designInfo;
	}

	/**
	 * Set the current site's default design configuration.
	 *
	 * @param object $config
	 * @return bool
	 */
	public static function setDefaultDesignConfig(object $config): bool
	{
		// Clean up the object.
		$valid_keys = ['layout_srl', 'mlayout_srl', 'module', 'theme'];
		foreach ($config as $key => $val)
		{
			if (!in_array($key, $valid_keys))
			{
				unset($config->{$key});
			}
		}
		$config->theme = preg_replace('/[^a-zA-Z0-9:_-]/', '', $config->theme ?? '');
		$config->layout_srl = intval($config->layout_srl);
		$config->mlayout_srl = intval($config->mlayout_srl);
		foreach ($config->module ?? [] as $moduleName => $skinInfo)
		{
			$skinInfo = (object)[
				'skin' => $skinInfo->skin ?? '',
				'mskin' => $skinInfo->mskin ?? '',
			];
			$config->module->{$moduleName} = $skinInfo;
		}

		// Clear local cache.
		self::$_default_design_config = null;

		// Write the object to a PHP file.
		$filename = \RX_BASEDIR . 'files/site_design/design_0.php';
		$content = preg_replace('/=>\s+\(object\) array\(/', '=> (object) array(', var_export($config, true));
		return Storage::write($filename, "<?php\n\n\$designInfo = " . $content . ";\n");
	}
}
