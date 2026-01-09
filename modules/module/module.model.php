<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleModel
 * @author NAVER (developers@xpressengine.com)
 * @brief Model class of module module
 */
class ModuleModel extends Module
{
	/**
	 * @brief Check if mid is available
	 */
	public static function isIDExists($id, $module = null)
	{
		if (!Rhymix\Modules\Module\Models\Prefix::isValidPrefix((string)$id, $module))
		{
			return true;
		}
		if (Rhymix\Modules\Module\Models\Prefix::exists((string)$id))
		{
			return true;
		}
		return false;
	}

	/**
	 * @brief Get all domains
	 */
	public static function getAllDomains($count = 20, $page = 1)
	{
		return Rhymix\Modules\Module\Models\Domain::getDomainList($count, $page);
	}

	/**
	 * @brief Get default domain information
	 */
	public static function getDefaultDomainInfo()
	{
		return Rhymix\Modules\Module\Models\Domain::getDefaultDomain() ?: false;
	}

	/**
	 * @brief Get site information by domain_srl
	 */
	public static function getSiteInfo($domain_srl)
	{
		return Rhymix\Modules\Module\Models\Domain::getDomain((int)$domain_srl) ?: false;
	}

	/**
	 * @brief Get site information by domain name
	 */
	public static function getSiteInfoByDomain($domain_name)
	{
		$domain_name = (string)$domain_name;
		if (str_contains($domain_name, '/'))
		{
			$domain_name = Rhymix\Framework\URL::getDomainFromURL($domain_name);
		}

		return Rhymix\Modules\Module\Models\Domain::getDomainByDomainName($domain_name) ?: false;
	}

	/**
	 * Get module information corresponding to module_srl
	 *
	 * @param int $module_srl
	 * @return ?object
	 */
	public static function getModuleInfoByModuleSrl($module_srl)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::getModuleInfo((int)$module_srl);
	}

	/**
	 * Shortcut to getModuleInfoByModuleSrl()
	 *
	 * @param int $module_srl
	 * @return ?object
	 */
	public static function getModuleInfo($module_srl)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::getModuleInfo((int)$module_srl);
	}

	/**
	 * Get module information by mid
	 *
	 * @param string $mid
	 * @return ?object
	 */
	public static function getModuleInfoByMid($mid)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::getModuleInfoByPrefix((string)$mid);
	}

	/**
	 * Get module information with document_srl
	 *
	 * @param int $document_srl
	 * @return ?object
	 */
	public static function getModuleInfoByDocumentSrl($document_srl)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::getModuleInfoByDocumentSrl((int)$document_srl);
	}

	/**
	 * @brief Get the default mid according to the domain
	 */
	public static function getDefaultMid($domain = '')
	{
		return Rhymix\Modules\Module\Models\Domain::getDefaultDomainWithModuleInfo($domain ?: null);
	}

	/**
	 * Get module info by menu_item_srl.
	 *
	 * @params int $menu_item_srl
	 *
	 * @return Object $moduleInfo
	 */
	public function getModuleInfoByMenuItemSrl($menu_item_srl = 0)
	{
		$menuItemSrl = Context::get('menu_item_srl');
		$menuItemSrl = (!$menuItemSrl) ? $menu_item_srl : $menuItemSrl;

		if(!$menuItemSrl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		$args = new stdClass();
		$args->menu_item_srl = $menuItemSrl;
		$output = executeQuery('module.getModuleInfoByMenuItemSrl', $args, [], 'auto', Rhymix\Modules\Module\Models\ModuleInstance::class);
		if(!$output->toBool())
		{
			return $output;
		}

		$moduleInfo = $output->data;
		$mid = $moduleInfo->mid;

		$moduleInfo->designSettings = new stdClass();
		$moduleInfo->designSettings->layout = new stdClass();
		$moduleInfo->designSettings->skin = new stdClass();

		$oLayoutAdminModel = getAdminModel('layout');
		$layoutSrlPc = ($moduleInfo->layout_srl == -1) ? $oLayoutAdminModel->getSiteDefaultLayout('P') : $moduleInfo->layout_srl;
		$layoutSrlMobile = ($moduleInfo->mlayout_srl == -1) ? $oLayoutAdminModel->getSiteDefaultLayout('M') : $moduleInfo->mlayout_srl;
		$skinNamePc = ($moduleInfo->is_skin_fix == 'N') ? self::getModuleDefaultSkin($moduleInfo->module, 'P') : $moduleInfo->skin;
		$skinNameMobile = ($moduleInfo->is_mskin_fix == 'N') ? self::getModuleDefaultSkin($moduleInfo->module, $moduleInfo->mskin === '/USE_RESPONSIVE/' ? 'P' : 'M') : $moduleInfo->mskin;

		$oLayoutModel = getModel('layout');
		$layoutInfoPc = $layoutSrlPc ? $oLayoutModel->getLayoutRawData($layoutSrlPc, array('title')) : NULL;
		$layoutInfoMobile = $layoutSrlMobile ? $oLayoutModel->getLayoutRawData($layoutSrlMobile, array('title')) : NULL;
		$skinInfoPc = self::loadSkinInfo(Modulehandler::getModulePath($moduleInfo->module), $skinNamePc);
		$skinInfoMobile = self::loadSkinInfo(Modulehandler::getModulePath($moduleInfo->module), $skinNameMobile, 'm.skins');
		if(!$skinInfoPc)
		{
			$skinInfoPc = new stdClass();
			$skinInfoPc->title = $skinNamePc;
		}
		if(!$skinInfoMobile)
		{
			$skinInfoMobile = new stdClass();
			$skinInfoMobile->title = $skinNameMobile;
		}

		$moduleInfo->designSettings->layout->pcIsDefault = $moduleInfo->layout_srl == -1 ? 1 : 0;
		$moduleInfo->designSettings->layout->pc = $layoutInfoPc->title ?? null;
		$moduleInfo->designSettings->layout->mobileIsDefault = $moduleInfo->mlayout_srl == -1 ? 1 : 0;
		$moduleInfo->designSettings->layout->mobile = $layoutInfoMobile->title ?? null;
		$moduleInfo->designSettings->skin->pcIsDefault = $moduleInfo->is_skin_fix == 'N' ? 1 : 0;
		$moduleInfo->designSettings->skin->pc = $skinInfoPc->title ?? null;
		$moduleInfo->designSettings->skin->mobileIsDefault = ($moduleInfo->is_mskin_fix == 'N' && $moduleInfo->mskin !== '/USE_RESPONSIVE/') ? 1 : 0;
		$moduleInfo->designSettings->skin->mobile = $skinInfoMobile->title ?? null;

		$module_srl = Rhymix\Framework\Cache::get('site_and_module:module_srl:' . $mid);
		if($module_srl)
		{
			$mid_info = Rhymix\Framework\Cache::get('site_and_module:module_info:' . $module_srl);
		}
		else
		{
			$mid_info = null;
		}

		if($mid_info === null)
		{
			Rhymix\Framework\Cache::set('site_and_module:module_srl:' . $mid, $output->data->module_srl, 0, true);
			Rhymix\Framework\Cache::set('site_and_module:module_info:' . $output->data->module_srl, $moduleInfo, 0, true);
		}
		else
		{
			$mid_info->designSettings = $moduleInfo->designSettings;
			$moduleInfo = $mid_info;
		}

		$moduleInfo = self::addModuleExtraVars($moduleInfo);

		if($moduleInfo->module == 'page' && $moduleInfo->page_type != 'ARTICLE')
		{
			unset($moduleInfo->skin);
			unset($moduleInfo->mskin);
		}

		$this->add('module_info_by_menu_item_srl', $moduleInfo);

		return $moduleInfo;
	}

	/**
	 * @brief Get module information corresponding to layout_srl
	 */
	public static function getModulesInfoByLayout($layout_srl, $columnList = array())
	{
		// Imported data
		$args = new stdClass;
		$args->layout_srl = $layout_srl;
		$output = executeQueryArray('module.getModulesByLayout', $args, [], Rhymix\Modules\Module\Models\ModuleInstance::class);

		$count = count($output->data);

		$modules = array();
		for($i=0;$i<$count;$i++)
		{
			$modules[] = $output->data[$i];
		}
		return self::addModuleExtraVars($modules);
	}

	/**
	 * @brief Get module information corresponding to multiple module_srls
	 */
	public static function getModulesInfo($module_srls, $columnList = array())
	{
		if (!is_array($module_srls))
		{
			$module_srls = explode(',', $module_srls);
		}

		return Rhymix\Modules\Module\Models\ModuleInfo::getModuleInfos($module_srls, (array)$columnList);
	}

	/**
	 * @brief Add extra vars to the module basic information
	 */
	public static function addModuleExtraVars($module_info)
	{
		if (is_array($module_info))
		{
			return Rhymix\Modules\Module\Models\ModuleInfo::addExtraVars($module_info);
		}
		else
		{
			$module_infos = [$module_info];
			Rhymix\Modules\Module\Models\ModuleInfo::addExtraVars($module_infos);
			return $module_infos[0];
		}
	}

	/**
	 * Get the next available mid with the given prefix.
	 *
	 * @param string $prefix
	 * @return string
	 */
	public static function getNextAvailableMid($prefix)
	{
		return Rhymix\Modules\Module\Models\Prefix::getNextAvailablePrefix((string)$prefix);
	}

	/**
	 * @brief Get a complete list of mid, which is created in the DB
	 */
	public static function getMidList($args = null, $columnList = array())
	{
		if (is_array($args))
		{
			$args = count($args) ? (object)$args : null;
		}
		if (is_object($args) && isset($args->site_srl))
		{
			unset($args->site_srl);
		}
		if (is_scalar($args))
		{
			$args = null;
		}

		return Rhymix\Modules\Module\Models\ModuleInfo::getModuleInstanceList($args, (array)$columnList);
	}

	/**
	 * @brief Get a complete list of module_srl, which is created in the DB
	 * @deprecated Use getMidList() instead.
	 */
	public static function getModuleSrlList($args = null, $columnList = array())
	{
		return self::getMidList($args, $columnList);
	}

	/**
	 * @brief Return the number of modules which are registered on a virtual site
	 */
	public static function getModuleCount($site_srl = 0, $module = null)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::getModuleInstanceCount($module);
	}

	/**
	 * @brief already instance created module list
	 */
	public static function getModuleListByInstance($site_srl = 0, $columnList = array())
	{
		$args = new stdClass();
		$output = executeQueryArray('module.getModuleListByInstance', $args, $columnList);
		return $output;
	}

	/**
	 * @brief Return an array of module_srl corresponding to a mid list
	 *
	 * @param int|string|array $mid
	 * @param bool $assoc
	 * @return array
	 */
	public static function getModuleSrlByMid($mid, $assoc = false)
	{
		if (!$mid)
		{
			return [];
		}
		if (!is_array($mid))
		{
			$mid = explode(',', $mid);
		}

		$result = Rhymix\Modules\Module\Models\Prefix::getModuleSrlByPrefix($mid);
		return $assoc ? $result : array_values($result);
	}

	/**
	 * @brief Return mid corresponding to a module_srl list
	 *
	 * @param int|array $module_srl
	 * @return string|array
	 */
	public static function getMidByModuleSrl($module_srl)
	{
		if (is_array($module_srl))
		{
			return Rhymix\Modules\Module\Models\Prefix::getPrefixByModuleSrl($module_srl);
		}
		else
		{
			return array_first(Rhymix\Modules\Module\Models\Prefix::getPrefixByModuleSrl([$module_srl]));
		}
	}

	/**
	 * Return the domain (including scheme and port) by module_srl
	 *
	 * @param int $module_srl
	 * @return ?string
	 */
	public static function getDomainByModuleSrl(int $module_srl): ?string
	{
		return Rhymix\Modules\Module\Models\Domain::getDomainPrefixByModuleSrl($module_srl);
	}

	/**
	 * @brief Get forward value by the value of act
	 *
	 * @param ?string $act
	 * @return array|object|null
	 */
	public static function getActionForward($act = null)
	{
		if ($act === null)
		{
			return Rhymix\Modules\Module\Models\GlobalRoute::getAllGlobalRoutes();
		}
		else
		{
			return Rhymix\Modules\Module\Models\GlobalRoute::getGlobalRoute($act);
		}
	}

	/**
	 * @brief Get trigger functions
	 */
	public static function getTriggerFunctions($trigger_name, $called_position)
	{
		if(isset($GLOBALS['__trigger_functions__'][$trigger_name][$called_position]))
		{
			return $GLOBALS['__trigger_functions__'][$trigger_name][$called_position];
		}
		else
		{
			return array();
		}
	}

	/**
	 * @brief Get a list of all triggers on the trigger_name
	 */
	public static function getTriggers($trigger_name, $called_position)
	{
		if(!isset($GLOBALS['__triggers__']))
		{
			$triggers = Rhymix\Framework\Cache::get('triggers');
			if($triggers === null)
			{
				$output = executeQueryArray('module.getTriggers');
				$triggers = $output->data;
				if($output->toBool())
				{
					Rhymix\Framework\Cache::set('triggers', $triggers, 0, true);
				}
			}

			$triggers = $triggers ?: array();
			foreach($triggers as $item)
			{
				$GLOBALS['__triggers__'][$item->trigger_name][$item->called_position][] = $item;
			}
		}

		return $GLOBALS['__triggers__'][$trigger_name][$called_position] ?? [];
	}

	/**
	 * @brief Get specific triggers from the trigger_name
	 */
	public static function getTrigger($trigger_name, $module, $type, $called_method, $called_position)
	{
		$triggers = self::getTriggers($trigger_name, $called_position);

		if($triggers && is_array($triggers))
		{
			foreach($triggers as $item)
			{
				if($item->module == $module && $item->type == $type && $item->called_method == $called_method)
				{
					return $item;
				}
			}
		}

		return NULL;
	}

	/**
	 * @brief Get module extend
	 *
	 * @deprecated
	 */
	public static function getModuleExtend($parent_module, $type, $kind = '')
	{
		return false;
	}

	/**
	 * @brief Get all the module extend
	 *
	 * @deprecated
	 *
	 */
	public static function loadModuleExtends()
	{
		return array();
	}

	/**
	 * @brief Get information from conf/info.xml
	 */
	public static function getModuleInfoXml($module)
	{
		return Rhymix\Modules\Module\Models\ModuleDefinition::getModuleInfoXml((string)$module);
	}

	/**
	 * @brief Return permisson and action data by conf/module.xml
	 */
	public static function getModuleActionXml($module)
	{
		return Rhymix\Modules\Module\Models\ModuleDefinition::getModuleActionXml((string)$module);
	}

	/**
	 * Get a skin list for js API.
	 * return void
	 */
	public function getModuleSkinInfoList()
	{
		$module = Context::get('module_type');

		if($module == 'ARTICLE')
		{
			$module = 'page';
		}

		$skinType = Context::get('skin_type');

		$path = ModuleHandler::getModulePath($module);
		$dir = ($skinType == 'M') ? 'm.skins' : 'skins';
		$skin_list = self::getSkins($path, $dir);

		$this->add('skin_info_list', $skin_list);
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
		return Rhymix\Modules\Module\Models\ModuleDefinition::getSkins($path, $dir);
	}

	/**
	 * @brief Get skin information on a specific location
	 */
	public static function loadSkinInfo($path, $skin, $dir = 'skins')
	{
		return Rhymix\Modules\Module\Models\ModuleDefinition::loadSkinInfo($path, $skin, $dir);
	}

	/**
	 * Get global config for a module.
	 *
	 * @param string $module
	 * @return mixed
	 */
	public static function getModuleConfig($module)
	{
		return Rhymix\Modules\Module\Models\ModuleConfig::getModuleConfig((string)$module);
	}

	/**
	 * Get an independent section of module config.
	 *
	 * @param string $module
	 * @param string $section
	 * @return mixed
	 */
	public static function getModuleSectionConfig($module, $section)
	{
		if (!$module || !is_scalar($module) || !$section || !is_scalar($section))
		{
			return null;
		}
		return Rhymix\Modules\Module\Models\ModuleConfig::getModuleConfig("$module:$section");
	}

	/**
	 * Get config for a specific module_srl.
	 *
	 * @param string module
	 * @param int $module_srl
	 * @return mixed
	 */
	public static function getModulePartConfig($module, $module_srl)
	{
		if (!$module || !is_scalar($module) || !$module_srl || !is_scalar($module_srl))
		{
			return null;
		}
		return Rhymix\Modules\Module\Models\ModuleConfig::getModulePartConfig((string)$module, (int)$module_srl);
	}

	/**
	 * @brief Get all of module configurations for each mid
	 */
	public static function getModulePartConfigs($module)
	{
		return Rhymix\Modules\Module\Models\ModuleConfig::getModulePartConfigs((string)$module);
	}

	/**
	 * @brief Get content from the module category
	 */
	public static function getModuleCategory($module_category_srl)
	{
		return Rhymix\Modules\Module\Models\ModuleCategory::getModuleCategory((int)$module_category_srl);
	}

	/**
	 * @brief Get a list of module category
	 */
	public static function getModuleCategories($module_category_srl = array())
	{
		return Rhymix\Modules\Module\Models\ModuleCategory::getModuleCategories((array)$module_category_srl);
	}

	/**
	 * @brief Get xml information of the module
	 */
	public static function getModulesXmlInfo()
	{
		return Rhymix\Modules\Module\Models\ModuleDefinition::getInstalledModuleList();
	}

	/**
	 * Get module base class
	 *
	 * This method supports namespaced modules as well as XE-compatible modules.
	 *
	 * @param string $module_name
	 * @return ModuleObject|null
	 */
	public static function getModuleDefaultClass(string $module_name, ?object $module_action_info = null)
	{
		return Rhymix\Modules\Module\Models\ModuleDefinition::getDefaultClass((string)$module_name, $module_action_info);
	}

	/**
	 * Get module install class
	 *
	 * This method supports namespaced modules as well as XE-compatible modules.
	 *
	 * @param string $module_name
	 * @return ModuleObject|null
	 */
	public static function getModuleInstallClass(string $module_name, ?object $module_action_info = null)
	{
		return Rhymix\Modules\Module\Models\ModuleDefinition::getInstallClass((string)$module_name, $module_action_info);
	}

	public static function checkNeedInstall($module_name)
	{
		return Rhymix\Modules\Module\Models\Updater::needsInstall((string)$module_name);
	}

	public static function checkNeedUpdate($module_name)
	{
		return Rhymix\Modules\Module\Models\Updater::needsUpdate((string)$module_name);
	}

	/**
	 * @brief 업데이트 적용 여부 확인
	 * @param array|string $update_id
	 * @return Boolean
	 */
	public static function needUpdate($update_id)
	{
		if(!is_array($update_id)) $update_id = array($update_id);

		$args = new stdClass();
		$args->update_id = implode(',', $update_id);
		$output = executeQueryArray('module.getModuleUpdateLog', $args);

		if(!!$output->error) return false;
		if(!$output->data) $output->data = array();
		if(count($update_id) === count($output->data)) return false;

		return true;
	}

	/**
	 * @brief Get a type and information of the module
	 */
	public static function getModuleList()
	{
		return Rhymix\Modules\Module\Models\ModuleDefinition::getInstalledModuleDetails();
	}

	/**
	 * @brief Combine module_srls with domain of sites
	 * Because XE DBHandler doesn't support left outer join,
	 * it should be as same as $Output->data[]->module_srl.
	 */
	public static function syncModuleToSite($data)
	{
		if (!is_array($data))
		{
			$data = [$data];
		}

		$module_srls = [];
		foreach ($data as $module_info)
		{
			if (empty($module_info->domain))
			{
				$module_srls[$module_info->module_srl] = null;
			}
		}
		if (!count($module_srls))
		{
			return;
		}

		$output = executeQueryArray('module.getModuleSites', ['module_srls' => array_keys($module_srls)]);
		foreach ($output->data as $val)
		{
			$module_srls[$val->module_srl] = $val;
		}
		foreach ($data as $val)
		{
			if (isset($module_srls[$val->module_srl]->domain))
			{
				$val->domain = $module_srls[$val->module_srl]->domain;
			}
			elseif (!isset($val->domain))
			{
				$val->domain = null;
			}
		}
	}

	/**
	 * @brief Check if it is an administrator of site_module_info
	 *
	 * @deprecated
	 */
	public static function isSiteAdmin($member_info)
	{
		if ($member_info && isset($member_info->is_admin) && $member_info->is_admin == 'Y')
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief Get admin information of the site
	 *
	 * @deprecated
	 */
	public static function getSiteAdmin()
	{
		return array();
	}

	/**
	 * Check if a member is a module administrator
	 *
	 * @return bool
	 */
	public static function isModuleAdmin($member_info, $module_srl = null)
	{
		if (!is_object($member_info))
		{
			return false;
		}
		if ($module_srl !== null && !is_scalar($module_srl))
		{
			return false;
		}
		if ($module_srl !== null)
		{
			$module_srl = (int)$module_srl;
		}

		return Rhymix\Modules\Module\Models\ModuleInfo::isManager($member_info, $module_srl);
	}

	/**
	 * @brief Get admin ID of the module
	 */
	public static function getAdminId($module_srl)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::getManagers((int)$module_srl);
	}

	/**
	 * @brief Get extra vars of the module
	 * Extra information, not in the modules table
	 */
	public static function getModuleExtraVars($list_module_srl)
	{
		if (!is_array($list_module_srl))
		{
			$list_module_srl = [$list_module_srl];
		}

		return Rhymix\Modules\Module\Models\ModuleInfo::getExtraVars($list_module_srl);
	}

	/**
	 * @brief Get skin information of the module
	 */
	public static function getModuleSkinVars($module_srl)
	{
		return get_object_vars(Rhymix\Modules\Module\Models\ModuleInfo::getSkinVars((int)$module_srl, 'P'));
	}

	/**
	 * Get mobile skin information of the module
	 * @param $module_srl Sequence of module
	 * @return array
	 */
	public static function getModuleMobileSkinVars($module_srl)
	{
		return get_object_vars(Rhymix\Modules\Module\Models\ModuleInfo::getSkinVars((int)$module_srl, 'M'));
	}

	/**
	 * Get default skin name
	 */
	public static function getModuleDefaultSkin($module_name, $skin_type = 'P', $site_srl = 0, $updateCache = true)
	{
		$target = ($skin_type == 'M') ? 'mskin' : 'skin';
		$designInfoFile = RX_BASEDIR.'files/site_design/design_0.php';
		if(is_readable($designInfoFile))
		{
			include($designInfoFile);

			$skinName = $designInfo->module->{$module_name}->{$target} ?? null;
		}
		if(!isset($designInfo))
		{
			$designInfo = new stdClass();
		}
		if(!$skinName)
		{
			$dir = ($skin_type == 'M') ? 'm.skins/' : 'skins/';
			$moduleSkinPath = ModuleHandler::getModulePath($module_name).$dir;

			if(is_dir($moduleSkinPath.'default'))
			{
				$skinName = 'default';
			}
			else if(is_dir($moduleSkinPath.'xe_default'))
			{
				$skinName = 'xe_default';
			}
			else
			{
				$skins = FileHandler::readDir($moduleSkinPath);
				if(count($skins) > 0)
				{
					$skinName = $skins[0];
				}
				else
				{
					$skinName = NULL;
				}
			}

			if($updateCache && $skinName)
			{
				if(!isset($designInfo->module))
				{
					$designInfo->module = new stdClass();
				}
				if(!isset($designInfo->module->{$module_name})) $designInfo->module->{$module_name} = new stdClass();
				$designInfo->module->{$module_name}->{$target} = $skinName;

				$oAdminController = getAdminController('admin');
				$oAdminController->makeDefaultDesignFile($designInfo);
			}
		}

		return $skinName;
	}

	/**
	 * @brief Combine skin information with module information
	 */
	public static function syncSkinInfoToModuleInfo($module_info)
	{
		if ($module_info instanceof Rhymix\Modules\Module\Models\ModuleInfo)
		{
			$module_info->addSkinVars();
		}
		elseif (isset($module_info->module_srl) && $module_info->module_srl)
		{
			$mode = (\Mobile::isFromMobilePhone() && $module_info->mskin !== '/USE_RESPONSIVE/') ? 'M' : 'P';
			$skin_vars = Rhymix\Modules\Module\Models\ModuleInfo::getSkinVars($module_info->module_srl, $mode);
			foreach ($skin_vars as $name => $val)
			{
				if (!isset($module_info->{$name}))
				{
					$module_info->{$name} = $val->value;
				}
			}
		}
	}

	/**
	 * Combine skin information with module information
	 * @param $module_info Module information
	 * @deprecated
	 */
	public static function syncMobileSkinInfoToModuleInfo($module_info)
	{
		if (!$module_info->module_srl)
		{
			return;
		}

		$skin_vars = Rhymix\Modules\Module\Models\ModuleInfo::getSkinVars($module_info->module_srl, 'M');
		foreach ($skin_vars as $name => $val)
		{
			if (!isset($module_info->{$name}))
			{
				$module_info->{$name} = $val->value;
			}
		}
	}

	/**
	 * Get privileges(granted) information by using module info, xml info and member info
	 *
	 * @param object $module_info
	 * @param object $member_info
	 * @param ?object $xml_info
	 * @return Rhymix\Modules\Module\Models\Permission
	 */
	public static function getGrant($module_info, $member_info, $xml_info = null)
	{
		if (!is_object($module_info))
		{
			$module_info = new stdClass;
			$module_info->module = '_';
			$module_info->module_srl = 0;
		}
		if (!is_object($member_info))
		{
			$member_info = new stdClass;
			$member_info->member_srl = 0;
		}

		return Rhymix\Modules\Module\Models\Permission::create($module_info, $member_info, $xml_info);
	}

	/**
	 * Get the list of modules that the member can access.
	 *
	 * @param object $member_info
	 * @return array
	 */
	public static function getAccessibleModuleList($member_info = null)
	{
		$member_info = $member_info ?? Rhymix\Framework\Session::getMemberInfo();
		return Rhymix\Modules\Module\Models\Permission::listModulesAccessibleBy($member_info);
	}

	/**
	 * Search all modules to find manager privilege of the member.
	 *
	 * @deprecated
	 * @param object $member_info member information
	 * @param string $module module name. if used, search scope is same module
	 * @return mixed success : object, fail : false
	 */
	public static function findManagerPrivilege($member_info, $module = null)
	{
		$member_info = $member_info ?? Rhymix\Framework\Session::getMemberInfo();
		if (!$member_info || !$member_info->member_srl)
		{
			return false;
		}

		$module = $module ? (string)$module : null;
		$result = Rhymix\Modules\Module\Models\Permission::listModulesManagedBy($member_info, $module);
		if ($result)
		{
			$module_info = array_first($result);
			return Rhymix\Modules\Module\Models\Permission::create($module_info, $member_info);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get privileges(granted) information of the member for target module by target_srl
	 *
	 * @param string $target_srl as module_srl. It may be a reference serial number
	 * @param string $type module name. get module_srl from module
	 * @param object $member_info member information
	 * @return mixed success : object, fail : false
	 * */
	public static function getPrivilegesBySrl($target_srl, $type = null, $member_info = null)
	{
		if(empty($target_srl = trim($target_srl)) || !preg_match('/^([0-9]+)$/', $target_srl) && $type != 'module')
		{
			return false;
		}

		if($type)
		{
			if($type == 'document')
			{
				$target_srl = DocumentModel::getDocument($target_srl, false, false)->get('module_srl');
			}
			else if($type == 'comment')
			{
				$target_srl = CommentModel::getComment($target_srl)->get('module_srl');
			}
			else if($type == 'file')
			{
				$target_srl = FileModel::getFile($target_srl)->module_srl;
			}
			else if($type == 'module')
			{
				$module_info = self::getModuleInfoByMid($target_srl);
			}
		}

		if(!isset($module_info))
		{
			$module_info = self::getModuleInfoByModuleSrl($target_srl);
		}

		if(!$module_info->module_srl)
		{
			return false;
		}

		if(!$member_info)
		{
			$member_info = Context::get('logged_info');
		}

		return self::getGrant($module_info, $member_info);
	}

	/**
	 * @brief Get module grants
	 */
	public static function getModuleGrants($module_srl)
	{
		return Rhymix\Modules\Module\Models\ModuleInfo::getGrants((int)$module_srl);
	}

	public static function getModuleFileBox($module_filebox_srl)
	{
		return Rhymix\Modules\Module\Models\Filebox::getFile((int)$module_filebox_srl);
	}

	public static function getModuleFileBoxList()
	{
		return Rhymix\Modules\Module\Models\Filebox::getFileList(5, intval(Context::get('page') ?? 1));
	}

	public static function unserializeAttributes($module_filebox_list)
	{
		// no-op
	}

	public function getFileBoxListHtml()
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin)
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}

		$link = parse_url($_SERVER["HTTP_REFERER"]);
		$link_params = explode('&',$link['query']);
		foreach ($link_params as $param)
		{
			$param = explode("=",$param);
			if($param[0] == 'selected_widget') $selected_widget = $param[1];
		}
		if($selected_widget) $widget_info = WidgetModel::getWidgetInfo($selected_widget);
		Context::set('allow_multiple', $widget_info->extra_var->images->allow_multiple);

		$output = self::getModuleFileBoxList();
		Context::set('filebox_list', $output->data);

		$page = Context::get('page');
		if (!$page) $page = 1;
		Context::set('page', $page);
		Context::set('page_navigation', $output->page_navigation);

		$security = new Security();
		$security->encodeHTML('filebox_list..comment', 'filebox_list..attributes.');

		$oTemplate = TemplateHandler::getInstance();
		$html = $oTemplate->compile(RX_BASEDIR . 'modules/module/tpl/', 'filebox_list_html');

		$this->add('html', $html);
	}

	public static function getModuleFileBoxPath($module_filebox_srl)
	{
		return Rhymix\Modules\Module\Models\Filebox::getStoragePath((int)$module_filebox_srl);
	}

	/**
	 * @brief Return ruleset cache file path
	 * @param module, act
	 */
	public static function getValidatorFilePath($module, $ruleset, $mid=null)
	{
		// load dynamic ruleset xml file
		if(strpos($ruleset, '@') !== false)
		{
			$rulsetFile = str_replace('@', '', $ruleset);
			$xml_file = sprintf('./files/ruleset/%s.xml', $rulsetFile);
			return FileHandler::getRealPath($xml_file);
		}
		else if (strpos($ruleset, '#') !== false)
		{
			$rulsetFile = str_replace('#', '', $ruleset).'.'.$mid;
			$xml_file = sprintf('./files/ruleset/%s.xml', $rulsetFile);
			if(is_readable($xml_file))
				return FileHandler::getRealPath($xml_file);
			else{
				$ruleset = str_replace('#', '', $ruleset);
			}

		}
		// Get a path of the requested module. Return if not exists.
		$class_path = ModuleHandler::getModulePath($module);
		if(!$class_path) return;

		// Check if module.xml exists in the path. Return if not exist
		$xml_file = sprintf("%sruleset/%s.xml", $class_path, $ruleset);
		if(!file_exists($xml_file)) return;

		return $xml_file;
	}

	public function getLangListByLangcodeForAutoComplete()
	{
		$args = new stdClass;
		$args->page = 1; // /< Page
		$args->list_count = 100; // /< the number of posts to display on a single page
		$args->page_count = 5; // /< the number of pages that appear in the page navigation
		$args->sort_index = 'name';
		$args->order_type = 'asc';
		$args->search_keyword = Context::get('search_keyword'); // /< keyword to search*/
		$output = executeQueryArray('module.getLangListByLangcode', $args);

		$list = array();

		if($output->toBool())
		{
			foreach((array)$output->data as $code_info)
			{
				unset($codeInfo);
				$codeInfo = array('name'=>'$user_lang->'.$code_info->name, 'value'=>$code_info->value);
				$list[] = $codeInfo;
			}
		}
		$this->add('results', $list);
	}

	public function getLangByLangcode()
	{
		$langCode = Context::get('langCode');
		if (!$langCode) return;

		$langCode = Context::replaceUserLang($langCode);
		$this->add('lang', $langCode);
	}
}
/* End of file module.model.php */
/* Location: ./modules/module/module.model.php */
