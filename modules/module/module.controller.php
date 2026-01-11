<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleController
 * @author NAVER (developers@xpressengine.com)
 * @brief controller class of the module module
 */
class ModuleController extends Module
{
	/**
	 * @brief Add a file into the file box
	 */
	public function insertModuleFileBox($vars)
	{
		return Rhymix\Modules\Module\Models\Filebox::insertFile($vars);
	}

	/**
	 * @brief Update a file into the file box
	 */
	public function updateModuleFileBox($vars)
	{
		return Rhymix\Modules\Module\Models\Filebox::updateFile($vars);
	}

	/**
	 * @brief Delete a file from the file box
	 */
	public function deleteModuleFileBox($vars)
	{
		return Rhymix\Modules\Module\Models\Filebox::deleteFile($vars);
	}

	/**
	 * API call to clear cache entries.
	 */
	public function procModuleClearCache()
	{
		$oController = Rhymix\Modules\Module\Controllers\General::getInstance();
		return $oController->procModuleClearCache();
	}

	/**
	 * @brief Add trigger callback function
	 *
	 * @param string $trigger_name
	 * @param string $called_position
	 * @param callable $callback_function
	 */
	public function addTriggerFunction($trigger_name, $called_position, $callback_function)
	{
		return Rhymix\Modules\Module\Models\Event::addEventHandler(
			(string)$trigger_name,
			(string)$called_position,
			$callback_function
		);
	}

	/**
	 * @brief Add module trigger
	 * module trigger is to call a trigger to a target module
	 *
	 */
	public function insertTrigger($trigger_name, $module, $type, $called_method, $called_position)
	{
		return Rhymix\Modules\Module\Models\Event::registerHandler(
			(string)$trigger_name,
			(string)$called_position,
			(string)$module,
			(string)$type,
			(string)$called_method
		);
	}

	/**
	 * @brief Delete module trigger
	 *
	 */
	public function deleteTrigger($trigger_name, $module, $type, $called_method, $called_position)
	{
		return Rhymix\Modules\Module\Models\Event::unregisterHandler(
			(string)$trigger_name,
			(string)$called_position,
			(string)$module,
			(string)$type,
			(string)$called_method
		);
	}

	/**
	 * @brief Delete module trigger
	 *
	 */
	public function deleteModuleTriggers($module)
	{
		return Rhymix\Modules\Module\Models\Event::unregisterHandlersByModule((string)$module);
	}

	/**
	 * @brief Set is_default as N in all modules(the default module is disabled)
	 */
	public function clearDefaultModule()
	{
		$output = executeQuery('module.clearDefaultModule');
		if(!$output->toBool()) return $output;

		Rhymix\Framework\Cache::clearGroup('site_and_module');
		return $output;
	}

	/**
	 * Update menu_srl of mid which belongs to menu_srl
	 *
	 * @deprecated
	 */
	public function updateModuleMenu($args)
	{
		$output = executeQuery('module.updateModuleMenu', $args);

		Rhymix\Framework\Cache::clearGroup('site_and_module');
		return $output;
	}

	/**
	 * Update menu_srl of a module.
	 *
	 * @param int $module_srl
	 * @param int $menu_srl
	 * @param bool $clear_cache
	 * @return BaseObject
	 */
	public function updateModuleMenuSrl(int $module_srl, int $menu_srl, bool $clear_cache = true): BaseObject
	{
		$output = executeQuery('module.updateModuleMenuSrl', [
			'module_srl' => $module_srl,
			'menu_srl' => $menu_srl,
		]);

		if ($clear_cache)
		{
			Rhymix\Framework\Cache::clearGroup('site_and_module');
		}
		return $output;
	}

	/**
	 * @brief Update layout_srl of mid which belongs to menu_srl
	 */
	public function updateModuleLayout($layout_srl, $menu_srl_list)
	{
		if(!count($menu_srl_list)) return;

		$args = new stdClass;
		$args->layout_srl = $layout_srl;
		$args->menu_srls = implode(',',$menu_srl_list);
		$output = executeQuery('module.updateModuleLayout', $args);

		Rhymix\Framework\Cache::clearGroup('site_and_module');
		return $output;
	}

	/* =========================================================== */

	/**
	 * Insert module
	 */
	public function insertModule($args)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::insertModule($args);
	}

	/**
	 * Modify module information
	 */
	public function updateModule($args)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::updateModule($args);
	}

	/**
	 * Arrange module information
	 *
	 * @deprecated
	 */
	public function arrangeModuleInfo(&$args, &$extra_vars)
	{
		// Test mid value
		if (!preg_match("/^[a-z][a-z0-9_]+$/i", $args->mid))
		{
			return new BaseObject(-1, 'msg_limit_mid');
		}

		list($args, $extra_vars) = Rhymix\Modules\Module\Models\ModuleInfo::splitExtraVars($args);
		return new BaseObject();
	}

	/**
	 * Save update log
	 *
	 * @deprecated
	 * @param string $update_id
	 * @return bool
	 */
	public function insertUpdatedLog(string $update_id): bool
	{
		$args = new stdClass();
		$args->update_id = $update_id;
		$output = executeQuery('module.insertModuleUpdateLog', $args);

		if(!!$output->error) return false;

		return true;
	}

	/**
	 * Change the module's virtual site
	 *
	 * @deprecated
	 */
	public function updateModuleSite($module_srl, $site_srl = 0, $layout_srl = 0)
	{

	}

	/**
	 * Delete module
	 * Attempt to delete all related information when deleting a module.
	 * Origin method is changed. because menu validation check is needed
	 */
	public function deleteModule($module_srl)
	{
		if (!$module_srl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}
		return Rhymix\Modules\Module\Models\ModuleInfo::deleteModuleWithMenu((int)$module_srl);
	}

	/**
	 * Delete module
	 * Attempt to delete all related information when deleting a module.
	 */
	public function onlyDeleteModule($module_srl)
	{
		if (!$module_srl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}
		return Rhymix\Modules\Module\Models\ModuleInfo::deleteModule((int)$module_srl);
	}

	/**
	 * @brief Register extra vars to the module
	 */
	public function insertModuleExtraVars($module_srl, $obj)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::insertExtraVars((int)$module_srl, $obj);
	}

	/**
	 * @brief Remove extra vars from the module
	 */
	public function deleteModuleExtraVars($module_srl)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::deleteExtraVars((int)$module_srl);
	}

	/**
	 * @brief Grant permission to the module
	 */
	public function insertModuleGrants($module_srl, $obj)
	{
		if(!$obj || !countobj($obj)) return;
		return Rhymix\Modules\Module\Models\ModuleInfo::insertGrants((int)$module_srl, $obj);
	}

	/**
	 * @brief Remove permission from the module
	 */
	public function deleteModuleGrants($module_srl)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::deleteGrants((int)$module_srl);
	}

	/**
	 * @brief Specify the admin ID to a module
	 */
	public function insertAdminId($module_srl, $admin_id, $scopes = null)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::insertManager($module_srl, $admin_id, $scopes);
	}

	/**
	 * @brief Remove the admin ID from a module
	 */
	public function deleteAdminId($module_srl, $admin_id = '')
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::deleteManager($module_srl, $admin_id);
	}

	/**
	 * Save global config for a module.
	 *
	 * @param string $module
	 * @param object $config
	 * @return BaseObject
	 */
	public function insertModuleConfig($module, $config)
	{
		return Rhymix\Modules\Module\Models\ModuleConfig::insertModuleConfig($module, $config);
	}

	public function updateModuleConfig($module, $config)
	{
		return Rhymix\Modules\Module\Models\ModuleConfig::updateModuleConfig($module, $config);
	}

	/**
	 * Save an independent section of module config.
	 *
	 * @param string $module
	 * @param string $section
	 * @param object $config
	 * @return BaseObject
	 */
	public function insertModuleSectionConfig($module, $section, $config)
	{
		return $this->insertModuleConfig("$module:$section", $config);
	}

	public function updateModuleSectionConfig($module, $section, $config)
	{
		return Rhymix\Modules\Module\Models\ModuleConfig::updateModuleSectionConfig($module, $section, $config);
	}

	/**
	 * Save module config for a specific pair of module and module_srl.
	 *
	 * @param string $module
	 * @param int $module_srl
	 * @param object $config
	 * @return BaseObject
	 */
	public function insertModulePartConfig($module, $module_srl, $config)
	{
		return Rhymix\Modules\Module\Models\ModuleConfig::insertModulePartConfig($module, $module_srl, $config);
	}

	public function updateModulePartConfig($module, $module_srl, $config)
	{
		return Rhymix\Modules\Module\Models\ModuleConfig::updateModulePartConfig($module, $module_srl, $config);
	}

	/**
	 * Insert skin vars to a module
	 * @param $module_srl Sequence of module
	 * @param $obj Skin variables
	 */
	public function insertModuleSkinVars($module_srl, $obj)
	{
		return $this->_insertModuleSkinVars($module_srl, $obj, 'P');
	}

	/**
	 * Insert mobile skin vars to a module
	 * @param $module_srl Sequence of module
	 * @param $obj Skin variables
	 */
	public function insertModuleMobileSkinVars($module_srl, $obj)
	{
		return $this->_insertModuleSkinVars($module_srl, $obj, 'M');
	}

	/**
	 * @brief Insert skin vars to a module
	 */
	public function _insertModuleSkinVars($module_srl, $obj, $mode)
	{
		$mode = $mode === 'P' ? 'P' : 'M';
		return Rhymix\Modules\Module\Models\ModuleInfo::insertSkinVars((int)$module_srl, $obj, $mode);
	}

	/**
	 * @brief Change other information of the module
	 * @deprecated
	 */
	public function updateModuleSkinVars($module_srl, $skin_vars)
	{
		return new BaseObject();
	}

	/**
	 * Remove skin vars ofa module
	 * @param $module_srl seqence of module
	 */
	public function deleteModuleSkinVars($module_srl)
	{
		return $this->_deleteModuleSkinVars($module_srl, 'P');
	}

	/**
	 * Remove mobile skin vars ofa module
	 * @param $module_srl seqence of module
	 */
	public function deleteModuleMobileSkinVars($module_srl)
	{
		return $this->_deleteModuleSkinVars($module_srl, 'M');
	}

	/**
	 * @brief Remove skin vars of a module
	 */
	public function _deleteModuleSkinVars($module_srl, $mode)
	{
		$mode = $mode === 'P' ? 'P' : 'M';
		return Rhymix\Modules\Module\Models\ModuleInfo::deleteSkinVars((int)$module_srl, $mode);
	}

	/**
	 * Add action forward
	 *
	 * @deprecated
	 */
	public function insertActionForward($module, $type, $act, $route_regexp = null, $route_config = null, $global_route = 'N')
	{
		return Rhymix\Modules\Module\Models\GlobalRoute::insertGlobalRoute($act, $module, $type, $route_regexp, $route_config, $global_route);
	}

	/**
	 * Delete action forward
	 *
	 * @deprecated
	 */
	public function deleteActionForward($module, $type, $act)
	{
		return Rhymix\Modules\Module\Models\GlobalRoute::deleteGlobalRoute($act, $module, $type);
	}

	/**
	 * Insert module extend (not supported since Rhymix 2.0)
	 *
	 * @deprecated
	 */
	public function insertModuleExtend($parent_module, $extend_module, $type, $kind = '')
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled;
	}

	/**
	 * Delete module extend (not supported since Rhymix 2.0)
	 *
	 * @deprecated
	 */
	public function deleteModuleExtend($parent_module, $extend_module, $type, $kind = '')
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled;
	}

	/**
	 * Create virtual site
	 *
	 * @deprecated
	 */
	public function insertSite($domain, $index_module_srl)
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled;
	}

	/**
	 * Modify virtual site
	 *
	 * @deprecated
	 */
	public function updateSite($args)
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled;
	}

	/**
	 * @deprecated
	 */
	public function updateModuleInSites($site_srls, $args)
	{

	}

	/**
	 * Check if all action-forwardable routes are registered. If not, register them.
	 *
	 * @param string $module_name
	 * @return object
	 */
	public function registerActionForwardRoutes(string $module_name)
	{
		return Rhymix\Modules\Module\Models\Updater::registerGlobalRoutes($module_name);
	}

	/**
	 * Check if all event handlers are registered. If not, register them.
	 *
	 * @param string $module_name
	 * @return object
	 */
	public function registerEventHandlers(string $module_name)
	{
		return Rhymix\Modules\Module\Models\Updater::registerEventHandlers($module_name);
	}

	/**
	 * Check if all custom namespaces are registered. If not, register them.
	 *
	 * @param string $module_name
	 * @return object
	 */
	public function registerNamespaces(string $module_name)
	{
		return Rhymix\Modules\Module\Models\Updater::registerNamespacePrefixes($module_name);
	}

	/**
	 * Check if all prefixes for a module are registered. If not, register them.
	 *
	 * @param string $module_name
	 * @return object
	 */
	public function registerPrefixes(string $module_name)
	{
		return Rhymix\Modules\Module\Models\Updater::registerDefaultPrefixes($module_name);
	}

	/**
	 * @deprecated
	 */
	public static function replaceDefinedLangCode(&$output, $replace = true)
	{
		if ($replace)
		{
			$output = Context::replaceUserLang($output);
		}
	}

	/**
	 * @deprecated
	 */
	public function lock($lock_name, $timeout, $member_srl = null)
	{
		$this->unlockTimeoutPassed();
		$args = new stdClass;
		$args->lock_name = $lock_name;
		if(!$timeout) $timeout = 60;
		$args->deadline = date("YmdHis", $_SERVER['REQUEST_TIME'] + $timeout);
		if($member_srl) $args->member_srl = $member_srl;
		$output = executeQuery('module.insertLock', $args);
		if($output->toBool())
		{
			$output->add('lock_name', $lock_name);
			$output->add('deadline', $args->deadline);
		}
		return $output;
	}

	/**
	 * @deprecated
	 */
	public function unlockTimeoutPassed()
	{
		executeQuery('module.deleteLocksTimeoutPassed');
	}

	/**
	 * @deprecated
	 */
	public function unlock($lock_name, $deadline)
	{
		$args = new stdClass;
		$args->lock_name = $lock_name;
		$args->deadline = $deadline;
		$output = executeQuery('module.deleteLock', $args);
		return $output;
	}
}
/* End of file module.controller.php */
/* Location: ./modules/module/module.controller.php */
