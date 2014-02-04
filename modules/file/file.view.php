<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The view class file module
 * @author NAVER (developers@xpressengine.com)
 */
class fileView extends file
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * This is for additional configuration for service module
	 * It only receives file configurations
	 *
	 * @param string $obj The html string of page of addition setup of module
	 * @return Object
	 */
	function triggerDispFileAdditionSetup(&$obj)
	{
		$current_module_srl = Context::get('module_srl');
		$current_module_srls = Context::get('module_srls');

		if(!$current_module_srl && !$current_module_srls)
		{
			// Get information of the current module
			$current_module_info = Context::get('current_module_info');
			$current_module_srl = $current_module_info->module_srl;
			if(!$current_module_srl) return new Object();
		}
		// Get file configurations of the module
		$oFileModel = getModel('file');
		$file_config = $oFileModel->getFileModuleConfig($current_module_srl);
		Context::set('file_config', $file_config);
		// Get a permission for group setting
		$oMemberModel = getModel('member');
		$site_module_info = Context::get('site_module_info');
		$group_list = $oMemberModel->getGroups($site_module_info->site_srl);
		Context::set('group_list', $group_list);
		// Set a template file
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'file_module_config');
		$obj .= $tpl;

		return new Object();
	}
}
/* End of file file.view.php */
/* Location: ./modules/file/file.view.php */
