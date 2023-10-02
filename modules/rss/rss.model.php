<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The model class of the rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class RssModel extends Rss
{
	public static function getRssURL($format = 'rss', $mid = '')
	{
		return getFullUrl('', 'mid', $mid, 'act', $format);
	}

	public static function getConfig()
	{
		$config = ModuleModel::getModuleConfig('rss') ?: new stdClass;
		$config->use_total_feed = $config->use_total_feed ?? 'Y';
		$config->feed_document_count = intval($config->feed_document_count ?? 15) ?: 15;
		if (isset($config->image) && $config->image)
		{
			$config->image_url = $config->image . '?t=' . filemtime($config->image);
		}

		return $config;
	}

	public static function getRssModuleConfig($module_srl)
	{
		$config = ModuleModel::getModulePartConfig('rss', $module_srl) ?: new stdClass;
		$config->module_srl = $module_srl;
		$config->open_rss = $config->open_rss ?? 'N';
		$config->open_total_feed = $config->open_total_feed ?? 'N';

		return $config;
	}

	/**
	 * Compatible function
	 */
	public static function getModuleFeedUrl($vid, $mid, $format = 'rss', $absolute_url = false)
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
