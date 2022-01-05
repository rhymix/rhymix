<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * boardAdminModel class
 * Board the module's admin model class
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/board
 * @version 0.1
 */
class boardAdminModel extends board
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Get the board module admin simple setting page
	 * @return void
	 */
	public function getBoardAdminSimpleSetup($moduleSrl, $setupUrl)
	{
		if(!$moduleSrl)
		{
			return;
		}
		Context::set('module_srl', $moduleSrl);

		// default module info setting
		$oModuleModel = getModel('module');
		$moduleInfo = $oModuleModel->getModuleInfoByModuleSrl($moduleSrl);
		$moduleInfo->use_status = explode('|@|', $moduleInfo->use_status);
		if($moduleInfo)
		{
			Context::set('module_info', $moduleInfo);
		}

		// get document status list
		$oDocumentModel = getModel('document');
		$documentStatusList = array_intersect_key($oDocumentModel->getStatusNameList(), [
			'PUBLIC' => true,
			'SECRET' => true,
		]);
		Context::set('document_status_list', $documentStatusList);

		// set order target list
		foreach($this->order_target AS $key)
		{
			$order_target[$key] = lang($key);
		}
		$order_target['list_order'] = lang('document_srl');
		$order_target['update_order'] = lang('last_update');
		Context::set('order_target', $order_target);

		// for advanced language & url
		$oAdmin = getClass('admin');
		Context::set('setupUrl', $setupUrl);

		// Extract admin ID set in the current module
		$admin_member = $oModuleModel->getAdminId($moduleSrl);
		Context::set('admin_member', $admin_member);

		$oTemplate = &TemplateHandler::getInstance();
		$html = $oTemplate->compile($this->module_path.'tpl/', 'board_setup_basic');

		return $html;
	}

}
/* End of file board.admin.model.php */
/* Location: ./modules/board/board.admin.model.php */
