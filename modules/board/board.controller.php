<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  boardController
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module Controller class
 **/

class boardController extends board
{
	/**
	 * @brief initialization
	 **/
	function init()
	{
	}
	
	/**
	 * @brief insert document
	 **/
	function procBoardInsertDocument()
	{
		// check grant
		if(!$this->grant->write_document)
		{
			return new Object(-1, 'msg_not_permitted');
		}
		
		// setup variables
		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_srl;
		$obj->commentStatus = $obj->comment_status;
		
		// Return error if content is empty.
		if (is_empty_html_content($obj->content))
		{
			return new Object(-1, 'msg_empty_content');
		}
		
		// unset document style if not manager
		if(!$this->grant->manager)
		{
			unset($obj->is_notice);
			unset($obj->title_color);
			unset($obj->title_bold);
		}
		else
		{
			$obj->is_admin = 'Y';
		}
		
		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');
		
		$_SECRET = $oDocumentModel->getConfigStatus('secret');
		$use_status = explode('|@|', $this->module_info->use_status);
		
		// Set status
		if(($obj->is_secret == 'Y' || $obj->status == $_SECRET) && is_array($use_status) && in_array($_SECRET, $use_status))
		{
			$obj->status = $_SECRET;
		}
		else
		{
			unset($obj->is_secret);
			$obj->status = $oDocumentModel->getConfigStatus('public');
		}
		
		// Set update log
		if($this->module_info->update_log == 'Y')
		{
			$obj->update_log_setting = 'Y';
		}
		
		$manual = false;
		$logged_info = Context::get('logged_info');
		
		// Set anonymous information
		if($this->module_info->use_anonymous == 'Y')
		{
			if(!$obj->document_srl)
			{
				$obj->document_srl = getNextSequence();
			}
			
			$manual = true;
			$anonymous_name = $this->module_info->anonymous_name ?: 'anonymous';
			$anonymous_name = $this->createAnonymousName($anonymous_name, $logged_info->member_srl, $obj->document_srl);
			$this->module_info->admin_mail = '';
			
			$obj->notify_message = 'N';
			$obj->email_address = $obj->homepage = $obj->user_id = '';
			$obj->user_name = $obj->nick_name = $anonymous_name;
		}
		
		// Update if the document already exists.
		$oDocument = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);
		if($oDocument->isExists())
		{
			if(!$oDocument->isGranted())
			{
				return new Object(-1, 'msg_not_permitted');
			}
			
			// Protect admin document
			$member_info = getModel('member')->getMemberInfoByMemberSrl($oDocument->get('member_srl'));
			if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
			{
				return new Object(-1, 'msg_admin_document_no_modify');
			}
			
			// if document status is temp
			if($oDocument->get('status') == $oDocumentModel->getConfigStatus('temp'))
			{
				// if use anonymous, set the member_srl to a negative number
				if($this->module_info->use_anonymous == 'Y')
				{
					$obj->member_srl = abs($oDocument->get('member_srl')) * -1;
					$oDocument->add('member_srl', $obj->member_srl);
				}
				
				// Update list order, date
				$obj->last_update = $obj->regdate = date('YmdHis');
				$obj->update_order = $obj->list_order = (getNextSequence() * -1);
			}
			else
			{
				// Protect document by comment
				if($this->module_info->protect_content == 'Y' || $this->module_info->protect_update_content == 'Y')
				{
					if($oDocument->get('comment_count') > 0 && !$this->grant->manager)
					{
						return new Object(-1, 'msg_protect_update_content');
					}
				}
				
				// Protect document by date
				if($this->module_info->protect_document_regdate > 0 && !$this->grant->manager)
				{
					if($oDocument->get('regdate') < date('YmdHis', strtotime('-' . $this->module_info->protect_document_regdate . ' day')))
					{
						return new Object(-1, sprintf(lang('msg_protect_regdate_document'), $this->module_info->protect_document_regdate));
					}
				}
				
				// notice & document style same as before if not manager
				if(!$this->grant->manager)
				{
					$obj->is_notice = $oDocument->get('is_notice');
					$obj->title_color = $oDocument->get('title_color');
					$obj->title_bold = $oDocument->get('title_bold');
				}
				
				$obj->reason_update = escape($obj->reason_update);
			}
			
			// Update
			$output = $oDocumentController->updateDocument($oDocument, $obj, $manual);
			
			$msg_code = 'success_updated';
		}
		// Insert a new document.
		else
		{
			// if use anonymous, set the member_srl to a negative number
			if($this->module_info->use_anonymous == 'Y')
			{
				$obj->member_srl = $logged_info->member_srl * -1;
			}
			
			// Update list order if document_srl is already assigned
			if ($obj->document_srl)
			{
				$obj->update_order = $obj->list_order = (getNextSequence() * -1);
			}
			
			// Insert
			$output = $oDocumentController->insertDocument($obj, $manual, false, $obj->document_srl ? false : true);
			
			if ($output->toBool())
			{
				// Set grant for the new document.
				$oDocument = $oDocumentModel->getDocument($output->get('document_srl'));
				$oDocument->setGrantForSession();
				
				// send an email to admin user
				if ($this->module_info->admin_mail && config('mail.default_from'))
				{
					$mail_title = sprintf(lang('msg_document_notify_mail'), $this->module_info->browser_title, cut_str($obj->title, 20, '...'));
					$mail_content = sprintf("From : <a href=\"%s\">%s</a><br/>\r\n%s", getFullUrl('', 'document_srl', $output->get('document_srl')), getFullUrl('', 'document_srl', $output->get('document_srl')), $obj->content);
					
					$oMail = new \Rhymix\Framework\Mail();
					$oMail->setSubject($mail_title);
					$oMail->setBody($mail_content);
					foreach (array_map('trim', explode(',', $this->module_info->admin_mail)) as $email_address)
					{
						if ($email_address)
						{
							$oMail->addTo($email_address);
						}
					}
					$oMail->send();
				}
			}
			
			$msg_code = 'success_registed';
		}
		
		// if there is an error
		if(!$output->toBool())
		{
			return $output;
		}
		
		// return the results
		$this->add('mid', Context::get('mid'));
		$this->add('document_srl', $output->get('document_srl'));
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'document_srl', $output->get('document_srl')));
		
		// alert a message
		$this->setMessage($msg_code);
	}

	function procBoardRevertDocument()
	{
		$update_id = Context::get('update_id');
		$logged_info = Context::get('logged_info');
		if(!$update_id)
		{
			return new Object(-1, 'msg_no_update_id');
		}

		$oDocumentModel = getModel('document');
		$oDocumentController = getController('document');
		$update_log = $oDocumentModel->getUpdateLog($update_id);

		if($logged_info->is_admin != 'Y')
		{
			$Exists_log = $oDocumentModel->getUpdateLogAdminisExists($update_log->document_srl);
			if($Exists_log === true)
			{
				return new Object(-1, 'msg_admin_update_log');
			}
		}

		if(!$update_log)
		{
			return new Object(-1, 'msg_no_update_log');
		}

		$oDocument = $oDocumentModel->getDocument($update_log->document_srl);
		$obj = new stdClass();
		$obj->title = $update_log->title;
		$obj->document_srl = $update_log->document_srl;
		$obj->title_bold = $update_log->title_bold;
		$obj->title_color = $update_log->title_color;
		$obj->content = $update_log->content;
		$obj->update_log_setting = 'Y';
		$obj->reason_update = lang('board.revert_reason_update');
		$output = $oDocumentController->updateDocument($oDocument, $obj);
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'),'act', '', 'document_srl', $update_log->document_srl));
		$this->add('mid', Context::get('mid'));
		$this->add('document_srl', $update_log->document_srl);
	}

	/**
	 * @brief delete the document
	 **/
	function procBoardDeleteDocument()
	{
		// get the document_srl
		$document_srl = Context::get('document_srl');

		// if the document is not existed
		if(!$document_srl)
		{
			return new Object(-1, 'msg_invalid_document');
		}

		$oDocumentModel = &getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		// check protect content
		if($this->module_info->protect_content == 'Y' || $this->module_info->protect_delete_content == 'Y')
		{
			if($oDocument->get('comment_count') > 0 && $this->grant->manager == false)
			{
				return new Object(-1, 'msg_protect_delete_content');
			}
		}

		if($this->module_info->protect_document_regdate > 0 && $this->grant->manager == false)
		{
			if($oDocument->get('regdate') < date('YmdHis', strtotime('-'.$this->module_info->protect_document_regdate.' day')))
			{
				$format =  lang('msg_protect_regdate_document');
				$massage = sprintf($format, $this->module_info->protect_document_regdate);
				return new Object(-1, $massage);
			}
		}
		// generate document module controller object
		$oDocumentController = getController('document');
		if($this->module_info->trash_use == 'Y')
		{
			// move the trash
			if($oDocument->isGranted() === true)
			{
				$output = $oDocumentController->moveDocumentToTrash($oDocument);
				if(!$output->toBool())
				{
					return $output;
				}
			}
		}
		else
		{
			// delete the document
			$output = $oDocumentController->deleteDocument($document_srl, $this->grant->manager);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		// alert an message
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '', 'page', Context::get('page'), 'document_srl', ''));
		$this->add('mid', Context::get('mid'));
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');
	}

	/**
	 * @brief vote
	 **/
	function procBoardVoteDocument()
	{
		// generate document module controller object
		$oDocumentController = getController('document');

		$document_srl = Context::get('document_srl');
		return $oDocumentController->updateVotedCount($document_srl);
	}

	/**
	 * @brief insert comments
	 **/
	function procBoardInsertComment()
	{
		// check grant
		if(!$this->grant->write_comment)
		{
			return new Object(-1, 'msg_not_permitted');
		}
		$logged_info = Context::get('logged_info');

		// get the relevant data for inserting comment
		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_srl;

		if(!$this->module_info->use_status) $this->module_info->use_status = 'PUBLIC';
		if(!is_array($this->module_info->use_status))
		{
			$this->module_info->use_status = explode('|@|', $this->module_info->use_status);
		}

		if(in_array('SECRET', $this->module_info->use_status))
		{
			$this->module_info->secret = 'Y';
		}
		else
		{
			unset($obj->is_secret);
			$this->module_info->secret = 'N';
		}

		// check if the doument is existed
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($obj->document_srl);
		if(!$oDocument->isExists())
		{
			return new Object(-1,'msg_not_founded');
		}

		// For anonymous use, remove writer's information and notifying information
		if($this->module_info->use_anonymous == 'Y')
		{
			$this->module_info->admin_mail = '';
			$obj->notify_message = 'N';
			$obj->member_srl = -1*$logged_info->member_srl;
			$obj->email_address = $obj->homepage = $obj->user_id = '';
			$obj->user_name = $obj->nick_name = $this->createAnonymousName($this->module_info->anonymous_name ?: 'anonymous', $logged_info->member_srl, $obj->document_srl);
			$manual = true;
		}
		else
		{
			$manual = false;
		}

		// generate comment  module model object
		$oCommentModel = getModel('comment');

		// generate comment module controller object
		$oCommentController = getController('comment');

		// check the comment is existed
		// if the comment is not existed, then generate a new sequence
		if(!$obj->comment_srl)
		{
			$obj->comment_srl = getNextSequence();
		}
		else
		{
			$comment = $oCommentModel->getComment($obj->comment_srl, $this->grant->manager);
			if($this->module_info->protect_update_comment === 'Y' && $this->grant->manager == false)
			{
				$childs = $oCommentModel->getChildComments($obj->comment_srl);
				if(count($childs) > 0)
				{
					return new Object(-1, 'msg_board_update_protect_comment');
				}
			}
		}

		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($comment->member_srl);

		if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
		{
			return new Object(-1, 'msg_admin_comment_no_modify');
		}

		// INSERT if comment_srl does not exist.
		if($comment->comment_srl != $obj->comment_srl)
		{
			// Update document last_update info?
			$update_document = $this->module_info->update_order_on_comment === 'N' ? false : true;
			
			// Parent exists.
			if($obj->parent_srl)
			{
				$parent_comment = $oCommentModel->getComment($obj->parent_srl);
				if(!$parent_comment->comment_srl)
				{
					return new Object(-1, 'msg_invalid_request');
				}
				if($parent_comment->isSecret() && $this->module_info->secret === 'Y')
				{
					$obj->is_secret = 'Y';
				}
				$output = $oCommentController->insertComment($obj, $manual, $update_document);
			}
			// Parent does not exist.
			else
			{
				$output = $oCommentController->insertComment($obj, $manual, $update_document);
			}
			// Set grant for the new comment.
			if ($output->toBool())
			{
				$comment = $oCommentModel->getComment($output->get('comment_srl'));
				$comment->setGrantForSession();
			}
		}
		// UPDATE if comment_srl already exists.
		else
		{
			if($this->module_info->protect_comment_regdate > 0 && $this->grant->manager == false)
			{
				if($comment->get('regdate') < date('YmdHis', strtotime('-'.$this->module_info->protect_document_regdate.' day')))
				{
					$format =  lang('msg_protect_regdate_comment');
					$massage = sprintf($format, $this->module_info->protect_document_regdate);
					return new Object(-1, $massage);
				}
			}
			// check the grant
			if(!$comment->isGranted())
			{
				return new Object(-1,'msg_not_permitted');
			}
			$obj->parent_srl = $comment->parent_srl;
			$output = $oCommentController->updateComment($obj, $this->grant->manager);
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_registed');
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '', 'document_srl', $obj->document_srl) . '#comment_' . $obj->comment_srl);
		$this->add('mid', Context::get('mid'));
		$this->add('document_srl', $obj->document_srl);
		$this->add('comment_srl', $obj->comment_srl);
	}

	/**
	 * @brief delete the comment
	 **/
	function procBoardDeleteComment()
	{
		// get the comment_srl
		$comment_srl = Context::get('comment_srl');

		$instant_delete = null;
		if($this->grant->manager == true)
		{
			$instant_delete = Context::get('instant_delete');
		}

		if(!$comment_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$oCommentModel = getModel('comment');

		if($this->module_info->protect_delete_comment === 'Y' && $this->grant->manager == false)
		{
			$childs = $oCommentModel->getChildComments($comment_srl);
			if(count($childs) > 0)
			{
				return new Object(-1, 'msg_board_delete_protect_comment');
			}
		}
		$comment = $oCommentModel->getComment($comment_srl, $this->grant->manager);
		if($this->module_info->protect_comment_regdate > 0 && $this->grant->manager == false)
		{
			if($comment->get('regdate') < date('YmdHis', strtotime('-'.$this->module_info->protect_document_regdate.' day')))
			{
				$format =  lang('msg_protect_regdate_comment');
				$massage = sprintf($format, $this->module_info->protect_document_regdate);
				return new Object(-1, $massage);
			}
		}
		// generate comment  controller object
		$oCommentController = getController('comment');

		$updateComment = false;
		if($this->module_info->comment_delete_message === 'yes' && $instant_delete != 'Y')
		{
			$output = $oCommentController->updateCommentByDelete($comment, $this->grant->manager);
			$updateComment = true;
			if($this->module_info->trash_use == 'Y')
			{
				$output = $oCommentController->moveCommentToTrash($comment, $updateComment);
			}
		}
		elseif(starts_with('only_comm', $this->module_info->comment_delete_message) && $instant_delete != 'Y')
		{
			$childs = $oCommentModel->getChildComments($comment_srl);
			if(count($childs) > 0)
			{
				$output = $oCommentController->updateCommentByDelete($comment, $this->grant->manager);
				$updateComment = true;
				if($this->module_info->trash_use == 'Y')
				{
					$output = $oCommentController->moveCommentToTrash($comment, $updateComment);
				}
			}
			else
			{
				if($this->module_info->trash_use == 'Y')
				{
					$output = $oCommentController->moveCommentToTrash($comment, $updateComment);
				}
				else
				{
					$output = $oCommentController->deleteComment($comment_srl, $this->grant->manager, FALSE, $childs);
					if(!$output->toBool())
					{
						return $output;
					}
				}
			}
		}
		else
		{
			if($this->module_info->trash_use == 'Y')
			{
				$output = $oCommentController->moveCommentToTrash($comment, $this->module_info->comment_delete_message);
			}
			else
			{
				$output = $oCommentController->deleteComment($comment_srl, $this->grant->manager);
				if(!$output->toBool())
				{
					return $output;
				}
			}
		}

		$this->add('mid', Context::get('mid'));
		$this->add('page', Context::get('page'));
		$this->add('document_srl', $output->get('document_srl'));
		$this->setMessage('success_deleted');
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '', 'page', Context::get('page'), 'document_srl', $output->get('document_srl')));
	}

	/**
	 * @brief delete the tracjback
	 **/
	function procBoardDeleteTrackback()
	{
		$trackback_srl = Context::get('trackback_srl');

		// generate trackback module controller object
		$oTrackbackController = getController('trackback');

		if(!$oTrackbackController) return;

		$output = $oTrackbackController->deleteTrackback($trackback_srl, $this->grant->manager);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('mid', Context::get('mid'));
		$this->add('page', Context::get('page'));
		$this->add('document_srl', $output->get('document_srl'));
		$this->setMessage('success_deleted');
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '', 'page', Context::get('page'), 'document_srl', $output->get('document_srl')));
	}

	/**
	 * @brief check the password for document and comment
	 **/
	function procBoardVerificationPassword()
	{
		// get the id number of the document and the comment
		$password = Context::get('password');
		$document_srl = Context::get('document_srl');
		$comment_srl = Context::get('comment_srl');

		$oMemberModel = getModel('member');

		// if the comment exists
		if($comment_srl)
		{
			// get the comment information
			$oCommentModel = getModel('comment');
			$oComment = $oCommentModel->getComment($comment_srl);
			if(!$oComment->isExists())
			{
				return new Object(-1, 'msg_invalid_request');
			}

			// compare the comment password and the user input password
			if(!$oMemberModel->isValidPassword($oComment->get('password'),$password))
			{
				return new Object(-1, 'msg_invalid_password');
			}

			$oComment->setGrantForSession();
		} else {
			 // get the document information
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl);
			if(!$oDocument->isExists())
			{
				return new Object(-1, 'msg_invalid_request');
			}

			// compare the document password and the user input password
			if(!$oMemberModel->isValidPassword($oDocument->get('password'),$password))
			{
				return new Object(-1, 'msg_invalid_password');
			}

			$oDocument->setGrantForSession();
		}
	}

	/**
	 * @brief the trigger for displaying 'view document' link when click the user ID
	 **/
	function triggerMemberMenu(&$obj)
	{
		$member_srl = Context::get('target_srl');
		$mid = Context::get('cur_mid');

		if(!$member_srl || !$mid)
		{
			return new Object();
		}

		$logged_info = Context::get('logged_info');

		// get the module information
		$oModuleModel = getModel('module');
		$columnList = array('module');
		$cur_module_info = $oModuleModel->getModuleInfoByMid($mid, 0, $columnList);

		if($cur_module_info->module != 'board')
		{
			return new Object();
		}

		// get the member information
		if($member_srl == $logged_info->member_srl)
		{
			$member_info = $logged_info;
		} else {
			$oMemberModel = getModel('member');
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		}

		if(!$member_info->user_id)
		{
			return new Object();
		}

		//search
		$url = getUrl('','mid',$mid,'search_target','nick_name','search_keyword',$member_info->nick_name);
		$oMemberController = getController('member');
		$oMemberController->addMemberPopupMenu($url, 'cmd_view_own_document', '');

		return new Object();
	}
	
	/**
	 * Create an anonymous nickname.
	 * 
	 * @param string $format
	 * @param int $member_srl
	 * @param int $document_srl
	 * @return string
	 */
	public function createAnonymousName($format, $member_srl, $document_srl)
	{
		if (strpos($format, '$NUM') !== false)
		{
			$num = hash_hmac('sha256', $member_srl ?: \RX_CLIENT_IP, config('crypto.authentication_key'));
			$num = sprintf('%08d', hexdec(substr($num, 0, 8)) % 100000000);
			return strtr($format, array('$NUM' => $num));
		}
		elseif (strpos($format, '$DAILYNUM') !== false)
		{
			$num = hash_hmac('sha256', ($member_srl ?: \RX_CLIENT_IP) . ':date:' . date('Y-m-d'), config('crypto.authentication_key'));
			$num = sprintf('%08d', hexdec(substr($num, 0, 8)) % 100000000);
			return strtr($format, array('$DAILYNUM' => $num));
		}
		elseif (strpos($format, '$DOCNUM') !== false)
		{
			$num = hash_hmac('sha256', ($member_srl ?: \RX_CLIENT_IP) . ':document_srl:' . $document_srl, config('crypto.authentication_key'));
			$num = sprintf('%08d', hexdec(substr($num, 0, 8)) % 100000000);
			return strtr($format, array('$DOCNUM' => $num));
		}
		elseif (strpos($format, '$DOCDAILYNUM') !== false)
		{
			$num = hash_hmac('sha256', ($member_srl ?: \RX_CLIENT_IP) . ':document_srl:' . $document_srl . ':date:' . date('Y-m-d'), config('crypto.authentication_key'));
			$num = sprintf('%08d', hexdec(substr($num, 0, 8)) % 100000000);
			return strtr($format, array('$DOCDAILYNUM' => $num));
		}
		else
		{
			return $format;
		}
	}
}
