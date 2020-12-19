<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * trashAdminView class
 * Admin view class of the trash module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/trash
 * @version 0.1
 */
class trashAdminView extends trash
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
		// 문서 및 댓글 모듈 lang 파일 로딩
		Context::loadLang('./modules/document/lang');
		Context::loadLang('./modules/comment/lang');
		
		// 템플릿 경로 지정 (board의 경우 tpl에 관리자용 템플릿 모아놓음)
		$template_path = sprintf("%stpl/",$this->module_path);
		$this->setTemplatePath($template_path);
	}

	/**
	 * Trash list
	 * @return void
	 */
	function dispTrashAdminList()
	{
		$args = new stdClass();
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of posts to display on a single page
		$args->page_count = 5; // /< the number of pages that appear in the page navigation
		$args->originModule = Context::get('originModule');

		$search_target = Context::get('search_target'); // /< search (title, contents ...)
		$search_keyword = Context::get('search_keyword'); // /< keyword to search
		
		switch($search_target)
		{
			case 'title':
				$args->s_title = $search_keyword;
				break;
			case 'user_id':
				$args->s_user_id = $search_keyword;
				break;
			case 'nick_name':
				$args->s_nick_name = $search_keyword;
				break;
			case 'trash_ipaddress':
				$args->s_ipaddress = $search_keyword;
				break;
		}

		$oTrashModel = getModel('trash');
		$output = $oTrashModel->getTrashList($args);

		Context::set('trash_list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		$oModuleModel = getModel('module');
		$module_list = array();
		$mod_srls = array();
		foreach($output->data as $oTrashVO)
		{
			$mod_srls[] = $oTrashVO->unserializedObject['module_srl'];
		}
		$mod_srls = array_unique($mod_srls);
		// Module List
		$mod_srls_count = count($mod_srls);
		if($mod_srls_count)
		{
			$columnList = array('module_srl', 'mid', 'browser_title');
			$module_output = $oModuleModel->getModulesInfo($mod_srls, $columnList);
			if($module_output && is_array($module_output))
			{
				foreach($module_output as $module)
				{
					$module_list[$module->module_srl] = $module;
				}
			}
		}

		Context::set('module_list', $module_list);

		// 템플릿 파일 지정
		$this->setTemplateFile('trash_list');
	}
	
	
	// Trash View - sejin7940
	function dispTrashAdminView() 
	{
		$trash_srl = Context::get('trash_srl');

		$oTrashModel = getModel('trash');
		$output = $oTrashModel->getTrash($trash_srl);
		if(!$output->data->getTrashSrl()) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$originObject = unserialize($output->data->getSerializedObject());
		if(is_array($originObject)) $originObject = (object)$originObject;

		Context::set('oTrashVO',$output->data);
		Context::set('oOrigin',$originObject);

		$oMemberModel = &getModel('member');
		$remover_info = $oMemberModel->getMemberInfoByMemberSrl($output->data->getRemoverSrl());
		Context::set('remover_info', $remover_info);

		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($originObject->module_srl);
		Context::set('module_info', $module_info);

		if($originObject) {
			$args_extra = new stdClass;
			$args_extra->module_srl = $originObject->module_srl;
			$args_extra->document_srl = $originObject->document_srl;
			$output_extra = executeQueryArray('trash.getDocumentExtraVars', $args_extra);				
			Context::set('oOriginExtraVars',$output_extra->data);
		}
		$this->setTemplateFile('trash_view');
	}
}
/* End of file trash.admin.view.php */
/* Location: ./modules/trash/trash.admin.view.php */
