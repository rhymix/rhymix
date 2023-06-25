<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  moduleView
 * @author NAVER (developers@xpressengine.com)
 * @brief view class of the module module
 */
class ModuleView extends Module
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
		$skin = preg_replace('/[^a-zA-Z0-9-_]/', '', Context::get('skin'));

		// Get modules/skin information
		$module_path = sprintf("./modules/%s/", $selected_module);
		if(!is_dir($module_path)) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$skin_info_xml = sprintf("%sskins/%s/skin.xml", $module_path, $skin);
		if(!file_exists($skin_info_xml)) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$skin_info = ModuleModel::loadSkinInfo($module_path, $skin);
		Context::set('skin_info',$skin_info);

		$this->setLayoutFile("popup_layout");
		$this->setTemplateFile("skin_info");
	}

	/**
	 * @brief Select a module
	 */
	function dispModuleSelectList()
	{
		// Get a list of modules at the site
		$args = new stdClass;
		$output = executeQueryArray(isset($query_id) ? $query_id : 'module.getSiteModules', $args);

		$mid_list = array();

		foreach($output->data as $key => $val)
		{
			if(!ModuleModel::getGrant($val, Context::get('logged_info'))->manager)
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
			$mid_list[$val->module]->title = ModuleModel::getModuleInfoXml($val->module)->title;
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
				Context::set('selected_mids', $mid_list['board']->list);
				Context::set('selected_module', 'board');
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
		if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$input_name = Context::get('input');
		if(!$input_name || !preg_match('/^[a-z0-9_]+$/i', $input_name))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$addscript = sprintf('<script>//<![CDATA[
				var selected_filebox_input_name = "%s";
				//]]></script>',$input_name);
		Context::addHtmlHeader($addscript);

		$output = ModuleModel::getModuleFileBoxList();
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
		if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$filter = Context::get('filter');
		if($filter) Context::set('arrfilter',explode(',',$filter));

		$this->setLayoutFile('popup_layout');
		$this->setTemplateFile('filebox_add');
	}

	/**
	 * Display the current site's favicon
	 */
	public function dispModuleCurrentSiteFavicon()
	{
		$domain = ModuleModel::getSiteInfoByDomain($_SERVER['HTTP_HOST']) ?: ModuleModel::getDefaultDomainInfo();
		$domain_srl = $domain->domain_srl ?? 0;
		$filename = \RX_BASEDIR . 'files/attach/xeicon/' . ($domain_srl ? ($domain_srl . '/') : '') . 'favicon.ico';

		if (Rhymix\Framework\Storage::exists($filename))
		{
			// Handle 304 Not Modified
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) > filemtime($uploaded_filename))
			{
				header('HTTP/1.1 304 Not Modified');
				exit();
			}

			// Clear buffer
			while(ob_get_level()) ob_end_clean();

			// Set headers
			header('Content-Type: image/x-icon');
			header('Content-Length: ' . filesize($filename));
			header('Cache-Control: private; max-age=3600');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

			// Print icon and exit
			readfile($filename);
			exit();
		}
		else
		{
			$this->stop('msg_not_founded');
			$this->setHttpStatusCode(404);
		}
	}

	/**
	 * Moved from mobile class
	 */
	function dispModuleChangeLang()
	{
		$this->setTemplatePath(sprintf("%stpl/",$this->module_path));
		$this->setTemplateFile('lang.html');
	}
}
/* End of file module.view.php */
/* Location: ./modules/module/module.view.php */
