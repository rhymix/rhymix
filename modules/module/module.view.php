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
		$args = new stdClass;
		
		if(Context::get('logged_info')->is_admin === 'Y')
		{
			// If site keyword exists, extract information from the sites
			if($site_keyword = Context::get('site_keyword'))
			{
				$args->site_keyword = $site_keyword;
			}
			// If there is no site keyword, use as information of the current virtual site
			else
			{
				$args->site_srl = 0;
				$query_id = 'module.getDefaultModules';
			}
			
			Context::set('site_count', executeQuery('module.getSiteCount')->data->count);
		}
		else
		{
			$args->site_srl = (int) Context::get('site_module_info')->site_srl;
		}
		
		// Get a list of modules at the site
		$output = executeQueryArray(isset($query_id) ? $query_id : 'module.getSiteModules', $args);
		
		$mid_list = array();
		$oModuleModel = getModel('module');
		
		foreach($output->data as $key => $val)
		{
			if(!$oModuleModel->getGrant($val, Context::get('logged_info'))->manager)
			{
				continue;
			}
			
			if(!isset($mid_list[$val->module]))
			{
				$mid_list[$val->module] = new stdClass;
				$mid_list[$val->module]->list = array();
			}
			
			$obj = new stdClass;
			$obj->module_srl = $val->module_srl;
			$obj->browser_title = $val->browser_title;
			
			$mid_list[$val->module]->list[$val->category ?: 0][$val->mid] = $obj;
			$mid_list[$val->module]->title = $oModuleModel->getModuleInfoXml($val->module)->title;
		}
		
		Context::set('mid_list', $mid_list);
		
		if(!empty($mid_list))
		{
			if(($selected_module = Context::get('selected_module')) && isset($mid_list[$selected_module]->list))
			{
				Context::set('selected_mids', $mid_list[$selected_module]->list);
			}
			else
			{
				Context::set('selected_mids', array_first($mid_list)->list);
				Context::set('selected_module', array_first_key($mid_list));
			}
		}
		else
		{
			Context::set('selected_mids', array());
		}
		
		$security = new Security();
		$security->encodeHTML('id', 'type', 'site_keyword');
		
		$this->setLayoutFile('popup_layout');
		$this->setTemplateFile('module_selector');
	}

	// See the file box
	function dispModuleFileBox()
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return $this->setError('msg_not_permitted');

		$input_name = Context::get('input');
		if(!preg_match('/^[a-z0-9_]+$/i', $input_name))
		{
			return $this->setError('msg_invalid_request');
		}

		if(!$input_name) return $this->setError('msg_not_permitted');

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
		if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return $this->setError('msg_not_permitted');

		$filter = Context::get('filter');
		if($filter) Context::set('arrfilter',explode(',',$filter));

		$this->setLayoutFile('popup_layout');
		$this->setTemplateFile('filebox_add');
	}
}
/* End of file module.view.php */
/* Location: ./modules/module/module.view.php */
