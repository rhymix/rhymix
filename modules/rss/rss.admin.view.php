<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The admin view class of the rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssAdminView extends rss
{
	function init()
	{
		Context::set('config', getModel('rss')->getConfig());
		
		$this->setTemplatePath($this->module_path . 'tpl');
	}
	
	function dispRssAdminIndex()
	{
		$oRssModel = getModel('rss');
		
		$rss_list = array();
		foreach (ModuleModel::getMidList((object)['module' => 'board']) as $module_info)
		{
			$args = new stdClass;
			$args->mid = $module_info->mid;
			$args->url = $oRssModel->getRssURL('rss', $module_info->mid);
			$args->open_feed = 'N';
			$args->open_total_feed = 'N';
			$args->feed_description = '';
			
			$rss_list[$module_info->module_srl] = $args;
		}
		
		foreach (ModuleModel::getModulePartConfigs('rss') as $module_srl => $module_config)
		{
			$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
			
			$args = new stdClass;
			$args->mid = $module_info->mid;
			$args->url = $oRssModel->getRssURL('rss', $module_info->mid);
			$args->open_feed = $module_config->open_rss;
			$args->open_total_feed = $module_config->open_total_feed;
			$args->feed_description = $module_config->feed_description;
			
			$rss_list[$module_srl] = $args;
		}
		Context::set('rss_list', $rss_list);
		Context::set('general_rss_url', $oRssModel->getRssURL('rss'));
		
		$this->setTemplateFile('rss_admin_index');
	}
}
/* End of file rss.admin.view.php */
/* Location: ./modules/rss/rss.admin.view.php */
