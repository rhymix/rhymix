<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * commentView class
 * comment module's view class
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/comment
 * @version 0.1
 */
class commentView extends comment
{

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{

	}

	/**
	 * Add a form fot comment setting on the additional setting of module
	 * @param string $obj
	 * @return string
	 */
	function triggerDispCommentAdditionSetup(&$obj)
	{
		$current_module_srl = Context::get('module_srl');
		$current_module_srls = Context::get('module_srls');

		if(!$current_module_srl && !$current_module_srls)
		{
			// get information of the selected module
			$current_module_info = Context::get('current_module_info');
			$current_module_srl = $current_module_info->module_srl;
			if(!$current_module_srl)
			{
				return new Object();
			}
		}

		// get the comment configuration
		$oCommentModel = getModel('comment');
		$comment_config = $oCommentModel->getCommentConfig($current_module_srl);
		Context::set('comment_config', $comment_config);

		// get a group list
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list', $group_list);

		// Set a template file
		$oTemplate = TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path . 'tpl', 'comment_module_config');
		$obj .= $tpl;

		return new Object();
	}

}
/* End of file comment.view.php */
/* Location: ./modules/comment/comment.view.php */
