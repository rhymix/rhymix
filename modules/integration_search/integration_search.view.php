<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The view class of the integration_search module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class integration_searchView extends integration_search
{
	/**
	 * Target mid
	 * @var array target mid
	 */
	var $target_mid = array();
	/**
	 * Skin
	 * @var string skin name
	 */
	var $skin = 'default';

	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Search Result
	 *
	 * @return Object
	 */
	function IS()
	{
		$oFile = getClass('file');
		$oModuleModel = getModel('module');
		$logged_info = Context::get('logged_info');

		// Check permissions
		if(!$this->grant->access) return new Object(-1,'msg_not_permitted');

		$config = $oModuleModel->getModuleConfig('integration_search');
		if(!$config) $config = new stdClass;
		if(!$config->skin)
		{
			$config->skin = 'default';
			$template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);
		}
		else
		{
			//check theme
			$config_parse = explode('|@|', $config->skin);
			if (count($config_parse) > 1)
			{
				$template_path = sprintf('./themes/%s/modules/integration_search/', $config_parse[0]);
			}
			else
			{
				$template_path = sprintf('%sskins/%s', $this->module_path, $config->skin);
			}
		}
		// Template path
		$this->setTemplatePath($template_path);
		$skin_vars = ($config->skin_vars) ? unserialize($config->skin_vars) : new stdClass;
		Context::set('module_info', $skin_vars);

		$target = $config->target;
		if(!$target) $target = 'include';

		if(empty($config->target_module_srl))
			$module_srl_list = array();
		else
			$module_srl_list = explode(',',$config->target_module_srl);

		// https://github.com/xpressengine/xe-core/issues/1522
		// 검색 대상을 지정하지 않았을 때 검색 제한
		if($target === 'include' && !count($module_srl_list))
		{
			$oMessageObject = ModuleHandler::getModuleInstance('message');
			$oMessageObject->setError(-1);
			$oMessageObject->setMessage('msg_not_enabled');
			$oMessageObject->dispMessage();
			$this->setTemplatePath($oMessageObject->getTemplatePath());
			$this->setTemplateFile($oMessageObject->getTemplateFile());
			return;
		}

		// Set a variable for search keyword
		$is_keyword = Context::get('is_keyword');
		// Set page variables
		$page = (int)Context::get('page');
		if(!$page) $page = 1;
		// Search by search tab
		$where = Context::get('where');
		// Create integration search model object
		if($is_keyword)
		{
			$oIS = getModel('integration_search');
			switch($where)
			{
				case 'document' :
					$search_target = Context::get('search_target');
					if(!in_array($search_target, array('title','content','title_content','tag'))) $search_target = 'title';
					Context::set('search_target', $search_target);

					$output = $oIS->getDocuments($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("document", $page);
					break;
				case 'comment' :
					$output = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("comment", $page);
					break;
				case 'trackback' :
					$search_target = Context::get('search_target');
					if(!in_array($search_target, array('title','url','blog_name','excerpt'))) $search_target = 'title';
					Context::set('search_target', $search_target);

					$output = $oIS->getTrackbacks($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("trackback", $page);
					break;
				case 'multimedia' :
					$output = $oIS->getImages($target, $module_srl_list, $is_keyword, $page,20);
					Context::set('output', $output);
					$this->setTemplateFile("multimedia", $page);
					break;
				case 'file' :
					$output = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 20);
					Context::set('output', $output);
					$this->setTemplateFile("file", $page);
					break;
				default :
					$output['document'] = $oIS->getDocuments($target, $module_srl_list, 'title', $is_keyword, $page, 5);
					$output['comment'] = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 5);
					$output['trackback'] = $oIS->getTrackbacks($target, $module_srl_list, 'title', $is_keyword, $page, 5);
					$output['multimedia'] = $oIS->getImages($target, $module_srl_list, $is_keyword, $page, 5);
					$output['file'] = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 5);
					Context::set('search_result', $output);
					Context::set('search_target', 'title');
					$this->setTemplateFile("index", $page);
					break;
			}
		}
		else
		{
			$this->setTemplateFile("no_keywords");
		}

		$security = new Security();
		$security->encodeHTML('is_keyword', 'search_target', 'where', 'page');
	}
}
/* End of file integration_search.view.php */
/* Location: ./modules/integration_search/integration_search.view.php */
