<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\Helpers\DBResultHelper;
use Rhymix\Framework\Parsers\PluginInfoParser;
use Rhymix\Framework\Storage;
use Rhymix\Modules\Extravar\Models\Value as ExtraValue;

/**
 * This class manages plugins.
 */
class Plugin
{
	/**
	 * Get the list of installed plugins.
	 *
	 * @return array
	 */
	public static function getInstalledPluginList(): array
	{
		$plugins = [];

		// Read the list of plugin directories and get their information.
		$subdirs = Storage::readDirectory(\RX_BASEDIR . 'plugins', false, true, false);
		foreach ($subdirs as $plugin_name)
		{
			$info = self::getPluginInfo($plugin_name);
			if (!$info)
			{
				continue;
			}
			$plugins[$plugin_name] = $info;
		}

		// Use the database to add the is_enabled property to each plugin.
		$output = executeQueryArray('module.getInstalledPluginList', ['is_enabled' => 'Y']);
		foreach ($output->data as $row)
		{
			if (isset($plugins[$row->plugin_name]))
			{
				$plugins[$row->plugin_name]->is_enabled = true;
			}
		}

		return $plugins;
	}

	/**
	 * Get information about a specific plugin.
	 *
	 * @param string $plugin_name
	 * @return ?object
	 */
	public static function getPluginInfo(string $plugin_name): ?object
	{
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $plugin_name))
		{
			return null;
		}

		$xml_file = \RX_BASEDIR . 'plugins/' . $plugin_name . '/plugin.xml';
		if (!Storage::exists($xml_file) || !Storage::isReadable($xml_file))
		{
			return null;
		}

		$cache_key = 'plugin:info:' . $plugin_name . ':' . filemtime($xml_file);
		$info = Cache::get($cache_key);
		if ($info)
		{
			return $info;
		}

		$info = PluginInfoParser::loadXML($xml_file, $plugin_name);
		Cache::set($cache_key, $info, 0, true);
		return $info;
	}

	/**
	 * Get the current configuration of a specific plugin.
	 *
	 * @param string $plugin_name
	 * @return ?object
	 */
	public static function getPluginConfig(string $plugin_name): ?object
	{
		$cache_key = 'plugin:config:' . $plugin_name;
		$config = Cache::get($cache_key);
		if ($config)
		{
			return $config;
		}

		$output = executeQuery('module.getPluginConfig', ['plugin_name' => $plugin_name]);
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
	 * Get the default configuration of a specific plugin.
	 *
	 * @param string $plugin_name
	 * @return ?object
	 */
	public static function getDefaultConfig(string $plugin_name): ?object
	{
		$info = self::getPluginInfo($plugin_name);
		if (!$info)
		{
			return null;
		}

		$default_config = new \stdClass();
		foreach ($info->config as $key => $var)
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
	 * Check if a specific plugin is enabled.
	 *
	 * @param string $plugin_name
	 * @return bool
	 */
	public static function isPluginEnabled(string $plugin_name): bool
	{
		$output = executeQuery('module.getPluginConfig', ['plugin_name' => $plugin_name]);
		return $output->data && $output->data->is_enabled === 'Y';
	}

	/**
	 * Check if a specific plugin has been loaded in the current request.
	 *
	 * @param string $plugin_name
	 * @return bool
	 */
	public static function isPluginLoaded(string $plugin_name): bool
	{
		return isset(ModuleCache::$pluginInstances[$plugin_name]);
	}

	/**
	 * Get the list of enabled plugins and their configuration.
	 *
	 * @return array
	 */
	public static function getEnabledConfigList(): array
	{
		$cache_key = 'plugin:enabled';
		$enabled_plugins = Cache::get($cache_key);
		if ($enabled_plugins)
		{
			return $enabled_plugins;
		}

		$enabled_plugins = [];
		$output = $output = executeQueryArray('module.getInstalledPluginList', ['is_enabled' => 'Y'], ['plugin_name', 'config']);
		foreach ($output->data as $row)
		{
			$enabled_plugins[$row->plugin_name] = json_decode($row->config);
		}

		Cache::set($cache_key, $enabled_plugins, 0, true);
		return $enabled_plugins;
	}

	/**
	 * Load all enabled plugins.
	 *
	 * @return void
	 */
	public static function loadPlugins(): void
	{
		$plugin_list = self::getEnabledConfigList();
		foreach ($plugin_list as $plugin_name => $config)
		{
			$class_name = 'Rhymix\\Plugins\\' . $plugin_name . '\\plugin';
			if (class_exists($class_name))
			{
				ModuleCache::$pluginInstances[$plugin_name] = new $class_name($config);
			}
		}
	}

	/**
	 * Save the configuration for a specific plugin.
	 *
	 * @param string $plugin_name
	 * @param object $config
	 * @param bool $is_enabled
	 * @return DBResultHelper
	 */
	public static function insertPluginConfig(string $plugin_name, object $config, bool $is_enabled): DBResultHelper
	{
		$output = executeQuery('module.insertPluginConfig', [
			'plugin_name' => $plugin_name,
			'config' => json_encode($config, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES),
			'is_enabled' => $is_enabled ? 'Y' : 'N',
		]);

		Cache::delete('plugin:config:' . $plugin_name);
		Cache::delete('plugin:enabled');
		return $output;
	}

	/**
	 * Update the configuration for a specific plugin.
	 *
	 * @param string $plugin_name
	 * @param ?object $config
	 * @param ?bool $is_enabled
	 * @return DBResultHelper
	 */
	public static function updatePluginConfig(string $plugin_name, ?object $config, ?bool $is_enabled = null): DBResultHelper
	{
		$output = executeQuery('module.updatePluginConfig', [
			'plugin_name' => $plugin_name,
			'config' => $config ? json_encode($config, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) : null,
			'is_enabled' => $is_enabled === null ? null : ($is_enabled ? 'Y' : 'N'),
		]);

		Cache::delete('plugin:config:' . $plugin_name);
		Cache::delete('plugin:enabled');
		return $output;
	}

	/**
	 * Delete the configuration for a specific plugin.
	 *
	 * @param string $plugin_name
	 * @return DBResultHelper
	 */
	public static function deletePluginConfig(string $plugin_name): DBResultHelper
	{
		$output = executeQuery('module.deletePluginConfig', [
			'plugin_name' => $plugin_name,
		]);

		Cache::delete('plugin:config:' . $plugin_name);
		Cache::delete('plugin:enabled');
		return $output;
	}
}
