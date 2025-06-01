<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  boardModel
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module  Model class
 */
class BoardModel extends Board
{
	/**
	 * Default cofiguration for each module instance.
	 */
	public const DEFAULT_MODULE_CONFIG = [

		// Title and SEO settings
		'browser_title' => '',
		'meta_keywords' => '',
		'meta_description' => '',
		'robots_tag' => 'all',

		// PC and common display settings
		'layout_srl' => -1,
		'skin' => '/USE_DEFAULT/',
		'list_count' => 20,
		'search_list_count' => 20,
		'page_count' => 10,
		'header_text' => '',
		'footer_text' => '',

		// Mobile display settings
		'use_mobile' => 'N',
		'mlayout_srl' => -2,
		'mskin' => '/USE_DEFAULT/',
		'mobile_list_count' => 20,
		'mobile_search_list_count' => 20,
		'mobile_page_count' => 5,
		'mobile_header_text' => '',
		'mobile_footer_text' => '',

		// List settings
		'order_target' => 'list_order',
		'order_type' => 'asc',
		'except_notice' => 'Y',
		'use_bottom_list' => 'Y',
		'skip_bottom_list_for_olddoc' => 'N',
		'skip_bottom_list_days' => 30,
		'skip_bottom_list_for_robot' => 'Y',

		// Feature settings
		'consultation' => 'N',
		'use_anonymous' => 'N',
		'anonymous_except_admin' => 'N',
		'anonymous_name' => 'anonymous',
		'update_log' => 'N',
		'update_order_on_comment' => 'N',
		'comment_delete_message' => 'no',
		'trash_use' => 'N',
		'use_status' => 'PUBLIC',
		'use_category' => 'N',
		'allow_no_category' => 'N',

		// Limits and protections
		'document_length_limit' => 1024,
		'comment_length_limit' => 128,
		'inline_data_url_limit' => 64,
		'filter_specialchars' => 'Y',
		'protect_delete_content' => 'N',
		'protect_update_content' => 'N',
		'protect_delete_comment' => 'N',
		'protect_update_comment' => 'N',
		'protect_admin_content_delete' => 'Y',
		'protect_admin_content_update' => 'Y',
		'protect_document_regdate' => '',
		'protect_comment_regdate' => '',

		// Extra settings
		'admin_mail' => '',
		'module_category_srl' => 0,
		'description' => '',
	];

	/**
	 * Fix module configuration so that there are no missing values.
	 *
	 * The return value will be set to true if any values were added.
	 * The object will be modified in place.
	 *
	 * @param object $module_info
	 * @return bool
	 */
	public static function fixModuleConfig(object $module_info): bool
	{
		$fixed = false;
		foreach (self::DEFAULT_MODULE_CONFIG as $key => $value)
		{
			if (!isset($module_info->{$key}))
			{
				$module_info->{$key} = $value;
				$fixed = true;
			}
		}
		return $fixed;
	}

	/**
	 * @brief get the list configuration
	 */
	public static function getListConfig($module_srl)
	{
		// get the list config value, if it is not exitsted then setup the default value
		$module_srl = (int)$module_srl;
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
	 */
	public static function getDefaultListConfig($module_srl)
	{
		$extra_vars = [];
		$module_srl = (int)$module_srl;

		// add virtual srl, title, registered date, update date, nickname, ID, name, readed count, voted count etc.
		$virtual_vars = array( 'no', 'title', 'regdate', 'last_update', 'last_post', 'module_title', 'nick_name',
				'user_id', 'user_name', 'readed_count', 'voted_count', 'blamed_count', 'comment_count',
				'thumbnail', 'summary', 'comment_status');
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
	 */
	public function triggerModuleListInSitemap(&$obj)
	{
		array_push($obj, 'board');
	}
}
