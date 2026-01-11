<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Exception;
use Rhymix\Framework\Helpers\DBResultHelper;
use Rhymix\Framework\Parsers\DBQuery\NullValue;
use BaseObject;
use Context;
use MemberModel;
use MenuAdminController;
use MenuAdminModel;
use ModuleHandler;

#[\AllowDynamicProperties]
class ModuleInfo
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		// Apply default skin.
		if (isset($this->is_skin_fix) && $this->is_skin_fix == 'N')
		{
			$this->skin = '/USE_DEFAULT/';
		}
		if (isset($this->is_mskin_fix) && $this->is_mskin_fix == 'N' && (!isset($this->mskin) || $this->mskin !== '/USE_RESPONSIVE/'))
		{
			$this->mskin = '/USE_DEFAULT/';
		}
	}

	/**
	 * Load skin variables and add them to the current object.
	 */
	public function addSkinVars(): void
	{
		if (empty($this->module_srl))
		{
			return;
		}

		$mode = (\Mobile::isFromMobilePhone() && $this->mskin !== '/USE_RESPONSIVE/') ? 'M' : 'P';
		$skin_vars = self::getSkinVars($this->module_srl, $mode);
		foreach ($skin_vars as $name => $val)
		{
			if (!isset($this->{$name}))
			{
				$this->{$name} = $val->value;
			}
		}
	}

	/**
	 * Get module instance information by module_srl.
	 *
	 * @param int $module_srl
	 * @return ?ModuleInstance
	 */
	public static function getModuleInfo(int $module_srl): ?ModuleInstance
	{
		if (!$module_srl)
		{
			return null;
		}

		$module_info = Cache::get("site_and_module:module_info:$module_srl");
		if (!($module_info instanceof ModuleInstance))
		{
			$output = executeQueryArray('module.getMidInfo', ['module_srl' => $module_srl], [], ModuleInstance::class);
			if (!$output->toBool() || !count($output->data))
			{
				return null;
			}
			$module_info = array_first($output->data);
			ModuleCache::$module_srl2prefix[$module_srl] = $module_info->mid;
			Cache::set("site_and_module:module_info:$module_srl", $module_info, 0, true);
		}

		self::addExtraVars([$module_info]);
		return $module_info;
	}

	/**
	 * Get module information by URL prefix (mid).
	 *
	 * @param string $prefix
	 * @return ?ModuleInstance
	 */
	public static function getModuleInfoByPrefix(string $prefix): ?ModuleInstance
	{
		if ($prefix === '' || !preg_match('/^[a-z]([a-z0-9_]+)$/i', $prefix))
		{
			return null;
		}

		$module_srl = ModuleCache::$prefix2module_srl[$prefix] ?? Cache::get('site_and_module:module_srl:' . $prefix);
		if ($module_srl)
		{
			return self::getModuleInfo($module_srl);
		}

		$output = executeQueryArray('module.getMidInfo', ['mid' => $prefix], [], ModuleInstance::class);
		if (!$output->toBool() || !count($output->data))
		{
			return null;
		}
		$module_info = array_first($output->data);
		ModuleCache::$prefix2module_srl[$prefix] = $module_info->module_srl;
		Cache::set('site_and_module:module_info:' . $module_info->module_srl, $module_info, 0, true);
		Cache::set('site_and_module:module_srl:' . $prefix, $module_info->module_srl, 0, true);

		self::addExtraVars([$module_info]);
		return $module_info;
	}

	/**
	 * Get module information with document_srl.
	 *
	 * @param int $document_srl
	 * @return ?ModuleInstance
	 */
	public static function getModuleInfoByDocumentSrl(int $document_srl): ?ModuleInstance
	{
		$module_info = Cache::get("site_and_module:document_srl:$document_srl");
		if (!($module_info instanceof ModuleInstance))
		{
			$output = executeQueryArray('module.getModuleInfoByDocument', ['document_srl' => $document_srl], [], ModuleInstance::class);
			if (!$output->toBool() || !count($output->data))
			{
				return null;
			}
			$module_info = array_first($output->data);
			Cache::set("site_and_module:document_srl:$document_srl", $module_info);
		}

		self::addExtraVars([$module_info]);
		return $module_info;
	}

	/**
	 * Get a list of module instances specified by their module_srl.
	 *
	 * @param array $module_srls
	 * @param array $columnList
	 * @return array<ModuleInstance>
	 */
	public static function getModuleInfos(array $module_srls, array $columnList = []): array
	{
		if (!count($module_srls))
		{
			return [];
		}

		$output = executeQueryArray('module.getModulesInfo', ['module_srls' => $module_srls], $columnList, ModuleInstance::class);
		$result = $output->data ?? [];
		if (!$columnList)
		{
			$result = self::addExtraVars($result);
		}
		return $result;
	}

	/**
	 * Get a list of module instances.
	 *
	 * @param ?object $args
	 * @param array $columnList
	 * @return array<ModuleInstance>
	 */
	public static function getModuleInstanceList(?object $args = null, array $columnList = []): array
	{
		// Is this a custom search?
		$is_custom_search = isset($args) && count(get_object_vars($args)) > 0;
		if (!$is_custom_search)
		{
			$columnList = [];
		}

		// Use cache only if this is NOT a custom search.
		$list = $is_custom_search ? null : Cache::get('site_and_module:module:module_info_list');
		if ($list === null)
		{
			$output = executeQueryArray('module.getMidList', $args, $columnList, ModuleInstance::class);
			$list = $output->data ?? [];

			// Set cache only if there are no arguments.
			if (!$is_custom_search)
			{
				Cache::set('site_and_module:module:module_info_list', $list, 0, true);
			}
		}

		$assoc_list = [];
		foreach($list as $val)
		{
			$assoc_list[$val->mid] = $val;
		}
		return $assoc_list;
	}

	/**
	 * Get the number of instances belonging to a module.
	 * This method will return the count of all instances if no module or domain is specified.
	 *
	 * @param ?string $module
	 * @param ?int $domain_srl
	 * @return int
	 */
	public static function getModuleInstanceCount(?string $module = null, ?int $domain_srl = null): int
	{
		$args = new \stdClass;
		$args->module = $module;
		$args->domain_srl = $domain_srl;
		$output = executeQuery('module.getModuleCount', $args);
		return intval($output->data->count ?? 0);
	}

	/**
	 * Get extra variables for a list of modules.
	 * These variables are stored in the module_extra_vars table.
	 *
	 * @param array $module_srls
	 * @return array
	 */
	public static function getExtraVars($module_srls): array
	{
		// Compile the list of module_srl.
		$extra_vars = [];
		$load_from_db_srls = [];
		foreach ($module_srls as $module_srl)
		{
			$module_srl = (int)$module_srl;
			$vars = Cache::get('site_and_module:module_extra_vars:' . $module_srl);
			if($vars !== null)
			{
				$extra_vars[$module_srl] = $vars;
			}
			else
			{
				$load_from_db_srls[] = $module_srl;
			}
		}

		if (count($load_from_db_srls) > 0)
		{
			$output = executeQueryArray('module.getModuleExtraVars', ['module_srl' => $load_from_db_srls]);
			if (!$output->toBool())
			{
				return $extra_vars;
			}

			foreach ($output->data ?? [] as $val)
			{
				if (in_array($val->name, ['mid', 'module']) || $val->value === 'Array')
				{
					continue;
				}
				if (!isset($extra_vars[$val->module_srl]))
				{
					$extra_vars[$val->module_srl] = new \stdClass;
				}
				$extra_vars[$val->module_srl]->{$val->name} = $val->value;
			}

			foreach ($load_from_db_srls as $module_srl)
			{
				if (isset($extra_vars[$module_srl]))
				{
					Cache::set('site_and_module:module_extra_vars:' . $module_srl, $extra_vars[$module_srl], 0, true);
				}
				else
				{
					$extra_vars[$module_srl] = new \stdClass;
				}
			}
		}

		return $extra_vars;
	}

	/**
	 * Get skin variables for a module.
	 * These variables are stored in the module_skin_vars table.
	 *
	 * @param int $module_srl
	 * @param string $mode 'P' for PC skin, 'M' for mobile skin
	 * @return object
	 */
	public static function getSkinVars(int $module_srl, string $mode = 'P'): object
	{
		if ($mode === 'P')
		{
			$cache_key = "site_and_module:module_skin_vars:$module_srl";
			$query_id = 'module.getModuleSkinVars';
		}
		else
		{
			$cache_key = "site_and_module:module_mobile_skin_vars:$module_srl";
			$query_id = 'module.getModuleMobileSkinVars';
		}

		$skin_vars = Cache::get($cache_key);
		if (!is_object($skin_vars))
		{
			$output = executeQueryArray($query_id, ['module_srl' => $module_srl]);
			if (!$output->toBool())
			{
				return new \stdClass;
			}

			$skin_vars = new \stdClass;
			foreach ($output->data as $vars)
			{
				$skin_vars->{$vars->name} = $vars;
			}

			Cache::set($cache_key, $skin_vars, 0, true);
		}

		return $skin_vars;
	}

	/**
	 * Get module grants.
	 *
	 * @param int $module_srl
	 * @return DBResultHelper
	 */
	public static function getGrants(int $module_srl): DBResultHelper
	{
		$output = Cache::get("site_and_module:module_grants:$module_srl");
		if (!($output instanceof DBResultHelper))
		{
			$output = executeQueryArray('module.getModuleGrants', ['module_srl' => $module_srl]);
			if ($output->toBool())
			{
				Cache::set("site_and_module:module_grants:$module_srl", $output, 0, true);
			}
		}
		return $output;
	}

	/**
	 * Get the list of a module's managers.
	 *
	 * @param int $module_srl
	 * @return array
	 */
	public static function getManagers(int $module_srl): array
	{
		$output = executeQueryArray('module.getAdminID', ['module_srl' => $module_srl]);
		if (!$output->toBool() || !$output->data)
		{
			return [];
		}
		foreach ($output->data as $row)
		{
			$row->scopes = !empty($row->scopes) ? json_decode($row->scopes) : null;
		}
		return $output->data;
	}

	/**
	 * Check if a member is a module manager.
	 *
	 * @param object $member_info
	 * @param ?int $module_srl
	 * @return array|bool
	 */
	public static function isManager(object $member_info, ?int $module_srl = null)
	{
		if (empty($member_info->member_srl))
		{
			return false;
		}
		if (isset($member_info->is_admin) && $member_info->is_admin == 'Y')
		{
			return true;
		}
		if ($module_srl === null)
		{
			$site_module_info = Context::get('site_module_info');
			if (!$site_module_info || !$site_module_info->module_srl)
			{
				return false;
			}
			$module_srl = $site_module_info->module_srl;
		}

		$managers = Cache::get("site_and_module:module_managers:$module_srl");
		if ($managers === null)
		{
			$output = executeQueryArray('module.getModuleAdmin', ['module_srl' => $module_srl]);
			$managers = array();
			foreach ($output->data as $module_admin)
			{
				$managers[$module_admin->member_srl] = $module_admin->scopes ? json_decode($module_admin->scopes) : true;
			}
			if ($output->toBool())
			{
				Cache::set("site_and_module:module_managers:$module_srl", $managers, 0, true);
			}
		}

		if (isset($managers[$member_info->member_srl]))
		{
			return $managers[$member_info->member_srl];
		}
		else
		{
			return false;
		}
	}

	/**
	 * Insert a module.
	 *
	 * @param object $args
	 * @return BaseObject
	 */
	public static function insertModule(object $args): BaseObject
	{
		// This flag will be removed in the future.
		$isMenuCreate = $args->isMenuCreate ?? true;

		// Split extra vars from $args.
		list($args, $extra_vars) = self::splitExtraVars($args);

		// Check the prefix.
		if (!Prefix::isValidPrefix($args->mid, $args->module ?? null))
		{
			return new BaseObject(-1, 'msg_limit_mid');
		}
		if (Prefix::exists($args->mid))
		{
			return new BaseObject(-1, 'msg_module_name_exists');
		}

		// Fill default values.
		if (empty($args->module_srl))
		{
			$args->module_srl = getNextSequence();
		}
		$args->browser_title = escape($args->browser_title ?? '', false, true);
		$args->description = isset($args->description) ? escape($args->description, false, true) : null;

		// is_skin_fix is 'Y' only if a skin is explicitly selected.
		// Deferring to the default skin does not count as having selected a fixed skin.
		if (!isset($args->skin) || $args->skin == '/USE_DEFAULT/')
		{
			$args->is_skin_fix = 'N';
		}
		elseif (isset($args->is_skin_fix))
		{
			$args->is_skin_fix = ($args->is_skin_fix != 'Y') ? 'N' : 'Y';
		}
		else
		{
			$args->is_skin_fix = 'Y';
		}

		// is_mskin_fix is 'Y' only if a mobile skin is explicitly selected.
		// Deferring to the default skin or a responsive PC skin does not count.
		if (!isset($args->mskin) || $args->mskin == '/USE_DEFAULT/' || $args->mskin == '/USE_RESPONSIVE/')
		{
			$args->is_mskin_fix = 'N';
		}
		elseif (isset($args->is_mskin_fix))
		{
			$args->is_mskin_fix = ($args->is_mskin_fix != 'Y') ? 'N' : 'Y';
		}
		else
		{
			$args->is_mskin_fix = 'Y';
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// Create a menu for the module. This will be removed in the future.
		$menuArgs = new \stdClass;
		if ($isMenuCreate)
		{
			// Check if a menu already exists.
			if (isset($args->menu))
			{
				$menuArgs->menu_srl = $args->menu_srl;
				$menuOutput = executeQuery('menu.getMenu', $menuArgs);
				$menuExists = $menuOutput->toBool() && $menuOutput->data;
			}
			else
			{
				$menuExists = false;
			}

			// Create a new menu if none exists.
			if (!$menuExists)
			{
				$oMenuAdminController = MenuAdminController::getInstance();
				$menuSrl = $oMenuAdminController->getUnlinkedMenu();
				if ($menuSrl instanceof BaseObject && !$menuSrl->toBool())
				{
					return $menuSrl;
				}

				$menuArgs->menu_srl = $menuSrl;
				$menuArgs->menu_item_srl = getNextSequence();
				$menuArgs->parent_srl = 0;
				$menuArgs->open_window = 'N';
				$menuArgs->url = $args->mid;
				$menuArgs->expand = 'N';
				$menuArgs->is_shortcut = 'N';
				$menuArgs->name = $args->browser_title;
				$menuArgs->listorder = $args->menu_item_srl * -1;

				$menuItemOutput = executeQuery('menu.insertMenuItem', $menuArgs);
				if (!$menuItemOutput->toBool())
				{
					$oDB->rollback();
					return $menuItemOutput;
				}

				$oMenuAdminController->makeXmlFile($menuSrl);
			}
		}

		// Insert a module
		$args->menu_srl = $menuArgs->menu_srl ?? null;
		$output = executeQuery('module.insertModule', $args);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Insert module extra vars
		self::insertExtraVars($args->module_srl, $extra_vars);

		$oDB->commit();

		ModuleCache::clearAll();
		$output->add('module_srl', $args->module_srl);
		return $output;
	}

	/**
	 * Update a module.
	 *
	 * @param object $args
	 * @return BaseObject
	 */
	public static function updateModule(object $args): BaseObject
	{
		// This flag will be removed in the future.
		$isMenuCreate = $args->isMenuCreate ?? true;

		// Split extra vars from $args.
		list($args, $extra_vars) = self::splitExtraVars($args);

		// Check the prefix.
		if (!Prefix::isValidPrefix($args->mid, $args->module ?? null))
		{
			return new BaseObject(-1, 'msg_limit_mid');
		}

		// Check whether the prefix already exists.
		$module_info = self::getModuleInfo($args->module_srl);
		if (Prefix::exists($args->mid) && $args->mid !== $module_info->mid)
		{
			return new BaseObject(-1, 'msg_module_name_exists');
		}

		$args->browser_title = escape($args->browser_title ?? $module_info->browser_title, false, true);
		$args->description = isset($args->description) ? escape($args->description, false) : null;

		// is_skin_fix
		if (!isset($args->skin) || $args->skin == '/USE_DEFAULT/')
		{
			$args->is_skin_fix = 'N';
		}
		elseif (isset($args->is_skin_fix))
		{
			$args->is_skin_fix = ($args->is_skin_fix != 'Y') ? 'N' : 'Y';
		}
		else
		{
			$args->is_skin_fix = 'Y';
		}

		// is_mskin_fix
		if (!isset($args->mskin) || $args->mskin == '/USE_DEFAULT/' || $args->mskin == '/USE_RESPONSIVE/')
		{
			$args->is_mskin_fix = 'N';
		}
		elseif (isset($args->is_mskin_fix))
		{
			$args->is_mskin_fix = ($args->is_mskin_fix != 'Y') ? 'N' : 'Y';
		}
		else
		{
			$args->is_mskin_fix = 'Y';
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// Update the menu for the module if the prefix has changed.
		if ($isMenuCreate && $module_info->mid != $args->mid)
		{
			$menuArgs = new \stdClass;
			$menuArgs->url = $module_info->mid;
			$menuOutput = executeQueryArray('menu.getMenuItemByUrl', $menuArgs);
			if ($menuOutput->data && count($menuOutput->data))
			{
				$oMenuAdminController = MenuAdminController::getInstance();
				foreach ($menuOutput->data as $itemInfo)
				{
					$itemInfo->url = $args->mid;

					$updateMenuItemOutput = $oMenuAdminController->updateMenuItem($itemInfo);
					if (!$updateMenuItemOutput->toBool())
					{
						$oDB->rollback();
						return $updateMenuItemOutput;
					}
				}
			}
		}

		// Update the module.
		$output = executeQuery('module.updateModule', $args);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Update module extra vars.
		self::insertExtraVars($args->module_srl, $extra_vars);

		$oDB->commit();

		// if mid changed, change mid of success_return_url to new mid
		if ($module_info->mid != $args->mid && ($success_return_url = Context::get('success_return_url')))
		{
			$success_return_url = preg_replace('/(?<=&|\?)mid=' . preg_quote($module_info->mid, '/') . '\b/', 'mid=' . urlencode($args->mid), $success_return_url);
			Context::set('success_return_url', $success_return_url);
		}

		ModuleCache::clearAll();
		$output->add('module_srl', $args->module_srl);
		return $output;
	}

	/**
	 * Delete a module with the associated menu.
	 *
	 * @param int $module_srl
	 * @param bool $delete_menu
	 * @return BaseObject
	 */
	public static function deleteModuleWithMenu(int $module_srl, bool $delete_menu = true): BaseObject
	{
		if ($module_srl <= 0)
		{
			return new BaseObject(-1,'msg_invalid_request');
		}

		$module_info = self::getModuleInfo($module_srl);

		$args = new \stdClass();
		$args->url = $module_info->mid;
		$args->is_shortcut = 'N';

		$oMenuAdminModel = MenuAdminModel::getInstance();
		$menuOutput = $oMenuAdminModel->getMenuList($args);
		if (is_array($menuOutput->data))
		{
			foreach ($menuOutput->data as $value)
			{
				$args->menu_srl = $value->menu_srl;
				break;
			}
		}

		// menu delete
		$output = executeQuery('menu.getMenuItemByUrl', $args);
		if ($output->data && $delete_menu)
		{
			$args = new \stdClass;
			$args->menu_srl = $output->data->menu_srl ?: 0;
			$args->menu_item_srl = $output->data->menu_item_srl ?: 0;
			$args->is_force = 'N';

			$oMenuAdminController = MenuAdminController::getInstance();
			$output = $oMenuAdminController->deleteItem($args, true);

			if ($output->toBool())
			{
				return new BaseObject(0, 'success_deleted');
			}
			else
			{
				return new BaseObject($output->error, $output->message);
			}
		}
		// only delete module
		else
		{
			return self::deleteModule($module_srl);
		}
	}

	/**
	 * Delete a module and all related information, except the menu.
	 *
	 * @param int $module_srl
	 * @return BaseObject
	 */
	public static function deleteModule(int $module_srl): BaseObject
	{
		if ($module_srl <= 0)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		// The index module of the default domain cannot be deleted.
		$default_domain = Domain::getDefaultDomain();
		if ($default_domain && $module_srl == $default_domain->index_module_srl)
		{
			return new BaseObject(-1, 'msg_cannot_delete_startmodule');
		}

		// Call a trigger (before)
		$trigger_obj = new \stdClass();
		$trigger_obj->module_srl = $module_srl;
		$output = ModuleHandler::triggerCall('module.deleteModule', 'before', $trigger_obj);
		if (!$output->toBool())
		{
			return $output;
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// Delete module from the DB
		$output = executeQuery('module.deleteModule', ['module_srl' => $module_srl]);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Delete module extra vars
		$output = self::deleteExtraVars($module_srl);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Delete skin settings
		$output = self::deleteSkinVars($module_srl, 'P');
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$output = self::deleteSkinVars($module_srl, 'M');
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Delete permissions and module managers
		$output = self::deleteGrants($module_srl);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$output = self::deleteManager($module_srl);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Call a trigger (after)
		ModuleHandler::triggerCall('module.deleteModule', 'after', $trigger_obj);

		// Commit
		$oDB->commit();

		// Clear cache
		ModuleCache::clearAll();
		return $output;
	}

	/**
	 * Insert a module's extra configuration variables.
	 *
	 * @param int $module_srl
	 * @param object $extra_vars
	 * @return BaseObject
	 */
	public static function insertExtraVars(int $module_srl, object $extra_vars): BaseObject
	{
		$output = null;
		self::deleteExtraVars($module_srl);
		foreach (get_object_vars($extra_vars) as $key => $val)
		{
			$key = trim($key);
			if ($key === '' || isset(self::DELETE_VARS[$key]) || is_object($val) || is_array($val) || is_resource($val))
			{
				continue;
			}

			$val = trim($val);
			if ($val === '')
			{
				continue;
			}

			$args = new \stdClass;
			$args->module_srl = $module_srl;
			$args->name = $key;
			$args->value = $val;
			$output = executeQuery('module.insertModuleExtraVars', $args);
		}

		Cache::delete("site_and_module:module_extra_vars:$module_srl");
		return $output ?? new BaseObject;
	}

	/**
	 * Delete a module's extra configuration variables.
	 *
	 * @param int $module_srl
	 * @return DBResultHelper
	 */
	public static function deleteExtraVars(int $module_srl): DBResultHelper
	{
		$output = executeQuery('module.deleteModuleExtraVars', ['module_srl' => $module_srl]);
		Cache::delete("site_and_module:module_extra_vars:$module_srl");
		return $output;
	}

	/**
	 * Insert a module's skin configuration.
	 *
	 * @param int $module_srl
	 * @param object $skin_vars
	 * @param string $mode 'P' for PC skin, 'M' for mobile skin
	 * @return BaseObject
	 */
	public static function insertSkinVars(int $module_srl, object $skin_vars, string $mode = 'P'): BaseObject
	{
		$mode = $mode === 'P' ? 'P' : 'M';
		$output = null;

		$oDB = DB::getInstance();
		$oDB->begin();

		$output = self::deleteSkinVars($module_srl, $mode);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		foreach (get_object_vars($skin_vars) as $key => $val)
		{
			$key = trim($key);
			if ($key === '' || isset(self::DELETE_VARS[$key]) || is_object($val) || is_resource($val))
			{
				continue;
			}
			if (is_array($val))
			{
				$val = serialize($val);
			}

			$val = trim($val);
			if ($val === '')
			{
				continue;
			}

			$args = new \stdClass;
			$args->module_srl = $module_srl;
			$args->name = trim($key);
			$args->value = trim($val);

			if ($mode === 'P')
			{
				$output = executeQuery('module.insertModuleSkinVars', $args);
			}
			else
			{
				$output = executeQuery('module.insertModuleMobileSkinVars', $args);
			}

			if (!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		$oDB->commit();
		return $output ?? new BaseObject;
	}

	/**
	 * Delete a module's skin configuration.
	 *
	 * @param int $module_srl
	 * @param string $mode 'P' for PC skin, 'M' for mobile skin
	 * @return DBResultHelper
	 */
	public static function deleteSkinVars(int $module_srl, string $mode = 'P'): DBResultHelper
	{
		$mode = $mode === 'P' ? 'P' : 'M';

		if ($mode === 'P')
		{
			$object_key = 'site_and_module:module_skin_vars:' . $module_srl;
			$query_id = 'module.deleteModuleSkinVars';
		}
		else
		{
			$object_key = 'site_and_module:module_mobile_skin_vars:' . $module_srl;
			$query_id = 'module.deleteModuleMobileSkinVars';
		}

		// Clear cache and execute query
		Cache::delete($object_key);
		return executeQuery($query_id, ['module_srl' => $module_srl]);
	}

	/**
	 * Insert a module's permission information.
	 *
	 * @param int $module_srl
	 * @param object $obj
	 * @return BaseObject
	 */
	public static function insertGrants(int $module_srl, object $obj): BaseObject
	{
		$output = null;
		self::deleteModuleGrants($module_srl);
		foreach ($obj as $name => $val)
		{
			if (!$val)
			{
				continue;
			}
			foreach ($val as $group_srl)
			{
				$args = new \stdClass();
				$args->module_srl = $module_srl;
				$args->name = $name;
				$args->group_srl = trim($group_srl);
				if (!$args->name || !$args->group_srl)
				{
					continue;
				}
				$output = executeQuery('module.insertModuleGrant', $args);
			}
		}

		Cache::delete("site_and_module:module_grants:$module_srl");
		return $output ?? new BaseObject;
	}

	/**
	 * Delete a module's permission information.
	 *
	 * @param int $module_srl
	 * @return DBResultHelper
	 */
	public static function deleteGrants(int $module_srl): DBResultHelper
	{
		$output = executeQuery('module.deleteModuleGrants', ['module_srl' => $module_srl]);
		Cache::delete("site_and_module:module_grants:$module_srl");
		return $output;
	}

	/**
	 * Add a member as a module manager.
	 *
	 * @param int $module_srl
	 * @param string $user_id
	 * @param ?array $scopes
	 * @return BaseObject
	 */
	public static function insertManager(int $module_srl, string $user_id, ?array $scopes = null): BaseObject
	{
		if (strpos($user_id, '@') !== false)
		{
			$member_info = MemberModel::getMemberInfoByEmailAddress($user_id);
		}
		else
		{
			$member_info = MemberModel::getMemberInfoByUserID($user_id);
		}

		if (!$member_info || !$member_info->member_srl)
		{
			return new BaseObject(-1, 'msg_not_founded');
		}

		$args = new \stdClass();
		$args->module_srl = intval($module_srl);
		$args->member_srl = $member_info->member_srl;
		if (is_array($scopes))
		{
			$args->scopes = json_encode(array_values($scopes));
		}
		else
		{
			$args->scopes = new NullValue;
		}

		$output = executeQuery('module.insertAdminId', $args);
		Cache::delete("site_and_module:module_admins:$module_srl");
		return $output;
	}

	/**
	 * Remove a module manager.
	 *
	 * If the user ID or email address is given, only that user will be removed.
	 * If not, all managers for the module will be removed.
	 *
	 * @param int $module_srl
	 * @param ?string $user_id
	 * @return BaseObject
	 */
	public static function deleteManager(int $module_srl, ?string $user_id = null): BaseObject
	{
		$args = new \stdClass();
		$args->module_srl = intval($module_srl);

		if (!empty($user_id))
		{
			if (strpos($user_id, '@') !== false)
			{
				$member_info = MemberModel::getMemberInfoByEmailAddress($user_id);
			}
			else
			{
				$member_info = MemberModel::getMemberInfoByUserID($user_id);
			}

			if ($member_info && $member_info->member_srl)
			{
				$args->member_srl = $member_info->member_srl;
			}
		}

		$output = executeQuery('module.deleteAdminId', $args);
		Cache::delete("site_and_module:module_admins:$module_srl");
		return $output;
	}

	/**
	 * Load extra variables from the DB and add them to module information.
	 *
	 * @param array $module_info
	 * @return array
	 */
	public static function addExtraVars(array $module_infos)
	{
		// Compile the list of module_srl.
		$module_srls = [];
		foreach ($module_infos as $val)
		{
			if ($val->module_srl)
			{
				$module_srls[] = $val->module_srl;
			}
		}
		if (!count($module_srls))
		{
			return $module_infos;
		}

		// Get extra vars for all modules.
		$extra_vars = self::getExtraVars($module_srls);
		if (!count($extra_vars))
		{
			return $module_infos;
		}

		// Add extra_vars to each object.
		foreach ($module_infos as $val)
		{
			if (!isset($val->module_srl) || !$val->module_srl)
			{
				continue;
			}
			if (!isset($extra_vars[$val->module_srl]))
			{
				continue;
			}
			foreach ($extra_vars[$val->module_srl] as $k => $v)
			{
				if (!isset($val->{$k}) || !$val->{$k})
				{
					$val->{$k} = $v;
				}
			}
		}

		return $module_infos;
	}

	/**
	 * Split module information and extra variables.
	 *
	 * @param object $args
	 * @return array
	 */
	public static function splitExtraVars(object $args): array
	{
		$extra_vars = new \stdClass();

		foreach ($args as $key => $val)
		{
			if (isset(self::MODULE_VARS[$key]))
			{
				continue;
			}
			elseif (isset(self::DELETE_VARS[$key]))
			{
				unset($args->{$key});
			}
			else
			{
				$extra_vars->{$key} = $val;
				unset($args->{$key});
			}
		}

		return [$args, $extra_vars];
	}

	/**
	 * List of variables that can be stored directly in the modules table.
	 * All other configuration goes to the module_extra_vars table.
	 */
	public const MODULE_VARS = [
		'module_srl' => true,
		'module' => true,
		'module_category_srl' => true,
		'menu_srl' => true,
		'domain_srl' => true,
		'mid' => true,
		'layout_srl' => true,
		'mlayout_srl' => true,
		'use_mobile' => true,
		'skin' => true,
		'is_skin_fix' => true,
		'mskin' => true,
		'is_mskin_fix' => true,
		'browser_title' => true,
		'description' => true,
		'content' => true,
		'mcontent' => true,
		'is_default' => true,
		'open_rss' => true,
		'header_text' => true,
		'footer_text' => true,
	];

	/**
	 * List of variables to be ignored when inserting/updating settings.
	 */
	public const DELETE_VARS = [
		'error_return_url' => true,
		'success_return_url' => true,
		'xe_js_callback' => true,
		'xe_validator_id' => true,
		'_filter' => true,
		'_rx_ajax_compat' => true,
		'_rx_ajax_form' => true,
		'_rx_csrf_token' => true,
		'site_srl' => true,
		'body' => true,
		'act' => true,
		'page' => true,
	];
}
