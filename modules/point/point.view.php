<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pointView
 * @author NAVER (developers@xpressengine.com)
 * @brief The view class of the point module
 *
 * POINT 2.0 format document output
 *
 */
class pointView extends point
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Additional configurations for a service module
	 * Receive the form for the form used by point
	 */
	function triggerDispPointAdditionSetup(&$obj)
	{
		$current_module_srl = Context::get('module_srl');
		$current_module_srls = Context::get('module_srls');

		if(!$current_module_srl && !$current_module_srls)
		{
			$current_module_info = Context::get('current_module_info');
			$current_module_srl = $current_module_info->module_srl;
			if(!$current_module_srl) return;
		}
		// Get the configuration information
		$config = ModuleModel::getModuleConfig('point');

		if($current_module_srl)
		{
			$module_config = ModuleModel::getModulePartConfig('point', $current_module_srl);
			if(!$module_config)
			{
				$module_config = array();
				$module_config['insert_document'] = $config->insert_document;
				$module_config['insert_comment'] = $config->insert_comment;
				$module_config['upload_file'] = $config->upload_file;
				$module_config['download_file'] = $config->download_file;
				$module_config['read_document'] = $config->read_document;
				$module_config['voted'] = $config->voted;
				$module_config['blamed'] = $config->blamed;
				$module_config['voted_comment'] = $config->voted_comment;
				$module_config['blamed_comment'] = $config->blamed_comment;
			}
			elseif(is_object($module_config))
			{
				$module_config = get_object_vars($module_config);
			}
		}

		$module_config['module_srl'] = $current_module_srl;
		$module_config['point_name'] = $config->point_name;
		Context::set('module_config', $module_config);
		// Set the template file
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'point_module_config');
		$obj .= $tpl;
	}
}
/* End of file point.view.php */
/* Location: ./modules/point/point.view.php */
