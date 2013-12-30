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
	 * @param string $format Feed format. ef)xe, atom, rss1.0
	 * @return string
	 */
	function getModuleFeedUrl($vid = null, $mid, $format)
	{
		if(Context::isAllowRewrite())
		{
			$request_uri = Context::getRequestUri();
			// If the virtual site variable exists and it is different from mid (vid and mid should not be the same)
			if($vid && $vid != $mid)
			{
				return $request_uri.$vid.'/'.$mid.'/'.$format;
			}
			else
			{
				return $request_uri.$mid.'/'.$format;
			}
		}
		else
		{
			return getUrl('','mid',$mid,'act',$format);
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
