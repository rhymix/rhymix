<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  boardController
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module Controller class
 */

class BoardController extends Board
{
	/**
	 * @brief insert document
	 */
	public function procBoardInsertDocument()
	{
		// check grant
		if(!$this->grant->write_document)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		$logged_info = Context::get('logged_info');

		// Fix any missing module configurations
		BoardModel::fixModuleConfig($this->module_info);

		// setup variables
		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_srl;
		$obj->commentStatus = $obj->comment_status ?? null;
		unset($obj->extra_vars);

		// Remove disallowed Unicode symbols.
		if ($this->module_info->filter_specialchars === 'Y')
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
		$document_length_limit = $this->module_info->document_length_limit * 1024;
		if (strlen($obj->content) > $document_length_limit && !$this->grant->manager)
		{
			throw new Rhymix\Framework\Exception('msg_content_too_long');
		}

		// Return error if content conains excessively large data URLs.
		$inline_data_url_limit = $this->module_info->inline_data_url_limit * 1024;
		preg_match_all('!src="\s*(data:[^,]*,[a-z0-9+/=\%\$\!._-]+)!i', (string)$obj->content, $matches);
		foreach ($matches[1] as $match)
		{
			if (strlen($match) > $inline_data_url_limit)
			{
				throw new Rhymix\Framework\Exception('msg_data_url_restricted');
			}
		}

		// Check category
		if (!$obj->category_srl && !$this->grant->manager && $this->module_info->allow_no_category !== 'Y')
		{
			$category_list = DocumentModel::getCategoryList($this->module_srl);
			if (count($category_list) > 0)
			{
				return new BaseObject(-1, sprintf(lang('common.filter.isnull'), lang('common.category')));
			}
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

		// Check document_srl
		if ($obj->document_srl)
		{
			$oDocument = DocumentModel::getDocument($obj->document_srl);
			if (!$oDocument->isExists())
			{
				if (!checkUserSequence($obj->document_srl))
				{
					$obj->document_srl = getNextSequence();
				}
			}
			elseif (!$oDocument->isGranted())
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
		}
		else
		{
			$oDocument = DocumentModel::getDocument(0);
			$obj->document_srl = getNextSequence();
		}

		// Set secret status
		if (empty($this->module_info->use_status))
		{
			$this->module_info->use_status = ['PUBLIC'];
		}
		if (!is_array($this->module_info->use_status))
		{
			$this->module_info->use_status = explode('|@|', $this->module_info->use_status);
		}
		if (in_array('SECRET', $this->module_info->use_status))
		{
			if ($obj->status === 'SECRET' || (isset($obj->is_secret) && $obj->is_secret === 'Y'))
			{
				$obj->status = DocumentModel::getConfigStatus('secret');
			}
			else
			{
				$obj->status = DocumentModel::getConfigStatus('public');
			}
		}
		else
		{
			$obj->status = DocumentModel::getConfigStatus('public');
		}
		unset($obj->is_secret);

		// Set update log
		if($this->module_info->update_log == 'Y')
		{
			$obj->update_log_setting = 'Y';
		}

		// Update if the document already exists.
		if($oDocument->isExists())
		{
			// Protect admin document
			if ($this->module_info->protect_admin_content_update === 'Y')
			{
				$member_info = MemberModel::getMemberInfo($oDocument->get('member_srl'));
				if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
				{
					throw new Rhymix\Framework\Exception('msg_admin_document_no_modify');
				}
			}

			// Additional protections for published (non-TEMP) documents
			if($oDocument->get('status') !== DocumentModel::getConfigStatus('temp'))
			{
				// Protect document by comment
				if($this->module_info->protect_content == 'Y' || $this->module_info->protect_update_content == 'Y')
				{
					$comment_limit = $this->module_info->protect_update_content_limit ?? 1;
					if($oDocument->get('comment_count') >= $comment_limit && !$this->grant->manager)
					{
						throw new Rhymix\Framework\Exception(sprintf(lang('msg_protect_update_content'), $comment_limit));
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
				else
				{
					$obj->module_srl = $oDocument->get('module_srl');
				}

				// notice & document style same as before if not manager
				if(!$this->grant->manager)
				{
					$obj->is_notice = $oDocument->get('is_notice');
					$obj->title_color = $oDocument->get('title_color');
					$obj->title_bold = $oDocument->get('title_bold');
				}

				if (isset($obj->reason_update))
				{
					$obj->reason_update = escape($obj->reason_update);
				}
				else
				{
					$obj->reason_update = '';
				}
			}

			// Update
			$oDocumentController = DocumentController::getInstance();
			$output = $oDocumentController->updateDocument($oDocument, $obj);
			$msg_code = 'success_updated';
		}
		// Insert a new document.
		else
		{
			// Set update order and list order.
			$obj->update_order = $obj->list_order = $obj->document_srl * -1;

			// Insert
			$oDocumentController = DocumentController::getInstance();
			$output = $oDocumentController->insertDocument($obj);
			$msg_code = 'success_registed';

			if ($output->toBool())
			{
				// Set grant for the new document.
				$oDocument = DocumentModel::getDocument($output->get('document_srl'));
				$oDocument->setGrantForSession();

				// send an email to admin user
				if (isset($this->module_info->admin_mail) && $this->module_info->admin_mail && config('mail.default_from'))
				{
					$browser_title = Context::replaceUserLang($this->module_info->browser_title);
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
		}

		// if there is an error
		if(!$output->toBool())
		{
			return $output;
		}

		// return the results
		$this->add('mid', Context::get('mid'));
		$this->add('document_srl', $output->get('document_srl'));
		$this->add('category_srl', $output->get('category_srl'));
		$this->setRedirectUrl(getNotEncodedFullUrl('', 'mid', Context::get('mid'), 'document_srl', $output->get('document_srl')));

		// alert a message
		$this->setMessage($msg_code);
	}

	public function procBoardRevertDocument()
	{
		$update_id = Context::get('update_id');
		if (!$update_id)
		{
			throw new Rhymix\Framework\Exception('msg_no_update_id');
		}

		$update_log = DocumentModel::getUpdateLog($update_id);
		if (!$update_log)
		{
			throw new Rhymix\Framework\Exception('msg_no_update_log');
		}

		$oDocument = DocumentModel::getDocument($update_log->document_srl);
		if (!$oDocument->isGranted())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted();
		}
		if (!$this->user->isAdmin())
		{
			if (DocumentModel::getUpdateLogAdminisExists($update_log->document_srl))
			{
				throw new Rhymix\Framework\Exception('msg_admin_update_log');
			}
		}

		$obj = new stdClass();
		$obj->title = $update_log->title;
		$obj->document_srl = $update_log->document_srl;
		$obj->title_bold = $update_log->title_bold;
		$obj->title_color = $update_log->title_color;
		$obj->content = $update_log->content;
		$obj->update_log_setting = 'Y';
		$obj->reason_update = lang('board.revert_reason_update');

		$extra_vars = $update_log->extra_vars ? unserialize($update_log->extra_vars) : [];
		$extra_vars_map = [];
		if ($extra_vars)
		{
			$extra_keys = DocumentModel::getExtraKeys($oDocument->get('module_srl'));
			foreach ($extra_keys as $item)
			{
				$extra_vars_map[$item->name] = $item->idx;
			}
		}
		foreach ($extra_vars as $name => $value)
		{
			$idx = $extra_vars_map[$name] ?? null;
			if ($idx !== null)
			{
				$obj->{'extra_vars' . $idx} = $value;
			}
		}

		$oDocumentController = DocumentController::getInstance();
		$output = $oDocumentController->updateDocument($oDocument, $obj);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->add('mid', Context::get('mid'));
		$this->add('document_srl', $update_log->document_srl);
		$this->setRedirectUrl(getNotEncodedUrl([
			'mid' => Context::get('mid'),
			'document_srl' => $update_log->document_srl,
		]));
	}

	/**
	 * @brief delete the document
	 */
	public function procBoardDeleteDocument()
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

		// Fix any missing module configurations
		BoardModel::fixModuleConfig($this->module_info);

		// check protect content
		if($this->module_info->protect_content == 'Y' || $this->module_info->protect_delete_content == 'Y')
		{
			$comment_limit = $this->module_info->protect_delete_content_limit ?? 1;
			if($oDocument->get('comment_count') >= $comment_limit && $this->grant->manager == false)
			{
				throw new Rhymix\Framework\Exception(sprintf(lang('msg_protect_delete_content'), $comment_limit));
			}
		}

		if ($this->module_info->protect_admin_content_delete === 'Y' && $this->user->is_admin !== 'Y')
		{
			$member_info = MemberModel::getMemberInfo($oDocument->get('member_srl'));
			if($member_info->is_admin === 'Y')
			{
				return new BaseObject(-1, 'document.msg_document_is_admin_not_permitted');
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
		$oDocumentController = DocumentController::getInstance();
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
		$this->setRedirectUrl(getNotEncodedFullUrl('', 'mid', Context::get('mid'), 'page', Context::get('page')));
		$this->add('mid', Context::get('mid'));
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');
	}

	/**
	 * @brief vote
	 */
	public function procBoardVoteDocument()
	{
		// Check document_srl
		$document_srl = intval(Context::get('document_srl'));
		if (!$document_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Pass to procDocumentVoteUp
		Context::set('target_srl', $document_srl);
		$oDocumentController = DocumentController::getInstance();
		return $oDocumentController->procDocumentVoteUp();
	}

	/**
	 * @brief insert comments
	 */
	public function procBoardInsertComment()
	{
		// check grant
		if(!$this->grant->write_comment)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		$logged_info = Context::get('logged_info');

		// get the relevant data for inserting comment
		$obj = Context::getRequestVars();

		// Check the document.
		$oDocument = DocumentModel::getDocument($obj->document_srl);
		if(!$oDocument->isExists())
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		if(!$oDocument->isAccessible())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		// Comments belong in the same module_srl as the document.
		$obj->module_srl = $oDocument->get('module_srl');

		// Fix any missing module configurations
		BoardModel::fixModuleConfig($this->module_info);

		// Remove disallowed Unicode symbols.
		if ($this->module_info->filter_specialchars === 'Y')
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

		// Return error if content conains excessively large data URLs.
		$inline_data_url_limit = ($this->module_info->inline_data_url_limit ?: 64) * 1024;
		preg_match_all('!src="\s*(data:[^,]*,[a-z0-9+/=\%\$\!._-]+)!i', (string)$obj->content, $matches);
		foreach ($matches[1] as $match)
		{
			if (strlen($match) > $inline_data_url_limit)
			{
				throw new Rhymix\Framework\Exception('msg_data_url_restricted');
			}
		}

		// Check comment_srl
		if ($obj->comment_srl)
		{
			$comment = CommentModel::getComment($obj->comment_srl);
			if (!$comment->isExists())
			{
				if (!checkUserSequence($obj->comment_srl))
				{
					$obj->comment_srl = getNextSequence();
				}
				$comment = null;
			}
			elseif (!$comment->isGranted())
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
		}
		else
		{
			$obj->comment_srl = getNextSequence();
			$comment = null;
		}

		// Set secret status
		if (empty($this->module_info->use_status))
		{
			$this->module_info->use_status = ['PUBLIC'];
		}
		if (!is_array($this->module_info->use_status))
		{
			$this->module_info->use_status = explode('|@|', $this->module_info->use_status);
		}
		if (in_array('SECRET', $this->module_info->use_status))
		{
			$this->module_info->secret = 'Y';
		}
		else
		{
			$this->module_info->secret = 'N';
			unset($obj->is_secret);
		}

		// INSERT if comment_srl does not exist.
		if (!$comment)
		{
			// Update document last_update info?
			$update_document = $this->module_info->update_order_on_comment === 'N' ? false : true;

			// Check parent comment.
			if (!empty($obj->parent_srl))
			{
				$parent_comment = CommentModel::getComment($obj->parent_srl);
				if(!$parent_comment->comment_srl || $parent_comment->get('document_srl') != $oDocument->get('document_srl'))
				{
					throw new Rhymix\Framework\Exceptions\TargetNotFound;
				}
				if(!$parent_comment->isAccessible())
				{
					throw new Rhymix\Framework\Exceptions\NotPermitted;
				}
				if($parent_comment->isSecret() && $this->module_info->secret === 'Y')
				{
					$obj->is_secret = 'Y';
				}
			}

			// Insert comment.
			$oCommentController = CommentController::getInstance();
			$output = $oCommentController->insertComment($obj, false, $update_document);

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
			if ($this->module_info->protect_update_comment === 'Y' && $this->grant->manager == false)
			{
				$childs_limit = $this->module_info->protect_update_comment_limit ?? 1;
				$childs = CommentModel::getChildComments($obj->comment_srl);
				if(count($childs) >= $childs_limit)
				{
					throw new Rhymix\Framework\Exception(sprintf(lang('msg_board_update_protect_comment'), $childs_limit));
				}
			}

			if ($this->module_info->protect_admin_content_update === 'Y')
			{
				$member_info = MemberModel::getMemberInfo($comment->member_srl);
				if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
				{
					throw new Rhymix\Framework\Exception('msg_admin_comment_no_modify');
				}
			}

			if ($this->module_info->protect_comment_regdate > 0 && $this->grant->manager == false)
			{
				if($comment->get('regdate') < date('YmdHis', strtotime('-'.$this->module_info->protect_comment_regdate.' day')))
				{
					$format =  lang('msg_protect_regdate_comment');
					$message = sprintf($format, $this->module_info->protect_comment_regdate);
					throw new Rhymix\Framework\Exception($message);
				}
			}

			$obj->parent_srl = $comment->parent_srl;

			$oCommentController = CommentController::getInstance();
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
	 */
	public function procBoardDeleteComment()
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

		$comment = CommentModel::getComment($comment_srl);
		if (!$comment || !$comment->isExists())
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		if (!$comment->isGranted())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		// Fix any missing module configurations
		BoardModel::fixModuleConfig($this->module_info);

		$childs = null;
		if($this->module_info->protect_delete_comment === 'Y' && $this->grant->manager == false)
		{
			$childs_limit = $this->module_info->protect_delete_comment_limit ?? 1;
			$childs = CommentModel::getChildComments($comment_srl);
			if(count($childs) >= $childs_limit)
			{
				throw new Rhymix\Framework\Exception(sprintf(lang('msg_board_delete_protect_comment'), $childs_limit));
			}
		}

		if ($this->module_info->protect_admin_content_delete === 'Y' && $this->user->is_admin !== 'Y')
		{
			$member_info = MemberModel::getMemberInfo($comment->get('member_srl'));
			if($member_info->is_admin === 'Y')
			{
				return new BaseObject(-1, 'comment.msg_admin_comment_no_delete');
			}
		}

		if($this->module_info->protect_comment_regdate > 0 && $this->grant->manager == false)
		{
			if($comment->get('regdate') < date('YmdHis', strtotime('-'.$this->module_info->protect_comment_regdate.' day')))
			{
				$format =  lang('msg_protect_regdate_comment');
				$message = sprintf($format, $this->module_info->protect_comment_regdate);
				throw new Rhymix\Framework\Exception($message);
			}
		}
		// generate comment  controller object
		$oCommentController = CommentController::getInstance();
		if($this->module_info->comment_delete_message === 'yes' && $instant_delete != 'Y')
		{
			$output = $oCommentController->updateCommentByDelete($comment, $this->grant->manager);
			if($this->module_info->trash_use == 'Y')
			{
				$output = $oCommentController->moveCommentToTrash($comment, true);
			}
		}
		elseif(starts_with('only_comm', $this->module_info->comment_delete_message) && $instant_delete != 'Y')
		{
			$childs = ($childs !== null) ? $childs : CommentModel::getChildComments($comment_srl);
			if(count($childs) > 0)
			{
				$output = $oCommentController->updateCommentByDelete($comment, $this->grant->manager);
				if($this->module_info->trash_use == 'Y')
				{
					$output = $oCommentController->moveCommentToTrash($comment, true);
				}
			}
			else
			{
				if($this->module_info->trash_use == 'Y')
				{
					$output = $oCommentController->moveCommentToTrash($comment);
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
				$output = $oCommentController->moveCommentToTrash($comment);
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
		$this->setRedirectUrl(getNotEncodedFullUrl('', 'mid', Context::get('mid'), 'document_srl', $output->get('document_srl')));
	}

	/**
	 * @brief delete the tracjback
	 */
	public function procBoardDeleteTrackback()
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
	 */
	public function procBoardVerificationPassword()
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
		}
		else
		{
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
	 * Trigger before document.insertDocument for anonymity processing
	 *
	 * @param object $obj
	 */
	public function triggerBeforeInsertDocument($obj)
	{
		self::_applyAnonymity($obj, 'insertDocument');
	}

	/**
	 * Trigger before document.updateDocument for anonymity processing
	 *
	 * @param object $obj
	 */
	public function triggerBeforeUpdateDocument($obj)
	{
		self::_applyAnonymity($obj, 'updateDocument');
	}

	/**
	 * Trigger before comment.insertComment for anonymity processing
	 *
	 * @param object $obj
	 */
	public function triggerBeforeInsertComment($obj)
	{
		self::_applyAnonymity($obj, 'insertComment');
	}

	/**
	 * Trigger before comment.updateComment for anonymity processing
	 *
	 * @param object $obj
	 */
	public function triggerBeforeUpdateComment($obj)
	{
		self::_applyAnonymity($obj, 'updateComment');
	}

	/**
	 * @brief the trigger for displaying 'view document' link when click the user ID
	 */
	public function triggerMemberMenu($obj)
	{
		if(!$mid = Context::get('cur_mid'))
		{
			return;
		}

		// get the module information
		$module_info = ModuleModel::getModuleInfoByMid($mid);
		if (!$module_info || !isset($module_info->module) || $module_info->module !== 'board')
		{
			return;
		}
		if (($module_info->use_anonymous ?? 'N') === 'Y')
		{
			if (($module_info->anonymous_except_admin ?? 'N') !== 'Y' || !ModuleModel::isModuleAdmin($obj, $module_info->module_srl))
			{
				return;
			}
		}

		$url = getUrl('', 'mid', $mid, 'member_srl', $obj->member_srl);

		$oMemberController = MemberController::getInstance();
		$oMemberController->addMemberPopupMenu($url, 'cmd_view_own_document', '', 'self', 'board_own_document');
	}

	/**
	 * Trigger called at module copy
	 */
	public function triggerCopyModule(&$obj)
	{
		$board_config = ModuleModel::getModulePartConfig('board', $obj->originModuleSrl);

		$oModuleController = ModuleController::getInstance();
		if (is_array($obj->moduleSrlList))
		{
			foreach ($obj->moduleSrlList as $module_srl)
			{
				$oModuleController->insertModulePartConfig('board', $module_srl, $board_config);
			}
		}
	}

	/**
	 * Create an anonymous nickname.
	 *
	 * @param string $format
	 * @param int $member_srl
	 * @param int $document_srl
	 * @return string
	 */
	public static function createAnonymousName($format, $member_srl, $document_srl): string
	{
		$format = trim((string)$format);
		if ($format === '')
		{
			$format = BoardModel::DEFAULT_MODULE_CONFIG['anonymous_name'];
		}

		return preg_replace_callback('/\$(DAILY|DOC|DOCDAILY|)(NUM|STR)(?::([0-9]))?/', function($matches) use($member_srl, $document_srl) {
			$digits = empty($matches[3]) ? 8 : max(1, min(8, intval($matches[3])));
			$type = strtolower($matches[2]);
			switch ($matches[1])
			{
				case '': return self::_createHash($member_srl ?: \RX_CLIENT_IP, $digits, $type);
				case 'DAILY': return self::_createHash(($member_srl ?: \RX_CLIENT_IP) . ':date:' . date('Y-m-d'), $digits, $type);
				case 'DOC': return self::_createHash(($member_srl ?: \RX_CLIENT_IP) . ':document_srl:' . $document_srl, $digits, $type);
				case 'DOCDAILY': return self::_createHash(($member_srl ?: \RX_CLIENT_IP) . ':document_srl:' . $document_srl . ':date:' . date('Y-m-d'), $digits, $type);
			}
		}, $format);
	}

	/**
	 * Subroutine for hashing anonymous nickname.
	 *
	 * @param string $content
	 * @param int $digits
	 * @param string $type
	 * @return string
	 */
	protected static function _createHash(string $content, int $digits = 8, string $type = 'num'): string
	{
		$hash = hash_hmac('sha256', $content, config('crypto.authentication_key'));
		if ($type === 'num')
		{
			return sprintf('%0' . $digits . 'd', hexdec(substr($hash, 0, 8)) % pow(10, $digits));
		}
		else
		{
			return substr($hash, 0, $digits);
		}
	}

	/**
	 * Subroutine for applying anonymity information to a document or comment.
	 *
	 * @param object $obj
	 * @param string $type
	 * @return void
	 */
	protected static function _applyAnonymity(object $obj, string $type): void
	{
		// Check whether anonymity should be applied
		$module_srl = $obj->module_srl;
		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		if (!$module_info || !isset($module_info->use_anonymous) || $module_info->use_anonymous !== 'Y')
		{
			return;
		}
		$logged_info = Context::get('logged_info');
		if (isset($module_info->anonymous_except_admin) && $module_info->anonymous_except_admin === 'Y')
		{
			$grant = ModuleModel::getGrant($module_srl, $logged_info);
			if ($grant->manager)
			{
				return;
			}
		}

		// Check the source object
		if ($type === 'updateDocument')
		{
			$source_obj = DocumentModel::getDocument($obj->document_srl);
		}
		elseif ($type === 'updateComment')
		{
			$source_obj = CommentModel::getComment($obj->comment_srl);
		}
		else
		{
			$source_obj = null;
		}

		if ($source_obj && $source_obj->isExists())
		{
			if ($source_obj->get('status') === 'TEMP')
			{
				$anonymous_name = self::createAnonymousName($module_info->anonymous_name ?? '', $logged_info->member_srl, $obj->document_srl);
			}
			else
			{
				$anonymous_name = $source_obj->get('nick_name');
			}
			$member_srl = abs($source_obj->get('member_srl')) * -1;
		}
		else
		{
			$anonymous_name = self::createAnonymousName($module_info->anonymous_name ?? '', $logged_info->member_srl, $obj->document_srl);
			$member_srl = $logged_info->member_srl * -1;
		}

		$obj->member_srl = $member_srl;
		$obj->nick_name = $anonymous_name;
		$obj->user_name = $anonymous_name;
		$obj->user_id = '';
		$obj->email_address = '';
		$obj->homepage = '';
		$obj->notify_message = 'N';
	}
}
