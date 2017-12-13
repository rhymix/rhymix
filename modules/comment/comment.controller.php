<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * commentController class
 * controller class of the comment module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/comment
 * @version 0.1
 */
class commentController extends comment
{

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{

	}

	/**
	 * Action to handle recommendation votes on comments (Up)
	 * @return Object
	 */
	function procCommentVoteUp()
	{
		if(!Context::get('is_logged'))
		{
			return $this->setError('msg_invalid_request');
		}

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl)
		{
			return $this->setError('msg_invalid_request');
		}

		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($comment_srl, FALSE, FALSE);
		$module_srl = $oComment->get('module_srl');
		if(!$module_srl)
		{
			return $this->setError('msg_invalid_request');
		}

		$oModuleModel = getModel('module');
		$comment_config = $oModuleModel->getModulePartConfig('comment', $module_srl);
		if($comment_config->use_vote_up == 'N')
		{
			return $this->setError('msg_invalid_request');
		}

		$point = 1;
		$output = $this->updateVotedCount($comment_srl, $point);
		$this->add('voted_count', $output->get('voted_count'));
		return $output;
	}

	function procCommentVoteUpCancel()
	{
		if(!Context::get('logged_info')) return $this->setError('msg_invalid_request');

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl) return $this->setError('msg_invalid_request');

		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($comment_srl, FALSE, FALSE);
		if($oComment->get('voted_count') <= 0)
		{
			return $this->setError('msg_comment_voted_cancel_not');
		}
		$point = 1;
		$output = $this->updateVotedCountCancel($comment_srl, $oComment, $point);

		$output = new BaseObject();
		$output->setMessage('success_voted_canceled');

		return $output;
	}


	/**
	 * Action to handle recommendation votes on comments (Down)
	 * @return Object
	 */
	function procCommentVoteDown()
	{
		if(!Context::get('is_logged'))
		{
			return $this->setError('msg_invalid_request');
		}

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl)
		{
			return $this->setError('msg_invalid_request');
		}

		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($comment_srl, FALSE, FALSE);
		$module_srl = $oComment->get('module_srl');
		if(!$module_srl)
		{
			return $this->setError('msg_invalid_request');
		}

		$oModuleModel = getModel('module');
		$comment_config = $oModuleModel->getModulePartConfig('comment', $module_srl);
		if($comment_config->use_vote_down == 'N')
		{
			return $this->setError('msg_invalid_request');
		}

		$point = -1;
		$output = $this->updateVotedCount($comment_srl, $point);
		$this->add('blamed_count', $output->get('blamed_count'));
		return $output;
	}

	function procCommentVoteDownCancel()
	{
		if(!Context::get('logged_info')) return $this->setError('msg_invalid_request');

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl) return $this->setError('msg_invalid_request');

		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($comment_srl, FALSE, FALSE);
		if($oComment->get('blamed_count') >= 0)
		{
			return $this->setError('msg_comment_blamed_cancel_not');
		}
		$point = -1;
		$output = $this->updateVotedCountCancel($comment_srl, $oComment, $point);

		$output = new BaseObject();
		$output->setMessage('success_blamed_canceled');

		return $output;
	}

	function updateVotedCountCancel($comment_srl, $oComment, $point)
	{
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$d_args = new stdClass();
		$args->comment_srl = $d_args->comment_srl = $comment_srl;
		$d_args->member_srl = $logged_info->member_srl;
		if($point > 0)
		{
			$args->voted_count = $oComment->get('voted_count') - $point;
			$output = executeQuery('comment.updateVotedCount', $args);
		}
		else
		{
			$args->blamed_count = $oComment->get('blamed_count') - $point;
			$output = executeQuery('comment.updateBlamedCount', $args);
		}
		$d_output = executeQuery('comment.deleteCommentVotedLog', $d_args);
		if(!$d_output->toBool()) return $d_output;

		//session reset
		$_SESSION['voted_comment'][$comment_srl] = false;

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		$obj = new stdClass();
		$obj->member_srl = $oComment->get('member_srl');
		$obj->module_srl = $oComment->get('module_srl');
		$obj->comment_srl = $oComment->get('comment_srl');
		$obj->update_target = ($point < 0) ? 'blamed_count' : 'voted_count';
		$obj->point = $point;
		$obj->before_point = ($point < 0) ? $oComment->get('blamed_count') : $oComment->get('voted_count');
		$obj->after_point = ($point < 0) ? $args->blamed_count : $args->voted_count;
		$obj->cancel = 1;
		
		ModuleHandler::triggerCall('comment.updateVotedCountCancel', 'after', $obj);
		$oDB->commit();
		return $output;
	}

	/**
	 * Action to be called when a comment posting is reported
	 * @return void|Object
	 */
	function procCommentDeclare()
	{
		if(!Context::get('is_logged'))
		{
			return $this->setError('msg_invalid_request');
		}

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl)
		{
			return $this->setError('msg_invalid_request');
		}

		// if an user select message from options, message would be the option.
		$message_option = strval(Context::get('message_option'));
		$improper_comment_reasons = lang('improper_comment_reasons');
		$declare_message = ($message_option !== 'others' && isset($improper_comment_reasons[$message_option]))?
			$improper_comment_reasons[$message_option] : trim(Context::get('declare_message'));

		// if there is return url, set that.
		if(Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}

		return $this->declaredComment($comment_srl, $declare_message);
	}

	/**
	 * Trigger to delete its comments together with document deleted
	 * @return Object
	 */
	function triggerDeleteDocumentComments(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl)
		{
			return;
		}

		return $this->deleteComments($document_srl, $obj);
	}

	/**
	 * Trigger to delete corresponding comments when deleting a module
	 * @return object
	 */
	function triggerDeleteModuleComments(&$obj)
	{
		$module_srl = $obj->module_srl;
		if(!$module_srl)
		{
			return;
		}

		$oCommentController = getAdminController('comment');
		return $oCommentController->deleteModuleComments($module_srl);
	}

	/**
	 * Authorization of the comments
	 * available only in the current connection of the session value
	 * @return void
	 */
	function addGrant($comment_srl)
	{
		$comment = getModel('comment')->getComment($comment_srl);
		if ($comment->isExists())
		{
			$comment->setGrant();
		}
	}

	/**
	 * Check if module is using comment validation system
	 * @param int $document_srl
	 * @param int $module_srl
	 * @return bool
	 */
	function isModuleUsingPublishValidation($module_srl = NULL)
	{
		if($module_srl == NULL)
		{
			return FALSE;
		}

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		$module_part_config = $oModuleModel->getModulePartConfig('comment', $module_info->module_srl);
		$use_validation = FALSE;
		if(isset($module_part_config->use_comment_validation) && $module_part_config->use_comment_validation == "Y")
		{
			$use_validation = TRUE;
		}
		return $use_validation;
	}

	/**
	 * Enter comments
	 * @param object $obj
	 * @param bool $manual_inserted
	 * @param bool $update_document
	 * @return object
	 */
	function insertComment($obj, $manual_inserted = FALSE, $update_document = TRUE)
	{
		if(!$manual_inserted && !checkCSRF())
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if(!is_object($obj))
		{
			$obj = new stdClass();
		}

		// check if comment's module is using comment validation and set the publish status to 0 (false)
		// for inserting query, otherwise default is 1 (true - means comment is published)
		$using_validation = $this->isModuleUsingPublishValidation($obj->module_srl);
		if(!$manual_inserted)
		{
			if(Context::get('is_logged'))
			{
				$logged_info = Context::get('logged_info');
				if($logged_info->is_admin == 'Y')
				{
					$is_admin = TRUE;
				}
				else
				{
					$is_admin = FALSE;
				}
			}
		}
		else
		{
			$is_admin = FALSE;
		}

		if(!$using_validation)
		{
			$obj->status = 1;
		}
		else
		{
			if($is_admin)
			{
				$obj->status = 1;
			}
			else
			{
				$obj->status = 0;
			}
		}
		$obj->__isupdate = FALSE;

		// Remove manual member info to prevent forgery. This variable can be set by triggers only.
		unset($obj->manual_member_info);
		
		// call a trigger (before)
		$output = ModuleHandler::triggerCall('comment.insertComment', 'before', $obj);
		if(!$output->toBool())
		{
			return $output;
		}

		// check if a posting of the corresponding document_srl exists
		$document_srl = $obj->document_srl;
		if(!$document_srl)
		{
			return new BaseObject(-1, 'msg_invalid_document');
		}
		
		// creat the comment model object
		$oCommentModel = getModel('comment');
		// get a object of document model
		$oDocumentModel = getModel('document');

		// even for manual_inserted if password exists, hash it.
		if($obj->password)
		{
			$obj->password = getModel('member')->hashPassword($obj->password);
		}

		// get the original posting
		if(!$manual_inserted)
		{
			$oDocument = $oDocumentModel->getDocument($document_srl);

			if($document_srl != $oDocument->document_srl)
			{
				return new BaseObject(-1, 'msg_invalid_document');
			}
			if($oDocument->isLocked())
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}

			if($obj->homepage)
			{
				$obj->homepage = removeHackTag($obj->homepage);
				if(!preg_match('/^[a-z]+:\/\//i',$obj->homepage))
				{
					$obj->homepage = 'http://'.$obj->homepage;
				}
			}

			// input the member's information if logged-in
			$logged_info = Context::get('logged_info');
			if(Context::get('is_logged') && !$obj->manual_member_info)
			{
				$obj->member_srl = $logged_info->member_srl;

				// user_id, user_name and nick_name already encoded
				$obj->user_id = htmlspecialchars_decode($logged_info->user_id);
				$obj->user_name = htmlspecialchars_decode($logged_info->user_name);
				$obj->nick_name = htmlspecialchars_decode($logged_info->nick_name);
				$obj->email_address = $logged_info->email_address;
				$obj->homepage = $logged_info->homepage;
			}
		}

		// error display if neither of log-in info and user name exist.
		if(!$logged_info->member_srl && !$obj->nick_name)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if(!$obj->comment_srl)
		{
			$obj->comment_srl = getNextSequence();
		}
		elseif(!$is_admin && !$manual_inserted && !checkUserSequence($obj->comment_srl)) 
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}

		// determine the order
		$obj->list_order = getNextSequence() * -1;

		// remove Rhymix's own tags from the contents
		$obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);

		// Return error if content is empty.
		if (!$manual_inserted && is_empty_html_content($obj->content))
		{
			return new BaseObject(-1, 'msg_empty_content');
		}
		
		// if use editor of nohtml, Remove HTML tags from the contents.
		if(!$manual_inserted || isset($obj->allow_html) || isset($obj->use_html))
		{
			$obj->content = getModel('editor')->converter($obj, 'comment');
		}
		
		if(!$obj->regdate)
		{
			$obj->regdate = date("YmdHis");
		}

		// remove iframe and script if not a top administrator on the session.
		if($logged_info->is_admin != 'Y')
		{
			$obj->content = removeHackTag($obj->content);
		}
		$obj->content = utf8_mbencode($obj->content);

		if(!$obj->notify_message)
		{
			$obj->notify_message = 'N';
		}

		if(!$obj->is_secret)
		{
			$obj->is_secret = 'N';
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// Enter a list of comments first
		$list_args = new stdClass();
		$list_args->comment_srl = $obj->comment_srl;
		$list_args->document_srl = $obj->document_srl;
		$list_args->module_srl = $obj->module_srl;
		$list_args->regdate = $obj->regdate;

		// If parent comment doesn't exist, set data directly
		if(!$obj->parent_srl)
		{
			$list_args->head = $list_args->arrange = $obj->comment_srl;
			$list_args->depth = 0;
			// If parent comment exists, get information of the parent comment
		}
		else
		{
			// get information of the parent comment posting
			$parent_args = new stdClass();
			$parent_args->comment_srl = $obj->parent_srl;
			$parent_output = executeQuery('comment.getCommentListItem', $parent_args);

			// return if no parent comment exists
			if(!$parent_output->toBool() || !$parent_output->data)
			{
				return $parent_output;
			}

			$parent = $parent_output->data;

			$list_args->head = $parent->head;
			$list_args->depth = $parent->depth + 1;

			// if the depth of comments is less than 2, execute insert.
			if($list_args->depth < 2)
			{
				$list_args->arrange = $obj->comment_srl;
				// if the depth of comments is greater than 2, execute update.
			}
			else
			{
				// get the top listed comment among those in lower depth and same head with parent's.
				$p_args = new stdClass();
				$p_args->head = $parent->head;
				$p_args->arrange = $parent->arrange;
				$p_args->depth = $parent->depth;
				$output = executeQuery('comment.getCommentParentNextSibling', $p_args);

				if($output->data->arrange)
				{
					$list_args->arrange = $output->data->arrange;
					$output = executeQuery('comment.updateCommentListArrange', $list_args);
				}
				else
				{
					$list_args->arrange = $obj->comment_srl;
				}
			}
		}

		$output = executeQuery('comment.insertCommentList', $list_args);
		if(!$output->toBool())
		{
			return $output;
		}

		// insert comment
		$output = executeQuery('comment.insertComment', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		
		// get the number of all comments in the posting
		$comment_count = $oCommentModel->getCommentCount($document_srl);

		// create the controller object of the document
		$oDocumentController = getController('document');

		// Update the number of comments in the post
		if(!$using_validation || $is_admin)
		{
			$output = $oDocumentController->updateCommentCount($document_srl, $comment_count, $obj->nick_name, $update_document);
		}

		// call a trigger(after)
		ModuleHandler::triggerCall('comment.insertComment', 'after', $obj);

		// commit
		$oDB->commit();

		// grant autority of the comment
		if(!$manual_inserted)
		{
			$this->addGrant($obj->comment_srl);
		}

		if(!$manual_inserted)
		{
			// send a message if notify_message option in enabled in the original article
			$oDocument->notify(lang('comment'), $obj->content);

			// send a message if notify_message option in enabled in the original comment
			if($obj->parent_srl)
			{
				$oParent = $oCommentModel->getComment($obj->parent_srl);
				if($oParent->get('member_srl') != $oDocument->get('member_srl'))
				{
					$oParent->notify(lang('comment'), $obj->content);
				}
			}
		}

		$this->sendEmailToAdminAfterInsertComment($obj);

		$output->add('comment_srl', $obj->comment_srl);

		return $output;
	}

	/**
	 * Send email to module's admins after a new comment was interted successfully
	 * if Comments Approval System is used 
	 * @param object $obj 
	 * @return void
	 */
	function sendEmailToAdminAfterInsertComment($obj)
	{
		$using_validation = $this->isModuleUsingPublishValidation($obj->module_srl);

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($obj->document_srl);
		$author_email_address = $oDocument->get('email_address');

		$oMemberModel = getModel("member");
		$is_logged = Context::get('is_logged');
		if(isset($obj->member_srl) && !is_null($obj->member_srl))
		{
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);
		}
		else
		{
			$member_info = new stdClass();
			$member_info->is_admin = "N";
			$member_info->nick_name = $obj->nick_name;
			$member_info->user_name = $obj->user_name;
			$member_info->email_address = $obj->email_address;
		}

		$oCommentModel = getModel("comment");
		$oModuleModel = getModel("module");
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($obj->document_srl);

		// If there is no problem to register comment then send an email to all admin were set in module admin panel
		if($module_info->admin_mail && $member_info->is_admin != 'Y')
		{
			$mail_title = sprintf(lang('msg_comment_notify_mail'), Context::get('mid'), cut_str($oDocument->getTitleText(), 20, '...'));
			$url_comment = getFullUrl('','document_srl',$obj->document_srl).'#comment_'.$obj->comment_srl;
			if($using_validation)
			{
				$nr_comments_not_approved = $oCommentModel->getCommentAllCount(NULL, FALSE);
				$url_approve = getFullUrl('', 'module', 'admin', 'act', 'procCommentAdminChangePublishedStatusChecked', 'cart[]', $obj->comment_srl, 'will_publish', '1', 'search_target', 'is_published', 'search_keyword', 'N');
				$url_trash = getFullUrl('', 'module', 'admin', 'act', 'procCommentAdminDeleteChecked', 'cart[]', $obj->comment_srl, 'search_target', 'is_trash', 'search_keyword', 'true');
				$mail_content = "
					A new comment on the document \"" . $oDocument->getTitleText() . "\" is waiting for your approval.
					<br />
					<br />
					Author: " . $member_info->nick_name . "
					<br />Author e-mail: " . $member_info->email_address . "
					<br />From : <a href=\"" . $url_comment . "\">" . $url_comment . "</a>
					<br />Comment:
					<br />\"" . $obj->content . "\"
					<br />Document:
					<br />\"" . $oDocument->getContentText(). "\"
					<br />
					<br />
					Approve it: <a href=\"" . $url_approve . "\">" . $url_approve . "</a>
					<br />Trash it: <a href=\"" . $url_trash . "\">" . $url_trash . "</a>
					<br />Currently " . $nr_comments_not_approved . " comments on \"" . Context::get('mid') . "\" module are waiting for approval. Please visit the moderation panel:
					<br /><a href=\"" . getFullUrl('', 'module', 'admin', 'act', 'dispCommentAdminList', 'search_target', 'module', 'search_keyword', $obj->module_srl) . "\">" . getFullUrl('', 'module', 'admin', 'act', 'dispCommentAdminList', 'search_target', 'module', 'search_keyword', $obj->module_srl) . "</a>
					";
			}
			else
			{
				$mail_content = "
					Author: " . $member_info->nick_name . "
					<br />Author e-mail: " . $member_info->email_address . "
					<br />From : <a href=\"" . $url_comment . "\">" . $url_comment . "</a>
					<br />Comment:
					<br />\"" . $obj->content . "\"
					<br />Document:
					<br />\"" . $oDocument->getContentText(). "\"
					";
			}

			// get all admins emails
			$oMail = new \Rhymix\Framework\Mail();
			$oMail->setSubject($mail_title);
			$oMail->setBody($mail_content);
			$oMail->setFrom(config('mail.default_from') ?: $member_info->email_address, $member_info->nick_name);
			$oMail->setReplyTo($member_info->email_address);
			foreach (array_map('trim', explode(',', $module_info->admin_mail)) as $email_address)
			{
				if ($email_address && $email_address !== $author_email_address)
				{
					$oMail->addTo($email_address);
				}
			}
			$oMail->send();
			//  send email to all admins - STOP
		}

		$comment_srl_list = array(0 => $obj->comment_srl);
		// call a trigger for calling "send mail to subscribers" (for moment just for forum)
		ModuleHandler::triggerCall("comment.sendEmailToAdminAfterInsertComment", "after", $comment_srl_list);

		return;
	}

	/**
	 * Fix the comment
	 * @param object $obj
	 * @param bool $is_admin
	 * @param bool $manual_updated
	 * @return object
	 */
	function updateComment($obj, $is_admin = FALSE, $manual_updated = FALSE)
	{
		if(!$manual_updated && !checkCSRF())
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if(!is_object($obj))
		{
			$obj = new stdClass();
		}

		$obj->__isupdate = TRUE;

		// Remove manual member info to prevent forgery. This variable can be set by triggers only.
		unset($obj->manual_member_info);

		// call a trigger (before)
		$output = ModuleHandler::triggerCall('comment.updateComment', 'before', $obj);
		if(!$output->toBool())
		{
			return $output;
		}

		// create a comment model object
		$oCommentModel = getModel('comment');

		// get the original data
		$source_obj = $oCommentModel->getComment($obj->comment_srl);
		if(!$source_obj->getMemberSrl())
		{
			$obj->member_srl = $source_obj->get('member_srl');
			$obj->user_name = $source_obj->get('user_name');
			$obj->nick_name = $source_obj->get('nick_name');
			$obj->email_address = $source_obj->get('email_address');
			$obj->homepage = $source_obj->get('homepage');
		}

		// check if permission is granted
		if(!$is_admin && !$source_obj->isGranted())
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}

		if($obj->password)
		{
			$obj->password = getModel('member')->hashPassword($obj->password);
		}

		if($obj->homepage) 
		{
			$obj->homepage = removeHackTag($obj->homepage);
			if(!preg_match('/^[a-z]+:\/\//i',$obj->homepage))
			{
				$obj->homepage = 'http://'.$obj->homepage;
			}
		}

		// set modifier's information if logged-in and posting author and modifier are matched.
		$logged_info = Context::get('logged_info');
		if(Context::get('is_logged') && !$obj->manual_member_info)
		{
			if($source_obj->member_srl == $logged_info->member_srl)
			{
				$obj->member_srl = $logged_info->member_srl;
				$obj->user_name = $logged_info->user_name;
				$obj->nick_name = $logged_info->nick_name;
				$obj->email_address = $logged_info->email_address;
				$obj->homepage = $logged_info->homepage;
			}
		}

		// if nick_name of the logged-in author doesn't exist
		if($source_obj->get('member_srl') && !$obj->nick_name && !$obj->manual_member_info)
		{
			$obj->member_srl = $source_obj->get('member_srl');
			$obj->user_name = $source_obj->get('user_name');
			$obj->nick_name = $source_obj->get('nick_name');
			$obj->email_address = $source_obj->get('email_address');
			$obj->homepage = $source_obj->get('homepage');
		}

		if(!$obj->content)
		{
			$obj->content = $source_obj->get('content');
		}

		// remove Rhymix's wn tags from contents
		$obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);
		// Return error if content is empty.
		if (!$manual_updated && is_empty_html_content($obj->content))
		{
			return new BaseObject(-1, 'msg_empty_content');
		}
		
		// if use editor of nohtml, Remove HTML tags from the contents.
		if(!$manual_updated || isset($obj->allow_html) || isset($obj->use_html))
		{
			$obj->content = getModel('editor')->converter($obj, 'comment');
		}
		
		// remove iframe and script if not a top administrator on the session
		if($logged_info->is_admin != 'Y')
		{
			$obj->content = removeHackTag($obj->content);
		}
		$obj->content = utf8_mbencode($obj->content);

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// Update
		$output = executeQuery('comment.updateComment', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// call a trigger (after)
		ModuleHandler::triggerCall('comment.updateComment', 'after', $obj);

		// commit
		$oDB->commit();

		$output->add('comment_srl', $obj->comment_srl);

		return $output;
	}

	/**
	 * Fix comment the delete comment message
	 * @param object $obj
	 * @param bool $is_admin
	 * @return object
	 */
	function updateCommentByDelete($obj, $is_admin = FALSE)
	{
		if (!$obj->comment_srl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}
		
		// call a trigger (before)
		$output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
		if(!$output->toBool())
		{
			return $output;
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// If the case manager to delete comments, it indicated that the administrator deleted.
		$logged_info = Context::get('logged_info');
		if($is_admin === true && $obj->member_srl !== $logged_info->member_srl)
		{
			$obj->content = lang('msg_admin_deleted_comment');
			$obj->status = RX_STATUS_DELETED_BY_ADMIN;
		}
		else
		{
			$obj->content = lang('msg_deleted_comment');
			$obj->status = RX_STATUS_DELETED;
		}
		$obj->member_srl = 0;
		unset($obj->last_update);
		$output = executeQuery('comment.updateCommentByDelete', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// call a trigger (after)
		ModuleHandler::triggerCall('comment.deleteComment', 'after', $obj);

		// update the number of comments
		$oCommentModel = getModel('comment');
		$comment_count = $oCommentModel->getCommentCount($obj->document_srl);
		// only document is exists
		if(isset($comment_count))
		{
			// create the controller object of the document
			$oDocumentController = getController('document');

			// update comment count of the article posting
			$output = $oDocumentController->updateCommentCount($obj->document_srl, $comment_count, NULL, FALSE);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		$oDB->commit();

		$output->add('document_srl', $obj->document_srl);

		return $output;
	}

	function updateCommentByRestore($obj)
	{
		if (!$obj->comment_srl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		$obj->status = RX_STATUS_PUBLIC;
		// use to query default
		unset($obj->last_update);
		$output = executeQuery('comment.updateCommentByRestore', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// update the number of comments
		$oCommentModel = getModel('comment');
		$comment_count = $oCommentModel->getCommentCount($obj->document_srl);
		// only document is exists
		if(isset($comment_count))
		{
			// create the controller object of the document
			$oDocumentController = getController('document');

			// update comment count of the article posting
			$output = $oDocumentController->updateCommentCount($obj->document_srl, $comment_count, NULL, FALSE);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		$oDB->commit();

		return $output;
	}

	/**
	 * Delete comment
	 * @param int $comment_srl
	 * @param bool $is_admin
	 * @param bool $isMoveToTrash
	 * @param object $childs
	 * @return object
	 */
	function deleteComment($comment_srl, $is_admin = FALSE, $isMoveToTrash = FALSE, $childs = null)
	{
		// create the comment model object
		$oCommentModel = getModel('comment');

		$logged_info = Context::get('logged_info');

		// check if comment already exists
		$comment = $oCommentModel->getComment($comment_srl);
		if($comment->comment_srl != $comment_srl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($comment->member_srl);

		$document_srl = $comment->document_srl;

		// call a trigger (before)
		$output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
		if(!$output->toBool())
		{
			return $output;
		}

		// check if permission is granted
		if(!$is_admin && !$comment->isGranted())
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}

		// check if child comment exists on the comment
		if(!$childs)
		{
			$childs = $oCommentModel->getChildComments($comment_srl);
		}
		if(count($childs) > 0)
		{
			$deleteAllComment = TRUE;
			$deleteAdminComment = TRUE;
			if(!$is_admin)
			{
				$logged_info = Context::get('logged_info');
				foreach($childs as $val)
				{
					if($val->member_srl != $logged_info->member_srl)
					{
						$deleteAllComment = FALSE;
						break;
					}
				}
			}
			else if($is_admin)
			{
				$logged_info = Context::get('logged_info');
				foreach($childs as $val)
				{
					$c_member_info = $oMemberModel->getMemberInfoByMemberSrl($val->member_srl);
					if($c_member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
					{
						$deleteAdminComment = FALSE;
						break;
					}
				}
			}

			if(!$deleteAllComment)
			{
				return new BaseObject(-1, 'fail_to_delete_have_children');
			}
			elseif(!$deleteAdminComment)
			{
				return new BaseObject(-1, 'msg_admin_c_comment_no_delete');
			}
			else
			{
				foreach($childs as $val)
				{
					$output = $this->deleteComment($val->comment_srl, $is_admin, $isMoveToTrash);
					if(!$output->toBool())
					{
						return $output;
					}
				}
			}
		}

		if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
		{
			return new BaseObject(-1, 'msg_admin_comment_no_delete');
		}
		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// Delete
		$args = new stdClass();
		$args->comment_srl = $comment_srl;
		$output = executeQuery('comment.deleteComment', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$output = executeQuery('comment.deleteCommentList', $args);

		// update the number of comments
		$comment_count = $oCommentModel->getCommentCount($document_srl);

		// only document is exists
		if(isset($comment_count))
		{
			// create the controller object of the document
			$oDocumentController = getController('document');

			// update comment count of the article posting
			$output = $oDocumentController->updateCommentCount($document_srl, $comment_count, NULL, FALSE);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		// call a trigger (after)
		$comment->isMoveToTrash = $isMoveToTrash;
		ModuleHandler::triggerCall('comment.deleteComment', 'after', $comment);
		unset($comment->isMoveToTrash);

		if(!$isMoveToTrash)
		{
			$this->_deleteDeclaredComments($args);
			$this->_deleteVotedComments($args);
		} 
		else 
		{
			$args = new stdClass();
			$args->upload_target_srl = $comment_srl;
			$args->isvalid = 'N';
			$output = executeQuery('file.updateFileValid', $args);
		}

		// Remove the thumbnail file
		Rhymix\Framework\Storage::deleteEmptyDirectory(RX_BASEDIR . sprintf('files/thumbnails/%s', getNumberingPath($comment_srl, 3)), true);

		// commit
		$oDB->commit();

		$output->add('document_srl', $document_srl);

		return $output;
	}

	/**
	 * Comment move to trash
	 * @param $obj
	 * @return object
	 */
	function moveCommentToTrash($obj, $updateComment = false)
	{
		$logged_info = Context::get('logged_info');
		$trash_args = new stdClass();
		if(!$obj->trash_srl)
		{
			$trash_args->trash_srl = getNextSequence();
		}
		else
		{
			$trash_args->trash_srl = $obj->trash_srl;
		}

		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($obj->comment_srl);

		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($oComment->get('member_srl'));
		if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
		{
			return new BaseObject(-1, 'msg_admin_comment_no_move_to_trash');
		}

		$trash_args->module_srl = $oComment->variables['module_srl'];
		$obj->module_srl = $oComment->variables['module_srl'];

		if($trash_args->module_srl === 0)
		{
			return new BaseObject(-1, 'msg_module_srl_not_exists');
		}
		$trash_args->document_srl = $obj->document_srl;
		$trash_args->comment_srl = $obj->comment_srl;
		$trash_args->description = $obj->description;

		if(!Context::get('is_logged'))
		{
			$trash_args->member_Srl = $logged_info->member_srl;
			$trash_args->user_id = htmlspecialchars_decode($logged_info->user_id);
			$trash_args->user_name = htmlspecialchars_decode($logged_info->user_name);
			$trash_args->nick_name = htmlspecialchars_decode($logged_info->nick_name);
		}

		$oDB = &DB::getInstance();
		$oDB->begin();

		require_once(RX_BASEDIR.'modules/trash/model/TrashVO.php');
		$oTrashVO = new TrashVO();
		$oTrashVO->setTrashSrl(getNextSequence());
		$oTrashVO->setTitle(trim(strip_tags($obj->variables['content'])));
		$oTrashVO->setOriginModule('comment');
		$oTrashVO->setSerializedObject(serialize($obj->variables));
		$oTrashVO->setDescription($obj->description);

		$oTrashAdminController = getAdminController('trash');
		$output = $oTrashAdminController->insertTrash($oTrashVO);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		if($updateComment !== true)
		{
			$output = executeQuery('comment.deleteComment', $trash_args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		$args = new stdClass();
		$args->comment_srl = $obj->comment_srl;
		$output = executeQuery('comment.deleteCommentList', $args);

		// update the number of comments
		$comment_count = $oCommentModel->getCommentCount($obj->document_srl);

		// only document is exists
		if(isset($comment_count))
		{
			// create the controller object of the document
			$oDocumentController = getController('document');

			// update comment count of the article posting
			$output = $oDocumentController->updateCommentCount($obj->document_srl, $comment_count, NULL, FALSE);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		if($oComment->hasUploadedFiles())
		{
			$args = new stdClass();
			$args->upload_target_srl = $oComment->comment_srl;
			$args->isvalid = 'N';
			executeQuery('file.updateFileValid', $args);
		}

		ModuleHandler::triggerCall('comment.moveCommentToTrash', 'after', $obj);

		$oDB->commit();


		Rhymix\Framework\Storage::deleteEmptyDirectory(RX_BASEDIR . sprintf('files/thumbnails/%s', getNumberingPath($comment_srl, 3)), true);
		$output->add('document_srl', $obj->document_srl);

		return $output;
	}

	/**
	 * Remove all comment relation log
	 * @return Object
	 */
	function deleteCommentLog($args)
	{
		$this->_deleteDeclaredComments($args);
		$this->_deleteVotedComments($args);
		return new BaseObject(0, 'success');
	}

	/**
	 * Remove all comments of the article
	 * @param int $document_srl
	 * @return object
	 */
	function deleteComments($document_srl, $obj = NULL)
	{
		// create the document model object
		$oDocumentModel = getModel('document');
		$oCommentModel = getModel('comment');

		// check if permission is granted
		if(is_object($obj))
		{
			$oDocument = new documentItem();
			$oDocument->setAttribute($obj);
		}
		else
		{
			$oDocument = $oDocumentModel->getDocument($document_srl);
		}

		if(!$oDocument->isGranted())
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}

		// get a list of comments and then execute a trigger(way to reduce the processing cost for delete all)
		$args = new stdClass();
		$args->document_srl = $document_srl;
		$comments = executeQueryArray('comment.getAllComments', $args);
		if($comments->data)
		{
			$commentSrlList = array();
			foreach($comments->data as $comment)
			{
				$commentSrlList[] = $comment->comment_srl;

				// call a trigger (before)
				$output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
				if(!$output->toBool())
				{
					continue;
				}

				// call a trigger (after)
				ModuleHandler::triggerCall('comment.deleteComment', 'after', $comment);
			}
		}

		// delete the comment
		$args->document_srl = $document_srl;
		$output = executeQuery('comment.deleteComments', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		// Delete a list of comments
		$output = executeQuery('comment.deleteCommentsList', $args);

		//delete declared, declared_log, voted_log
		if(is_array($commentSrlList) && count($commentSrlList) > 0)
		{
			$args = new stdClass();
			$args->comment_srl = join(',', $commentSrlList);
			$this->_deleteDeclaredComments($args);
			$this->_deleteVotedComments($args);
		}

		return $output;
	}

	/**
	 * delete declared comment, log
	 * @param array|string $commentSrls : srls string (ex: 1, 2,56, 88)
	 * @return void
	 */
	function _deleteDeclaredComments($commentSrls)
	{
		executeQuery('comment.deleteDeclaredComments', $commentSrls);
		executeQuery('comment.deleteCommentDeclaredLog', $commentSrls);
	}

	/**
	 * delete voted comment log
	 * @param array|string $commentSrls : srls string (ex: 1, 2,56, 88)
	 * @return void
	 */
	function _deleteVotedComments($commentSrls)
	{
		executeQuery('comment.deleteCommentVotedLog', $commentSrls);
	}

	/**
	 * Increase vote-up counts of the comment
	 * @param int $comment_srl
	 * @param int $point
	 * @return Object
	 */
	function updateVotedCount($comment_srl, $point = 1)
	{
		if($point > 0)
		{
			$failed_voted = 'failed_voted';
			$success_message = 'success_voted';
		}
		else
		{
			$failed_voted = 'failed_blamed';
			$success_message = 'success_blamed';
		}

		// invalid vote if vote info exists in the session info.
		if($_SESSION['voted_comment'][$comment_srl])
		{
			return new BaseObject(-1, $failed_voted);
		}

		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($comment_srl, FALSE, FALSE);

		// invalid vote if both ip addresses between author's and the current user are same.
		if($oComment->get('ipaddress') == $_SERVER['REMOTE_ADDR'])
		{
			$_SESSION['voted_comment'][$comment_srl] = false;
			return new BaseObject(-1, $failed_voted);
		}

		// if the comment author is a member
		if($oComment->get('member_srl'))
		{
			// create the member model object
			$oMemberModel = getModel('member');
			$member_srl = $oMemberModel->getLoggedMemberSrl();

			// session registered if the author information matches to the current logged-in user's.
			if($member_srl && $member_srl == abs($oComment->get('member_srl')))
			{
				$_SESSION['voted_comment'][$comment_srl] = false;
				return new BaseObject(-1, $failed_voted);
			}
		}

		$args = new stdClass();

		// If logged-in, use the member_srl. otherwise use the ipaddress.
		if($member_srl)
		{
			$args->member_srl = $member_srl;
		}
		else
		{
			$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		}

		$args->comment_srl = $comment_srl;
		$output = executeQuery('comment.getCommentVotedLogInfo', $args);

		// session registered if log info contains recommendation vote log.
		if($output->data->count)
		{
			$_SESSION['voted_comment'][$comment_srl] = false;
			return new BaseObject(-1, $failed_voted);
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// update the number of votes
		if($point < 0)
		{
			// leave into session information
			$_SESSION['voted_comment'][$comment_srl] = $point;
			$args->blamed_count = $oComment->get('blamed_count') + $point;
			$output = executeQuery('comment.updateBlamedCount', $args);
		}
		else
		{
			$_SESSION['voted_comment'][$comment_srl] = $point;
			$args->voted_count = $oComment->get('voted_count') + $point;
			$output = executeQuery('comment.updateVotedCount', $args);
		}

		// leave logs
		$args->point = $point;
		$output = executeQuery('comment.insertCommentVotedLog', $args);

		$obj = new stdClass();
		$obj->member_srl = $oComment->get('member_srl');
		$obj->module_srl = $oComment->get('module_srl');
		$obj->comment_srl = $oComment->get('comment_srl');
		$obj->update_target = ($point < 0) ? 'blamed_count' : 'voted_count';
		$obj->point = $point;
		$obj->before_point = ($point < 0) ? $oComment->get('blamed_count') : $oComment->get('voted_count');
		$obj->after_point = ($point < 0) ? $args->blamed_count : $args->voted_count;
		
		ModuleHandler::triggerCall('comment.updateVotedCount', 'after', $obj);
		$oDB->commit();

		// Return the result
		$output = new BaseObject(0, $success_message);
		if($point > 0)
		{
			$output->add('voted_count', $obj->after_point);
		}
		else
		{
			$output->add('blamed_count', $obj->after_point);
		}

		return $output;
	}

	/**
	 * Report a blamed comment
	 * @param $comment_srl
	 * @param string $declare_message
	 * @return void
	 */
	function declaredComment($comment_srl, $declare_message)
	{
		// Fail if session information already has a reported document
		if($_SESSION['declared_comment'][$comment_srl])
		{
			return new BaseObject(-1, 'failed_declared');
		}

		// check if already reported
		$args = new stdClass();
		$args->comment_srl = $comment_srl;
		$output = executeQuery('comment.getDeclaredComment', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		
		$declared_count = ($output->data->declared_count) ? $output->data->declared_count : 0;
		$declare_message = trim(htmlspecialchars($declare_message));
		
		$trigger_obj = new stdClass();
		$trigger_obj->comment_srl = $comment_srl;
		$trigger_obj->declared_count = $declared_count;
		$trigger_obj->declare_message = $declare_message;
		
		// Call a trigger (before)
		$trigger_output = ModuleHandler::triggerCall('comment.declaredComment', 'before', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
		}

		// get the original comment
		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($comment_srl, FALSE, FALSE);

		// failed if both ip addresses between author's and the current user are same.
		if($oComment->get('ipaddress') == $_SERVER['REMOTE_ADDR'])
		{
			$_SESSION['declared_comment'][$comment_srl] = TRUE;
			return new BaseObject(-1, 'failed_declared');
		}

		// if the comment author is a member
		if($oComment->get('member_srl'))
		{
			// create the member model object
			$oMemberModel = getModel('member');
			$member_srl = $oMemberModel->getLoggedMemberSrl();

			// session registered if the author information matches to the current logged-in user's.
			if($member_srl && $member_srl == abs($oComment->get('member_srl')))
			{
				$_SESSION['declared_comment'][$comment_srl] = TRUE;
				return new BaseObject(-1, 'failed_declared');
			}
		}

		// If logged-in, use the member_srl. otherwise use the ipaddress.
		if($member_srl)
		{
			$args->member_srl = $member_srl;
		}
		else
		{
			$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		}

		$args->comment_srl = $comment_srl;
		$args->declare_message = $declare_message;
		$log_output = executeQuery('comment.getCommentDeclaredLogInfo', $args);

		// session registered if log info contains report log.
		if($log_output->data->count)
		{
			$_SESSION['declared_comment'][$comment_srl] = TRUE;
			return new BaseObject(-1, 'failed_declared');
		}

		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		// execute insert
		if($output->data->declared_count > 0)
		{
			$output = executeQuery('comment.updateDeclaredComment', $args);
		}
		else
		{
			$output = executeQuery('comment.insertDeclaredComment', $args);
		}

		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// leave the log
		$output = executeQuery('comment.insertCommentDeclaredLog', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$this->add('declared_count', $declared_count + 1);

		// Call a trigger (after)
		$trigger_obj->declared_count = $declared_count + 1;
		ModuleHandler::triggerCall('comment.declaredComment', 'after', $trigger_obj);

		// commit
		$oDB->commit();

		// leave into the session information
		$_SESSION['declared_comment'][$comment_srl] = TRUE;

		$this->setMessage('success_declared');
	}

	/**
	 * Method to add a pop-up menu when clicking for displaying child comments
	 * @param string $url
	 * @param string $str
	 * @param strgin $icon
	 * @param strgin $target
	 * @return void
	 */
	function addCommentPopupMenu($url, $str, $icon = '', $target = 'self')
	{
		$comment_popup_menu_list = Context::get('comment_popup_menu_list');
		if(!is_array($comment_popup_menu_list))
		{
			$comment_popup_menu_list = array();
		}

		$obj = new stdClass();
		$obj->url = $url;
		$obj->str = $str;
		$obj->icon = $icon;
		$obj->target = $target;
		$comment_popup_menu_list[] = $obj;

		Context::set('comment_popup_menu_list', $comment_popup_menu_list);
	}

	/**
	 * Save the comment extension form for each module
	 * @return void
	 */
	function procCommentInsertModuleConfig()
	{
		$target_module_srl = Context::get('target_module_srl');
		$target_module_srl = array_map('trim', explode(',', $target_module_srl));
		$logged_info = Context::get('logged_info');
		$module_srl = array();
		$oModuleModel = getModel('module');
		foreach ($target_module_srl as $srl)
		{
			if (!$srl) continue;
			
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($srl);
			if (!$module_info->module_srl)
			{
				return $this->setError('msg_invalid_request');
			}
			
			$module_grant = $oModuleModel->getGrant($module_info, $logged_info);
			if (!$module_grant->manager)
			{
				return $this->setError('msg_not_permitted');
			}
			
			$module_srl[] = $srl;
		}

		$comment_config = new stdClass();
		$comment_config->comment_count = (int) Context::get('comment_count');
		if(!$comment_config->comment_count)
		{
			$comment_config->comment_count = 50;
		}

		$comment_config->use_vote_up = Context::get('use_vote_up');
		if(!$comment_config->use_vote_up)
		{
			$comment_config->use_vote_up = 'Y';
		}

		$comment_config->use_vote_down = Context::get('use_vote_down');
		if(!$comment_config->use_vote_down)
		{
			$comment_config->use_vote_down = 'Y';
		}

		$comment_config->use_comment_validation = Context::get('use_comment_validation');
		if(!$comment_config->use_comment_validation)
		{
			$comment_config->use_comment_validation = 'N';
		}

		foreach ($module_srl as $srl)
		{
			$output = $this->setCommentModuleConfig($srl, $comment_config);
		}

		$this->setError(-1);
		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Comment module config setting
	 * @param int $srl
	 * @param object $comment_config
	 * @return Object
	 */
	function setCommentModuleConfig($srl, $comment_config)
	{
		$oModuleController = getController('module');
		$oModuleController->insertModulePartConfig('comment', $srl, $comment_config);
		return new BaseObject();
	}

	/**
	 * Get comment all list
	 * @return void
	 */
	function procCommentGetList()
	{
		if(!Context::get('is_logged'))
		{
			return $this->setError('msg_not_permitted');
		}

		$commentSrls = Context::get('comment_srls');
		if($commentSrls)
		{
			$commentSrlList = explode(',', $commentSrls);
		}

		if(count($commentSrlList) > 0)
		{
			$oCommentModel = getModel('comment');
			$commentList = $oCommentModel->getComments($commentSrlList);

			if(is_array($commentList))
			{
				foreach($commentList as $value)
				{
					$value->content = strip_tags($value->content);
				}
			}
		}
		else
		{
			global $lang;
			$commentList = array();
			$this->setMessage($lang->no_documents);
		}

		$oSecurity = new Security($commentList);
		$oSecurity->encodeHTML('..variables.', '..');

		$this->add('comment_list', $commentList);
	}

	function triggerCopyModule(&$obj)
	{
		$oModuleModel = getModel('module');
		$commentConfig = $oModuleModel->getModulePartConfig('comment', $obj->originModuleSrl);

		$oModuleController = getController('module');
		if(is_array($obj->moduleSrlList))
		{
			foreach($obj->moduleSrlList as $moduleSrl)
			{
				$oModuleController->insertModulePartConfig('comment', $moduleSrl, $commentConfig);
			}
		}
	}

}
/* End of file comment.controller.php */
/* Location: ./modules/comment/comment.controller.php */
