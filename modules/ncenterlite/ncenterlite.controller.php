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
		if($config->use != 'Y')
		{
			return new Object();
		}

		$content = strip_tags($obj->title . ' ' . $obj->content);

		$mention_targets = $this->_getMentionTarget($content);
		if(!$mention_targets || !count($mention_targets))
		{
			return new Object();
		}

		$document_srl = $obj->document_srl;
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);

		$is_anonymous = $this->_isAnonymous($this->_TYPE_DOCUMENT, $obj);
		// 맨션 알림일경우 맨션알림 시작.
		if($mention_targets)
		{
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
		if($config->use != 'Y')
		{
			return new Object();
		}

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

		// 멘션
		$mention_targets = $this->_getMentionTarget(strip_tags($obj->content));
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

		$admin_list = $oNcenterliteModel->getMemberAdmins();
		$admins_list = $admin_list->data;

		foreach($admins_list as $admins)
		{
			if(is_array($config->admin_comment_module_srls) && in_array($module_info->module_srl, $config->admin_comment_module_srls))
			{
				$args = new stdClass();
				$args->member_srl = $admins->member_srl;
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
			}
		}
		// 대댓글
		if($parent_srl)
		{
			$oCommentModel = getModel('comment');
			$oComment = $oCommentModel->getComment($parent_srl);
			$member_srl = $oComment->member_srl;
			$comment_member_config = $oNcenterliteModel->getMemberConfig($member_srl);
			$parent_member_config = $comment_member_config->data;

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
		if(!$parent_srl || ($parent_srl && $config->document_notify == 'all-comment'))
		{
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl);

			$member_srl = $oDocument->get('member_srl');
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
		if($config->use != 'Y')
		{
			return new Object();
		}
		if($config->message_notify == 'N')
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
		if($config->use != 'Y')
		{
			return new Object();
		}
		if($config->voted_format != 'Y')
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
		if($config->use != 'Y')
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
		if($config->use != 'Y')
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
		if($config->use != 'Y')
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

		$logged_info = Context::get('logged_info');

		// 로그인 상태가 아니면 중지
		if(!$logged_info)
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

		// 알림센터가 비활성화 되어 있으면 중지
		if($config->use != 'Y')
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

		$oNcenterliteModel = getModel('ncenterlite');

		// 알림 목록 가져오기
		$_output = $oNcenterliteModel->getMyNotifyList();
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
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoXml('ncenterlite');
		if(file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.css')))
		{
			Context::addCssFile($this->template_path . 'ncenterlite.css', true, 'all', '', 100);
		}

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(!Mobile::isFromMobilePhone())
		{
			if($config->colorset && file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.' . $config->colorset . '.css')))
			{
				Context::addCssFile($this->template_path . 'ncenterlite.' . $config->colorset . '.css', true, 'all', '', 100);
			}
		}
		elseif(Mobile::isFromMobilePhone())
		{
			if($config->mcolorset && file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.' . $config->mcolorset . '.css')))
			{
				Context::addCssFile($this->template_path . 'ncenterlite.' . $config->mcolorset . '.css', true, 'all', '', 100);
			}

			Context::loadFile(array('./common/js/jquery.min.js', 'head', '', -100000), true);
			Context::loadFile(array('./common/js/xe.min.js', 'head', '', -100000), true);
			Context::addCssFile($this->template_path . 'ncenterlite.mobile.css', true, 'all', '', 100);
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

		return $output;
	}

	function updateNotifyReadiByTargetSrl($target_srl, $member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->target_srl = $target_srl;
		$output = executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
		//$output = executeQuery('ncenterlite.deleteNotifyByTargetSrl', $args);

		return $output;
	}

	function updateNotifyReadAll($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.updateNotifyReadedAll', $args);
		//$output = executeQuery('ncenterlite.deleteNotifyByMemberSrl', $args);

		return $ouptut;
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
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		// 비회원 노티 제거
		if($args->member_srl <= 0)
		{
			return new Object();
		}

		$logged_info = Context::get('logged_info');

		if($anonymous == TRUE)
		{
			// 설정에서 익명 이름이 설정되어 있으면 익명 이름을 설정함. 없을 경우 Anonymous 를 사용한다.
			if(!$config->anonymous_name)
			{
				$anonymous_name = 'Anonymous';
			}
			else
			{
				$anonymous_name = $config->anonymous_name;
			}
			// 익명 노티 시 회원정보 제거
			$args->target_member_srl = 0;
			$args->target_nick_name = $anonymous_name;
			$args->target_user_id = $anonymous_name;
			$args->target_email_address = $anonymous_name;
		}
		else if($logged_info)
		{
			// 익명 노티가 아닐 때 로그인 세션의 회원정보 넣기
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

		return $output;
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
		$config = $oNcenterliteModel->getConfig();
		$logged_info = Context::get('logged_info');

		$list = array();

		$content = strip_tags($content);
		$content = str_replace('&nbsp;', ' ', $content);

		// 정규표현식 정리
		$split = array();
		if(in_array('comma', $config->mention_format))
		{
			$split[] = ',';
		}
		$regx = join('', array('/(^|\s)@([^@\s', join('', $split), ']+)/i'));

		preg_match_all($regx, $content, $matches);

		// '님'문자 이후 제거
		if(in_array('respect', $config->mention_format))
		{
			foreach($matches[2] as $idx => $item)
			{
				$pos = strpos($item, '님');
				if($pos !== false && $pos > 0)
				{
					$matches[2][$idx] = trim(substr($item, 0, $pos));
					if($logged_info && $logged_info->nick_name == $matches[2][$idx])
					{
						unset($matches[2][$idx]);
					}
				}
			}
		}

		$nicks = array_unique($matches[2]);

		$oMemberModel = getModel('member');
		$member_config = $oMemberModel->getMemberConfig();

		if($config->mention_names == 'id' && $member_config->identifier != 'email_address')
		{
			foreach($nicks as $user_id)
			{
				$vars = new stdClass();
				$vars->user_id = $user_id;
				$output = executeQuery('ncenterlite.getMemberSrlById', $vars);
				if($output->data && $output->data->member_srl)
				{
					$list[] = $output->data->member_srl;
				}
			}
		}
		else
		{
			foreach($nicks as $nick_name)
			{
				$vars = new stdClass();
				$vars->nick_name = $nick_name;
				$output = executeQuery('ncenterlite.getMemberSrlByNickName', $vars);
				if($output->data && $output->data->member_srl)
				{
					$list[] = $output->data->member_srl;
				}
			}
		}

		return $list;
	}
}
