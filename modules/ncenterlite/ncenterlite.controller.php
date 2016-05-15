<?php

class ncenterliteController extends ncenterlite
{
	function procNcenterliteUserConfig()
	{
		$logged_info = Context::get('logged_info');
		$oNcenterliteModel = getModel('ncenterlite');

		$member_srl = Context::get('member_srl');

		if(!$member_srl)
		{
			$member_srl = $logged_info->member_srl;
		}

		if($logged_info->member_srl != $member_srl && $logged_info->is_admin != 'Y')
		{
			return new Object(-1, 'ncenterlite_stop_no_permission_other_user_settings');
		}

		$output = $oNcenterliteModel->getMemberConfig($member_srl);

		$obj = Context::getRequestVars();

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->comment_notify = $obj->comment_notify;
		$args->mention_notify = $obj->mention_notify;
		$args->message_notify = $obj->message_notify;

		if(!$output)
		{
			$outputs = executeQuery('ncenterlite.insertUserConfig', $args);
		}
		else
		{
			$outputs = executeQuery('ncenterlite.updateUserConfig', $args);
		}

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'act', 'dispNcenterliteUserConfig', 'member_srl', $member_srl);
			header('location: ' . $returnUrl);
			return;
		}
	}

	function triggerAfterDeleteMember($obj)
	{
		$member_srl = $obj->member_srl;
		if(!$member_srl)
		{
			return new Object();
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.deleteNotifyByMemberSrl', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		return new Object();
	}

	function triggerAfterInsertDocument(&$obj)
	{
		$oModuleModel = getModel('module');

		if($this->_isDisable())
		{
			return;
		}

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		$mention_targets = $this->_getMentionTarget($obj->title . ' ' . $obj->content);

		$document_srl = $obj->document_srl;
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);

		$is_anonymous = $this->_isAnonymous($this->_TYPE_DOCUMENT, $obj);

		$logged_info = Context::get('logged_info');
		$admin_list = $oNcenterliteModel->getMemberAdmins();

		// 맨션 알림일경우 맨션알림 시작.
		if(!empty($mention_targets))
		{
			if(!$mention_targets && !count($mention_targets) || !isset($config->use['mention']))
			{
				return new Object();
			}

			// !TODO 공용 메소드로 분리
			foreach($mention_targets as $mention_member_srl)
			{
				$target_member_config = $oNcenterliteModel->getMemberConfig($mention_member_srl);
				$notify_member_config = $target_member_config->data;

				if($notify_member_config->mention_notify == 'N')
				{
					continue;
				}

				$args = new stdClass();
				$args->member_srl = $mention_member_srl;
				$args->srl = $obj->document_srl;
				$args->target_p_srl = $obj->document_srl;
				$args->target_srl = $obj->document_srl;
				$args->type = $this->_TYPE_DOCUMENT;
				$args->target_type = $this->_TYPE_MENTION;
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $obj->document_srl);
				$args->target_summary = cut_str(strip_tags($obj->title), 50);
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = date('YmdHis');
				$args->target_browser = $module_info->browser_title;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				if(!$output->toBool())
				{
					return $output;
				}
			}
		}

		if(isset($config->use['admin_content']) && is_array($config->admin_notify_module_srls) && in_array($module_info->module_srl, $config->admin_notify_module_srls) && empty($mention_targets))
		{
			foreach($admin_list as $admins)
			{
				if($logged_info->member_srl == $admins)
				{
					continue;
				}

				$args = new stdClass();
				$args->member_srl = $admins;
				$args->srl = $obj->document_srl;
				$args->target_p_srl = $obj->document_srl;
				$args->target_srl = $obj->document_srl;
				$args->type = $this->_TYPE_DOCUMENT;
				$args->target_type = $this->_TYPE_ADMIN_DOCUMENT;
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $obj->document_srl);
				$args->target_summary = cut_str(strip_tags($obj->title), 50);
				$args->regdate = date('YmdHis');
				$args->target_browser = $module_info->browser_title;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				if(!$output->toBool())
				{
					return $output;
				}
			}
		}

		return new Object();
	}

	function triggerAfterInsertComment(&$obj)
	{
		if($this->_isDisable())
		{
			return;
		}

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		$logged_info = Context::get('logged_info');
		$notify_member_srl = array();

		$document_srl = $obj->document_srl;
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
		$comment_srl = $obj->comment_srl;
		$parent_srl = $obj->parent_srl;
		$content = $obj->content;
		$regdate = $obj->regdate;

		// 익명 노티 체크
		$is_anonymous = $this->_isAnonymous($this->_TYPE_COMMENT, $obj);

		$admin_comment_notify = false;
		$admin_list = $oNcenterliteModel->getMemberAdmins();

		if(isset($config->use['admin_content']) && is_array($config->admin_notify_module_srls) && in_array($module_info->module_srl, $config->admin_notify_module_srls))
		{
			foreach($admin_list as $admins)
			{
				if($logged_info->member_srl == $admins)
				{
					continue;
				}
				$args = new stdClass();
				$args->member_srl = $admins;
				$args->target_p_srl = $obj->comment_srl;
				$args->srl = $obj->document_srl;
				$args->target_srl = $obj->comment_srl;
				$args->type = $this->_TYPE_COMMENT;
				$args->target_type = $this->_TYPE_ADMIN_COMMENT;
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $document_srl, '_comment_srl', $comment_srl) . '#comment_' . $comment_srl;
				$args->target_summary = cut_str(strip_tags($content), 50);
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = date('YmdHis');
				$args->target_browser = $module_info->browser_title;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				if($output->toBool())
				{
					$admin_comment_notify = true;
				}
			}
		}

		// check use the mention option.
		if(isset($config->use['mention']))
		{
			$mention_targets = $this->_getMentionTarget($content);

			// !TODO 공용 메소드로 분리
			foreach($mention_targets as $mention_member_srl)
			{
				$target_member_config = $oNcenterliteModel->getMemberConfig($mention_member_srl);
				$notify_member_config = $target_member_config->data;
				if($notify_member_config->mention_notify == 'N')
				{
					continue;
				}

				if(is_array($admin_list) && in_array($mention_member_srl, $admin_list) && isset($config->use['admin_content']) && $admin_comment_notify == true)
				{
					continue;
				}

				$args = new stdClass();
				$args->member_srl = $mention_member_srl;
				$args->target_p_srl = $obj->comment_srl;
				$args->srl = $obj->document_srl;
				$args->target_srl = $obj->comment_srl;
				$args->type = $this->_TYPE_COMMENT;
				$args->target_type = $this->_TYPE_MENTION;
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $document_srl, '_comment_srl', $comment_srl) . '#comment_' . $comment_srl;
				$args->target_summary = cut_str(strip_tags($content), 50);
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = date('YmdHis');
				$args->target_browser = $module_info->browser_title;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				$notify_member_srl[] = $mention_member_srl;
			}
		}

		if(!isset($config->use['comment']))
		{
			return new Object();
		}

		// 대댓글
		if($parent_srl)
		{
			$oCommentModel = getModel('comment');
			$oComment = $oCommentModel->getComment($parent_srl);
			$member_srl = $oComment->member_srl;
			$comment_member_config = $oNcenterliteModel->getMemberConfig($member_srl);
			$parent_member_config = $comment_member_config->data;
			if(is_array($admin_list) && in_array(abs($member_srl), $admin_list) && isset($config->use['admin_content']) && $admin_comment_notify == true)
			{
				return new Object();
			}

			// !TODO 공용 메소드로 분리
			if(!in_array(abs($member_srl), $notify_member_srl) && (!$logged_info || ($member_srl != 0 && abs($member_srl) != $logged_info->member_srl)) && $parent_member_config->comment_notify != 'N')
			{
				$args = new stdClass();
				$args->member_srl = abs($member_srl);
				$args->srl = $obj->document_srl;
				$args->target_p_srl = $parent_srl;
				$args->target_srl = $obj->comment_srl;
				$args->type = $this->_TYPE_COMMENT;
				$args->target_type = $this->_TYPE_COMMENT;
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $document_srl, '_comment_srl', $comment_srl) . '#comment_' . $comment_srl;
				$args->target_summary = cut_str(strip_tags($content), 50);
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = $regdate;
				$args->target_browser = $module_info->browser_title;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				$notify_member_srl[] = abs($member_srl);
			}
		}
		// 대댓글이 아니고, 게시글의 댓글을 남길 경우
		if(!$parent_srl || ($parent_srl && isset($config->use['comment_comment'])))
		{
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl);

			$member_srl = $oDocument->get('member_srl');

			if(is_array($admin_list) && in_array(abs($member_srl), $admin_list) && isset($config->use['admin_content']) && $admin_comment_notify == true)
			{
				return new Object();
			}
			$comment_member_config = $oNcenterliteModel->getMemberConfig($member_srl);
			$document_comment_member_config = $comment_member_config->data;

			// !TODO 공용 메소드로 분리
			if(!in_array(abs($member_srl), $notify_member_srl) && (!$logged_info || ($member_srl != 0 && abs($member_srl) != $logged_info->member_srl)) && $document_comment_member_config->comment_notify != 'N')
			{
				$args = new stdClass();
				$args->member_srl = abs($member_srl);
				$args->srl = $document_srl;
				$args->target_p_srl = $comment_srl;
				$args->target_srl = $comment_srl;
				$args->type = $this->_TYPE_DOCUMENT;
				$args->target_type = $this->_TYPE_COMMENT;
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $document_srl, '_comment_srl', $comment_srl) . '#comment_' . $comment_srl;
				$args->target_summary = cut_str(strip_tags($content), 50);
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = $regdate;
				$args->target_browser = $module_info->browser_title;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
			}
		}

		return new Object();
	}

	function triggerAfterSendMessage(&$trigger_obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		$communication_config = getModel('communication')->getConfig();

		if($communication_config->enable_message != 'Y')
		{
			return new Object();
		}

		if(!isset($config->use['message']))
		{
			return new Object();
		}

		$messages_member_config = $oNcenterliteModel->getMemberConfig($trigger_obj->receiver_srl);
		$message_member_config = $messages_member_config->data;

		if($message_member_config->message_notify != 'N')
		{
			$args = new stdClass();
			$args->member_srl = $trigger_obj->receiver_srl;
			$args->srl = $trigger_obj->related_srl;
			$args->target_p_srl = '1';
			$args->target_srl = $trigger_obj->message_srl;
			$args->type = $this->_TYPE_MESSAGE;
			$args->target_type = $this->_TYPE_MESSAGE;
			$args->target_summary = $trigger_obj->title;
			$args->regdate = date('YmdHis');
			$args->notify = $this->_getNotifyId($args);
			$args->target_url = getNotEncodedFullUrl('', 'act', 'dispCommunicationMessages', 'message_srl', $trigger_obj->related_srl);
			$output = $this->_insertNotify($args);
		}
	}

	function triggerAfterVotedupdate(&$obj)
	{
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($obj->document_srl, false, false);

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(!isset($config->use['vote']))
		{
			return new Object();
		}

		if($obj->point < 0)
		{
			return new Object();
		}

		$args = new stdClass();
		$args->member_srl = $obj->member_srl;
		$args->srl = $obj->document_srl;
		$args->target_p_srl = '1';
		$args->target_srl = $obj->document_srl;
		$args->type = $this->_TYPE_DOCUMENT;
		$args->target_type = $this->_TYPE_VOTED;
		$args->target_summary = $oDocument->get('title');
		$args->regdate = date('YmdHis');
		$args->notify = $this->_getNotifyId($args);
		$args->target_url = getNotEncodedFullUrl('', 'document_srl', $obj->document_srl);
		$output = $this->_insertNotify($args);
	}

	function triggerAfterDeleteComment(&$obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(empty($config->use))
		{
			return new Object();
		}

		$args = new stdClass();
		$args->srl = $obj->comment_srl;
		$output = executeQuery('ncenterlite.deleteNotifyBySrl', $args);
		return new Object();
	}

	function triggerAfterDeleteDocument(&$obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(empty($config->use))
		{
			return new Object();
		}

		$args = new stdClass();
		$args->srl = $obj->document_srl;
		$output = executeQuery('ncenterlite.deleteNotifyBySrl', $args);
		return new Object();
	}

	function triggerAfterMoveToTrash(&$obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		if(empty($config->use))
		{
			return new Object();
		}

		$args = new stdClass();
		$args->srl = $obj->document_srl;
		$output = executeQuery('ncenterlite.deleteNotifyBySrl', $args);
		return new Object();
	}

	function triggerAfterModuleHandlerProc(&$oModule)
	{
		$vars = Context::getRequestVars();
		$logged_info = Context::get('logged_info');
		$args = new stdClass();

		if($oModule->getLayoutFile() == 'popup_layout.html')
		{
			Context::set('ncenterlite_is_popup', TRUE);
		}

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		// if the array is empty, lets return.
		if(empty($config->use))
		{
			return new Object();
		}
		$this->_hide_ncenterlite = false;
		if($oModule->module == 'beluxe' && Context::get('is_modal'))
		{
			$this->_hide_ncenterlite = true;
		}
		if($oModule->module == 'bodex' && Context::get('is_iframe'))
		{
			$this->_hide_ncenterlite = true;
		}
		if($oModule->getLayoutFile() == 'popup_layout.html')
		{
			$this->_hide_ncenterlite = true;
		}

		if($oModule->act == 'dispBoardReplyComment')
		{
			$comment_srl = Context::get('comment_srl');
			$logged_info = Context::get('logged_info');
			if($comment_srl && $logged_info)
			{
				$args->target_srl = $comment_srl;
				$args->member_srl = $logged_info->member_srl;
				$output_update = executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
			}
		}
		else if($oModule->act == 'dispBoardContent')
		{
			$comment_srl = Context::get('_comment_srl');
			$document_srl = Context::get('document_srl');
			$oDocument = Context::get('oDocument');
			$logged_info = Context::get('logged_info');

			if($document_srl && $logged_info && $config->document_read == 'Y')
			{
				$args->srl = $document_srl;
				$args->member_srl = $logged_info->member_srl;
				$outputs = executeQuery('ncenterlite.updateNotifyReadedBySrl', $args);
			}

			if($comment_srl && $document_srl && $oDocument)
			{
				$_comment_list = $oDocument->getComments();
				if($_comment_list)
				{
					if(array_key_exists($comment_srl, $_comment_list))
					{
						$url = getNotEncodedUrl('_comment_srl', '') . '#comment_' . $comment_srl;
						$need_check_socialxe = true;
					}
					else
					{
						$cpage = $oDocument->comment_page_navigation->cur_page;
						if($cpage > 1)
						{
							$url = getNotEncodedUrl('cpage', $cpage - 1) . '#comment_' . $comment_srl;
							$need_check_socialxe = true;
						}
						else
						{
							$url = getNotEncodedUrl('_comment_srl', '', 'cpage', '') . '#comment_' . $comment_srl;
						}
					}

					if($need_check_socialxe)
					{
						$oDB = &DB::getInstance();
						if($oDB->isTableExists('socialxe'))
						{
							$args = new stdClass();
							$oModuleModel = getModel('module');
							$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
							$args->module_srl = $module_info->module_srl;
							$output = executeQuery('ncenterlite.getSocialxeCount', $args);
							if($output->data->cnt)
							{
								$socialxe_comment_srl = $comment_srl;

								$args = new stdClass();
								$args->comment_srl = $comment_srl;
								$oCommentModel = getModel('comment');
								$oComment = $oCommentModel->getComment($comment_srl);
								$parent_srl = $oComment->get('parent_srl');
								if($parent_srl)
								{
									$socialxe_comment_srl = $parent_srl;
								}

								$url = getNotEncodedUrl('_comment_srl', '', 'cpage', '', 'comment_srl', $socialxe_comment_srl) . '#comment_' . $comment_srl;
							}
						}
					}

					$url = str_replace('&amp;', '&', $url);
					header('location: ' . $url);
					Context::close();
					exit;
				}
			}
		}
		elseif($oModule->act == 'dispCommunicationMessages')
		{
			$message_srl = Context::get('message_srl');
			$logged_info = Context::get('logged_info');
			if($message_srl)
			{
				$args = new stdClass();
				$args->target_srl = $message_srl;
				$args->member_srl = $logged_info->member_srl;
				executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
			}
		}

		// 지식인 모듈의 의견
		// TODO: 코드 분리
		if($oModule->act == 'procKinInsertComment')
		{
			// 글, 댓글 구분
			$parent_type = ($vars->document_srl == $vars->parent_srl) ? 'DOCUMENT' : 'COMMENT';
			if($parent_type == 'DOCUMENT')
			{
				$oDocumentModel = getModel('document');
				$oDocument = $oDocumentModel->getDocument($vars->document_srl);
				$member_srl = $oDocument->get('member_srl');
				$type = $this->_TYPE_DOCUMENT;
			}
			else
			{
				$oCommentModel = getModel('comment');
				$oComment = $oCommentModel->getComment($vars->parent_srl);
				$member_srl = $oComment->get('member_srl');
				$type = $this->_TYPE_COMMENT;
			}

			if($logged_info->member_srl != $member_srl)
			{
				$args = new stdClass();
				$args->member_srl = abs($member_srl);
				$args->srl = ($parent_type == 'DOCUMENT') ? $vars->document_srl : $vars->parent_srl;
				$args->type = $type;
				$args->target_type = $this->_TYPE_COMMENT;
				$args->target_srl = $vars->parent_srl;
				$args->target_p_srl = '1';
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $vars->document_srl, '_comment_srl', $vars->parent_srl) . '#comment_' . $vars->parent_srl;
				$args->target_summary = cut_str(strip_tags($vars->content), 50);
				$args->target_nick_name = $logged_info->nick_name;
				$args->target_email_address = $logged_info->email_address;
				$args->regdate = date('YmdHis');
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args);
			}
		}
		else if($oModule->act == 'dispKinView' || $oModule->act == 'dispKinIndex')
		{
			// 글을 볼 때 알림 제거
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($vars->document_srl);
			$member_srl = $oDocument->get('member_srl');

			if($logged_info->member_srl == $member_srl)
			{
				$args = new stdClass;
				$args->member_srl = $logged_info->member_srl;
				$args->srl = $vars->document_srl;
				$args->type = $this->_TYPE_DOCUMENT;
				$output = executeQuery('ncenterlite.updateNotifyReadedBySrl', $args);
			}
		}
		else if($oModule->act == 'getKinComments')
		{
			// 의견을 펼칠 때 알림 제거
			$args = new stdClass;
			$args->member_srl = $logged_info->member_srl;
			$args->target_srl = $vars->parent_srl;
			$output = executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
		}

		return new Object();
	}

	function triggerBeforeDisplay(&$output_display)
	{
		$act = Context::get('act');
		// 팝업창이면 중지
		if(Context::get('ncenterlite_is_popup'))
		{
			return;
		}

		// 자신의 알림목록을 보고 있을 경우엔 알림센터창을 띄우지 않는다.
		if($act == 'dispNcenterliteNotifyList')
		{
			return;
		}

		if(count($this->disable_notify_bar_act))
		{
			if(in_array(Context::get('act'), $this->disable_notify_bar_act))
			{
				return;
			}
		}

		// HTML 모드가 아니면 중지 + act에 admin이 포함되어 있으면 중지
		if(Context::getResponseMethod() != 'HTML' || strpos(strtolower(Context::get('act')), 'admin') !== false)
		{
			return;
		}

		// 로그인 상태가 아니면 중지
		if(!Context::get('is_logged'))
		{
			return;
		}

		$module_info = Context::get('module_info');

		if(count($this->disable_notify_bar_mid))
		{
			if(in_array($module_info->mid, $this->disable_notify_bar_mid))
			{
				return;
			}
		}

		// admin 모듈이면 중지
		if($module_info->module == 'admin')
		{
			return;
		}

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		// if the array is empty, dose not output the notification.
		if(empty($config->use))
		{
			return new Object();
		}

		if($config->display_use == 'N')
		{
			return new Object();
		}

		// 노티바 제외 페이지이면 중지
		if(in_array($module_info->module_srl, $config->hide_module_srls))
		{
			return new Object();
		}

		Context::set('ncenterlite_config', $config);

		$js_args = array('./modules/ncenterlite/tpl/js/ncenterlite.js', 'body', '', 100000);
		Context::loadFile($js_args);

		// 알림 목록 가져오기
		$logged_info = Context::get('logged_info');
		$_output = $oNcenterliteModel->getMyNotifyList($logged_info->member_srl);
		// 알림 메시지가 없어도 항상 표시하게 하려면 이 줄을 제거 또는 주석 처리하세요.
		if(!$_output->data)
		{
			return;
		}

		$_latest_notify_id = array_slice($_output->data, 0, 1);
		$_latest_notify_id = $_latest_notify_id[0]->notify;
		Context::set('ncenterlite_latest_notify_id', $_latest_notify_id);

		if($_COOKIE['_ncenterlite_hide_id'] && $_COOKIE['_ncenterlite_hide_id'] == $_latest_notify_id)
		{
			return;
		}
		setcookie('_ncenterlite_hide_id', '', 0, '/');

		$oMemberModel = getModel('member');
		$memberConfig = $oMemberModel->getMemberConfig();
		if($memberConfig->profile_image == 'Y')
		{
			$profileImage = $oMemberModel->getProfileImage($logged_info->member_srl);
			Context::set('profileImage', $profileImage);
		}
		Context::set('useProfileImage', ($memberConfig->profile_image == 'Y') ? true : false);

		Context::set('ncenterlite_list', $_output->data);
		Context::set('ncenterlite_page_navigation', $_output->page_navigation);
		Context::set('_ncenterlite_num', $_output->page_navigation->total_count);

		if(Mobile::isFromMobilePhone())
		{
			$this->template_path = sprintf('%sm.skins/%s/', $this->module_path, $config->mskin);
			if(!is_dir($this->template_path) || !$config->mskin)
			{
				$config->mskin = 'default';
				$this->template_path = sprintf('%sm.skins/%s/', $this->module_path, $config->mskin);
			}
		}
		else
		{
			$this->template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
			if(!is_dir($this->template_path) || !$config->skin)
			{
				$config->skin = 'default';
				$this->template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
			}
		}

		$this->_addFile();
		$html = $this->_getTemplate();
		$output_display = $html . $output_display;
	}

	function triggerAddMemberMenu()
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		if($config->user_config_list == 'Y')
		{
			$logged_info = Context::get('logged_info');
			if(!Context::get('is_logged'))
			{
				return new Object();
			}
			$target_srl = Context::get('target_srl');

			$oMemberController = getController('member');
			$oMemberController->addMemberMenu('dispNcenterliteNotifyList', 'ncenterlite_my_list');
			$oMemberController->addMemberMenu('dispNcenterliteUserConfig', 'ncenterlite_my_settings');

			if($logged_info->is_admin == 'Y')
			{
				$url = getUrl('', 'act', 'dispNcenterliteUserConfig', 'member_srl', $target_srl);
				$str = Context::getLang('ncenterlite_user_settings');
				$oMemberController->addMemberPopupMenu($url, $str, '');
			}
		}

		return new Object();
	}

	function _addFile()
	{
		if(file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.css')))
		{
			Context::loadFile(array($this->template_path . 'ncenterlite.css', '', '', 100), true);
		}

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(!Mobile::isFromMobilePhone())
		{
			if($config->colorset && file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.' . $config->colorset . '.css')))
			{
				Context::loadFile(array($this->template_path . 'ncenterlite.' . $config->colorset . '.css', '', '', 100), true);
			}
		}
		elseif(Mobile::isFromMobilePhone())
		{
			if($config->mcolorset && file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.' . $config->mcolorset . '.css')))
			{
				Context::loadFile(array($this->template_path . 'ncenterlite.' . $config->mcolorset . '.css', '', '', 100), true);
			}

			Context::loadFile(array('./common/js/jquery.min.js', 'head', '', -100000), true);
			Context::loadFile(array('./common/js/xe.min.js', 'head', '', -100000), true);
			Context::loadFile(array($this->template_path . 'ncenterlite.mobile.css', '', '', 100), true);
		}
		if($config->zindex)
		{
			Context::set('ncenterlite_zindex', ' style="z-index:' . $config->zindex . ';" ');
		}
	}

	function _getTemplate()
	{
		$oNcenterModel = getModel('ncenterlite');
		$config = $oNcenterModel->getConfig();

		$oTemplateHandler = TemplateHandler::getInstance();
		$result = '';

		if(Mobile::isFromMobilePhone())
		{
			$path = sprintf('%sm.skins/%s/', $this->module_path, $config->mskin);
		}
		else
		{
			$path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
		}
		$result = $oTemplateHandler->compile($path, 'ncenterlite.html');

		return $result;
	}

	function updateNotifyRead($notify, $member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->notify = $notify;
		$output = executeQuery('ncenterlite.updateNotifyReaded', $args);
		//$output = executeQuery('ncenterlite.deleteNotify', $args);

		//Remove flag files
		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($args->member_srl) . $args->member_srl . '.php';
		if(file_exists($flag_path))
		{
			FileHandler::removeFile($flag_path);
		}
		return $output;
	}

	function updateNotifyReadiByTargetSrl($target_srl, $member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->target_srl = $target_srl;
		$output = executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
		//$output = executeQuery('ncenterlite.deleteNotifyByTargetSrl', $args);

		//Remove flag files
		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($args->member_srl) . $args->member_srl . '.php';
		if(file_exists($flag_path))
		{
			FileHandler::removeFile($flag_path);
		}
		return $output;
	}

	function updateNotifyReadAll($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.updateNotifyReadedAll', $args);
		//$output = executeQuery('ncenterlite.deleteNotifyByMemberSrl', $args);

		//Remove flag files
		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($args->member_srl) . $args->member_srl . '.php';
		if(file_exists($flag_path))
		{
			FileHandler::removeFile($flag_path);
		}
		return $output;
	}

	function procNcenterliteNotifyReadAll()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$output = $this->updateNotifyReadAll($logged_info->member_srl);
		return $output;
	}

	function procNcenterliteRedirect()
	{
		$logged_info = Context::get('logged_info');
		$url = Context::get('url');
		$notify = Context::get('notify');
		if(!$logged_info || !$url || !$notify)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$output = $this->updateNotifyRead($notify, $logged_info->member_srl);
		if(!$output->toBool())
		{
			return $output;
		}

		$url = str_replace('&amp;', '&', $url);
		header('Location: ' . $url, TRUE, 302);
		Context::close();
		exit;
	}

	/**
	 * @brief 익명으로 노티해야 할지 체크하여 반환
	 * @return boolean
	 **/
	function _isAnonymous($source_type, $triggerObj)
	{
		// 회원번호가 음수
		if($triggerObj->member_srl < 0)
		{
			return TRUE;
		}

		$module_info = Context::get('module_info');

		// DX 익명 체크박스
		if($module_info->module == 'beluxe' && $triggerObj->anonymous == 'Y')
		{
			return TRUE;
		}

		if($source_type == $this->_TYPE_COMMENT)
		{
			// DX 익명 강제
			if($module_info->module == 'beluxe' && $module_info->use_anonymous == 'Y')
			{
				return TRUE;
			}
		}

		if($source_type == $this->_TYPE_DOCUMENT)
		{
			// DX 익명 강제
			if($module_info->module == 'beluxe' && $module_info->use_anonymous == 'Y')
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	function _insertNotify($args, $anonymous = FALSE)
	{
		// 비회원 노티 제거
		if($args->member_srl <= 0)
		{
			return new Object();
		}



		if($anonymous == TRUE)
		{
			$args->target_member_srl = 0;
			$args->target_user_id = $args->target_nick_name;
			$args->target_email_address = $args->target_nick_name;
		}
		// 로그인을 했을경우 logged_info 정보를 가져와 검사한다.
		else if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->target_member_srl = $logged_info->member_srl;
			$args->target_nick_name = $logged_info->nick_name;
			$args->target_user_id = $logged_info->user_id;
			$args->target_email_address = $logged_info->email_address;
		}
		else if($args->target_member_srl)
		{
			$oMemberModel = getModel('member');
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($args->target_member_srl);
			$args->target_member_srl = $member_info->member_srl;
			$args->target_nick_name = $member_info->nick_name;
			$args->target_user_id = $member_info->user_id;
			$args->target_email_address = $member_info->email_address;
		}
		else
		{
			// 비회원
			$args->target_member_srl = 0;
			$args->target_user_id = '';
		}

		$output = executeQuery('ncenterlite.insertNotify', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		if($output->toBool())
		{
			$trigger_notify = ModuleHandler::triggerCall('ncenterlite._insertNotify', 'after', $args);
			if(!$trigger_notify->toBool())
			{
				return $trigger_notify;
			}
		}

		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($args->member_srl) . $args->member_srl . '.php';
		if(file_exists($flag_path))
		{
			//remove flag files
			FileHandler::removeFile($flag_path);
		}

		return $output;
	}

	public static function updateFlagFile($member_srl = null, $output)
	{
		if(!$member_srl)
		{
			return;
		}
		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($member_srl) . $member_srl . '.php';
		if(file_exists($flag_path))
		{
			return;
		}

		FileHandler::makeDir(\RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($member_srl));
		$buff = "<?php return unserialize(" . var_export(serialize($output), true) . ");\n";
		FileHandler::writeFile($flag_path, $buff);
	}


	/**
	 * @brief 노티 ID 반환
	 **/
	function _getNotifyId($args)
	{
		return md5(uniqid('') . $args->member_srl . $args->srl . $args->target_srl . $args->type . $args->target_type);
	}

	/**
	 * @brief 멘션 대상 member_srl 목록 반환
	 * @return array
	 **/
	function _getMentionTarget($content)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$oMemberModel =  getModel('member');
		$config = $oNcenterliteModel->getConfig();
		$logged_info = Context::get('logged_info');
		
		// Extract mentions.
		$content = html_entity_decode(strip_tags($content));
		preg_match_all('/(?:^|\s)@([^\pC\pM\pP\pS\pZ]+)/u', $content, $matches);
		$mentions = array_unique($matches[1]);
		$members = array();
		
		// Find members.
		foreach ($mentions as $mention)
		{
			if (isset($members[$mention]))
			{
				continue;
			}
			
			if (count($members) >= $config->mention_limit)
			{
				break;
			}
			
			if ($config->mention_suffix_always_cut != 'Y')
			{
				if ($config->mention_names === 'id')
				{
					$member_srl = $oMemberModel->getMemberSrlByUserID($mention);
				}
				else
				{
					$member_srl = $oMemberModel->getMemberSrlByNickName($mention);
				}
			}
			else
			{
				$member_srl = null;
			}
			
			if (!$member_srl)
			{
				foreach ($config->mention_suffixes as $suffix)
				{
					if (($pos = strpos($mention, $suffix)) !== false && $pos > 0)
					{
						$mention = substr($mention, 0, $pos);
					}
				}
				
				if (isset($members[$mention]))
				{
					continue;
				}
				elseif ($config->mention_names === 'id')
				{
					$member_srl = $oMemberModel->getMemberSrlByUserID($mention);
				}
				else
				{
					$member_srl = $oMemberModel->getMemberSrlByNickName($mention);
				}
			}
			if (!$member_srl || ($logged_info && ($member_srl == $logged_info->member_srl)))
			{
				continue;
			}
			
			$members[$mention] = $member_srl;
		}
		
		return array_values($members);
	}
}
