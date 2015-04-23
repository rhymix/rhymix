<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleView
 * @author NAVER (developers@xpressengine.com)
 * @brief view class of the module module
 */
class moduleView extends module
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
	 * @brief Display skin information
	 */
	function dispModuleSkinInfo()
	{
		$selected_module = Context::get('selected_module');
		$skin = Context::get('skin');
		// Get modules/skin information
		$module_path = sprintf("./modules/%s/", $selected_module);
		if(!is_dir($module_path)) $this->stop("msg_invalid_request");

		$skin_info_xml = sprintf("%sskins/%s/skin.xml", $module_path, $skin);
		if(!file_exists($skin_info_xml)) $this->stop("msg_invalid_request");

		$oModuleModel = getModel('module');
		$skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);
		Context::set('skin_info',$skin_info);

		$this->setLayoutFile("popup_layout");
		$this->setTemplateFile("skin_info");
	}

	/**
	 * @brief Select a module
	 */
	function dispModuleSelectList()
	{
		if(!Context::get('is_logged')) return new Object(-1, 'msg_not_permitted');

		$oModuleModel = getModel('module');
		// Extract the number of virtual sites
		$output = executeQuery('module.getSiteCount');
		$site_count = $output->data->count;
		Context::set('site_count', $site_count);
		// Variable setting for site keyword
		$site_keyword = Context::get('site_keyword');
		// If there is no site keyword, use as information of the current virtual site
		$args = new stdClass();
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin == 'Y')
		{
			$query_id = 'module.getSiteModules';
			$module_category_exists = false;
			if(!$site_keyword)
			{
				$site_module_info = Context::get('site_module_info');
				if($site_module_info && $logged_info->is_admin != 'Y')
				{
					$site_keyword = $site_module_info->domain;
					$args->site_srl = (int)$site_module_info->site_srl;
					Context::set('site_keyword', $site_keyword);
				}
				else
				{
					$query_id = 'module.getDefaultModules';
					$args->site_srl = 0;
					$module_category_exists = true;
				}
				// If site keyword exists, extract information from the sites
			}
			else
			{
				$args->site_keyword = $site_keyword;
			}
		}
		else
		{
			$query_id = 'module.getSiteModules';
			$site_module_info = Context::get('site_module_info');
			$args->site_srl = (int)$site_module_info->site_srl;
		}
		//if(is_null($args->site_srl)) $query_id = 'module.getDefaultModules';
		// Get a list of modules at the site
		$output = executeQueryArray($query_id, $args);
		$category_list = $mid_list = array();
		if(count($output->data))
		{
			foreach($output->data as $key => $val)
			{
				$module = trim($val->module);
				if(!$module) continue;

				$category = $val->category;
				$obj = new stdClass();
				$obj->module_srl = $val->module_srl;
				$obj->browser_title = $val->browser_title;
				$mid_list[$module]->list[$category][$val->mid] = $obj;
			}
		}

		$selected_module = Context::get('selected_module');
		if(count($mid_list))
		{
			foreach($mid_list as $module => $val)
			{
				if(!$selected_module) $selected_module = $module;
				$xml_info = $oModuleModel->getModuleInfoXml($module);
				$mid_list[$module]->title = $xml_info->title;
			}
		}
		
		// not show admin bar
		Context::set('mid_list', $mid_list);
		Context::set('selected_module', $selected_module);
		Context::set('selected_mids', $mid_list[$selected_module]->list);
		Context::set('module_category_exists', $module_category_exists);

		$security = new Security();
		$security->encodeHTML('id', 'type');

		// Set the layout to be pop-up
		$this->setLayoutFile('popup_layout');
		// Set a template file
		$this->setTemplateFile('module_selector');
	}

	// See the file box
	function dispModuleFileBox()
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return new Object(-1, 'msg_not_permitted');

		$input_name = Context::get('input');
		if(!preg_match('/^[a-z0-9_]+$/i', $input_name))
		{
			return new Object(-1, 'msg_invalid_request');
		}

		if(!$input_name) return new Object(-1, 'msg_not_permitted');

		$addscript = sprintf('<script>//<![CDATA[
				var selected_filebox_input_name = "%s";
				//]]></script>',$input_name);
		Context::addHtmlHeader($addscript);

		$oModuleModel = getModel('module');
		$output = $oModuleModel->getModuleFileBoxList();
		Context::set('filebox_list', $output->data);

		$filter = Context::get('filter');
		if($filter) Context::set('arrfilter',explode(',',$filter));

		Context::set('page_navigation', $output->page_navigation);
		$this->setLayoutFile('popup_layout');
		$this->setTemplateFile('filebox_list');
	}

	// Screen to add a file box
	function dispModuleFileBoxAdd()
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return new Object(-1, 'msg_not_permitted');

		$filter = Context::get('filter');
		if($filter) Context::set('arrfilter',explode(',',$filter));

		$this->setLayoutFile('popup_layout');
		$this->setTemplateFile('filebox_add');
	}
}
/* End of file module.view.php */
/* Location: ./modules/module/module.view.php */
