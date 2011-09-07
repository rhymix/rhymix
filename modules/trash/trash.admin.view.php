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
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of posts to display on a single page
		$args->page_count = 5; // /< the number of pages that appear in the page navigation

		$args->search_target = Context::get('search_target'); // /< search (title, contents ...)
		$args->search_keyword = Context::get('search_keyword'); // /< keyword to search

		$oTrashModel = getModel('trash');
		$output = $oTrashModel->getTrashList($args);

		// for no text comment language and for document manange language
		$oCommentModel = &getModel('comment');
		$oDocumentModel = &getModel('document');

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
