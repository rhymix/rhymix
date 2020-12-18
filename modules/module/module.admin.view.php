<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleAdminView
 * @author NAVER (developers@xpressengine.com)
 * @brief admin view class of the module module
 */
class moduleAdminView extends module
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		// Set the template path
		$this->setTemplatePath($this->module_path.'tpl');
	}

	/**
	 * @brief Module admin page
	 */
	function dispModuleAdminContent()
	{
		$this->dispModuleAdminList();
	}

	/**
	 * @brief Display a lost of modules
	 */
	function dispModuleAdminList()
	{
		// Obtain a list of modules
		$oAdminModel = getAdminModel('admin');
		$oModuleModel = getModel('module');
		$oAutoinstallModel = getModel('autoinstall');

		$module_list = $oModuleModel->getModuleList();
		if(is_array($module_list))
		{
			foreach($module_list as $key => $val)
			{
				$module_list[$key]->delete_url = $oAutoinstallModel->getRemoveUrlByPath($val->path);

				// get easyinstall need update
				$packageSrl = $oAutoinstallModel->getPackageSrlByPath($val->path);
				$package = $oAutoinstallModel->getInstalledPackages($packageSrl);
				$module_list[$key]->need_autoinstall_update = $package[$packageSrl]->need_update;

				// get easyinstall update url
				if($module_list[$key]->need_autoinstall_update == 'Y')
				{
					$module_list[$key]->update_url = $oAutoinstallModel->getUpdateUrlByPackageSrl($packageSrl);
				}
			}
		}

		$output = $oAdminModel->getFavoriteList('0');

		$favoriteList = $output->get('favoriteList');
		$favoriteModuleList = array();
		if($favoriteList)
		{
			foreach($favoriteList as $favorite => $favorite_info)
			{
				$favoriteModuleList[] = $favorite_info->module;
			}
		}

		Context::set('favoriteModuleList', $favoriteModuleList);
		Context::set('module_list', $module_list);

		$security = new Security();
		$security->encodeHTML('module_list....');

		// Set a template file
		$this->setTemplateFile('module_list');

	}

	/**
	 * @brief Pop-up details of the module (conf/info.xml)
	 */
	function dispModuleAdminInfo()
	{
		// Obtain a list of modules
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoXml(Context::get('selected_module'));
		Context::set('module_info', $module_info);

		$security = new Security();				
		$security->encodeHTML('module_info...');

		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');
		// Set a template file
		$this->setTemplateFile('module_info');
	}

	/**
	 * @brief Module Categories
	 */
	function dispModuleAdminCategory()
	{
		$module_category_srl = Context::get('module_category_srl');

		// Obtain a list of modules
		$oModuleModel = getModel('module');
		// Display the category page if a category is selected
		//Security
		$security = new Security();

		if($module_category_srl)
		{
			$selected_category  = $oModuleModel->getModuleCategory($module_category_srl);
			Context::set('selected_category', $selected_category);

			//Security
			$security->encodeHTML('selected_category.title');

			// Set a template file
			$this->setTemplateFile('category_update_form');
			// If not selected, display a list of categories
		}
		else
		{
			$category_list = $oModuleModel->getModuleCategories();
			Context::set('category_list', $category_list);

			//Security
			$security->encodeHTML('category_list..title');

			// Set a template file
			$this->setTemplateFile('category_list');
		}
	}

	/**
	 * @brief Feature to copy module
	 */
	function dispModuleAdminCopyModule()
	{
		// Get a target module to copy
		$module_srl = Context::get('module_srl');
		// Get information of the module
		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module', 'mid', 'browser_title');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		Context::set('module_info', $module_info);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('module_info.');
		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');
		// Set a template file
		$this->setTemplateFile('copy_module');
	}

	/**
	 * @brief Applying the default settings to all modules
	 */
	function dispModuleAdminModuleSetup()
	{
		$module_srls = Context::get('module_srls');

		$modules = explode(',',$module_srls);
		if(!count($modules)) if(!$module_srls) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($modules[0], $columnList);
		// Get a skin list of the module
		$skin_list = $oModuleModel->getSkins(RX_BASEDIR . 'modules/'.$module_info->module);
		Context::set('skin_list',$skin_list);
		// Get a layout list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);
		// Get a list of module categories
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$security = new Security();
		$security->encodeHTML('layout_list..title','layout_list..layout');
		$security->encodeHTML('skin_list....');
		$security->encodeHTML('module_category...');

		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');
		// Set a template file
		$this->setTemplateFile('module_setup');
	}

	/**
	 * @brief Apply module addition settings to all modules
	 */
	function dispModuleAdminModuleAdditionSetup()
	{
		$module_srls = Context::get('module_srls');

		$modules = explode(',',$module_srls);
		if(!count($modules)) if(!$module_srls) throw new Rhymix\Framework\Exceptions\InvalidRequest;
		// pre-define variables because you can get contents from other module (call by reference)
		$content = '';
		// Call a trigger for additional settings
		// Considering uses in the other modules, trigger name cen be publicly used
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
		Context::set('setup_content', $content);
		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');
		// Set a template file
		$this->setTemplateFile('module_addition_setup');
	}

	/**
	 * @brief Applying module permission settings to all modules
	 */
	function dispModuleAdminModuleGrantSetup()
	{
		$module_srls = Context::get('module_srls');

		$modules = explode(',',$module_srls);
		if(!count($modules)) if(!$module_srls) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module', 'site_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($modules[0], $columnList);
		$xml_info = $oModuleModel->getModuleActionXml($module_info->module);
		$source_grant_list = $xml_info->grant;
		// Grant virtual permissions for access and manager
		$grant_list->access->title = lang('grant_access');
		$grant_list->access->default = 'guest';
		if(count($source_grant_list))
		{
			foreach($source_grant_list as $key => $val)
			{
				if(!$val->default) $val->default = 'guest';
				if($val->default == 'root') $val->default = 'manager';
				$grant_list->{$key} = $val;
			}
		}
		$grant_list->manager->title = lang('grant_manager');
		$grant_list->manager->default = 'manager';
		Context::set('grant_list', $grant_list);
		// Get a list of groups
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups($module_info->site_srl);
		Context::set('group_list', $group_list);
		$security = new Security();				
		$security->encodeHTML('group_list..title');

		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');
		// Set a template file
		$this->setTemplateFile('module_grant_setup');
	}

	/**
	 * @brief Language codes
	 */
	function dispModuleAdminLangcode()
	{
		// Get the language file of the current site
		$site_module_info = Context::get('site_module_info');
		$args = new stdClass();
		$args->site_srl = (int)$site_module_info->site_srl;
		$args->langCode = Context::get('lang_type');
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of posts to display on a single page
		$args->page_count = 5; // /< the number of pages that appear in the page navigation
		$args->sort_index = 'name';
		$args->order_type = 'asc';
		$args->search_target = Context::get('search_target'); // /< search (title, contents ...)
		$args->search_keyword = Context::get('search_keyword'); // /< keyword to search

		$oModuleAdminModel = getAdminModel('module');
		$output = $oModuleAdminModel->getLangListByLangcode($args);

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('lang_code_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		if(Context::get('module') != 'admin')
		{
			$this->setLayoutPath('./common/tpl');
			$this->setLayoutFile('popup_layout');
		}
		// Set a template file
		$this->setTemplateFile('module_langcode');
	}

	function dispModuleAdminFileBox()
	{
		$oModuleModel = getModel('module');
		$output = $oModuleModel->getModuleFileBoxList();
		$page = Context::get('page');
		$page = $page?$page:1;
		Context::set('filebox_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('page', $page);
		
		$max_filesize = min(FileHandler::returnBytes(ini_get('upload_max_filesize')), FileHandler::returnBytes(ini_get('post_max_size')));
		Context::set('max_filesize', $max_filesize);
		
		$oSecurity = new Security();
		$oSecurity->encodeHTML('filebox_list..comment', 'filebox_list..attributes.');
		$this->setTemplateFile('adminFileBox');
	}
}
/* End of file module.admin.view.php */
/* Location: ./modules/module/module.admin.view.php */
