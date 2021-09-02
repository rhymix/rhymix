<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * commentAdminController class
 * admin controller class of the comment module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/comment
 * @version 0.1
 */
class commentAdminController extends comment
{

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{

	}

	/**
	 * Modify comment(s) status to publish/unpublish if calling module is using Comment Approval System
	 * @return void
	 */
	function procCommentAdminChangePublishedStatusChecked()
	{
		// Error display if none is selected
		$cart = Context::get('cart');
		if(!is_array($cart))
		{
			$comment_srl_list = explode('|@|', $cart);
		}
		else
		{
			$comment_srl_list = $cart;
		}

		$this->procCommentAdminChangeStatus();

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCommentAdminList', 'search_keyword', '');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Change comment status
	 * @return void|object
	 */
	function procCommentAdminChangeStatus()
	{
		$will_publish = Context::get('will_publish');

		// Error display if none is selected
		$cart = Context::get('cart');
		if(!$cart)
		{
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
		}
		if(!is_array($cart))
		{
			$comment_srl_list = explode('|@|', $cart);
		}
		else
		{
			$comment_srl_list = $cart;
		}

		$args = new stdClass();
		$args->status = $will_publish;
		if ($will_publish)
		{
			$args->old_status = array(0, 1, 2, 3, 4, 5, 6);
		}
		else
		{
			$args->old_status = array(0, 1);
		}
		$args->comment_srls_list = $comment_srl_list;
		$output = executeQuery('comment.updatePublishedStatus', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		else
		{
			//update comment count for document
			$updated_documents_arr = array();
			// create the controller object of the document
			$oDocumentController = getController('document');
			// create the model object of the document
			$oDocumentModel = getModel('document');
			// create the comment model object
			$oCommentModel = getModel('comment');
			//get admin info
			$logged_info = Context::get('logged_info');
			//$oMemberModule = getModel("member");
			//$logged_info = $oMemberModule->getMemberInfoByMemberSrl($logged_member_srl);
			$new_status = lang('comment.published_name_list')[$will_publish];
			foreach($comment_srl_list as $comment_srl)
			{
				// check if comment already exists
				$comment = $oCommentModel->getComment($comment_srl);
				if($comment->comment_srl != $comment_srl || $comment->status != $will_publish)
				{
					continue;
				}

				$document_srl = $comment->document_srl;
				if(!in_array($document_srl, $updated_documents_arr))
				{
					$updated_documents_arr[] = $document_srl;
					// update the number of comments
					$comment_count = $oCommentModel->getCommentCount($document_srl);
					// update comment count of the article posting
					$output = $oDocumentController->updateCommentCount($document_srl, $comment_count, NULL, FALSE);

					$oDocument = $oDocumentModel->getDocument($document_srl);

					$oModuleModel = getModel("module");
					$module_info = $oModuleModel->getModuleInfoByModuleSrl($comment->module_srl);
					if($module_info->admin_mail)
					{
						$mail_title = "[Rhymix - " . $module_info->mid . "] comment(s) status changed to " . $new_status . " on document: \"" . $oDocument->getTitleText() . "\"";
						$mail_content = "
							The comment #" . $comment_srl . " on document \"" . $oDocument->getTitleText() . "\" has been " . $new_status . " by admin of <strong><i>" . strtoupper($module_info->mid) . "</i></strong> module.
							<br />
							<br />Comment content:
							" . $comment->content . "
							<br />
							";
						$oMail = new \Rhymix\Framework\Mail();
						$oMail->setSubject($mail_title);
						$oMail->setBody($mail_content);
						$oMail->setFrom(config('mail.default_from') ?: $logged_info->email_address, $logged_info->nick_name);
						$oMail->setReplyTo($logged_info->email_address);
						foreach (array_map('trim', explode(',', $module_info->admin_mail)) as $email_address)
						{
							$oMail->addTo($email_address);
						}
						$oMail->send();
					}
				}
			}
			// call a trigger for calling "send mail to subscribers" (for moment just for forum)
			ModuleHandler::triggerCall("comment.procCommentAdminChangeStatus", "after", $comment_srl_list);
		}

		// for message send - start
		$message_content = Context::get('message_content');
		if($message_content)
		{
			$message_content = nl2br($message_content);
		}

		if($message_content)
		{
			$this->_sendMessageForComment($message_content, $comment_srl_list);
		}
		// for message send - end
	}

	/**
	 * Delete the selected comment from the administrator page
	 * @return void
	 */
	function procCommentAdminDeleteChecked()
	{
		$isTrash = Context::get('is_trash');

		// Error display if none is selected
		$cart = Context::get('cart');
		if(!$cart)
		{
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
		}
		if(!is_array($cart))
		{
			$comment_srl_list = explode('|@|', $cart);
		}
		else
		{
			$comment_srl_list = $cart;
		}
		$comment_count = count($comment_srl_list);
		if(!$comment_count)
		{
			throw new Rhymix\Framework\Exception('msg_cart_is_null');
		}

		$oCommentController = getController('comment');
		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// for message send - start
		$message_content = Context::get('message_content');
		if($message_content)
		{
			$message_content = nl2br($message_content);
		}

		if($message_content)
		{
			$this->_sendMessageForComment($message_content, $comment_srl_list);
		}
		// for message send - end
		// comment into trash
		if($isTrash == 'true')
		{
			$this->_moveCommentToTrash($comment_srl_list, $oCommentController, $oDB, $message_content);
		}

		$deleted_count = 0;
		// Delete the comment posting
		for($i = 0; $i < $comment_count; $i++)
		{
			$comment_srl = trim($comment_srl_list[$i]);
			if(!$comment_srl)
			{
				continue;
			}

			$output = $oCommentController->deleteComment($comment_srl, TRUE, toBool($isTrash));
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			$deleted_count++;
		}

		$oDB->commit();

		$msgCode = '';
		if($isTrash == 'true')
		{
			$msgCode = 'success_trashed';
		}
		else
		{
			$msgCode = 'success_deleted';
		}
		//$this->setMessage( sprintf(lang('msg_checked_comment_is_deleted'), $deleted_count) );
		$this->setMessage($msgCode, 'info');

		//set url params
		$search_keyword = Context::get('search_keyword');
		$search_target = Context::get('search_target');
		$page = Context::get('page');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCommentAdminList', 'search_keyword', $search_keyword, 'search_target', $search_target, 'page', $page);
		$this->setRedirectUrl($returnUrl);
	}

	private function _sendMessageForComment($message_content, $comment_srl_list)
	{
		$oCommunicationController = getController('communication');
		$oCommentModel = getModel('comment');

		$logged_info = Context::get('logged_info');

		$title = cut_str($message_content, 10, '...');
		$sender_member_srl = $logged_info->member_srl;

		$comment_count = count($comment_srl_list);
		for($i = 0; $i < $comment_count; $i++)
		{
			$comment_srl = $comment_srl_list[$i];
			$oComment = $oCommentModel->getComment($comment_srl, TRUE);

			if(!$oComment->get('member_srl') || $oComment->get('member_srl') == $sender_member_srl)
			{
				continue;
			}

			$content = sprintf("<div>%s</div><hr /><div style=\"font-weight:bold\">%s</div>", $message_content, $oComment->getContentText(20));

			$oCommunicationController->sendMessage($sender_member_srl, $oComment->get('member_srl'), $title, $content, false, null, false);
		}
	}

	/**
	 * comment move to trash
	 * @return void|object
	 */
	function _moveCommentToTrash($commentSrlList, &$oCommentController, &$oDB, $message_content = NULL)
	{
		require_once(RX_BASEDIR . 'modules/trash/model/TrashVO.php');

		if(is_array($commentSrlList))
		{
			$logged_info = Context::get('logged_info');
			$oCommentModel = getModel('comment');
			$commentItemList = $oCommentModel->getComments($commentSrlList);
			$oTrashAdminController = getAdminController('trash');

			foreach($commentItemList AS $key => $oComment)
			{
				$oTrashVO = new TrashVO();
				$oTrashVO->setTrashSrl(getNextSequence());
				$oTrashVO->setTitle($oComment->getContentText(200));
				$oTrashVO->setOriginModule('comment');
				$oTrashVO->setSerializedObject(serialize($oComment->variables));
				$oTrashVO->setDescription($message_content);

				$output = $oTrashAdminController->insertTrash($oTrashVO);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
				
				$obj = new stdClass;
				$obj->comment_srl = $oComment->get('comment_srl');
				$obj->module_srl = $oComment->get('module_srl');
				$obj->document_srl = $oComment->get('document_srl');
				$obj->parent_srl = $oComment->get('parent_srl');
				$obj->member_srl = $oComment->get('member_srl');
				$obj->regdate = $oComment->get('regdate');
				$obj->last_update = $oComment->get('last_update');
				ModuleHandler::triggerCall('comment.moveCommentToTrash', 'after', $obj);
			}
		}
	}

	/**
	  * @fn procCommentAdminMoveToTrash
	  * @brief move a comment to trash
	  * @see commentModel::getCommentMenu
	  */
	function procCommentAdminMoveToTrash()
	{
		$oDB = DB::getInstance();
		$oDB->begin();

		$comment_srl = Context::get('comment_srl');
		$oCommentModel = getModel('comment');
		$oCommentController = getController('comment');
		$oComment = $oCommentModel->getComment($comment_srl, false);

		if(!$oComment->isGranted()) throw new Rhymix\Framework\Exceptions\NotPermitted;

		$message_content = "";
		$this->_moveCommentToTrash(array($comment_srl), $oCommentController, $oDB, $message_content);

		$isTrash = true;
		$output = $oCommentController->deleteComment($comment_srl, TRUE, $isTrash);

		$oDB->commit();

		$returnUrl = Context::get('cur_url');
		$this->add('redirect_url', $returnUrl);
	}

	/**
	 * Cancel the blacklist of abused comments reported by other users
	 * @return void|object
	 */
	function procCommentAdminCancelDeclare()
	{
		$comment_srl = trim(Context::get('comment_srl'));

		if($comment_srl)
		{
			$args = new stdClass();
			$args->comment_srl = $comment_srl;
			$output = executeQuery('comment.deleteDeclaredComments', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}
	}

	/**
	 * Comment add to _SESSION
	 * @return void
	 */
	function procCommentAdminAddCart()
	{
		$comment_srl = (int) Context::get('comment_srl');

		$oCommentModel = getModel('comment');
		$columnList = array('comment_srl');
		$commentSrlList = array($comment_srl);

		$output = $oCommentModel->getComments($commentSrlList);

		if(is_array($output))
		{
			foreach($output AS $key => $value)
			{
				if($_SESSION['comment_management'][$key])
				{
					unset($_SESSION['comment_management'][$key]);
				}
				else
				{
					$_SESSION['comment_management'][$key] = TRUE;
				}
			}
		}
	}

	/**
	 * Delete all comments of the specific module
	 * @return object
	 */
	function deleteModuleComments($module_srl)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$output = executeQuery('comment.deleteModuleComments', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$output = executeQuery('comment.deleteModuleCommentsList', $args);

		//remove from cache
		Rhymix\Framework\Cache::clearGroup('newestCommentsList');
		return $output;
	}

	/**
	 * Restore comment from trash module, called by trash module
	 * this method is passived
	 * @return object
	 */
	function restoreTrash($originObject)
	{
		if(is_array($originObject))
		{
			$originObject = (object) $originObject;
		}
		
		$oCommentController = getController('comment');
		$oCommentModel = getModel('comment');

		$oComment = $oCommentModel->getComment($originObject->comment_srl);
		if($oComment->isExists())
		{
			$output = $oCommentController->updateCommentByRestore($originObject);
		}
		else
		{
			$output = $oCommentController->insertComment($originObject, true);
		}

		return $output;
	}

	/**
	 * Empty comment in trash, called by trash module
	 * this method is passived
	 * @return object
	 */
	function emptyTrash($originObject)
	{
		$originObject = unserialize($originObject);
		if(is_array($originObject))
		{
			$originObject = (object) $originObject;
		}

		$oComment = new commentItem();
		$oComment->setAttribute($originObject);

		//already comment deleted, therefore only comment log delete
		$oCommentController = getController('comment');
		$args = new stdClass();
		$args->comment_srl = $oComment->get('comment_srl');
		$output = $oCommentController->deleteCommentLog($args);
		return $output;
	}

}
/* End of file comment.admin.controller.php */
/* Location: ./modules/comment/comment.admin.controller.php */
