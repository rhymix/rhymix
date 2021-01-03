<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pollAdminModel
 * @author NAVER (developers@xpressengine.com)
 * @brief The admin model class of the poll module
 */
class pollAdminModel extends poll
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Get the list of polls
	 */
	function getPollList($args)
	{
		return executeQueryArray('poll.getPollList', $args);
	}

	/**
	 * @brief Get the list of polls with member info
	 */
	function getPollListWithMember($args)
	{
		return executeQueryArray('poll.getPollListWithMember', $args);
	}

	/**
	 * @brief Get the original poll
	 */
	function getPollAdminTarget()
	{
		//$poll_srl = Context::get('poll_srl');
		$upload_target_srl = Context::get('upload_target_srl');

		$oDocument = DocumentModel::getDocument($upload_target_srl);
		if ($oDocument->isExists())
		{
			$this->add('document_srl', $oDocument->get('document_srl'));
		}
		else
		{
			$oComment = CommentModel::getComment($upload_target_srl);
			if ($oComment->isExists())
			{
				$this->add('document_srl', $oComment->get('document_srl'));
				$this->add('comment_srl', $oComment->get('comment_srl'));
			}
			else
			{
				$this->setError('msg_not_founded');
			}
		}
	}
}
/* End of file poll.admin.model.php */
/* Location: ./modules/poll/poll.admin.model.php */
