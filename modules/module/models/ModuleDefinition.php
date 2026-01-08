<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Parsers\ModuleActionParser;
use Rhymix\Framework\Parsers\ModuleInfoParser;
use Rhymix\Framework\Parsers\SkinInfoParser;
use Context;
use FileHandler;
use ModuleHandler;
use ModuleModel;

class ModuleDefinition
{
	/**
	 * @brief Get xml information of the module
	 */
	public static function getInstalledModuleList()
	{
		// Get a list of downloaded and installed modules
		$searched_list = FileHandler::readDir('./modules');
		$searched_count = count($searched_list);
		if(!$searched_count) return;
		sort($searched_list);

		for($i=0;$i<$searched_count;$i++)
		{
			// Module name
			$module_name = $searched_list[$i];

			$path = ModuleHandler::getModulePath($module_name);
			// Get information of the module
			$info = self::getModuleInfoXml($module_name);
			unset($obj);

			if(!isset($info)) continue;
			$info->module = $module_name;
			$info->created_table_count = null; //$created_table_count;
			$info->table_count = null; //$table_count;
			$info->path = $path;
			$info->admin_index_act = $info->admin_index_act ?? null;
			$list[] = $info;
		}
		return $list;
	}

	/**
	 * @brief Get a type and information of the module
	 */
	public static function getInstalledModuleDetails()
	{
		// Create DB Object
		$oDB = DB::getInstance();
		// Get a list of downloaded and installed modules
		$searched_list = FileHandler::readDir('./modules', '/^([a-zA-Z0-9_-]+)$/');
		sort($searched_list);

		$searched_count = count($searched_list);
		if(!$searched_count) return;

		// Get action forward
		$action_forward = GlobalRoute::getAllGlobalRoutes();

		foreach ($searched_list as $module_name)
		{
			$path = ModuleHandler::getModulePath($module_name);
			if(!is_dir(FileHandler::getRealPath($path))) continue;

			// Get the number of xml files to create a table in schemas
			$table_count = 0;
			$schema_files = FileHandler::readDir($path.'schemas', '/(\.xml)$/', false, true);
			foreach ($schema_files as $filename)
			{
				if (!preg_match('/<table\s[^>]*deleted="true"/i', file_get_contents($filename)))
				{
					$table_count++;
				}
			}

			// Check if the table is created
			$created_table_count = 0;
			foreach ($schema_files as $filename)
			{
				if (!preg_match('/\/([a-zA-Z0-9_]+)\.xml$/', $filename, $matches))
				{
					continue;
				}

				if($oDB->isTableExists($matches[1]))
				{
					$created_table_count++;
				}
			}
			// Get information of the module
			$info = self::getModuleInfoXml($module_name);
			if(!$info) continue;

			$info->module = $module_name;
			$info->created_table_count = $created_table_count;
			$info->table_count = $table_count;
			$info->path = $path;
			$info->admin_index_act = $info->admin_index_act ?? null;
			$info->need_install = false;
			$info->need_update = false;

			if(!Context::isBlacklistedPlugin($module_name, 'module'))
			{
				// Check if DB is installed
				if($table_count > $created_table_count)
				{
					$info->need_install = true;
				}

				// Check if it is upgraded to module.class.php on each module
				$oDummy = self::getInstallClass($module_name);
				if($oDummy && method_exists($oDummy, "checkUpdate"))
				{
					$info->need_update = $oDummy->checkUpdate();
				}
				unset($oDummy);

				// Check if all action-forwardable routes are registered
				$module_action_info = self::getModuleActionXml($module_name);
				$forwardable_routes = array();
				foreach ($module_action_info->action ?? [] as $action_name => $action_info)
				{
					if (count($action_info->route) && $action_info->standalone === 'true')
					{
						$forwardable_routes[$action_name] = array(
							'regexp' => array(),
							'config' => $action_info->route,
						);
					}
				}
				foreach ($module_action_info->route->GET ?? [] as $regexp => $action_name)
				{
					if (isset($forwardable_routes[$action_name]))
					{
						$forwardable_routes[$action_name]['regexp'][] = ['GET', $regexp];
					}
				}
				foreach ($module_action_info->route->POST ?? [] as $regexp => $action_name)
				{
					if (isset($forwardable_routes[$action_name]))
					{
						$forwardable_routes[$action_name]['regexp'][] = ['POST', $regexp];
					}
				}
				foreach ($forwardable_routes as $action_name => $route_info)
				{
					if (!isset($action_forward[$action_name]) ||
						$action_forward[$action_name]->route_regexp !== $route_info['regexp'] ||
						$action_forward[$action_name]->route_config !== $route_info['config'])
					{
						$info->need_update = true;
					}
				}

				// Clean up any action-forward routes that are no longer needed.
				foreach ($forwardable_routes as $action_name => $route_info)
				{
					unset($action_forward[$action_name]);
				}
				foreach ($action_forward as $action_name => $forward_info)
				{
					if ($forward_info->module === $module_name && $forward_info->route_regexp !== null)
					{
						$info->need_update = true;
					}
				}

				// Check if all event handlers are registered.
				$registered_event_handlers = [];
				foreach ($module_action_info->event_handlers ?? [] as $ev)
				{
					$key = implode(':', [$ev->event_name, $module_name, $ev->class_name, $ev->method, $ev->position]);
					$registered_event_handlers[$key] = true;
					if(!ModuleModel::getTrigger($ev->event_name, $module_name, $ev->class_name, $ev->method, $ev->position))
					{
						$info->need_update = true;
					}
				}
				if (count($registered_event_handlers))
				{
					foreach ($GLOBALS['__triggers__'] as $trigger_name => $val1)
					{
						foreach ($val1 as $called_position => $val2)
						{
							foreach ($val2 as $item)
							{
								if ($item->module === $module_name)
								{
									$key = implode(':', [$trigger_name, $item->module, $item->type, $item->called_method, $called_position]);
									if (!isset($registered_event_handlers[$key]))
									{
										$info->need_update = true;
									}
								}
							}
						}
					}
				}

				// Check if all namespaces are registered.
				$namespaces = config('namespaces') ?? [];
				foreach ($module_action_info->namespaces ?? [] as $name)
				{
					if(!isset($namespaces['mapping'][$name]))
					{
						$info->need_update = true;
					}
				}
				foreach ($namespaces['mapping'] ?? [] as $name => $path)
				{
					$attached_module = preg_replace('!^modules/!', '', $path);
					if ($attached_module === $module_name && !in_array($name, $module_action_info->namespaces ?? []))
					{
						$info->need_update = true;
					}
				}

				// Check if all prefixes are registered.
				foreach ($module_action_info->prefixes ?? [] as $name)
				{
					if(!ModuleModel::getModuleSrlByMid($name))
					{
						$info->need_update = true;
					}
				}
			}

			$list[] = $info;
		}

		return $list;
	}

	/**
	 * @brief Combine module_srls with domain of sites
	 * Because XE DBHandler doesn't support left outer join,
	 * it should be as same as $Output->data[]->module_srl.
	 */
	public static function syncModuleToSite(&$data)
	{
		if(!$data) return;

		if(is_array($data))
		{
			foreach($data as $key => $val)
			{
				$module_srls[] = $val->module_srl;
			}
			if(!count($module_srls)) return;
		}
		else
		{
			$module_srls[] = $data->module_srl;
		}

		$args = new \stdClass;
		$args->module_srls = implode(',',$module_srls);
		$output = executeQueryArray('module.getModuleSites', $args);
		if(!$output->data) return array();
		foreach($output->data as $key => $val)
		{
			$modules[$val->module_srl] = $val;
		}

		if(is_array($data))
		{
			foreach($data as $key => $val)
			{
				if (isset($modules[$val->module_srl]))
				{
					$data[$key]->domain = $modules[$val->module_srl]->domain;
				}
				elseif (!isset($data[$key]->domain))
				{
					$data[$key]->domain = null;
				}
			}
		}
		else
		{
			$data->domain = $modules[$data->module_srl]->domain ?? null;
		}
	}

	/**
	 * @brief Get information from conf/info.xml
	 */
	public static function getModuleInfoXml($module)
	{
		// Check the path and XML file name.
		$module_path = ModuleHandler::getModulePath($module);
		if (!$module_path) return;
		$xml_file = $module_path . 'conf/info.xml';
		if (!file_exists($xml_file)) return;

		// Load the XML file and cache the definition.
		$lang_type = Context::getLangType() ?: 'en';
		$mtime1 = filemtime($xml_file);
		$mtime2 = file_exists($module_path . 'conf/module.xml') ? filemtime($module_path . 'conf/module.xml') : 0;
		$cache_key = sprintf('site_and_module:module_info_xml:%s:%s:%d:%d', $module, $lang_type, $mtime1, $mtime2);
		$info = Cache::get($cache_key);
		if($info === null)
		{
			$info = ModuleInfoParser::loadXML($xml_file);
			Cache::set($cache_key, $info, 0, true);
		}

		return $info;
	}

	/**
	 * @brief Return permisson and action data by conf/module.xml
	 */
	public static function getModuleActionXml($module)
	{
		// Check the path and XML file name.
		$module_path = ModuleHandler::getModulePath($module);
		if (!$module_path) return;
		$xml_file = $module_path . 'conf/module.xml';
		if (!file_exists($xml_file)) return;

		// Load the XML file and cache the definition.
		$lang_type = Context::getLangType() ?: 'en';
		$mtime = filemtime($xml_file);
		$cache_key = sprintf('site_and_module:module_action_xml:%s:%s:%d', $module, $lang_type, $mtime);
		$info = Cache::get($cache_key);
		if($info === null)
		{
			$info = ModuleActionParser::loadXML($xml_file);
			Cache::set($cache_key, $info, 0, true);
		}

		return $info;
	}

	/**
	 * @brief Get a list of skins for the module
	 * Return file analysis of skin and skin.xml
	 */
	public static function getSkins($path, $dir = 'skins')
	{
		if(substr($path, -1) == '/')
		{
			$path = substr($path, 0, -1);
		}

		$skin_list = array();
		$skin_path = sprintf("%s/%s/", $path, $dir);
		$list = FileHandler::readDir($skin_path);
		//if(!count($list)) return;

		natcasesort($list);

		foreach($list as $skin_name)
		{
			if(!is_dir($skin_path . $skin_name))
			{
				continue;
			}
			unset($skin_info);
			$skin_info = self::loadSkinInfo($path, $skin_name, $dir);
			if(!$skin_info)
			{
				$skin_info = new \stdClass;
				$skin_info->title = $skin_name;
			}

			$skin_list[$skin_name] = $skin_info;
		}

		$tmpPath = strtr($path, array('/' => ' '));
		$tmpPath = trim($tmpPath);
		$module = array_last(explode(' ', $tmpPath));

		$siteInfo = Context::get('site_module_info');
		$oMenuAdminModel = getAdminModel('menu');
		$installedMenuTypes = $oMenuAdminModel->getModuleListInSitemap();
		$moduleName = $module;
		if($moduleName === 'page')
		{
			$moduleName = 'ARTICLE';
		}

		$useDefaultList = array();
		if(array_key_exists($moduleName, $installedMenuTypes))
		{
			$defaultSkinName = ModuleModel::getModuleDefaultSkin($module, $dir == 'skins' ? 'P' : 'M');
			if ($defaultSkinName)
			{
				if ($defaultSkinName === '/USE_RESPONSIVE/')
				{
					$defaultSkinInfo = (object)array('title' => lang('use_responsive_pc_skin'));
				}
				else
				{
					$defaultSkinInfo = self::loadSkinInfo($path, $defaultSkinName, $dir);
				}

				$useDefault = new \stdClass();
				$useDefault->title = lang('use_site_default_skin') . ' (' . ($defaultSkinInfo->title ?? null) . ')';

				$useDefaultList['/USE_DEFAULT/'] = $useDefault;
			}
		}
		if($dir == 'm.skins')
		{
			$useDefaultList['/USE_RESPONSIVE/'] = (object)array('title' => lang('use_responsive_pc_skin'));
		}

		$skin_list = array_merge($useDefaultList, $skin_list);

		return $skin_list;
	}

	/**
	 * @brief Get skin information on a specific location
	 */
	public static function loadSkinInfo($path, $skin, $dir = 'skins')
	{
		// Read xml file having skin information
		if (!str_ends_with($path, '/'))
		{
			$path .= '/';
		}
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $skin ?? ''))
		{
			return;
		}

		$skin_path = sprintf("%s%s/%s/", $path, $dir, $skin);
		$skin_xml_file = $skin_path . 'skin.xml';
		if (!file_exists($skin_xml_file))
		{
			return;
		}

		$skin_info = SkinInfoParser::loadXML($skin_xml_file, $skin, $skin_path);
		return $skin_info;
	}

	/**
	 * Get module base class
	 *
	 * This method supports namespaced modules as well as XE-compatible modules.
	 *
	 * @param string $module_name
	 * @return ModuleObject|null
	 */
	public static function getDefaultClass(string $module_name, ?object $module_action_info = null)
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
	}

	/**
	 * Get module install class
	 *
	 * This method supports namespaced modules as well as XE-compatible modules.
	 *
	 * @param string $module_name
	 * @return ModuleObject|null
	 */
	public static function getInstallClass(string $module_name, ?object $module_action_info = null)
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
	}
}
