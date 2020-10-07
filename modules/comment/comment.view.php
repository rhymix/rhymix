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
				return new BaseObject();
			}
		}

		// get the comment configuration
		$comment_config = CommentModel::getCommentConfig($current_module_srl);
		Context::set('comment_config', $comment_config);

		// get a group list
		$group_list = MemberModel::getGroups();
		Context::set('group_list', $group_list);

		// Set a template file
		$oTemplate = TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path . 'tpl', 'comment_module_config');
		$obj .= $tpl;

		return new BaseObject();
	}

	/**
	 * Report an improper comment
	 * @return void
	 */
	function dispCommentDeclare()
	{
		$this->setLayoutFile('popup_layout');
		$comment_srl = Context::get('target_srl');

		// A message appears if the user is not logged-in
		if(!$this->user->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		// Creates an object for displaying the selected comment
		$oComment = CommentModel::getComment($comment_srl);
		if(!$oComment->isExists())
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		// Check permissions
		if(!$oComment->isAccessible())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		// Browser title settings
		Context::set('target_comment', $oComment);

		Context::set('target_srl', $comment_srl);

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('declare_comment');
	}
}
/* End of file comment.view.php */
/* Location: ./modules/comment/comment.view.php */
