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
		if($this->module_info->non_login_vote !== 'Y')
		{
			if(!Context::get('is_logged'))
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
		}

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$oComment = CommentModel::getComment($comment_srl, FALSE, FALSE);
		$module_srl = $oComment->get('module_srl');
		if(!$module_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$comment_config = ModuleModel::getModulePartConfig('comment', $module_srl);
		if($comment_config->use_vote_up == 'N')
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}

		$point = 1;
		$output = $this->updateVotedCount($comment_srl, $point);
		$this->add('voted_count', $output->get('voted_count'));
		return $output;
	}

	function procCommentVoteUpCancel()
	{
		if($this->module_info->non_login_vote !== 'Y')
		{
			if(!Context::get('is_logged'))
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
		}

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oComment = CommentModel::getComment($comment_srl, FALSE, FALSE);
		if($oComment->get('voted_count') <= 0)
		{
			throw new Rhymix\Framework\Exception('failed_voted_canceled');
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
		if($this->module_info->non_login_vote !== 'Y')
		{
			if(!Context::get('is_logged'))
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
		}

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$oComment = CommentModel::getComment($comment_srl, FALSE, FALSE);
		$module_srl = $oComment->get('module_srl');
		if(!$module_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$comment_config = ModuleModel::getModulePartConfig('comment', $module_srl);
		if($comment_config->use_vote_down == 'N')
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled;
		}

		$point = -1;
		$output = $this->updateVotedCount($comment_srl, $point);
		$this->add('blamed_count', $output->get('blamed_count'));
		return $output;
	}

	function procCommentVoteDownCancel()
	{
		if($this->module_info->non_login_vote !== 'Y')
		{
			if(!Context::get('is_logged'))
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
		}

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oComment = CommentModel::getComment($comment_srl, FALSE, FALSE);
		if($oComment->get('blamed_count') >= 0)
		{
			throw new Rhymix\Framework\Exception('failed_blamed_canceled');
		}
		$point = -1;
		$output = $this->updateVotedCountCancel($comment_srl, $oComment, $point);

		$output = new BaseObject();
		$output->setMessage('success_blamed_canceled');

		return $output;
	}

	function updateVotedCountCancel($comment_srl, $oComment, $point)
	{
		if(!$_SESSION['voted_comment'][$comment_srl] && !$this->user->member_srl)
		{
			return new BaseObject(-1, $point > 0 ? 'failed_voted_canceled' : 'failed_blamed_canceled');
		}
		
		// Check if the current user has voted previously.
		$args = new stdClass;
		$args->comment_srl = $comment_srl;
		$args->point = $point;
		if($this->user->member_srl)
		{
			$args->member_srl = $this->user->member_srl;
		}
		else
		{
			$args->member_srl = 0;
			$args->ipaddress = \RX_CLIENT_IP;
		}
		$output = executeQuery('comment.getCommentVotedLogInfo', $args);
		if(!$output->data->count)
		{
			return new BaseObject(-1, $point > 0 ? 'failed_voted_canceled' : 'failed_blamed_canceled');
		}

		// Call a trigger (before)
		$trigger_obj = new stdClass;
		$trigger_obj->member_srl = $oComment->get('member_srl');
		$trigger_obj->module_srl = $oComment->get('module_srl');
		$trigger_obj->document_srl = $oComment->get('document_srl');
		$trigger_obj->comment_srl = $oComment->get('comment_srl');
		$trigger_obj->update_target = ($point < 0) ? 'blamed_count' : 'voted_count';
		$trigger_obj->point = $point;
		$trigger_obj->before_point = ($point < 0) ? $oComment->get('blamed_count') : $oComment->get('voted_count');
		$trigger_obj->after_point = $trigger_obj->before_point - $point;
		$trigger_obj->cancel = true;
		$trigger_output = ModuleHandler::triggerCall('comment.updateVotedCountCancel', 'before', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		$args = new stdClass();
		$d_args = new stdClass();
		$args->comment_srl = $d_args->comment_srl = $comment_srl;
		$d_args->member_srl = $this->user->member_srl;
		if ($trigger_obj->update_target === 'voted_count')
		{
			$args->voted_count = $trigger_obj->after_point;
			$output = executeQuery('comment.updateVotedCount', $args);
		}
		else
		{
			$args->blamed_count = $trigger_obj->after_point;
			$output = executeQuery('comment.updateBlamedCount', $args);
		}
		$d_output = executeQuery('comment.deleteCommentVotedLog', $d_args);
		if(!$d_output->toBool()) return $d_output;

		//session reset
		unset($_SESSION['voted_comment'][$comment_srl]);
		
		// Call a trigger (after)
		ModuleHandler::triggerCall('comment.updateVotedCountCancel', 'after', $trigger_obj);
		
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
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$comment_srl = Context::get('target_srl');
		if(!$comment_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
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
		$comment = CommentModel::getComment($comment_srl);
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

		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		$module_part_config = ModuleModel::getModulePartConfig('comment', $module_info->module_srl);
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
			return new BaseObject(-1, 'msg_security_violation');
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
		
		// Sanitize variables
		$obj->comment_srl = intval($obj->comment_srl);
		$obj->module_srl = intval($obj->module_srl);
		$obj->document_srl = intval($obj->document_srl);
		$obj->parent_srl = intval($obj->parent_srl);

		$obj->uploaded_count = FileModel::getFilesCount($obj->comment_srl);
		
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

		// even for manual_inserted if password exists, hash it.
		if($obj->password)
		{
			$obj->password = MemberModel::hashPassword($obj->password);
		}

		// get the original posting
		if(!$manual_inserted)
		{
			$oDocument = DocumentModel::getDocument($document_srl);

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
				$obj->homepage = escape($obj->homepage);
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
				return new BaseObject(-1, 'parent comment does not exist');
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
				$p_args->document_srl = $document_srl;
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
		
		// create the controller object of the document
		$oDocumentController = getController('document');

		// Update the number of comments in the post
		$comment_count = CommentModel::getCommentCount($document_srl);
		if($comment_count && (!$using_validation || $is_admin))
		{
			$output = $oDocumentController->updateCommentCount($document_srl, $comment_count, $obj->nick_name, $update_document);
		}

		if($obj->uploaded_count > 0)
		{
			$attachOutput = getController('file')->setFilesValid($obj->comment_srl, 'com');
			if(!$attachOutput->toBool())
			{
				$oDB->rollback();
				return $attachOutput;
			}
			$obj->updated_file_count = $attachOutput->get('updated_file_count');
		}
		else
		{
			$obj->updated_file_count = 0;
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
				$oParent = CommentModel::getComment($obj->parent_srl);
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

		$oDocument = DocumentModel::getDocument($obj->document_srl);
		if(isset($obj->member_srl) && !is_null($obj->member_srl))
		{
			$member_info = MemberModel::getMemberInfoByMemberSrl($obj->member_srl);
		}
		else
		{
			$member_info = new stdClass();
			$member_info->is_admin = "N";
			$member_info->nick_name = $obj->nick_name;
			$member_info->user_name = $obj->user_name;
			$member_info->email_address = $obj->email_address;
		}

		$module_info = ModuleModel::getModuleInfoByDocumentSrl($obj->document_srl);

		// If there is no problem to register comment then send an email to all admin were set in module admin panel
		if($module_info->admin_mail && $member_info->is_admin != 'Y')
		{
			$browser_title = Context::replaceUserLang($module_info->browser_title);
			$mail_title = sprintf(lang('msg_comment_notify_mail'), $browser_title, cut_str($oDocument->getTitleText(), 20, '...'));
			$url_comment = getFullUrl('','document_srl',$obj->document_srl).'#comment_'.$obj->comment_srl;
			if($using_validation)
			{
				$nr_comments_not_approved = CommentModel::getCommentAllCount(NULL, FALSE);
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
			if($member_info->email_address)
			{
				$oMail->setReplyTo($member_info->email_address);
			}
			foreach (array_map('trim', explode(',', $module_info->admin_mail)) as $email_address)
			{
				$oMail->addTo($email_address);
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
			return new BaseObject(-1, 'msg_security_violation');
		}

		if(!is_object($obj))
		{
			$obj = new stdClass();
		}

		$obj->__isupdate = TRUE;

		// Remove manual member info to prevent forgery. This variable can be set by triggers only.
		unset($obj->manual_member_info);
		
		// Sanitize variables
		$obj->comment_srl = intval($obj->comment_srl);
		$obj->module_srl = intval($obj->module_srl);
		$obj->document_srl = intval($obj->document_srl);
		$obj->parent_srl = intval($obj->parent_srl);

		$obj->uploaded_count = FileModel::getFilesCount($obj->comment_srl);
		
		// call a trigger (before)
		$output = ModuleHandler::triggerCall('comment.updateComment', 'before', $obj);
		if(!$output->toBool())
		{
			return $output;
		}

		// get the original data
		$source_obj = CommentModel::getComment($obj->comment_srl);
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
			$obj->password = MemberModel::hashPassword($obj->password);
		}

		if($obj->homepage) 
		{
			$obj->homepage = escape($obj->homepage);
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

		if($obj->uploaded_count > 0)
		{
			$attachOutput = getController('file')->setFilesValid($obj->comment_srl, 'com');
			if(!$attachOutput->toBool())
			{
				$oDB->rollback();
				return $attachOutput;
			}
			$obj->updated_file_count = $attachOutput->get('updated_file_count');
		}
		else
		{
			$obj->updated_file_count = 0;
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
		$comment = getModel('comment')->getComment($obj->comment_srl);
		if(!$comment->isExists())
		{
			return new BaseObject(-1, 'msg_not_founded');
		}
		if(!$is_admin && !$comment->isGranted())
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}
		
		// call a trigger (before)
		$output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
		if(!$output->toBool())
		{
			return $output;
		}

		// check if comment exists and permission is granted
		$comment = CommentModel::getComment($obj->comment_srl);
		if(!$comment->isExists())
		{
			return new BaseObject(-1, 'msg_not_founded');
		}
		if(!$is_admin && !$comment->isGranted())
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}

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

		// Begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();
		
		// Update
		$obj->member_srl = 0;
		unset($obj->last_update);
		$output = executeQuery('comment.updateCommentByDelete', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// call a trigger (after)
		ModuleHandler::triggerCall('comment.deleteComment', 'after', $comment);

		// update the number of comments
		$comment_count = CommentModel::getCommentCount($obj->document_srl);
		// only document is exists
		if(is_int($comment_count))
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
		$comment_count = CommentModel::getCommentCount($obj->document_srl);
		// only document is exists
		if(is_int($comment_count))
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
		// check if comment already exists
		$comment = CommentModel::getComment($comment_srl);
		if(!$comment->isExists())
		{
			return new BaseObject(-1, 'msg_not_founded');
		}
		if(!$is_admin && !$comment->isGranted())
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}

		$logged_info = Context::get('logged_info');
		$member_info = MemberModel::getMemberInfo($comment->get('member_srl'));
		$module_info = ModuleModel::getModuleInfo($comment->get('module_srl'));
		$document_srl = $comment->get('document_srl');

		// call a trigger (before)
		$comment->isMoveToTrash = $isMoveToTrash ? true : false;
		$output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
		if(!$output->toBool())
		{
			return $output;
		}

		// check if child comment exists on the comment
		if($childs === null)
		{
			$childs = CommentModel::getChildComments($comment_srl);
		}
		if(count($childs) > 0)
		{
			$deleteAllComment = TRUE;
			$deleteAdminComment = TRUE;
			if(!$is_admin)
			{
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
				foreach($childs as $val)
				{
					if ($module_info->protect_admin_content_delete !== 'N' && $logged_info->is_admin !== 'Y')
					{
						$c_member_info = MemberModel::getMemberInfoByMemberSrl($val->member_srl);
						if($c_member_info->is_admin == 'Y')
						{
							$deleteAdminComment = FALSE;
							break;
						}
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
		$comment_count = CommentModel::getCommentCount($document_srl);

		// only document is exists
		if(is_int($comment_count))
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
		Rhymix\Framework\Storage::deleteDirectory(RX_BASEDIR . sprintf('files/thumbnails/%s', getNumberingPath($comment_srl, 3)));

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
		// check if comment exists and permission is granted
		$oComment = CommentModel::getComment($obj->comment_srl);
		if(!$oComment->isExists())
		{
			return new BaseObject(-1, 'msg_not_founded');
		}
		if(!$oComment->isGranted())
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}
		
		$logged_info = Context::get('logged_info');
		$module_info = ModuleModel::getModuleInfo($oComment->get('module_srl'));
		
		if ($module_info->protect_admin_content_delete !== 'N' && $logged_info->is_admin !== 'Y')
		{
			$member_info = MemberModel::getMemberInfo($oComment->get('member_srl'));
			if($member_info->is_admin === 'Y')
			{
				return new BaseObject(-1, 'msg_admin_comment_no_move_to_trash');
			}
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		require_once(RX_BASEDIR.'modules/trash/model/TrashVO.php');
		$oTrashVO = new TrashVO();
		$oTrashVO->setTrashSrl(getNextSequence());
		$oTrashVO->setTitle($oComment->getContentText(200));
		$oTrashVO->setOriginModule('comment');
		$oTrashVO->setSerializedObject(serialize($oComment->variables));
		$oTrashVO->setDescription($obj->description);

		$oTrashAdminController = getAdminController('trash');
		$output = $oTrashAdminController->insertTrash($oTrashVO);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		if(!$updateComment)
		{
			$args = new stdClass;
			$args->comment_srl = $obj->comment_srl;
			$output = executeQuery('comment.deleteComment', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
			$output = executeQuery('comment.deleteCommentList', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		// update the number of comments
		$comment_count = CommentModel::getCommentCount($oComment->document_srl);
		if(is_int($comment_count))
		{
			$output = getController('document')->updateCommentCount($oComment->document_srl, $comment_count);
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

		$obj->module_srl = $oComment->get('module_srl');
		$obj->document_srl = $oComment->get('document_srl');
		$obj->parent_srl = $oComment->get('parent_srl');
		$obj->member_srl = $oComment->get('member_srl');
		$obj->regdate = $oComment->get('regdate');
		$obj->last_update = $oComment->get('last_update');
		ModuleHandler::triggerCall('comment.moveCommentToTrash', 'after', $obj);

		$oDB->commit();

		// Remove the thumbnail file
		Rhymix\Framework\Storage::deleteDirectory(RX_BASEDIR . sprintf('files/thumbnails/%s', getNumberingPath($obj->comment_srl, 3)));

		$output->add('document_srl', $oComment->document_srl);

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
		// check if permission is granted
		if(is_object($obj))
		{
			$oDocument = new documentItem();
			$oDocument->setAttribute($obj);
		}
		else
		{
			$oDocument = DocumentModel::getDocument($document_srl);
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
		}
		else
		{
			$failed_voted = 'failed_blamed';
		}

		// invalid vote if vote info exists in the session info.
		if($_SESSION['voted_comment'][$comment_srl])
		{
			return new BaseObject(-1, $failed_voted);
		}

		// Get the original comment
		$oComment = CommentModel::getComment($comment_srl, FALSE, FALSE);

		// Pass if the author's IP address is as same as visitor's.
		if($oComment->get('ipaddress') == \RX_CLIENT_IP)
		{
			$_SESSION['voted_comment'][$comment_srl] = false;
			return new BaseObject(-1, $failed_voted);
		}

		// Create a member model object
		$member_srl = MemberModel::getLoggedMemberSrl();

		// if the comment author is a member
		if($oComment->get('member_srl'))
		{
			// session registered if the author information matches to the current logged-in user's.
			if($member_srl && $member_srl == abs($oComment->get('member_srl')))
			{
				$_SESSION['voted_comment'][$comment_srl] = false;
				return new BaseObject(-1, $failed_voted);
			}
		}

		// If logged-in, use the member_srl. otherwise use the ipaddress.
		$args = new stdClass();
		if($member_srl)
		{
			$args->member_srl = $member_srl;
		}
		else
		{
			$args->member_srl = 0;
			$args->ipaddress = \RX_CLIENT_IP;
		}
		$args->comment_srl = $comment_srl;
		$output = executeQuery('comment.getCommentVotedLogInfo', $args);

		// Pass after registering a session if log information has vote-up logs
		if($output->data->count)
		{
			$_SESSION['voted_comment'][$comment_srl] = false;
			return new BaseObject(-1, $failed_voted);
		}

		// Call a trigger (before)
		$trigger_obj = new stdClass;
		$trigger_obj->member_srl = $oComment->get('member_srl');
		$trigger_obj->module_srl = $oComment->get('module_srl');
		$trigger_obj->document_srl = $oComment->get('document_srl');
		$trigger_obj->comment_srl = $oComment->get('comment_srl');
		$trigger_obj->update_target = ($point < 0) ? 'blamed_count' : 'voted_count';
		$trigger_obj->point = $point;
		$trigger_obj->before_point = ($point < 0) ? $oComment->get('blamed_count') : $oComment->get('voted_count');
		$trigger_obj->after_point = $trigger_obj->before_point + $point;
		$trigger_obj->cancel = false;
		$trigger_output = ModuleHandler::triggerCall('comment.updateVotedCount', 'before', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
		}

		// begin transaction
		$oDB = DB::getInstance();
		$oDB->begin();

		// Update the voted count
		if($trigger_obj->update_target === 'blamed_count')
		{
			// leave into session information
			$args->blamed_count = $trigger_obj->after_point;
			$output = executeQuery('comment.updateBlamedCount', $args);
		}
		else
		{
			$args->voted_count = $trigger_obj->after_point;
			$output = executeQuery('comment.updateVotedCount', $args);
		}

		// leave logs
		$args->point = $trigger_obj->point;
		$output = executeQuery('comment.insertCommentVotedLog', $args);

		// Leave in the session information
		$_SESSION['voted_comment'][$comment_srl] = $trigger_obj->point;

		// Call a trigger (after)
		ModuleHandler::triggerCall('comment.updateVotedCount', 'after', $trigger_obj);
		$oDB->commit();

		// Return the result
		$output = new BaseObject();
		if($trigger_obj->update_target === 'voted_count')
		{
			$output->setMessage('success_voted');
			$output->add('voted_count', $trigger_obj->after_point);
		}
		else
		{
			$output->setMessage('success_blamed');
			$output->add('blamed_count', $trigger_obj->after_point);
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
		if(isset($_SESSION['declared_comment'][$comment_srl]))
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
		$oComment = CommentModel::getComment($comment_srl, FALSE, FALSE);

		// failed if both ip addresses between author's and the current user are same.
		if($oComment->get('ipaddress') == \RX_CLIENT_IP && !$this->user->isAdmin())
		{
			$_SESSION['declared_comment'][$comment_srl] = FALSE;
			return new BaseObject(-1, 'failed_declared');
		}

		// Get currently logged in user.
		$member_srl = intval($this->user->member_srl);
		
		// if the comment author is a member
		if($oComment->get('member_srl'))
		{
			// session registered if the author information matches to the current logged-in user's.
			if($member_srl && $member_srl == abs($oComment->get('member_srl')))
			{
				$_SESSION['declared_comment'][$comment_srl] = FALSE;
				return new BaseObject(-1, 'failed_declared');
			}
		}

		// Pass after registering a sesson if reported/declared documents are in the logs.
		$args = new stdClass;
		$args->comment_srl = $comment_srl;
		if($member_srl)
		{
			$args->member_srl = $member_srl;
		}
		else
		{
			$args->ipaddress = \RX_CLIENT_IP;
		}
		$log_output = executeQuery('comment.getCommentDeclaredLogInfo', $args);
		if($log_output->data->count)
		{
			$_SESSION['declared_comment'][$comment_srl] = FALSE;
			return new BaseObject(-1, 'failed_declared');
		}
		
		// Fill in remaining information for logging.
		$args->member_srl = $member_srl;
		$args->ipaddress = \RX_CLIENT_IP;
		$args->declare_message = $declare_message;
		
		// begin transaction
		$oDB = DB::getInstance();
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

		// Send message to admin
		$message_targets = array();
		$module_srl = $oComment->get('module_srl');
		$comment_config = ModuleModel::getModulePartConfig('comment', $module_srl);
		if ($comment_config->declared_message && in_array('admin', $comment_config->declared_message))
		{
			$output = executeQueryArray('member.getAdmins', new stdClass);
			foreach ($output->data as $admin)
			{
				$message_targets[$admin->member_srl] = true;
			}
		}
		if ($comment_config->declared_message && in_array('manager', $comment_config->declared_message))
		{
			$output = executeQueryArray('module.getModuleAdmin', (object)['module_srl' => $module_srl]);
			foreach ($output->data as $manager)
			{
				$message_targets[$manager->member_srl] = true;
			}
		}
		if ($message_targets)
		{
			$oCommunicationController = getController('communication');
			$message_title = lang('document.declared_message_title');
			$message_content = sprintf('<p><a href="%s">%s</a></p><p>%s</p>', $oComment->getPermanentUrl(), $oComment->getContentText(50), $declare_message);
			foreach ($message_targets as $target_member_srl => $val)
			{
				$oCommunicationController->sendMessage($this->user->member_srl, $target_member_srl, $message_title, $message_content, false, null, false);
			}
		}

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
	function addCommentPopupMenu($url, $str, $icon = '', $target = '_blank')
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
		foreach ($target_module_srl as $srl)
		{
			if (!$srl) continue;
			
			$module_info = ModuleModel::getModuleInfoByModuleSrl($srl);
			if (!$module_info->module_srl)
			{
				throw new Rhymix\Framework\Exceptions\InvalidRequest;
			}
			
			$module_grant = ModuleModel::getGrant($module_info, $logged_info);
			if (!$module_grant->manager)
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
			
			$module_srl[] = $srl;
		}

		$comment_config = new stdClass();
		$comment_config->comment_count = (int) Context::get('comment_count');
		if(!$comment_config->comment_count)
		{
			$comment_config->comment_count = 50;
		}
		$comment_config->comment_page_count = (int) Context::get('comment_page_count');
		if(!$comment_config->comment_page_count)
		{
			$comment_config->comment_page_count = 10;
		}

		$comment_config->default_page = Context::get('default_page');
		if($comment_config->default_page !== 'first')
		{
			$comment_config->default_page = 'last';
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

		$comment_config->declared_message = Context::get('declared_message');
		if(!is_array($comment_config->declared_message)) $comment_config->declared_message = array();
		$comment_config->declared_message = array_values($comment_config->declared_message);

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
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$commentSrls = Context::get('comment_srls');
		if($commentSrls)
		{
			$commentSrlList = explode(',', $commentSrls);
		}

		if(count($commentSrlList) > 0)
		{
			$commentList = CommentModel::getComments($commentSrlList);

			if(is_array($commentList))
			{
				foreach($commentList as $value)
				{
					$value->content = escape(strip_tags($value->content), false);
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
	
	function triggerMoveDocument($obj)
	{
		executeQuery('comment.updateCommentModule', $obj);
		executeQuery('comment.updateCommentListModule', $obj);
	}
	
	function triggerAddCopyDocument(&$obj)
	{
		$args = new stdClass;
		$args->document_srls = $obj->source->document_srl;
		$comment_list = executeQueryArray('comment.getCommentsByDocumentSrls', $args)->data;
		
		$copied_comments = array();
		foreach($comment_list as $comment)
		{
			$copy = clone $comment;
			$copy->comment_srl = getNextSequence();
			$copy->module_srl = $obj->copied->module_srl;
			$copy->document_srl = $obj->copied->document_srl;
			$copy->parent_srl = $comment->parent_srl ? $copied_comments[$comment->parent_srl] : 0;
			
			// call a trigger (add)
			$args = new stdClass;
			$args->source = $comment;
			$args->copied = $copy;
			ModuleHandler::triggerCall('comment.copyCommentByDocument', 'add', $args);
			
			// insert a copied comment
			$this->insertComment($copy, true);
			
			$copied_comments[$comment->comment_srl] = $copy->comment_srl;
		}
		
		// update
		$obj->copied->last_updater = $copy->nick_name;
		$obj->copied->comment_count = count($copied_comments);
	}
	
	function triggerCopyModule(&$obj)
	{
		$commentConfig = ModuleModel::getModulePartConfig('comment', $obj->originModuleSrl);

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
