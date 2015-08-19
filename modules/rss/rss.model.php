<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The model class of the rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssModel extends rss
{
	/**
	 * Create the Feed url.
	 *
	 * @param string $vid Vid
	 * @param string $mid mid
	 * @param string $format Feed format. rss | atom
	 * @param bool $absolute_url
	 * @return string
	 */
	function getModuleFeedUrl($vid, $mid, $format = 'rss', $absolute_url = false)
	{
		if($absolute_url)
		{
			return getFullUrl('','vid',$vid, 'mid',$mid, 'act',$format);
		}
		else
		{
			return getUrl('','vid',$vid, 'mid',$mid, 'act',$format);
		}
	}

	/**
	 * Return the RSS configurations of the specific modules
	 *
	 * @param integer $module_srl Module_srl
	 * @return Object
	 */
	function getRssModuleConfig($module_srl)
	{
		// Get the configurations of the rss module
		$oModuleModel = getModel('module');
		$module_rss_config = $oModuleModel->getModulePartConfig('rss', $module_srl);
		if(!$module_rss_config)
		{
			$module_rss_config = new stdClass();
			$module_rss_config->open_rss = 'N';
		}
		$module_rss_config->module_srl = $module_srl;
		return $module_rss_config;
	}
}
/* End of file rss.model.php */
/* Location: ./modules/rss/rss.model.php */
