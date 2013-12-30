<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The admin view class of the rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssAdminView extends rss
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
		//Set template path
		$this->setTemplatePath($this->module_path.'tpl');
	}

	/**
	 * In case an administrator page has been initialized
	 *
	 * @return Object
	 */
	function dispRssAdminIndex()
	{
		$oModuleModel = getModel('module');
		$rss_config = $oModuleModel->getModulePartConfigs('rss');
		$total_config = $oModuleModel->getModuleConfig('rss');
		if(!$total_config)
		{
			$total_config = new stdClass();
		}
		$oRssModel = getModel('rss');

		if($rss_config)
		{
			$feed_config = array();
			foreach($rss_config as $module_srl => $config)
			{
				if($config)
				{
					$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
					$columnList = array('sites.domain');
					$site = $oModuleModel->getSiteInfo($module_info->site_srl, $columnList);
					if(!strpos($site->domain, '.')) $vid = $site->domain;
					else $site = null;
					if($site) $feed_config[$module_srl]['url'] = $oRssModel->getModuleFeedUrl($vid, $module_info->mid, 'rss');
					$feed_config[$module_srl]['mid'] = $module_info->mid;
					$feed_config[$module_srl]['open_feed'] = $config->open_rss;
					$feed_config[$module_srl]['open_total_feed'] = $config->open_total_feed;
					$feed_config[$module_srl]['feed_description'] = $config->feed_description;
				}
			}
		}
		if(!$total_config->feed_document_count) $total_config->feed_document_count = 15;

		Context::set('feed_config', $feed_config);
		Context::set('total_config', $total_config);

		$security = new Security();
		$security->encodeHTML('feed_config..mid','feed_config..url');
		$security->encodeHTML('total_config..');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('rss_admin_index');
	}
}
/* End of file rss.admin.view.php */
/* Location: ./modules/rss/rss.admin.view.php */
