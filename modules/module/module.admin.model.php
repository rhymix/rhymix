<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleAdminModel
 * @author NAVER (developers@xpressengine.com)
 * @version 0.1
 * @brief AdminModel class of the "module" module
 */
class moduleAdminModel extends module
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Return a list of target modules by using module_srls separated by comma(,)
	 * Used in the ModuleSelector
	 */
	function getModuleAdminModuleList()
	{
		$oModuleController = getController('module');
		$oModuleModel = getModel('module');
		$args = new stdClass;
		$args->module_srls = Context::get('module_srls');
		$output = executeQueryArray('module.getModulesInfo', $args);
		if(!$output->toBool() || !$output->data) return new BaseObject();

		foreach($output->data as $key => $val)
		{
			$info_xml = $oModuleModel->getModuleInfoXml($val->module);
			$oModuleController->replaceDefinedLangCode($val->browser_title);
			$list[$val->module_srl] = array('module_srl'=>$val->module_srl,'mid'=>$val->mid,'browser_title'=>$val->browser_title, 'module_name' => $info_xml->title);
		}
		$modules = explode(',',$args->module_srls);
		$module_list = [];
		foreach ($modules as $module_srl)
		{
			$module_list[$module_srl] = $list[$module_srl];
		}

		$this->add('id', Context::get('id'));
		$this->add('module_list', $module_list);
	}

	function getModuleMidList($args)
	{
		$args->list_count = 20;
		$args->page_count = 10;
		$output = executeQueryArray('module.getModuleMidList', $args);
		if(!$output->toBool()) return $output;

		ModuleModel::syncModuleToSite($output->data);

		return $output;
	}

	function getSelectedManageHTML($grantList, $tabChoice = array(), $modulePath = NULL)
	{
		if($modulePath)
		{
			// get the skins path
			$oModuleModel = getModel('module');
			$skin_list = $oModuleModel->getSkins($modulePath);
			Context::set('skin_list',$skin_list);

			$mskin_list = $oModuleModel->getSkins($modulePath, "m.skins");
			Context::set('mskin_list', $mskin_list);
		}

		// get the layouts path
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		$security = new Security();
		$security->encodeHTML('layout_list..layout', 'layout_list..title');
		$security->encodeHTML('mlayout_list..layout', 'mlayout_list..title');
		$security->encodeHTML('skin_list..title');
		$security->encodeHTML('mskin_list..title');

		$grant_list =new stdClass();
		// Grant virtual permission for access and manager
		if(!$grantList)
		{
			$grantList =new stdClass();
		}
		$grantList->access = new stdClass();
		$grantList->access->title = lang('grant_access');
		$grantList->access->default = 'guest';
		if(countobj($grantList))
		{
			foreach($grantList as $key => $val)
			{
				if(!$val->default) $val->default = 'guest';
				if($val->default == 'root') $val->default = 'manager';
				$grant_list->{$key} = $val;
			}
		}
		$grant_list->manager = new stdClass();
		$grant_list->manager->title = lang('grant_manager');
		$grant_list->manager->default = 'manager';
		Context::set('grant_list', $grant_list);

		// Get a list of groups
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups(0);
		Context::set('group_list', $group_list);

		Context::set('module_srls', 'dummy');
		$content = '';
		// Call a trigger for additional settings
		// Considering uses in the other modules, trigger name cen be publicly used
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
		Context::set('setup_content', $content);

		if(count($tabChoice) == 0)
		{
			$tabChoice = array('tab1'=>1, 'tab2'=>1, 'tab3'=>1);
		}
		Context::set('tabChoice', $tabChoice);

		// Get information of module_grants
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($this->module_path.'tpl', 'include.manage_selected.html');
	}

	/**
	 * @brief Common:: module's permission displaying page in the module
	 * Available when using module instance in all the modules
	 */
	function getModuleGrantHTML($module_srl, $source_grant_list)
	{
		if(!$module_srl)
		{
			return;
		}

		// get member module's config
		$oMemberModel = getModel('member');
		$member_config = $oMemberModel->getMemberConfig();
		Context::set('member_config', $member_config);

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'site_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		// Grant virtual permission for access and manager
		$grant_list = new stdClass();
		$grant_list->access = new stdClass();
		$grant_list->access->title = lang('grant_access');
		$grant_list->access->default = 'guest';
		if($source_grant_list)
		{
			foreach($source_grant_list as $key => $val)
			{
				if(!$val->default) $val->default = 'guest';
				if($val->default == 'root') $val->default = 'manager';
				$grant_list->{$key} = $val;
			}
		}
		$grant_list->manager = new stdClass();
		$grant_list->manager->title = lang('grant_manager');
		$grant_list->manager->default = 'manager';
		Context::set('grant_list', $grant_list);
		// Get a permission group granted to the current module
		$default_grant = array();
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$output = executeQueryArray('module.getModuleGrants', $args);
		if($output->data)
		{
			foreach($output->data as $val)
			{
				if($val->group_srl == 0) $default_grant[$val->name] = 'all';
				else if($val->group_srl == -1) $default_grant[$val->name] = 'member';
				else if($val->group_srl == -2) $default_grant[$val->name] = 'site';
				else if($val->group_srl == -3) $default_grant[$val->name] = 'manager';
				else
				{
					$selected_group[$val->name][] = $val->group_srl;
					$default_grant[$val->name] = 'group';
				}
			}
		}
		Context::set('selected_group', $selected_group);
		Context::set('default_grant', $default_grant);
		Context::set('module_srl', $module_srl);
		// Extract admin ID set in the current module
		$admin_member = ModuleModel::getAdminId($module_srl) ?: [];
		Context::set('admin_member', $admin_member);
		// Get a list of groups
		$group_list = MemberModel::getGroups($module_info->site_srl);
		Context::set('group_list', $group_list);

		//Security			
		$security = new Security();
		$security->encodeHTML('group_list..title');
		$security->encodeHTML('group_list..description');
		$security->encodeHTML('admin_member..nick_name');

		// Get information of module_grants
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($this->module_path.'tpl', 'module_grants');
	}

	public function getModuleAdminGrant()
	{
		$targetModule = Context::get('target_module');
		$moduleSrl = Context::get('module_srl');
		if(!$targetModule || !$moduleSrl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if($targetModule == '_SHORTCUT')
		{
			return new BaseObject();
		}

		$xmlInfo = ModuleModel::getModuleActionXml($targetModule);

		// Grant virtual permission for access and manager
		$grantList = new stdClass();
		$grantList->access = new stdClass();
		$grantList->access->title = lang('grant_access');
		$grantList->access->default = 'guest';
		if($xmlInfo->grant)
		{
			foreach($xmlInfo->grant as $key => $val)
			{
				if(!$val->default) $val->default = 'guest';
				if($val->default == 'root') $val->default = 'manager';
				$grantList->{$key} = $val;
			}
		}
		$grantList->manager = new stdClass();
		$grantList->manager->title = lang('grant_manager');
		$grantList->manager->default = 'manager';

		// Get a permission group granted to the current module
		$selectedGroup = new stdClass();
		$defaultGrant = new stdClass();
		$args = new stdClass();
		$args->module_srl = $moduleSrl;
		$output = executeQueryArray('module.getModuleGrants', $args);
		if($output->data)
		{
			foreach($output->data as $val)
			{
				if($val->group_srl == 0) $defaultGrant->{$val->name} = 'all';
				else if($val->group_srl == -1) $defaultGrant->{$val->name} = 'member';
				else if($val->group_srl == -2) $defaultGrant->{$val->name} = 'site';
				else if($val->group_srl == -3) $defaultGrant->{$val->name} = 'manager';
				else
				{
					$selectedGroup->{$val->name}[] = $val->group_srl;
					$defaultGrant->{$val->name} = 'group';
				}
			}
		}

		if(is_object($grantList))
		{
			foreach($grantList AS $key=>$value)
			{
				if(isset($defaultGrant->{$key}))
				{
					$grantList->{$key}->grant = $defaultGrant->{$key};
				}
				if(isset($selectedGroup->{$key}))
				{
					$grantList->{$key}->group_srls = $selectedGroup->{$key};
				}
			}
		}

		$this->add('grantList', $grantList);
	}

	/**
	 * @brief Common:: skin setting page for the module
	 */
	function getModuleSkinHTML($module_srl)
	{
		return $this->_getModuleSkinHTML($module_srl, 'P');
	}

	/**
	 * Common:: skin setting page for the module (mobile)
	 *
	 * @param $module_srl sequence of module
	 * @return string The html code
	 */
	function getModuleMobileSkinHTML($module_srl)
	{
		return $this->_getModuleSkinHtml($module_srl, 'M');
	}

	/**
	 * Skin setting page for the module
	 *
	 * @param $module_srl sequence of module
	 * @param $mode P or M
	 * @return string The HTML code
	 */
	function _getModuleSkinHTML($module_srl, $mode)
	{
		$mode = $mode === 'P' ? 'P' : 'M';

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		if(!$module_info) return;

		if($mode === 'P')
		{
			if($module_info->is_skin_fix == 'N')
			{
				$skin = $oModuleModel->getModuleDefaultSkin($module_info->module, 'P', $module_info->site_srl);
			}
			else
			{
				$skin = $module_info->skin;
			}
		}
		else
		{
			if($module_info->is_mskin_fix == 'N')
			{
				$skin_type = $module_info->mskin === '/USE_RESPONSIVE/' ? 'P' : 'M';
				$skin = $oModuleModel->getModuleDefaultSkin($module_info->module, $skin_type, $module_info->site_srl);
			}
			else
			{
				$skin = $module_info->mskin;
			}
		}

		$module_path = './modules/'.$module_info->module;

		// Get XML information of the skin and skin sinformation set in DB
		if($mode === 'P')
		{
			$skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);
			$skin_vars = $oModuleModel->getModuleSkinVars($module_srl);
		}
		else
		{
			$skin_info = $oModuleModel->loadSkinInfo($module_path, $skin, 'm.skins');
			$skin_vars = $oModuleModel->getModuleMobileSkinVars($module_srl);
		}

		if($skin_info->extra_vars)
		{
			foreach($skin_info->extra_vars as $key => $val) 
			{
				$group = $val->group;
				$name = $val->name;
				$type = $val->type;
				if($skin_vars[$name]) 
				{
					$value = $skin_vars[$name]->value;
				}
				else $value = '';
				if($type=="checkbox")
				{
					$value = $value?unserialize($value):array();
				}

				$value = empty($value) ? $val->default : $value;
				$skin_info->extra_vars[$key]->value= $value;
			}
		}

		Context::set('module_info', $module_info);
		Context::set('mid', $module_info->mid);
		Context::set('skin_info', $skin_info);
		Context::set('skin_vars', $skin_vars);
		Context::set('mode', $mode);

		//Security
		$security = new Security(); 
		$security->encodeHTML('mid');
		$security->encodeHTML('module_info.browser_title');
		$security->encodeHTML('skin_info...');

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($this->module_path.'tpl', 'skin_config');
	}

	/**
	 * @brief Get values for a particular language code
	 * Return its corresponding value if lang_code is specified. Otherwise return $name.
	 */
	function getLangCode($site_srl, $name, $isFullLanguage = FALSE)
	{
		if($isFullLanguage)
		{
			$lang_supported = Context::loadLangSupported();
		}
		else
		{
			$lang_supported = Context::get('lang_supported');
		}

		if(substr($name,0,12)=='$user_lang->')
		{
			$args = new stdClass();
			$args->site_srl = (int)$site_srl;
			$args->name = substr($name,12);
			$output = executeQueryArray('module.getLang', $args);
			if($output->data)
			{
				foreach($output->data as $key => $val)
				{
					$selected_lang[$val->lang_code] = $val->value;
				}
			}
		}
		else
		{
			$tmp = unserialize($name);
			if($tmp)
			{
				$selected_lang = array();
				$rand_name = $tmp[Context::getLangType()];
				if(!$rand_name) $rand_name = array_shift($tmp);
				if(is_array($lang_supported))
				{
					foreach($lang_supported as $key => $val)
						$selected_lang[$key] = $tmp[$key]?$tmp[$key]:$rand_name;
				}
			}
		}

		$output = array();
		if(is_array($lang_supported))
		{
			foreach($lang_supported as $key => $val)
			{
				$output[$key] = (isset($selected_lang[$key]) && $selected_lang[$key]) ? $selected_lang[$key] : $name;
			}
		}
		return $output;
	}

	/**
	 * @brief Return if the module language in ajax is requested
	 */
	function getModuleAdminLangCode()
	{
		$name = Context::get('name');
		if(!$name) return $this->setError('msg_invalid_request');
		$site_module_info = Context::get('site_module_info');
		$this->add('name', $name);
		$output = $this->getLangCode($site_module_info->site_srl, '$user_lang->'.$name);
		$this->add('langs', $output);
	}

	/**
	 * @brief Returns lang list by lang name
	 */
	function getModuleAdminLangListByName()
	{
		$args = Context::getRequestVars();
		if(!$args->site_srl) $args->site_srl = 0;

		$columnList = array('lang_code', 'name', 'value');

		$langList = array();

		$args->langName = preg_replace('/^\$user_lang->/', '', $args->lang_name);
		$output = executeQueryArray('module.getLangListByName', $args, $columnList);
		if($output->toBool()) $langList = $output->data;

		$this->add('lang_list', $langList);
		$this->add('lang_name', $args->langName);
	}

	/**
	 * @brief Return lang list
	 */
	function getModuleAdminLangListByValue()
	{
		$args = Context::getRequestVars();
		if(!$args->site_srl) $args->site_srl = 0;

		$langList = array();

		// search value
		$output = executeQueryArray('module.getLangNameByValue', $args);
		if($output->toBool() && is_array($output->data))
		{
			unset($args->value);

			foreach($output->data as $data)
			{
				$args->langName = $data->name;
				$columnList = array('lang_code', 'name', 'value');
				$outputByName = executeQueryArray('module.getLangListByName', $args, $columnList);

				if($outputByName->toBool())
				{
					$langList = array_merge($langList, $outputByName->data);
				}
			}
		}

		$this->add('lang_list', $langList);
	}

	/**
	 * @brief Return current lang list
	 */
	function getLangListByLangcode($args)
	{
		$output = executeQueryArray('module.getLangListByLangcode', $args);
		if(!$output->toBool()) return array();

		return $output;
	}

	/**
	 * return multilingual html
	 */
	function getModuleAdminMultilingualHtml()
	{
		$oTemplate = TemplateHandler::getInstance();
		$tpl = $oTemplate->compile(_XE_PATH_ . 'modules/module/tpl', 'multilingual_v17.html');

		$this->add('html', $tpl);
	}

	/**
	 * return multilingual list html
	 */
	function getModuleAdminLangListHtml()
	{
		$site_module_info = Context::get('site_module_info');
		$args = new stdClass();
		$args->site_srl = (int)$site_module_info->site_srl;
		$args->langCode = Context::get('lang_code');
		$args->page = Context::get('page');
		$args->sort_index = 'name';
		$args->order_type = 'asc';
		$args->search_keyword = Context::get('search_keyword');
		$args->name = Context::get('name');
		$args->list_count = Context::get('list_count');
		$args->page_count = 5;

		if(!$args->langCode)
		{
			$args->langCode = Context::get('lang_type');
		}

		$output = $this->getLangListByLangcode($args);

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('lang_code_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('lang_code_list..');

		$oTemplate = TemplateHandler::getInstance();
		$tpl = $oTemplate->compile(_XE_PATH_ . 'modules/module/tpl', 'multilingual_v17_list.html');

		$this->add('html', $tpl);
	}

	/**
	 * return module searcher html
	 */
	function getModuleAdminModuleSearcherHtml()
	{
		Context::loadLang(_XE_PATH_ . 'modules/admin/lang');
		$oTemplate = TemplateHandler::getInstance();
		$tpl = $oTemplate->compile(_XE_PATH_ . 'modules/module/tpl', 'module_searcher_v17.html');

		$this->add('html', $tpl);
	}

	/**
	 * return module info.
	 */
	function getModuleAdminModuleInfo()
	{
		if(Context::get('search_module_srl'))
		{
			$module_srl = Context::get('search_module_srl');
		}
		else
		{
			$module_srl = Context::get('module_srl');
		}

		$model = getModel('module');
		$module_info = $model->getModuleInfoByModuleSrl($module_srl);

		$this->add('module_info', $module_info);
	}
}
/* End of file module.admin.model.php */
/* Location: ./modules/module/module.admin.model.php */
