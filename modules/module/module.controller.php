<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleController
 * @author NAVER (developers@xpressengine.com)
 * @brief controller class of the module module
 */
class moduleController extends module
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Add action forward
	 * Action forward finds and forwards if an action is not in the requested module
	 * This is used when installing a module
	 */
	function insertActionForward($module, $type, $act)
	{
		$args = new stdClass();
		$args->module = $module;
		$args->type = $type;
		$args->act = $act;

		$output = executeQuery('module.insertActionForward', $args);

		$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheHandler->isSupport())
		{
			$cache_key = 'action_forward';
			$oCacheHandler->delete($cache_key);
		}

		return $output;
	}

	/**
	 * @brief Delete action forward
	 */
	function deleteActionForward($module, $type, $act)
	{
		$args = new stdClass();
		$args->module = $module;
		$args->type = $type;
		$args->act = $act;

		$output = executeQuery('module.deleteActionForward', $args);

		$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheHandler->isSupport())
		{
			$cache_key = 'action_forward';
			$oCacheHandler->delete($cache_key);
		}

		return $output;
	}

	/**
	 * @brief Add module trigger
	 * module trigger is to call a trigger to a target module
	 *
	 */
	function insertTrigger($trigger_name, $module, $type, $called_method, $called_position)
	{
		$args = new stdClass();
		$args->trigger_name = $trigger_name;
		$args->module = $module;
		$args->type = $type;
		$args->called_method = $called_method;
		$args->called_position = $called_position;

		$output = executeQuery('module.insertTrigger', $args);
		if($output->toBool())
		{
			//remove from cache
			$GLOBALS['__triggers__'] = NULL;
			$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
			if($oCacheHandler->isSupport())
			{
				$cache_key = 'triggers';
				$oCacheHandler->delete($cache_key);
			}
		}

		return $output;
	}

	/**
	 * @brief Delete module trigger
	 *
	 */
	function deleteTrigger($trigger_name, $module, $type, $called_method, $called_position)
	{
		$args = new stdClass();
		$args->trigger_name = $trigger_name;
		$args->module = $module;
		$args->type = $type;
		$args->called_method = $called_method;
		$args->called_position = $called_position;

		$output = executeQuery('module.deleteTrigger', $args);
		if($output->toBool())
		{
			//remove from cache
			$GLOBALS['__triggers__'] = NULL;
			$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
			if($oCacheHandler->isSupport())
			{
				$cache_key = 'triggers';
				$oCacheHandler->delete($cache_key);
			}
		}

		return $output;
	}

	/**
	 * @brief Delete module trigger
	 *
	 */
	function deleteModuleTriggers($module)
	{
		$args = new stdClass();
		$args->module = $module;

		$output = executeQuery('module.deleteModuleTriggers', $args);
		if($output->toBool())
		{
			//remove from cache
			$GLOBALS['__triggers__'] = NULL;
			$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
			if($oCacheHandler->isSupport())
			{
				$cache_key = 'triggers';
				$oCacheHandler->delete($cache_key);
			}
		}

		return $output;
	}

	/**
	 * @brief Add module extend
	 *
	 */
	function insertModuleExtend($parent_module, $extend_module, $type, $kind='')
	{
		if($kind != 'admin') $kind = '';
		if(!in_array($type,array('model','controller','view','api','mobile'))) return false;
		if(in_array($parent_module, array('module','addon','widget','layout'))) return false;

		$cache_file = './files/config/module_extend.php';
		FileHandler::removeFile($cache_file);

		$args = new stdClass;
		$args->parent_module = $parent_module;
		$args->extend_module = $extend_module;
		$args->type = $type;
		$args->kind = $kind;

		$output = executeQuery('module.getModuleExtendCount', $args);
		if($output->data->count>0) return false;

		$output = executeQuery('module.insertModuleExtend', $args);
		return $output;
	}

	/**
	 * @brief Delete module extend
	 *
	 */
	function deleteModuleExtend($parent_module, $extend_module, $type, $kind='')
	{
		$cache_file = './files/config/module_extend.php';
		FileHandler::removeFile($cache_file);

		$args = new stdClass;
		$args->parent_module = $parent_module;
		$args->extend_module = $extend_module;
		$args->type = $type;
		$args->kind = $kind;

		$output = executeQuery('module.deleteModuleExtend', $args);

		return $output;
	}

	function updateModuleConfig($module, $config, $site_srl = 0)
	{
		$args = new stdClass();
		$args->module = $module;
		$args->site_srl = $site_srl;

		$oModuleModel = getModel('module');
		$origin_config = $oModuleModel->getModuleConfig($module, $site_srl);

		if(!$origin_config) $origin_config = new stdClass;

		foreach($config as $key => $val)
		{
			$origin_config->{$key} = $val;
		}

		return $this->insertModuleConfig($module, $origin_config, $site_srl);
	}

	/**
	 * @brief Enter a specific set of modules
	 * In order to manage global configurations of modules such as board, member and so on
	 */
	function insertModuleConfig($module, $config, $site_srl = 0)
	{
		$args =new stdClass();
		$args->module = $module;
		$args->config = serialize($config);
		$args->site_srl = $site_srl;

		$output = executeQuery('module.deleteModuleConfig', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('module.insertModuleConfig', $args);

		//remove from cache
		$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}
		return $output;
	}

	/**
	 * @brief Save module configurations of the mid
	 * Manage mid configurations depending on module
	 */
	function insertModulePartConfig($module, $module_srl, $config)
	{
		$args = new stdClass();
		$args->module = $module;
		$args->module_srl = $module_srl;
		$args->config = serialize($config);

		$output = executeQuery('module.deleteModulePartConfig', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('module.insertModulePartConfig', $args);

		//remove from cache
		$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		return $output;
	}

	/**
	 * @brief create virtual site
	 */
	function insertSite($domain, $index_module_srl)
	{
		if(isSiteID($domain))
		{
			$oModuleModel = getModel('module');
			if($oModuleModel->isIDExists($domain, 0)) return new Object(-1,'msg_already_registed_vid');
		}
		else
		{
			$domain = strtolower($domain);
		}

		$args = new stdClass;
		$args->site_srl = getNextSequence();
		$args->domain = (substr_compare($domain, '/', -1) === 0) ? substr($domain, 0, -1) : $domain;
		$args->index_module_srl = $index_module_srl;
		$args->default_language = Context::getLangType();

		$columnList = array('modules.site_srl');
		$oModuleModel = getModel('module');
		$output = $oModuleModel->getSiteInfoByDomain($args->domain, $columnList);
		if($output) return new Object(-1,'msg_already_registed_vid');

		$output = executeQuery('module.insertSite', $args);
		if(!$output->toBool()) return $output;

		$output->add('site_srl', $args->site_srl);
		return $output;
	}

	/**
	 * @brief modify virtual site
	 */
	function updateSite($args)
	{
		$oModuleModel = getModel('module');
		$columnList = array('sites.site_srl', 'sites.domain');
		$site_info = $oModuleModel->getSiteInfo($args->site_srl, $columnList);

		if(!$args->domain && $site_info->site_srl == $args->site_srl)
		{
			$args->domain = $site_info->domain;
		}

		if($site_info->domain != $args->domain)
		{
			$info = $oModuleModel->getSiteInfoByDomain($args->domain, $columnList);
			if($info->site_srl && $info->site_srl != $args->site_srl) return new Object(-1,'msg_already_registed_domain');
			if(isSiteID($args->domain) && $oModuleModel->isIDExists($args->domain)) return new Object(-1,'msg_already_registed_vid');

			if($args->domain && !isSiteID($args->domain))
			{
				$args->domain = (strlen($args->domain) >= 1 && substr_compare($args->domain, '/', -1) === 0) ? substr($args->domain, 0, -1) : $args->domain;
			}
		}
		$output = executeQuery('module.updateSite', $args);
		//clear cache for default mid
		if($args->site_srl == 0) $vid='';
		else $vid=$args->domain;

		$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->index_module_srl);
		$mid = $module_info->mid;

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		return $output;
	}

	/**
	 * @brief Arrange module information
	 */
	function arrangeModuleInfo(&$args, &$extra_vars)
	{
		// Remove unnecessary information
		unset($args->body);
		unset($args->act);
		unset($args->page);
		// Test mid value
		if(!preg_match("/^[a-z][a-z0-9_]+$/i", $args->mid)) return new Object(-1, 'msg_limit_mid');
		// Test variables (separate basic vars and other vars in modules)
		$extra_vars = clone($args);
		unset($extra_vars->module_srl);
		unset($extra_vars->module);
		unset($extra_vars->module_category_srl);
		unset($extra_vars->layout_srl);
		unset($extra_vars->mlayout_srl);
		unset($extra_vars->use_mobile);
		unset($extra_vars->menu_srl);
		unset($extra_vars->site_srl);
		unset($extra_vars->mid);
		unset($extra_vars->is_skin_fix);
		unset($extra_vars->skin);
		unset($extra_vars->is_mskin_fix);
		unset($extra_vars->mskin);
		unset($extra_vars->browser_title);
		unset($extra_vars->description);
		unset($extra_vars->is_default);
		unset($extra_vars->content);
		unset($extra_vars->mcontent);
		unset($extra_vars->open_rss);
		unset($extra_vars->header_text);
		unset($extra_vars->footer_text);
		$args = delObjectVars($args, $extra_vars);

		return new Object();
	}

	/**
	 * @brief Insert module
	 */
	function insertModule($args)
	{
		if(isset($args->isMenuCreate))
		{
			$isMenuCreate = $args->isMenuCreate;
		}
		else
		{
			$isMenuCreate = TRUE;
		}

		$output = $this->arrangeModuleInfo($args, $extra_vars);
		if(!$output->toBool()) return $output;
		// Check whether the module name already exists
		if(!$args->site_srl) $args->site_srl = 0;
		$oModuleModel = getModel('module');
		if($oModuleModel->isIDExists($args->mid, $args->site_srl)) return new Object(-1, 'msg_module_name_exists');

		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();
		// Get colorset from the skin information
		$module_path = ModuleHandler::getModulePath($args->module);
		$skin_info = $oModuleModel->loadSkinInfo($module_path, $args->skin);
		$skin_vars = new stdClass();
		$skin_vars->colorset = $skin_info->colorset[0]->name;
		// Arrange variables and then execute a query
		if(!$args->module_srl) $args->module_srl = getNextSequence();

		// default value
		if($args->skin == '/USE_DEFAULT/')
		{
			$args->is_skin_fix = 'N';
		}
		else
		{
			if(isset($args->is_skin_fix))
			{
				$args->is_skin_fix = ($args->is_skin_fix != 'Y') ? 'N' : 'Y';
			}
			else
			{
				$args->is_skin_fix = 'Y';
			}
		}

		if($args->mskin == '/USE_DEFAULT/')
		{
			$args->is_mskin_fix = 'N';
		}
		else
		{
			if(isset($args->is_mskin_fix))
			{
				$args->is_mskin_fix = ($args->is_mskin_fix != 'Y') ? 'N' : 'Y';
			}
			else
			{
				$args->is_mskin_fix = 'Y';
			}
		}

		unset($output);

		if($isMenuCreate == TRUE)
		{
			$menuArgs = new stdClass;
			$menuArgs->menu_srl = $args->menu_srl;
			$menuOutput = executeQuery('menu.getMenu', $menuArgs);

			// if menu is not created, create menu also. and does not supported that in virtual site.
			if(!$menuOutput->data && !$args->site_srl)
			{
				$oMenuAdminModel = getAdminModel('menu');

				$oMenuAdminController = getAdminController('menu');
				$menuSrl = $oMenuAdminController->getUnlinkedMenu();

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
				if(!$menuItemOutput->toBool())
				{
					$oDB->rollback();
					return $menuItemOutput;
				}

				$oMenuAdminController->makeXmlFile($menuSrl);
			}
		}

		$args->menu_srl = $menuArgs->menu_srl;
		// Insert a module
		$output = executeQuery('module.insertModule', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Insert module extra vars
		$this->insertModuleExtraVars($args->module_srl, $extra_vars);

		// commit
		$oDB->commit();

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		$output->add('module_srl',$args->module_srl);
		return $output;
	}

	/**
	 * @brief Modify module information
	 */
	function updateModule($args)
	{
		$output = $this->arrangeModuleInfo($args, $extra_vars);
		if(!$output->toBool()) return $output;
		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'site_srl', 'browser_title', 'mid');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

		if(!$args->site_srl || !$args->browser_title)
		{
			if(!$args->site_srl) $args->site_srl = (int)$module_info->site_srl;
			if(!$args->browser_title) $args->browser_title = $module_info->browser_title;
		}

		$output = executeQuery('module.isExistsModuleName', $args);
		if(!$output->toBool() || $output->data->count)
		{
			$oDB->rollback();
			return new Object(-1, 'msg_module_name_exists');
		}

		// default value
		if($args->skin == '/USE_DEFAULT/')
		{
			$args->is_skin_fix = 'N';
		}
		else
		{
			if(isset($args->is_skin_fix))
			{
				$args->is_skin_fix = ($args->is_skin_fix != 'Y') ? 'N' : 'Y';
			}
			else
			{
				$args->is_skin_fix = 'Y';
			}
		}

		if($args->mskin == '/USE_DEFAULT/')
		{
			$args->is_mskin_fix = 'N';
		}
		else
		{
			if(isset($args->is_mskin_fix))
			{
				$args->is_mskin_fix = ($args->is_mskin_fix != 'Y') ? 'N' : 'Y';
			}
			else
			{
				$args->is_mskin_fix = 'Y';
			}
		}
		$output = executeQuery('module.updateModule', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$menuArgs = new stdClass;
		$menuArgs->url = $module_info->mid;
		$menuArgs->site_srl = $module_info->site_srl;
		$menuOutput = executeQueryArray('menu.getMenuItemByUrl', $menuArgs);
		if($menuOutput->data && count($menuOutput->data))
		{
			$oMenuAdminController = getAdminController('menu');
			foreach($menuOutput->data as $itemInfo)
			{
				$itemInfo->url = $args->mid;

				$updateMenuItemOutput = $oMenuAdminController->updateMenuItem($itemInfo);
				if(!$updateMenuItemOutput->toBool())
				{
					$oDB->rollback();
					return $updateMenuItemOutput;
				}
			}
		}

		// if mid changed, change mid of success_return_url to new mid
		if($module_info->mid != $args->mid && Context::get('success_return_url'))
		{
			changeValueInUrl('mid', $args->mid, $module_info->mid);
		}

		// Insert module extra vars
		$this->insertModuleExtraVars($args->module_srl, $extra_vars);

		$oDB->commit();

		$output->add('module_srl',$args->module_srl);

		//remove from cache
		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		return $output;
	}

	/**
	 * @brief Change the module's virtual site
	 */
	function updateModuleSite($module_srl, $site_srl, $layout_srl = 0)
	{
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->site_srl = $site_srl;
		$args->layout_srl = $layout_srl;
		$output = executeQuery('module.updateModuleSite', $args);
		if(!$output->toBool()) return $output;

		//remove from cache
		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		return $output;
	}

	/**
	 * Delete module
	 * Attempt to delete all related information when deleting a module.
	 * Origin method is changed. because menu validation check is needed
	 */
	function deleteModule($module_srl, $site_srl = 0)
	{
		if(!$module_srl) return new Object(-1,'msg_invalid_request');

		$site_module_info = Context::get('site_module_info');

		$oModuleModel = getModel('module');
		$output = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

		$args = new stdClass();
		$args->url = $output->mid;
		$args->is_shortcut = 'N';
		if(!$site_srl) $args->site_srl = $site_module_info->site_srl;
		else $args->site_srl = $site_srl;

		unset($output);

		$oMenuAdminModel = getAdminModel('menu');
		$menuOutput = $oMenuAdminModel->getMenuList($args);

		// get menu_srl by site_srl
		if(is_array($menuOutput->data))
		{
			foreach($menuOutput->data AS $key=>$value)
			{
				$args->menu_srl = $value->menu_srl;
				break;
			}
		}

		$output = executeQuery('menu.getMenuItemByUrl', $args);
		// menu delete
		if($output->data)
		{
			unset($args);
			$args = new stdClass;
			$args->menu_srl = $output->data->menu_srl;
			$args->menu_item_srl = $output->data->menu_item_srl;
			$args->is_force = 'N';

			$oMenuAdminController = getAdminController('menu');
			$output = $oMenuAdminController->deleteItem($args);

			if($output->isSuccess)
			{
				return new Object(0, 'success_deleted');
			}
			else
			{
				return new Object($output->error, $output->message);
			}
		}
		// only delete module
		else
		{
			return $this->onlyDeleteModule($module_srl);
		}
	}

	/**
	 * Delete module
	 * Attempt to delete all related information when deleting a module.
	 */
	public function onlyDeleteModule($module_srl)
	{
		if(!$module_srl) return new Object(-1,'msg_invalid_request');

		// check start module
		$oModuleModel = getModel('module');
		$columnList = array('sites.index_module_srl');
		$start_module = $oModuleModel->getSiteInfo(0, $columnList);
		if($module_srl == $start_module->index_module_srl) return new Object(-1, 'msg_cannot_delete_startmodule');

		// Call a trigger (before)
		$trigger_obj = new stdClass();
		$trigger_obj->module_srl = $module_srl;
		$output = ModuleHandler::triggerCall('module.deleteModule', 'before', $trigger_obj);
		if(!$output->toBool()) return $output;

		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		$args = new stdClass();
		$args->module_srl = $module_srl;
		// Delete module information from the DB
		$output = executeQuery('module.deleteModule', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Delete permission information
		$this->deleteModuleGrants($module_srl);
		// Remove skin information
		$this->deleteModuleSkinVars($module_srl);
		// Delete module extra vars
		$this->deleteModuleExtraVars($module_srl);
		// Remove the module manager
		$this->deleteAdminId($module_srl);
		// Call a trigger (after)
		if($output->toBool())
		{
			$trigger_output = ModuleHandler::triggerCall('module.deleteModule', 'after', $trigger_obj);
			if(!$trigger_output->toBool())
			{
				$oDB->rollback();
				return $trigger_output;
			}
		}

		// commit
		$oDB->commit();

		//remove from cache
		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}
		return $output;
	}

	/**
	 * @brief Change other information of the module
	 * @deprecated
	 */
	function updateModuleSkinVars($module_srl, $skin_vars)
	{
		return new Object();
	}

	/**
	 * @brief Set is_default as N in all modules(the default module is disabled)
	 */
	function clearDefaultModule()
	{
		$output = executeQuery('module.clearDefaultModule');
		if(!$output->toBool()) return $output;

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		return $output;
	}

	/**
	 * @brief Update menu_srl of mid which belongs to menu_srl
	 */
	function updateModuleMenu($args)
	{
		$output = executeQuery('module.updateModuleMenu', $args);

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		return $output;
	}

	/**
	 * @brief Update layout_srl of mid which belongs to menu_srl
	 */
	function updateModuleLayout($layout_srl, $menu_srl_list)
	{
		if(!count($menu_srl_list)) return;

		$args = new stdClass;
		$args->layout_srl = $layout_srl;
		$args->menu_srls = implode(',',$menu_srl_list);
		$output = executeQuery('module.updateModuleLayout', $args);

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		return $output;
	}

	/**
	 * @brief Change the site administrator
	 */
	function insertSiteAdmin($site_srl, $arr_admins)
	{
		// Remove the site administrator
		$args = new stdClass;
		$args->site_srl = $site_srl;

		$output = executeQuery('module.deleteSiteAdmin', $args);

		if(!$output->toBool()) return $output;
		// Get user id of an administrator
		if(!is_array($arr_admins) || !count($arr_admins)) return new Object();
		foreach($arr_admins as $key => $user_id)
		{
			if(!trim($user_id)) continue;
			$admins[] = trim($user_id);
		}
		if(!count($admins)) return new Object();

		$oMemberModel = getModel('member');
		$member_config = $oMemberModel->getMemberConfig();
		if($member_config->identifier == 'email_address')
		{
			$args->email_address = '\''.implode('\',\'',$admins).'\'';
		}
		else
		{
			$args->user_ids = '\''.implode('\',\'',$admins).'\'';
		}
		$output = executeQueryArray('module.getAdminSrls', $args);
		if(!$output->toBool()||!$output->data) return $output;

		foreach($output->data as $key => $val)
		{
			unset($args);
			$args = new stdClass;
			$args->site_srl = $site_srl;
			$args->member_srl = $val->member_srl;
			$output = executeQueryArray('module.insertSiteAdmin', $args);
			if(!$output->toBool()) return $output;
		}
		return new Object();
	}

	/**
	 * @brief Specify the admin ID to a module
	 */
	function insertAdminId($module_srl, $admin_id)
	{
		$oMemberModel = getModel('member');
		$member_config = $oMemberModel->getMemberConfig();

		if($member_config->identifier == 'email_address')
			$member_info = $oMemberModel->getMemberInfoByEmailAddress($admin_id);
		else
			$member_info = $oMemberModel->getMemberInfoByUserID($admin_id);

		if(!$member_info->member_srl) return;
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$args->member_srl = $member_info->member_srl;
		return executeQuery('module.insertAdminId', $args);
	}

	/**
	 * @brief Remove the admin ID from a module
	 */
	function deleteAdminId($module_srl, $admin_id = '')
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;

		if($admin_id)
		{
			$oMemberModel = getModel('member');
			$member_info = $oMemberModel->getMemberInfoByUserID($admin_id);
			if($member_info->member_srl) $args->member_srl = $member_info->member_srl;
		}
		return executeQuery('module.deleteAdminId', $args);
	}

	/**
	 * Insert skin vars to a module
	 * @param $module_srl Sequence of module
	 * @param $obj Skin variables
	 */
	function insertModuleSkinVars($module_srl, $obj)
	{
		return $this->_insertModuleSkinVars($module_srl, $obj, 'P');
	}

	/**
	 * Insert mobile skin vars to a module
	 * @param $module_srl Sequence of module
	 * @param $obj Skin variables
	 */
	function insertModuleMobileSkinVars($module_srl, $obj)
	{
		return $this->_insertModuleSkinVars($module_srl, $obj, 'M');
	}


	/**
	 * @brief Insert skin vars to a module
	 */
	function _insertModuleSkinVars($module_srl, $obj, $mode)
	{
		$mode = $mode === 'P' ? 'P' : 'M';

		$oDB = DB::getInstance();
		$oDB->begin();

		$output = $this->_deleteModuleSkinVars($module_srl, $mode);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		getDestroyXeVars($obj);
		if(!$obj || !count($obj)) return new Object();

		$args = new stdClass;
		$args->module_srl = $module_srl;
		foreach($obj as $key => $val)
		{
			// #17927989 For an old board which used the old blog module
			// it often saved menu item(stdClass) on the skin info column
			// When updating the module on XE core 1.2.0 later versions, it occurs an error
			// fixed the error
			if (is_object($val)) continue;
			if (is_array($val)) $val = serialize($val);

			$args->name = trim($key);
			$args->value = trim($val);
			if(!$args->name || !$args->value) continue;

			if($mode === 'P')
			{
				$output = executeQuery('module.insertModuleSkinVars', $args);
			}
			else
			{
				$output = executeQuery('module.insertModuleMobileSkinVars', $args);
			}
			if(!$output->toBool())
			{
				return $output;
				$oDB->rollback();
			}
		}

		$oDB->commit;

		return new Object();
	}

	/**
	 * Remove skin vars ofa module
	 * @param $module_srl seqence of module
	 */
	function deleteModuleSkinVars($module_srl)
	{
		return $this->_deleteModuleSkinVars($module_srl, 'P');
	}

	/**
	 * Remove mobile skin vars ofa module
	 * @param $module_srl seqence of module
	 */
	function deleteModuleMobileSkinVars($module_srl)
	{
		return $this->_deleteModuleSkinVars($module_srl, 'M');
	}

	/**
	 * @brief Remove skin vars of a module
	 */
	function _deleteModuleSkinVars($module_srl, $mode)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$mode = $mode === 'P' ? 'P' : 'M';

		if($mode === 'P')
		{
			$object_key = 'module_skin_vars:'.$module_srl;
			$query = 'module.deleteModuleSkinVars';
		}
		else
		{
			$object_key = 'module_mobile_skin_vars:'.$module_srl;
			$query = 'module.deleteModuleMobileSkinVars';
		}

		//remove from cache
		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->delete($cache_key);
		}

		return executeQuery($query, $args);
	}

	/**
	 * @brief Register extra vars to the module
	 */
	function insertModuleExtraVars($module_srl, $obj)
	{
		$this->deleteModuleExtraVars($module_srl);
		getDestroyXeVars($obj);
		if(!$obj || !count($obj)) return;

		foreach($obj as $key => $val)
		{
			if(is_object($val) || is_array($val)) continue;

			$args = new stdClass();
			$args->module_srl = $module_srl;
			$args->name = trim($key);
			$args->value = trim($val);
			if(!$args->name || !$args->value) continue;
			$output = executeQuery('module.insertModuleExtraVars', $args);
		}

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$object_key = 'module_extra_vars:'.$module_srl;
			$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
			$oCacheHandler->delete($cache_key);
		}
	}

	/**
	 * @brief Remove extra vars from the module
	 */
	function deleteModuleExtraVars($module_srl)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$output = executeQuery('module.deleteModuleExtraVars', $args);

		//remove from cache
		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$object_key = 'module_extra_vars:'.$module_srl;
			$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
			$oCacheHandler->delete($cache_key);
		}

		return $output;
	}

	/**
	 * @brief Grant permission to the module
	 */
	function insertModuleGrants($module_srl, $obj)
	{
		$this->deleteModuleGrants($module_srl);
		if(!$obj || !count($obj)) return;

		foreach($obj as $name => $val)
		{
			if(!$val || !count($val)) continue;

			foreach($val as $group_srl)
			{
				$args = new stdClass();
				$args->module_srl = $module_srl;
				$args->name = $name;
				$args->group_srl = trim($group_srl);
				if(!$args->name || !$args->group_srl) continue;
				executeQuery('module.insertModuleGrant', $args);
			}
		}
	}

	/**
	 * @brief Remove permission from the module
	 */
	function deleteModuleGrants($module_srl)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;
		return executeQuery('module.deleteModuleGrants', $args);
	}

	/**
	 * @brief Change user-defined language
	 */
	function replaceDefinedLangCode(&$output, $isReplaceLangCode = true)
	{
		if($isReplaceLangCode)
		{
			$output = preg_replace_callback('!\$user_lang->([a-z0-9\_]+)!is', array($this,'_replaceLangCode'), $output);
		}
	}

	function _replaceLangCode($matches)
	{
		static $lang = null;

		if(is_null($lang))
		{
			$site_module_info = Context::get('site_module_info');
			if(!$site_module_info)
			{
				$oModuleModel = getModel('module');
				$site_module_info = $oModuleModel->getDefaultMid();
				Context::set('site_module_info', $site_module_info);
			}
			$cache_file = sprintf('%sfiles/cache/lang_defined/%d.%s.php', _XE_PATH_, $site_module_info->site_srl, Context::getLangType());
			if(!file_exists($cache_file))
			{
				$oModuleAdminController = getAdminController('module');
				$oModuleAdminController->makeCacheDefinedLangCode($site_module_info->site_srl);
			}

			if(file_exists($cache_file))
			{
				$moduleAdminControllerMtime = filemtime(_XE_PATH_ . 'modules/module/module.admin.controller.php');
				$cacheFileMtime = filemtime($cache_file);
				if($cacheFileMtime < $moduleAdminControllerMtime)
				{
					$oModuleAdminController = getAdminController('module');
					$oModuleAdminController->makeCacheDefinedLangCode($site_module_info->site_srl);
				}

				require_once($cache_file);
			}
		}
		if(!Context::get($matches[1]) && $lang[$matches[1]]) return $lang[$matches[1]];

		return str_replace('$user_lang->','',$matches[0]);
	}


	/**
	 * @brief Add and update a file into the file box
	 */
	function procModuleFileBoxAdd()
	{
		$ajax = Context::get('ajax');
		if ($ajax) Context::setRequestMethod('JSON');

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return new Object(-1, 'msg_not_permitted');

		$vars = Context::gets('addfile','filter');
		$attributeNames = Context::get('attribute_name');
		$attributeValues = Context::get('attribute_value');
		if(is_array($attributeNames) && is_array($attributeValues) && count($attributeNames) == count($attributeValues))
		{
			$attributes = array();
			foreach($attributeNames as $no => $name)
			{
				if(empty($name))
				{
					continue;
				}
				$attributes[] = sprintf('%s:%s', $name, $attributeValues[$no]);
			}
			$attributes = implode(';', $attributes);
		}

		$vars->comment = $attributes;
		$module_filebox_srl = Context::get('module_filebox_srl');

		$ext = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
		$vars->ext = $ext;
		if($vars->filter) $filter = explode(',',$vars->filter);
		else $filter = array('jpg','jpeg','gif','png');
		if(!in_array($ext,$filter)) return new Object(-1, 'msg_error_occured');

		$vars->member_srl = $logged_info->member_srl;

		// update
		if($module_filebox_srl > 0)
		{
			$vars->module_filebox_srl = $module_filebox_srl;
			$output = $this->updateModuleFileBox($vars);
		}
		// insert
		else
		{
			if(!Context::isUploaded()) return new Object(-1, 'msg_error_occured');
			$addfile = Context::get('addfile');
			if(!is_uploaded_file($addfile['tmp_name'])) return new Object(-1, 'msg_error_occured');
			if($vars->addfile['error'] != 0) return new Object(-1, 'msg_error_occured');
			$output = $this->insertModuleFileBox($vars);
		}

		$this->setTemplatePath($this->module_path.'tpl');

		if (!$ajax)
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispModuleAdminFileBox');
			$this->setRedirectUrl($returnUrl);
			return;
		}
		else
		{
			if($output) $this->add('save_filename', $output->get('save_filename'));
			else $this->add('save_filename', '');
		}
	}

	/**
	 * @brief Update a file into the file box
	 */
	function updateModuleFileBox($vars)
	{
		$args = new stdClass;
		// have file
		if($vars->addfile['tmp_name'] && is_uploaded_file($vars->addfile['tmp_name']))
		{
			$oModuleModel = getModel('module');
			$output = $oModuleModel->getModuleFileBox($vars->module_filebox_srl);
			FileHandler::removeFile($output->data->filename);

			$path = $oModuleModel->getModuleFileBoxPath($vars->module_filebox_srl);
			FileHandler::makeDir($path);

			$save_filename = sprintf('%s%s.%s',$path, $vars->module_filebox_srl, $ext);
			$tmp = $vars->addfile['tmp_name'];

			// Check uploaded file
			if(!checkUploadedFile($tmp)) return false;

			if(!@move_uploaded_file($tmp, $save_filename))
			{
				return false;
			}

			$args->fileextension = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
			$args->filename = $save_filename;
			$args->filesize = $vars->addfile['size'];
		}

		$args->module_filebox_srl = $vars->module_filebox_srl;
		$args->comment = $vars->comment;

		// FIXME $args ??

		return executeQuery('module.updateModuleFileBox', $vars);
	}


	/**
	 * @brief Add a file into the file box
	 */
	function insertModuleFileBox($vars)
	{
		// set module_filebox_srl
		$vars->module_filebox_srl = getNextSequence();

		// get file path
		$oModuleModel = getModel('module');
		$path = $oModuleModel->getModuleFileBoxPath($vars->module_filebox_srl);
		FileHandler::makeDir($path);
		$save_filename = sprintf('%s%s.%s',$path, $vars->module_filebox_srl, $vars->ext);
		$tmp = $vars->addfile['tmp_name'];

		// Check uploaded file
		if(!checkUploadedFile($tmp)) return false;

		// upload
		if(!@move_uploaded_file($tmp, $save_filename))
		{
			return false;
		}

		// insert
		$args = new stdClass;
		$args->module_filebox_srl = $vars->module_filebox_srl;
		$args->member_srl = $vars->member_srl;
		$args->comment = $vars->comment;
		$args->filename = $save_filename;
		$args->fileextension = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
		$args->filesize = $vars->addfile['size'];

		$output = executeQuery('module.insertModuleFileBox', $args);
		$output->add('save_filename', $save_filename);
		return $output;
	}


	/**
	 * @brief Delete a file from the file box
	 */
	function procModuleFileBoxDelete()
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return new Object(-1, 'msg_not_permitted');

		$module_filebox_srl = Context::get('module_filebox_srl');
		if(!$module_filebox_srl) return new Object(-1, 'msg_invalid_request');
		$vars = new stdClass();
		$vars->module_filebox_srl = $module_filebox_srl;
		$output = $this->deleteModuleFileBox($vars);
		if(!$output->toBool()) return $output;
	}

	function deleteModuleFileBox($vars)
	{
		// delete real file
		$oModuleModel = getModel('module');
		$output = $oModuleModel->getModuleFileBox($vars->module_filebox_srl);
		FileHandler::removeFile($output->data->filename);

		$args = new stdClass();
		$args->module_filebox_srl = $vars->module_filebox_srl;
		return executeQuery('module.deleteModuleFileBox', $args);
	}

	/**
	 * @brief function of locking (timeout is in seconds)
	 */
	function lock($lock_name, $timeout, $member_srl = null)
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

	function unlockTimeoutPassed()
	{
		executeQuery('module.deleteLocksTimeoutPassed');
	}

	function unlock($lock_name, $deadline)
	{
		$args = new stdClass;
		$args->lock_name = $lock_name;
		$args->deadline = $deadline;
		$output = executeQuery('module.deleteLock', $args);
		return $output;
	}

	function updateModuleInSites($site_srls, $args)
	{
		$args = new stdClass;
		$args->site_srls = $site_srls;
		$output = executeQuery('module.updateModuleInSites', $args);

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		return $output;
	}
}
/* End of file module.controller.php */
/* Location: ./modules/module/module.controller.php */
