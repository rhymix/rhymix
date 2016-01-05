<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  krzipView
 * @author NAVER (developers@xpressengine.com)
 * @brief  Krzip module view class.
 */

class krzipView extends krzip
{
	function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
	}

	/**
	 * @brief 우편번호 검색
	 * @param integer $api_handler
	 * @return mixed
	 */
	function dispKrzipSearchForm($api_handler)
	{
		$oKrzipModel = getModel('krzip');
		$module_config = $oKrzipModel->getConfig();
		$module_config->sequence_id = ++self::$sequence_id;
		if(!isset($api_handler) || !isset(self::$api_list[$api_handler]))
		{
			$api_handler = $module_config->api_handler;
		}

		Context::set('template_config', $module_config);
		$this->setTemplateFile('searchForm.' . self::$api_list[$api_handler]);
		$this->setLayoutPath('./common/tpl/');
		$this->setLayoutFile('popup_layout');
	}
}

/* End of file krzip.view.php */
/* Location: ./modules/krzip/krzip.view.php */
