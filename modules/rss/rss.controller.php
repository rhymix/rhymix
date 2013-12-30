<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The controller class of rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssController extends rss
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Check whether to use RSS rss url by adding
	 *
	 * @return Object
	 */
	function triggerRssUrlInsert()
	{
		$oModuleModel = getModel('module');
		$total_config = $oModuleModel->getModuleConfig('rss');
		$current_module_srl = Context::get('module_srl');
		$site_module_info = Context::get('site_module_info');

		if(is_array($current_module_srl))
		{
			unset($current_module_srl);
		}
		if(!$current_module_srl) {
			$current_module_info = Context::get('current_module_info');
			$current_module_srl = $current_module_info->module_srl;
		}

		if(!$current_module_srl) return new Object();
		// Imported rss settings of the selected module
		$oRssModel = getModel('rss');
		$rss_config = $oRssModel->getRssModuleConfig($current_module_srl);

		if($rss_config->open_rss != 'N')
		{
			Context::set('rss_url', $oRssModel->getModuleFeedUrl(Context::get('vid'), Context::get('mid'), 'rss'));
			Context::set('atom_url', $oRssModel->getModuleFeedUrl(Context::get('vid'), Context::get('mid'), 'atom'));
		}

		if(Context::isInstalled() && $site_module_info->mid == Context::get('mid') && $total_config->use_total_feed != 'N')
		{
			if(Context::isAllowRewrite() && !Context::get('vid'))
			{
				$request_uri = Context::getRequestUri();
				Context::set('general_rss_url', $request_uri.'rss');
				Context::set('general_atom_url', $request_uri.'atom');
			}
			else
			{
				Context::set('general_rss_url', getUrl('','module','rss','act','rss'));
				Context::set('general_atom_url', getUrl('','module','rss','act','atom'));
			}
		}

		return new Object();
	}

	function triggerCopyModule(&$obj)
	{
		$oModuleModel = getModel('module');
		$rssConfig = $oModuleModel->getModulePartConfig('rss', $obj->originModuleSrl);

		$oModuleController = getController('module');
		if(is_array($obj->moduleSrlList))
		{
			foreach($obj->moduleSrlList AS $key=>$moduleSrl)
			{
				$oModuleController->insertModulePartConfig('rss', $moduleSrl, $rssConfig);
			}
		}
	}
}
/* End of file rss.controller.php */
/* Location: ./modules/rss/rss.controller.php */
