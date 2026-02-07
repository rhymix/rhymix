<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Parsers\ModuleActionParser;
use Rhymix\Framework\Parsers\ModuleInfoParser;
use Rhymix\Framework\Parsers\SkinInfoParser;
use Rhymix\Framework\Storage;
use Context;
use FileHandler;
use MenuAdminModel;
use ModuleModel;
use ModuleObject;

class ModuleDefinition
{
	/**
	 * Get the list of installed modules.
	 *
	 * @return array
	 */
	public static function getInstalledModuleList(): array
	{
		$searched_list = FileHandler::readDir(\RX_BASEDIR . 'modules', '/^([a-zA-Z0-9_]+)$/');
		if(!count($searched_list))
		{
			return [];
		}
		sort($searched_list);

		$list = [];
		foreach ($searched_list as $module_name)
		{
			$info = self::getModuleInfoXml($module_name);
			if ($info)
			{
				$info->module = $module_name;
				$info->created_table_count = null;
				$info->table_count = null;
				$info->path = sprintf('./modules/%s/', $module_name);
				$info->admin_index_act = $info->admin_index_act ?? null;
				$info->is_blacklisted = Context::isBlacklistedPlugin($module_name, 'module');

				$list[] = $info;
			}
		}
		return $list;
	}

	/**
	 * Get the list of installed modules, with details about installation and update status.
	 *
	 * @return array
	 */
	public static function getInstalledModuleDetails(): array
	{
		$searched_list = FileHandler::readDir(\RX_BASEDIR . 'modules', '/^([a-zA-Z0-9_]+)$/');
		if(!count($searched_list))
		{
			return [];
		}
		sort($searched_list);

		$list = [];
		foreach ($searched_list as $module_name)
		{
			$path = sprintf('./modules/%s/', $module_name);
			if (!Storage::isDirectory(FileHandler::getRealPath($path)))
			{
				continue;
			}

			// Get information of the module
			$info = self::getModuleInfoXml($module_name);
			if (!$info)
			{
				continue;
			}

			// Check created tables
			$tables = Updater::checkTables($module_name);

			$info->module = $module_name;
			$info->created_table_count = count(array_filter($tables, function($val) {
				return $val->exists;
			}));
			$info->table_count = count($tables);
			$info->path = $path;
			$info->admin_index_act = $info->admin_index_act ?? null;
			$info->is_blacklisted = Context::isBlacklistedPlugin($module_name, 'module');
			$info->need_install = false;
			$info->need_update = false;

			// If the module is blacklisted, stop here.
			if ($info->is_blacklisted)
			{
				$list[] = $info;
				continue;
			}

			// Check if DB is installed
			if($info->table_count > $info->created_table_count)
			{
				$info->need_install = true;
			}

			// Check the update status.
			$info->need_update = Updater::needsUpdate($module_name);

			$list[] = $info;
		}

		return $list;
	}

	/**
	 * Parse info.xml of a module.
	 *
	 * @param string $module
	 * @return ?object
	 */
	public static function getModuleInfoXml(string $module): ?object
	{
		// Check the path and XML file name.
		$xml_file = \RX_BASEDIR . sprintf('./modules/%s/conf/info.xml', $module);
		if (!Storage::isFile($xml_file) || !Storage::isReadable($xml_file))
		{
			return null;
		}

		// Load the XML file and cache the definition.
		$lang_type = Context::getLangType() ?: 'en';
		$xml_file2 = \RX_BASEDIR . sprintf('./modules/%s/conf/module.xml', $module);
		$mtime1 = filemtime($xml_file);
		$mtime2 = file_exists($xml_file2) ? filemtime($xml_file2) : 0;
		$cache_key = sprintf('site_and_module:module_info_xml:%s:%s:%d:%d', $module, $lang_type, $mtime1, $mtime2);
		$info = Cache::get($cache_key);
		if ($info === null)
		{
			$info = ModuleInfoParser::loadXML($xml_file);
			Cache::set($cache_key, $info, 0, true);
		}

		return $info;
	}

	/**
	 * Parse module.xml.
	 *
	 * @param string $module
	 * @return ?object
	 */
	public static function getModuleActionXml(string $module): ?object
	{
		// Check the path and XML file name.
		$xml_file = \RX_BASEDIR . sprintf('./modules/%s/conf/module.xml', $module);
		if (!Storage::isFile($xml_file) || !Storage::isReadable($xml_file))
		{
			return null;
		}

		// Load the XML file and cache the definition.
		$lang_type = Context::getLangType() ?: 'en';
		$mtime = filemtime($xml_file);
		$cache_key = sprintf('site_and_module:module_action_xml:%s:%s:%d', $module, $lang_type, $mtime);
		$info = Cache::get($cache_key);
		if ($info === null)
		{
			$info = ModuleActionParser::loadXML($xml_file);
			Cache::set($cache_key, $info, 0, true);
		}

		return $info;
	}

	/**
	 * Get a list of skins available in the given directory.
	 *
	 * This method does not assume that the directory is for module skins.
	 * It also supports widget skins and other arbitrary paths,
	 * but some features are only available for module skins.
	 *
	 * @param string $path
	 * @return array
	 */
	public static function getSkins(string $path): array
	{
		// Clean up the path and skin name.
		$path = rtrim(strtr($path, '\\', '/'), '/');

		// Get the list of subdirectories that might be skins.
		$skin_list = [];
		$list = FileHandler::readDir($path);
		natcasesort($list);

		// Collect basic information about each skin.
		foreach ($list as $skin_name)
		{
			if (!Storage::isDirectory($path . '/' . $skin_name))
			{
				continue;
			}
			$skin_info = self::getSkinInfo($path . '/' . $skin_name);
			if (!$skin_info)
			{
				$skin_info = new \stdClass;
				$skin_info->title = $skin_name;
			}

			$skin_list[$skin_name] = $skin_info;
		}

		// Detect the module to which these skins belong.
		if (preg_match('#/modules/([a-zA-Z0-9_]+)/((m\.)?skins)$#', $path, $matches))
		{
			$module = $matches[1];
			$dir = $matches[2];
		}
		else
		{
			$module = '';
			$dir = basename($path);
		}

		$useDefaultList = [];

		// Check if the module has a default skin.
		$oMenuAdminModel = MenuAdminModel::getInstance();
		$installedMenuTypes = $oMenuAdminModel->getModuleListInSitemap();
		$moduleName = ($module === 'page') ? 'ARTICLE' : $module;
		if (array_key_exists($moduleName, $installedMenuTypes))
		{
			$defaultSkinName = ModuleConfig::getModuleDefaultSkin($module, $dir == 'skins' ? 'P' : 'M');
			if ($defaultSkinName)
			{
				if ($defaultSkinName === '/USE_RESPONSIVE/')
				{
					$opt = new \stdClass;
					$opt->title = lang('use_responsive_pc_skin');
				}
				else
				{
					$defaultSkinInfo = self::getSkinInfo($path . '/' . $defaultSkinName);
					$opt = new \stdClass;
					$opt->title = lang('use_site_default_skin') .
						(isset($defaultSkinInfo->title) ? ' (' . $defaultSkinInfo->title . ')' : '');
				}

				$useDefaultList['/USE_DEFAULT/'] = $opt;
			}
		}

		// If mobile, add the responsive option.
		if ($module && $dir == 'm.skins')
		{
			$opt = new \stdClass;
			$opt->title = lang('use_responsive_pc_skin');
			$useDefaultList['/USE_RESPONSIVE/'] = $opt;
		}

		return array_merge($useDefaultList, $skin_list);
	}

	/**
	 * Load skin information from a path.
	 *
	 * @param string $path
	 * @return ?object
	 */
	public static function getSkinInfo(string $path): ?object
	{
		// Clean up the path and skin name.
		$path = rtrim($path, '/');
		$skin = basename($path);
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', (string)$skin))
		{
			return null;
		}

		$skin_xml_file = $path . '/skin.xml';
		if (!Storage::isFile($skin_xml_file) || !Storage::isReadable($skin_xml_file))
		{
			return null;
		}

		return SkinInfoParser::loadXML($skin_xml_file, $skin, $path);
	}

	/**
	 * Get module base class
	 *
	 * This method supports namespaced modules as well as XE-compatible modules.
	 *
	 * @param string $module_name
	 * @return ?ModuleObject
	 */
	public static function getDefaultClass(string $module_name, ?object $module_action_info = null): ?ModuleObject
	{
		if (!$module_action_info)
		{
			$module_action_info = self::getModuleActionXml($module_name);
		}

		if (isset($module_action_info->namespaces) && count($module_action_info->namespaces))
		{
			$namespace = array_first($module_action_info->namespaces);
		}
		else
		{
			$namespace = 'Rhymix\\Modules\\' . ucfirst($module_name);
		}

		if (isset($module_action_info->classes['default']))
		{
			$class_name = $namespace . '\\' . $module_action_info->classes['default'];
			return class_exists($class_name) ? $class_name::getInstance() : null;
		}

		$class_name = $namespace . '\\Base';
		if (class_exists($class_name))
		{
			return $class_name::getInstance();
		}

		$class_name = $namespace . '\\Controllers\\Base';
		if (class_exists($class_name))
		{
			return $class_name::getInstance();
		}

		if ($oModule = getModule($module_name, 'class'))
		{
			return $oModule;
		}

		return null;
	}

	/**
	 * Get module install class
	 *
	 * This method supports namespaced modules as well as XE-compatible modules.
	 *
	 * @param string $module_name
	 * @return ?ModuleObject
	 */
	public static function getInstallClass(string $module_name, ?object $module_action_info = null): ?ModuleObject
	{
		if (!$module_action_info)
		{
			$module_action_info = self::getModuleActionXml($module_name);
		}

		if (isset($module_action_info->namespaces) && count($module_action_info->namespaces))
		{
			$namespace = array_first($module_action_info->namespaces);
		}
		else
		{
			$namespace = 'Rhymix\\Modules\\' . ucfirst($module_name);
		}

		if (isset($module_action_info->classes['install']))
		{
			$class_name = $namespace . '\\' . $module_action_info->classes['install'];
			return class_exists($class_name) ? $class_name::getInstance() : null;
		}

		$class_name = $namespace . '\\Install';
		if (class_exists($class_name))
		{
			return $class_name::getInstance();
		}

		$class_name = $namespace . '\\Controllers\\Install';
		if (class_exists($class_name))
		{
			return $class_name::getInstance();
		}

		if ($oModule = getModule($module_name, 'class'))
		{
			return $oModule;
		}

		return null;
	}
}
