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
use ModuleHandler;
use ModuleModel;

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
	 * Get module information by module_srl.
	 *
	 * @param int $module_srl
	 * @return ?ModuleInstance
	 */
	public static function getModuleInfoByModuleSrl(int $module_srl): ?ModuleInstance
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
			Cache::set("site_and_module:module_info:$module_srl", $module_info, 0, true);
		}

		return self::addExtraVars($module_info);
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

		$module_srl = ModuleModel::$_mid_map[$prefix] ?? Cache::get('site_and_module:module_srl:' . $prefix);
		if ($module_srl)
		{
			return self::getModuleInfoByModuleSrl($module_srl);
		}

		$output = executeQueryArray('module.getMidInfo', ['mid' => $prefix], [], ModuleInstance::class);
		if (!$output->toBool() || !count($output->data))
		{
			return null;
		}
		$module_info = array_first($output->data);
		ModuleModel::$_mid_map[$prefix] = $module_info->module_srl;
		Cache::set('site_and_module:module_info:' . $module_info->module_srl, $module_info, 0, true);
		Cache::set('site_and_module:module_srl:' . $prefix, $module_info->module_srl, 0, true);

		return self::addExtraVars($module_info);
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

		return self::addExtraVars($module_info);
	}

	/**
	 * Insert a module.
	 *
	 * @param object $args
	 * @return BaseObject
	 */
	public static function insertModule(object $args): BaseObject
	{
		$isMenuCreate = $args->isMenuCreate ?? true;

		list($args, $extra_vars) = self::splitExtraVars($args);
		if (!Prefix::isValidPrefix($args->mid, $args->module ?? null))
		{
			return new BaseObject(-1, 'msg_limit_mid');
		}

		// Check whether the module name already exists
		if (ModuleModel::isIDExists($args->mid, $args->module))
		{
			return new BaseObject(-1, 'msg_module_name_exists');
		}

		// Fill default values
		if (empty($args->module_srl))
		{
			$args->module_srl = getNextSequence();
		}
		$args->browser_title = escape($args->browser_title ?? '', false, true);
		$args->description = isset($args->description) ? escape($args->description, false) : null;
		if (!isset($args->skin) || $args->skin == '/USE_DEFAULT/')
		{
			$args->is_skin_fix = 'N';
		}
		else
		{
			if (isset($args->is_skin_fix))
			{
				$args->is_skin_fix = ($args->is_skin_fix != 'Y') ? 'N' : 'Y';
			}
			else
			{
				$args->is_skin_fix = 'Y';
			}
		}
		if (!isset($args->mskin) || $args->mskin == '/USE_DEFAULT/' || $args->mskin == '/USE_RESPONSIVE/')
		{
			$args->is_mskin_fix = 'N';
		}
		else
		{
			if (isset($args->is_mskin_fix))
			{
				$args->is_mskin_fix = ($args->is_mskin_fix != 'Y') ? 'N' : 'Y';
			}
			else
			{
				$args->is_mskin_fix = 'Y';
			}
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		if ($isMenuCreate)
		{
			$menuArgs = new \stdClass;
			$menuArgs->menu_srl = $args->menu_srl;
			$menuOutput = executeQuery('menu.getMenu', $menuArgs);

			// if menu is not created, create menu also. and does not supported that in virtual site.
			if (!$menuOutput->data)
			{
				$oMenuAdminController = getAdminController('menu');
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
		$args->menu_srl = $menuArgs->menu_srl;
		$output = executeQuery('module.insertModule', $args);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Insert module extra vars
		self::insertModuleExtraVars($args->module_srl, $extra_vars);

		// commit
		$oDB->commit();

		Cache::clearGroup('site_and_module');
		ModuleModel::$_mid_map = ModuleModel::$_module_srl_map = [];
		$output->add('module_srl',$args->module_srl);
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
		$isMenuCreate = $args->isMenuCreate ?? true;

		list($args, $extra_vars) = self::splitExtraVars($args);
		if (!Prefix::isValidPrefix($args->mid, $args->module ?? null))
		{
			return new BaseObject(-1, 'msg_limit_mid');
		}

		// Check whether the module name already exists
		$module_info = ModuleModel::getModuleInfoByModuleSrl($args->module_srl);
		if ($args->mid !== $module_info->mid && ModuleModel::isIDExists($args->mid))
		{
			if ($args->module !== $args->mid)
			{
				return new BaseObject(-1, 'msg_module_name_exists');
			}
		}

		$args->browser_title = escape($args->browser_title ?? $module_info->browser_title, false, true);
		$args->description = isset($args->description) ? escape($args->description, false) : null;

		// default value
		if (!isset($args->skin) || $args->skin == '/USE_DEFAULT/')
		{
			$args->is_skin_fix = 'N';
		}
		else
		{
			if (isset($args->is_skin_fix))
			{
				$args->is_skin_fix = ($args->is_skin_fix != 'Y') ? 'N' : 'Y';
			}
			else
			{
				$args->is_skin_fix = 'Y';
			}
		}

		if (!isset($args->mskin) || $args->mskin == '/USE_DEFAULT/' || $args->mskin == '/USE_RESPONSIVE/')
		{
			$args->is_mskin_fix = 'N';
		}
		else
		{
			if (isset($args->is_mskin_fix))
			{
				$args->is_mskin_fix = ($args->is_mskin_fix != 'Y') ? 'N' : 'Y';
			}
			else
			{
				$args->is_mskin_fix = 'Y';
			}
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		if ($isMenuCreate)
		{
			$menuArgs = new \stdClass;
			$menuArgs->url = $module_info->mid;
			$menuOutput = executeQueryArray('menu.getMenuItemByUrl', $menuArgs);
			if ($menuOutput->data && count($menuOutput->data))
			{
				$oMenuAdminController = getAdminController('menu');
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

		$output = executeQuery('module.updateModule', $args);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// if mid changed, change mid of success_return_url to new mid
		if ($module_info->mid != $args->mid && Context::get('success_return_url'))
		{
			changeValueInUrl('mid', $args->mid, $module_info->mid);
		}

		// Insert module extra vars
		self::insertModuleExtraVars($args->module_srl, $extra_vars);

		$oDB->commit();

		$output->add('module_srl',$args->module_srl);

		//remove from cache
		Cache::clearGroup('site_and_module');
		ModuleModel::$_mid_map = ModuleModel::$_module_srl_map = [];
		return $output;
	}

	/**
	 * Delete a module, with all related information.
	 *
	 * @param int $module_srl
	 * @param bool $delete_menu
	 * @return BaseObject
	 */
	public static function deleteModule(int $module_srl, bool $delete_menu = true): BaseObject
	{
		if (!$module_srl)
		{
			return new BaseObject(-1,'msg_invalid_request');
		}

		$output = ModuleModel::getModuleInfoByModuleSrl($module_srl);

		$args = new \stdClass();
		$args->url = $output->mid;
		$args->is_shortcut = 'N';

		$oMenuAdminModel = getAdminModel('menu');
		$menuOutput = $oMenuAdminModel->getMenuList($args);
		if (is_array($menuOutput->data))
		{
			foreach ($menuOutput->data AS $key=>$value)
			{
				$args->menu_srl = $value->menu_srl;
				break;
			}
		}

		// menu delete
		$output = executeQuery('menu.getMenuItemByUrl', $args);
		if ($output->data && $delete_menu)
		{
			unset($args);
			$args = new \stdClass;
			$args->menu_srl = $output->data->menu_srl ?: 0;
			$args->menu_item_srl = $output->data->menu_item_srl ?: 0;
			$args->is_force = 'N';

			$oMenuAdminController = getAdminController('menu');
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
			return self::onlyDeleteModule($module_srl);
		}
	}

	/**
	 * Delete a module and all related information, except the menu.
	 *
	 * @param int $module_srl
	 * @return BaseObject
	 */
	public static function onlyDeleteModule(int $module_srl): BaseObject
	{
		if (!$module_srl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		// check start module
		$columnList = array('sites.index_module_srl');
		$start_module = ModuleModel::getSiteInfo(0, $columnList);
		if ($module_srl == $start_module->index_module_srl)
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
		self::deleteModuleExtraVars($module_srl);

		// Delete skin settings
		self::deleteModuleSkinVars($module_srl, 'P');
		self::deleteModuleSkinVars($module_srl, 'M');

		// Delete permissions and module managers
		self::deleteModuleGrants($module_srl);
		self::deleteModuleManager($module_srl);

		// Call a trigger (after)
		ModuleHandler::triggerCall('module.deleteModule', 'after', $trigger_obj);

		// Commit
		$oDB->commit();

		// Clear cache
		Cache::clearGroup('site_and_module');
		ModuleModel::$_mid_map = ModuleModel::$_module_srl_map = [];
		return $output;
	}

	/**
	 * Insert a module's extra configuration variables.
	 *
	 * @param int $module_srl
	 * @param object $extra_vars
	 * @return BaseObject
	 */
	public static function insertModuleExtraVars(int $module_srl, object $extra_vars): BaseObject
	{
		$output = null;
		self::deleteModuleExtraVars($module_srl);
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
	public static function deleteModuleExtraVars(int $module_srl): DBResultHelper
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
	public static function insertModuleSkinVars(int $module_srl, object $skin_vars, string $mode = 'P'): BaseObject
	{
		$mode = $mode === 'P' ? 'P' : 'M';
		$output = null;

		$oDB = DB::getInstance();
		$oDB->begin();

		$output = self::deleteModuleSkinVars($module_srl, $mode);
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
	public static function deleteModuleSkinVars(int $module_srl, string $mode = 'P'): DBResultHelper
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
	public static function insertModuleGrants(int $module_srl, object $obj): BaseObject
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
	public static function deleteModuleGrants(int $module_srl): DBResultHelper
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
	public static function insertModuleManager(int $module_srl, string $user_id, ?array $scopes = null): BaseObject
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
	public static function deleteModuleManager(int $module_srl, ?string $user_id = null): BaseObject
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
	 * @param array|self $module_info
	 * @return array|self
	 */
	public static function addExtraVars($module_info)
	{
		// If a single module info is given, convert it to an array.
		if (is_array($module_info))
		{
			$target_module_info = $module_info;
		}
		else
		{
			$target_module_info = array($module_info);
		}

		// Compile the list of module_srl.
		$module_srls = array();
		foreach ($target_module_info as $key => $val)
		{
			if ($val->module_srl)
			{
				$module_srls[] = $val->module_srl;
			}
		}
		if (!count($module_srls))
		{
			return $module_info;
		}

		// Get extra vars for all modules.
		$extra_vars = ModuleModel::getModuleExtraVars($module_srls);
		if (!count($extra_vars))
		{
			return $module_info;
		}

		// Add extra_vars to each object.
		foreach ($target_module_info as $key => $val)
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

		// Return the result in the original format (array or object).
		if (is_array($module_info))
		{
			return $target_module_info;
		}
		else
		{
			return $target_module_info[0];
		}
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
