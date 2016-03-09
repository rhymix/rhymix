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
		if($this->module_info->module != "board")
		{
			return new Object(-1, "msg_invalid_request");
		}
		if(!$this->grant->write_document)
		{
			return new Object(-1, 'msg_not_permitted');
		}
		$logged_info = Context::get('logged_info');

		// setup variables
		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_srl;
		if($obj->is_notice!='Y'||!$this->grant->manager) $obj->is_notice = 'N';
		$obj->commentStatus = $obj->comment_status;

		settype($obj->title, "string");
		if($obj->title == '') $obj->title = cut_str(trim(strip_tags(nl2br($obj->content))),20,'...');
		//setup dpcument title tp 'Untitled'
		if($obj->title == '') $obj->title = 'Untitled';

		// unset document style if the user is not the document manager
		if(!$this->grant->manager)
		{
			unset($obj->title_color);
			unset($obj->title_bold);
		}

		// generate document module model object
		$oDocumentModel = getModel('document');

		// generate document module의 controller object
		$oDocumentController = getController('document');

		// check if the document is existed
		$oDocument = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);

		// update the document if it is existed
		$is_update = false;
		if($oDocument->isExists() && $oDocument->document_srl == $obj->document_srl)
		{
			$is_update = true;
		}

		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($oDocument->get('member_srl'));
		if($member_info->is_admin == 'Y' && $logged_info->is_admin != 'Y')
		{
			return new Object(-1, 'msg_admin_document_no_modify');
		}

		// if use anonymous is true
		if($this->module_info->use_anonymous == 'Y')
		{
			$this->module_info->admin_mail = '';
			$obj->notify_message = 'N';
			if($is_update===false)
			{
				$obj->member_srl = -1*$logged_info->member_srl;
			}
			$obj->email_address = $obj->homepage = $obj->user_id = '';
			$obj->user_name = $obj->nick_name = 'anonymous';
			$bAnonymous = true;
			if($is_update===false)
			{
				$oDocument->add('member_srl', $obj->member_srl);
			}
		}
		else
		{
			$bAnonymous = false;
		}

		if($obj->is_secret == 'Y' || strtoupper($obj->status == 'SECRET'))
		{
			$use_status = explode('|@|', $this->module_info->use_status);
			if(!is_array($use_status) || !in_array('SECRET', $use_status))
			{
				unset($obj->is_secret);
				$obj->status = 'PUBLIC';
			}
		}

		if($this->module_info->update_log == 'Y')
		{
			$obj->update_log_setting = 'Y';
		}

		// update the document if it is existed
		if($is_update)
		{
			if(!$oDocument->isGranted())
			{
				return new Object(-1,'msg_not_permitted');
			}

			if($this->module_info->protect_content == 'Y' || $this->module_info->protect_update_content == 'Y')
			{
				if($oDocument->get('comment_count') > 0 && $this->grant->manager == false)
				{
					return new Object(-1, 'msg_protect_update_content');
				}
			}

			if($this->module_info->use_anonymous == 'Y') {
				$obj->member_srl = abs($oDocument->get('member_srl')) * -1;
				$oDocument->add('member_srl', $obj->member_srl);
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

			if(!$this->grant->manager)
			{
				// notice & document style same as before if not manager
				$obj->is_notice = $oDocument->get('is_notice');
				$obj->title_color = $oDocument->get('title_color');
				$obj->title_bold = $oDocument->get('title_bold');
			}
			
			// modify list_order if document status is temp
			if($oDocument->get('status') == 'TEMP')
			{
				$obj->last_update = $obj->regdate = date('YmdHis');
				$obj->update_order = $obj->list_order = (getNextSequence() * -1);
			}
			$obj->reason_update = escape($obj->reason_update);
			$output = $oDocumentController->updateDocument($oDocument, $obj, true);
			$msg_code = 'success_updated';

		// insert a new document otherwise
		}
		else
		{
			$output = $oDocumentController->insertDocument($obj, $bAnonymous);
			$msg_code = 'success_registed';
			$obj->document_srl = $output->get('document_srl');

			// send an email to admin user
			if($output->toBool() && $this->module_info->admin_mail)
			{
				$oModuleModel = getModel('module');
				$member_config = $oModuleModel->getModuleConfig('member');
				$is_logged = Context::get('is_logged');

				if(!$is_logged && !$member_config->webmaster_email)
				{
					$obj->email_address = $this->module_info->admin_mail;
				}
				
				$oMail = new Mail();
				$oMail->setTitle($obj->title);
				$oMail->setContent( sprintf("From : <a href=\"%s\">%s</a><br/>\r\n%s", getFullUrl('','document_srl',$obj->document_srl), getFullUrl('','document_srl',$obj->document_srl), $obj->content));
				$oMail->setSender($obj->user_name ? $obj->user_name : 'anonymous', $obj->email_address ? $obj->email_address : $member_config->webmaster_email);

				$target_mail = explode(',',$this->module_info->admin_mail);
				for($i=0;$i<count($target_mail);$i++)
				{
					$email_address = trim($target_mail[$i]);
					if(!$email_address) continue;
					$oMail->setReceiptor($email_address, $email_address);
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
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '', 'document_srl', $output->get('document_srl')));
		$this->add('mid', Context::get('mid'));
		$this->add('document_srl', $output->get('document_srl'));

		// alert a message
		$this->setMessage($msg_code);
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

		// delete the document
		$output = $oDocumentController->deleteDocument($document_srl, $this->grant->manager);
		if(!$output->toBool())
		{
			return $output;
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
			$obj->user_name = $obj->nick_name = 'anonymous';
			$bAnonymous = true;
		}
		else
		{
			$bAnonymous = false;
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

		// if comment_srl is not existed, then insert the comment
		if($comment->comment_srl != $obj->comment_srl)
		{

			// parent_srl is existed
			if($obj->parent_srl)
			{
				$parent_comment = $oCommentModel->getComment($obj->parent_srl);
				if(!$parent_comment->comment_srl)
				{
					return new Object(-1, 'msg_invalid_request');
				}

				$output = $oCommentController->insertComment($obj, $bAnonymous);

			// parent_srl is not existed
			}
			else
			{
				$output = $oCommentController->insertComment($obj, $bAnonymous);
			}
		// update the comment if it is not existed
		}
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

		$output = $oCommentController->deleteComment($comment_srl, $this->grant->manager);
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

			$oComment->setGrant();
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

			$oDocument->setGrant();
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
}
