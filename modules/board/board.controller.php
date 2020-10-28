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
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		// setup variables
		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_srl;
		$obj->commentStatus = $obj->comment_status;
		
		// Remove disallowed Unicode symbols.
		if ($this->module_info->filter_specialchars !== 'N')
		{
			if (isset($obj->title))
			{
				$obj->title = utf8_clean($obj->title);
			}
			if (isset($obj->content))
			{
				$obj->content = utf8_clean($obj->content);
			}
			if (isset($obj->tags))
			{
				$obj->tags = utf8_clean($obj->tags);
			}
		}
		
		// Return error if content is empty.
		if (is_empty_html_content($obj->content))
		{
			throw new Rhymix\Framework\Exception('msg_empty_content');
		}
		
		// Return error if content is too large.
		$document_length_limit = ($this->module_info->document_length_limit ?: 1024) * 1024;
		if (strlen($obj->content) > $document_length_limit && !$this->grant->manager)
		{
			throw new Rhymix\Framework\Exception('msg_content_too_long');
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
		
		$oDocumentController = getController('document');
		
		$secret_status = DocumentModel::getConfigStatus('secret');
		$use_status = explode('|@|', $this->module_info->use_status);
		
		// Set status
		if(($obj->is_secret == 'Y' || $obj->status == $secret_status) && is_array($use_status) && in_array($secret_status, $use_status))
		{
			$obj->status = $secret_status;
		}
		else
		{
			unset($obj->is_secret);
			$obj->status = DocumentModel::getConfigStatus('public');
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
		$oDocument = DocumentModel::getDocument($obj->document_srl, $this->grant->manager);
		if($oDocument->isExists())
		{
			if(!$oDocument->isGranted())
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
			
			// Protect admin document
			$member_info = MemberModel::getMemberInfo($oDocument->get('member_srl'));
			if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
			{
				throw new Rhymix\Framework\Exception('msg_admin_document_no_modify');
			}
			
			// if document status is temp
			if($oDocument->get('status') == DocumentModel::getConfigStatus('temp'))
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
						throw new Rhymix\Framework\Exception('msg_protect_update_content');
					}
				}
				
				// Protect document by date
				if($this->module_info->protect_document_regdate > 0 && !$this->grant->manager)
				{
					if($oDocument->get('regdate') < date('YmdHis', strtotime('-' . $this->module_info->protect_document_regdate . ' day')))
					{
						throw new Rhymix\Framework\Exception(sprintf(lang('msg_protect_regdate_document'), $this->module_info->protect_document_regdate));
					}
				}
				
				// Preserve module_srl if the document belongs to a module that is included in this board
				if ($oDocument->get('module_srl') != $obj->module_srl && in_array($oDocument->get('module_srl'), explode(',', $this->module_info->include_modules ?: '')))
				{
					$obj->module_srl = $oDocument->get('module_srl');
					$obj->category_srl = $oDocument->get('category_srl');
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
				$oDocument = DocumentModel::getDocument($output->get('document_srl'));
				$oDocument->setGrantForSession();
				
				// send an email to admin user
				if ($this->module_info->admin_mail && config('mail.default_from'))
				{
					$browser_title = $this->module_info->browser_title;
					getController('module')->replaceDefinedLangCode($browser_title);
					$mail_title = sprintf(lang('msg_document_notify_mail'), $browser_title, cut_str($obj->title, 20, '...'));
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
			throw new Rhymix\Framework\Exception('msg_no_update_id');
		}

		$oDocumentController = getController('document');
		$update_log = DocumentModel::getUpdateLog($update_id);

		if($logged_info->is_admin != 'Y')
		{
			$Exists_log = DocumentModel::getUpdateLogAdminisExists($update_log->document_srl);
			if($Exists_log === true)
			{
				throw new Rhymix\Framework\Exception('msg_admin_update_log');
			}
		}

		if(!$update_log)
		{
			throw new Rhymix\Framework\Exception('msg_no_update_log');
		}

		$oDocument = DocumentModel::getDocument($update_log->document_srl);
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
			throw new Rhymix\Framework\Exception('msg_invalid_document');
		}

		$oDocument = DocumentModel::getDocument($document_srl);
		if (!$oDocument || !$oDocument->isExists())
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		if (!$oDocument->isGranted())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		// check protect content
		if($this->module_info->protect_content == 'Y' || $this->module_info->protect_delete_content == 'Y')
		{
			if($oDocument->get('comment_count') > 0 && $this->grant->manager == false)
			{
				throw new Rhymix\Framework\Exception('msg_protect_delete_content');
			}
		}

		if($this->module_info->protect_document_regdate > 0 && $this->grant->manager == false)
		{
			if($oDocument->get('regdate') < date('YmdHis', strtotime('-'.$this->module_info->protect_document_regdate.' day')))
			{
				$format =  lang('msg_protect_regdate_document');
				$massage = sprintf($format, $this->module_info->protect_document_regdate);
				throw new Rhymix\Framework\Exception($massage);
			}
		}
		// generate document module controller object
		$oDocumentController = getController('document');
		if($this->module_info->trash_use == 'Y')
		{
			$output = $oDocumentController->moveDocumentToTrash($oDocument);
			if(!$output->toBool())
			{
				return $output;
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
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		$logged_info = Context::get('logged_info');

		// get the relevant data for inserting comment
		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_srl;
		
		// Remove disallowed Unicode symbols.
		if ($this->module_info->filter_specialchars !== 'N')
		{
			if (isset($obj->content))
			{
				$obj->content = utf8_clean($obj->content);
			}
		}
		
		// Return error if content is empty.
		if (is_empty_html_content($obj->content))
		{
			throw new Rhymix\Framework\Exception('msg_empty_content');
		}

		// Return error if content is too large.
		$comment_length_limit = ($this->module_info->comment_length_limit ?: 128) * 1024;
		if (strlen($obj->content) > $comment_length_limit && !$this->grant->manager)
		{
			throw new Rhymix\Framework\Exception('msg_content_too_long');
		}
		
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
		$oDocument = DocumentModel::getDocument($obj->document_srl);
		if(!$oDocument->isExists())
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
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
			$comment = CommentModel::getComment($obj->comment_srl, $this->grant->manager);
			if($this->module_info->protect_update_comment === 'Y' && $this->grant->manager == false)
			{
				$childs = CommentModel::getChildComments($obj->comment_srl);
				if(count($childs) > 0)
				{
					throw new Rhymix\Framework\Exception('msg_board_update_protect_comment');
				}
			}
		}

		$member_info = MemberModel::getMemberInfo($comment->member_srl);
		if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_admin_comment_no_modify');
		}

		// INSERT if comment_srl does not exist.
		if($comment->comment_srl != $obj->comment_srl)
		{
			// Update document last_update info?
			$update_document = $this->module_info->update_order_on_comment === 'N' ? false : true;
			
			// Parent exists.
			if($obj->parent_srl)
			{
				$parent_comment = CommentModel::getComment($obj->parent_srl);
				if(!$parent_comment->comment_srl)
				{
					throw new Rhymix\Framework\Exceptions\TargetNotFound;
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
				$comment = CommentModel::getComment($output->get('comment_srl'));
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
					throw new Rhymix\Framework\Exception($massage);
				}
			}
			// check the grant
			if(!$comment->isGranted())
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
			$obj->parent_srl = $comment->parent_srl;
			$output = $oCommentController->updateComment($obj, $this->grant->manager);
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_registed');
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '', 'document_srl', $obj->document_srl, 'comment_srl', $obj->comment_srl) . '#comment_' . $obj->comment_srl);
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
		if(!$comment_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$instant_delete = null;
		if($this->grant->manager == true)
		{
			$instant_delete = Context::get('instant_delete');
		}

		$comment = CommentModel::getComment($comment_srl, $this->grant->manager);
		if (!$comment || !$comment->isExists())
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		if (!$comment->isGranted())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		$childs = null;
		if($this->module_info->protect_delete_comment === 'Y' && $this->grant->manager == false)
		{
			$childs = CommentModel::getChildComments($comment_srl);
			if(count($childs) > 0)
			{
				throw new Rhymix\Framework\Exception('msg_board_delete_protect_comment');
			}
		}
		
		if($this->module_info->protect_comment_regdate > 0 && $this->grant->manager == false)
		{
			if($comment->get('regdate') < date('YmdHis', strtotime('-'.$this->module_info->protect_document_regdate.' day')))
			{
				$format =  lang('msg_protect_regdate_comment');
				$massage = sprintf($format, $this->module_info->protect_document_regdate);
				throw new Rhymix\Framework\Exception($massage);
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
			$childs = ($childs !== null) ? $childs : CommentModel::getChildComments($comment_srl);
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

		// if the comment exists
		if($comment_srl)
		{
			// get the comment information
			$oComment = CommentModel::getComment($comment_srl);
			if(!$oComment->isExists())
			{
				throw new Rhymix\Framework\Exceptions\TargetNotFound;
			}

			// compare the comment password and the user input password
			if(!MemberModel::isValidPassword($oComment->get('password'), $password))
			{
				throw new Rhymix\Framework\Exception('msg_invalid_password');
			}

			$oComment->setGrantForSession();
		} else {
			 // get the document information
			$oDocument = DocumentModel::getDocument($document_srl);
			if(!$oDocument->isExists())
			{
				throw new Rhymix\Framework\Exceptions\TargetNotFound;
			}

			// compare the document password and the user input password
			if(!MemberModel::isValidPassword($oDocument->get('password'), $password))
			{
				throw new Rhymix\Framework\Exception('msg_invalid_password');
			}

			$oDocument->setGrantForSession();
		}
	}

	/**
	 * @brief the trigger for displaying 'view document' link when click the user ID
	 **/
	function triggerMemberMenu(&$obj)
	{
		if(!$mid = Context::get('cur_mid'))
		{
			return;
		}
		
		// get the module information
		$module_info = ModuleModel::getModuleInfoByMid($mid);
		if(empty($module_info->module) || $module_info->module !== 'board' || $module_info->use_anonymous === 'Y')
		{
			return;
		}
		
		$url = getUrl('', 'mid', $mid, 'member_srl', $obj->member_srl);
		getController('member')->addMemberPopupMenu($url, 'cmd_view_own_document', '', 'self', 'board_own_document');
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
