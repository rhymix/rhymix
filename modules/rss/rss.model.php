<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The model class of the rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssModel extends rss
{
	function getRssURL($format = 'rss', $mid = '')
	{
		return getFullUrl('', 'mid', $mid, 'act', $format);
	}
	
	function getConfig()
	{
		$config = getModel('module')->getModuleConfig('rss');
		$config->use_total_feed = $config->use_total_feed ?: 'Y';
		$config->feed_document_count = $config->feed_document_count ?: 15;
		$config->image_url = $config->image . '?' . date('YmdHis', filemtime($config->image));
		
		return $config;
	}
	
	function getRssModuleConfig($module_srl)
	{
		$config = getModel('module')->getModulePartConfig('rss', $module_srl);
		$config->module_srl = $module_srl;
		$config->open_rss = $config->open_rss ?: 'N';
		$config->open_total_feed = $config->open_total_feed ?: 'N';
		
		return $config;
	}
	
	/**
	 * Compatible function
	 */
	function getModuleFeedUrl($vid, $mid, $format = 'rss', $absolute_url = false)
	{
		if($absolute_url)
		{
			return getFullUrl('', 'vid', $vid, 'mid', $mid, 'act', $format);
		}
		else
		{
			return getUrl('', 'vid', $vid, 'mid', $mid, 'act', $format);
		}
	}
}
/* End of file rss.model.php */
/* Location: ./modules/rss/rss.model.php */
