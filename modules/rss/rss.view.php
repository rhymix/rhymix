<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The view class of the rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssView extends rss
{
	// Disable gzhandler
	public $gzhandler_enable = false;
	
	function init()
	{
	}
	
	function rss($document_list = null, $rss_title = null, $add_description = null)
	{
		$obj = new stdClass;
		$obj->title = $rss_title;
		$obj->description = $add_description;
		$obj->document_list = $document_list;
		$this->output(Context::get('format'), $obj);
	}
	
	function atom()
	{
		$this->output('atom');
	}
	
	function dispError($module_srl = null)
	{
		$obj = new stdClass;
		$obj->error = true;
		$obj->module_srl = $module_srl;
		$obj->description = lang('msg_rss_is_disabled');
		$this->output(Context::get('format'), $obj);
	}
	
	/**
	 * Feed output
	 */
	function output($format, $obj = null)
	{
		if(!$obj)
		{
			$obj = new stdClass;
		}
		
		$act = Context::get('act');
		$page = $obj->page ?: Context::get('page');
		$start = $obj->start_date ?: Context::get('start_date');
		$end = $obj->end_date ?: Context::get('end_date');
		$site_module_srl = Context::get('site_module_info')->module_srl;
		$current_module_srl = Context::get('current_module_info')->module_srl;
		$target_module_srl = isset($obj->module_srl) ? $obj->module_srl : ($current_module_srl ?: $site_module_srl);
		$is_part_feed = (isset($obj->module_srl) || $target_module_srl !== $site_module_srl) ? true : false;
		
		// Set format
		switch($format)
		{
			// Atom 1.0
			case 'atom':
				$template = 'atom10';
				break;
			// RSS 1.0
			case 'rss1.0':
				$template = 'rss10';
				break;
			// XE compatibility
			case 'xe':
				$template = 'xe';
				break;
			// RSS 2.0 (default)
			default:
				$template = 'rss20';
				break;
		}
		
		$oRssModel = getModel('rss');
		$config = $oRssModel->getConfig();
		$module_config = $oRssModel->getRssModuleConfig($target_module_srl);
		$module_info = getModel('module')->getModuleInfoByModuleSrl($target_module_srl);
		
		// Get URL
		$format = ($act != $format) ? $format : '';
		$mid = $is_part_feed ? $module_info->mid : '';
		$channel_url = getFullUrl('', 'mid', $mid, 'act', $act, 'format', $format, 'page', $page, 'start_date', $start, 'end_date', $end);
		
		// Check error
		if($obj->error)
		{
			Context::set('target_modules', array());
			Context::set('category_list', array());
			Context::set('document_list', array());
		}
		else
		{
			if(!$target_module_srl || !$module_info->module_srl)
			{
				return $this->dispError();
			}
			
			// Set target module
			$target_modules = array();
			if($is_part_feed)
			{
				if($module_config->open_rss != 'N')
				{
					$target_modules[$module_config->module_srl] = $module_config->open_rss;
				}
			}
			// total feed
			elseif($config->use_total_feed == 'Y')
			{
				foreach(getModel('module')->getModulePartConfigs('rss') as $module_srl => $part_config)
				{
					if($part_config->open_rss == 'N' || $part_config->open_total_feed == 'T_N')
					{
						continue;
					}
					$target_modules[$module_srl] = $part_config->open_rss;
				}
			}
			Context::set('target_modules', $target_modules);
			
			// Set document list
			$document_list = $obj->document_list;
			if(!is_array($document_list))
			{
				if(!$target_modules)
				{
					return $this->dispError($module_info->module_srl);
				}
				
				$args = new stdClass;
				$args->start_date = $start;
				$args->end_date = $end;
				$args->search_target = 'is_secret';
				$args->search_keyword = 'N';
				$args->page = $page > 0 ? $page : 1;
				$args->module_srl = array_keys($target_modules);
				$args->list_count = $config->feed_document_count > 0 ? $config->feed_document_count : 20;
				$args->sort_index = 'regdate';
				$args->order_type = 'desc';
				$document_list = getModel('document')->getDocumentList($args)->data;
			}
			Context::set('document_list', $document_list);
			
			// Set category list
			$category_list = array();
			foreach($target_modules as $module_srl => $open_rss)
			{
				$category_list[$module_srl] = getModel('document')->getCategoryList($module_srl);
			}
			Context::set('category_list', $category_list);
		}
		
		// Set feed information
		$info = new stdClass;
		if($is_part_feed)
		{
			$info->title = $module_info->browser_title ?: Context::getBrowserTitle();
			$info->link = getFullUrl('', 'mid', $module_info->mid);
			$info->description = $module_config->feed_description ?: $module_info->description;
			$info->feed_copyright = $module_config->feed_copyright ?: $config->feed_copyright;
		}
		else
		{
			$info->title = $config->feed_title ?: Context::get('site_module_info')->browser_title;
			$info->link = Context::getRequestUri();
			$info->description = $config->feed_description;
			$info->feed_copyright = $config->feed_copyright;
		}
		
		$info->id = $channel_url;
		$info->feed_title = $config->feed_title;
		$info->title = Context::replaceUserLang($obj->title ?: $info->title);
		$info->description = $obj->description ?: $info->description;
		$info->language = Context::getLangType();
		$info->site_url = Context::getRequestUri();
		$info->date_r = date('r');
		$info->date_c = date('c');
		$info->image = $config->image ? Context::getRequestUri() . $config->image : '';
		
		Context::set('info', $info);
		
		// Set XML Output
		Context::setResponseMethod('RAW', 'text/xml');
		$this->setTemplatePath($this->module_path . 'tpl/format');
		$this->setTemplateFile($template);
	}
	
	/**
	 * Additional configurations for a service module
	 */
	function triggerDispRssAdditionSetup(&$output)
	{
		if(!($current_module_srl = Context::get('module_srl')) && !Context::get('module_srls'))
		{
			if(!$current_module_srl = Context::get('current_module_info')->module_srl)
			{
				return;
			}
		}
		
		// Get part configuration
		Context::set('module_config', getModel('rss')->getRssModuleConfig($current_module_srl));
		
		// Add output after compile template
		$output .= TemplateHandler::getInstance()->compile($this->module_path . 'tpl', 'rss_module_config');
	}
}
/* End of file rss.view.php */
/* Location: ./modules/rss/rss.view.php */
