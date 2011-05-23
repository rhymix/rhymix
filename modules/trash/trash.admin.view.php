<?php
/**
 * @class  trashView
 * @author NHN (developers@xpressengine.com)
 * @brief View class of the module trash
 **/

class trashAdminView extends trash {

	/**
	 * @brief Initialization
	 **/
	function init() {
		// 템플릿 경로 지정 (board의 경우 tpl에 관리자용 템플릿 모아놓음)
		$template_path = sprintf("%stpl/",$this->module_path);
		$this->setTemplatePath($template_path);
	}

	/**
	 * @brief trash list
	 **/
	function dispTrashAdminList() {
		$oTrashModel = getModel('trash');
		$output = $oTrashModel->getTrashList($args);

		Context::set('trash_list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		// 템플릿 파일 지정
		$this->setTemplateFile('trash_list');
	}
}
?>
