<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  boardModel
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module  Model class
 **/
class boardModel extends module
{
	/**
	 * @brief initialization
	 **/
	function init()
	{
	}

	/**
	 * @brief get the list configuration
	 **/
	public static function getListConfig($module_srl)
	{
		// get the list config value, if it is not exitsted then setup the default value
		$list_config = ModuleModel::getModulePartConfig('board', $module_srl);
		if(!is_array($list_config) || count($list_config) <= 0)
		{
			$list_config = array('no', 'title', 'nick_name', 'regdate', 'readed_count');
		}

		// get the extra variables
		$inserted_extra_vars = DocumentModel::getExtraKeys($module_srl);

		foreach($list_config as $key)
		{
			if(preg_match('/^([0-9]+)$/',$key))
			{
				if($inserted_extra_vars[$key])
				{
					$output['extra_vars'.$key] = $inserted_extra_vars[$key];
				}
				else
				{
					continue;
				}
			}
			else
			{
				$output[$key] = new ExtraItem($module_srl, -1, lang($key), $key, 'N', 'N', 'N', null);
			}
		}
		return $output;
	}

	/**
	 * @brief return the default list configration value
	 **/
	public static function getDefaultListConfig($module_srl)
	{
		// add virtual srl, title, registered date, update date, nickname, ID, name, readed count, voted count etc.
		$virtual_vars = array( 'no', 'title', 'regdate', 'last_update', 'last_post', 'module_title', 'nick_name',
				'user_id', 'user_name', 'readed_count', 'voted_count', 'blamed_count', 'thumbnail', 'summary', 'comment_status');
		foreach($virtual_vars as $key)
		{
			$extra_vars[$key] = new ExtraItem($module_srl, -1, lang($key), $key, 'N', 'N', 'N', null);
		}

		// get the extra variables from the document model
		$inserted_extra_vars = DocumentModel::getExtraKeys($module_srl);
		if(count($inserted_extra_vars))
		{
			foreach($inserted_extra_vars as $obj)
			{
				$extra_vars['extra_vars'.$obj->idx] = $obj;
			}
		}

		return $extra_vars;

	}

	/**
	 * @brief return module name in sitemap
	 **/
	public function triggerModuleListInSitemap(&$obj)
	{
		array_push($obj, 'board');
	}
}
