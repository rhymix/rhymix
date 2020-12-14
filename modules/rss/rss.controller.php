<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The controller class of rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssController extends rss
{
	function init()
	{
	}
	
	/**
	 * Set RSS URL
	 */
	function triggerRssUrlInsert($obj)
	{
		$current_module_srl = Context::get('current_module_info')->module_srl ?? null;
		if (!$current_module_srl)
		{
			return;
		}
		
		$oRssModel = getModel('rss');
		$config = $oRssModel->getConfig();
		$module_config = $oRssModel->getRssModuleConfig($current_module_srl);
		
		if($config->use_total_feed != 'N' && Context::get('site_module_info')->mid == Context::get('mid'))
		{
			Context::set('general_rss_url', $oRssModel->getRssURL('rss'));
			Context::set('general_atom_url', $oRssModel->getRssURL('atom'));
		}
		
		if($module_config->open_rss != 'N')
		{
			Context::set('rss_url', $oRssModel->getRssURL('rss', Context::get('mid')));
			Context::set('atom_url', $oRssModel->getRssURL('atom', Context::get('mid')));
		}
	}
	
	/**
	 * Copy RSS configuration
	 */
	function triggerCopyModule(&$obj)
	{
		$module_config = getModel('rss')->getRssModuleConfig($obj->originModuleSrl);
		
		foreach($obj->moduleSrlList as $module_srl)
		{
			getController('module')->insertModulePartConfig('rss', $module_srl, $module_config);
		}
	}
}
/* End of file rss.controller.php */
/* Location: ./modules/rss/rss.controller.php */
