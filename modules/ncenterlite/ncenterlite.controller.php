<?php

class ncenterliteController extends ncenterlite
{
	/**
	 * Send any message to a member.
	 * 
	 * @param int $from_member_srl Sender
	 * @param int $to_member_srl Recipient
	 * @param string|object $message Message content
	 * @param string $url The URL to redirect to when the recipient clicks the notification
	 * @param int $target_srl The sequence number associated with this notification
	 * @return BaseObject
	 */
	public function sendNotification($from_member_srl, $to_member_srl, $message, $url = '', $target_srl = 0)
	{
		$args = new stdClass();
		$args->config_type = 'custom';
		$args->module_srl = 0;
		$args->member_srl = intval($to_member_srl);
		$args->type = 'X';
		$args->srl = 0;
		$args->target_p_srl = 0;
		$args->target_srl = intval($target_srl);
		$args->target_member_srl = intval($from_member_srl ?: $to_member_srl);
		$args->target_type = $this->_TYPE_CUSTOM;
		$args->target_url = $url;
		$args->target_browser = '';
		$args->target_summary = '';
		
		if (is_object($message))
		{
			$args->target_body = $message->subject;
			$args->target_url = $message->url ?: $args->target_url;
			$args->extra_content = $message->content;
			$args->extra_data = $message->data ?: [];
		}
		else
		{
			$args->target_body = $message;
		}
		
		$output = $this->_insertNotify($args);
		if(!$output->toBool())
		{
			return $output;
		}
		else
		{
			return new BaseObject;
		}
	}

	function procNcenterliteUserConfig()
	{
		$logged_info = Context::get('logged_info');
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if($config->user_notify_setting != 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_not_use_user_setting');
		}

		$member_srl = Context::get('member_srl');

		if(!$member_srl)
		{
			$member_srl = $logged_info->member_srl;
		}

		if($logged_info->member_srl != $member_srl && $logged_info->is_admin != 'Y')
		{
			throw new Rhymix\Framework\Exception('ncenterlite_stop_no_permission_other_user_settings');
		}

		$user_config = $oNcenterliteModel->getUserConfig($member_srl);

		$obj = Context::getRequestVars();

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->comment_notify = $obj->comment_notify;
		$args->comment_comment_notify = $obj->comment_comment_notify;
		$args->mention_notify = $obj->mention_notify;
		$args->message_notify = $obj->message_notify;
		$args->vote_notify = $obj->vote_notify;
		$args->scrap_notify = $obj->scrap_notify;

		if(!$user_config->data)
		{
			$insert_output = executeQuery('ncenterlite.insertUserConfig', $args);
			if(!$insert_output->toBool())
			{
				return $insert_output;
			}
		}
		else
		{
			$update_output = executeQuery('ncenterlite.updateUserConfig', $args);
			if(!$update_output->toBool())
			{
				return $update_output;
			}
		}

		$this->setMessage('success_updated');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('act', 'dispNcenterliteUserConfig', 'member_srl', $member_srl));
		}
	}
	
	function procNcenterliteInsertUnsubscribe()
	{
		/** @var ncenterliteModel $oNcenterliteModel */
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		
		if($config->unsubscribe !== 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_unsubscribe_block_not_support');
		}
		
		if(!Rhymix\Framework\Session::isMember()) 
		{
			throw new Rhymix\Framework\Exception\MustLogin;
		}
		
		$obj = Context::getRequestVars();
		
		if(!$this->user->member_srl || (!intval($obj->unsubscribe_srl) && !intval($obj->target_srl)))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		if($obj->target_srl)
		{
			$userBlockData = $oNcenterliteModel->getUserUnsubscribeConfigByTargetSrl($obj->target_srl, $this->user->member_srl);
			
			// If there was a record directed by unsubscribe_srl, the record should be used to validate the input data.
			if($userBlockData)
			{
				if (!intval($obj->unsubscribe_srl))
				{
					$obj->unsubscribe_srl = $userBlockData->unsubscribe_srl;
				}
				
				if (intval($obj->unsubscribe_srl) != intval($userBlockData->unsubscribe_srl))
				{
					throw new Rhymix\Framework\Exceptions\InvalidRequest;
				}
			}
		}
		
		if(!$userBlockData && $obj->unsubscribe_srl)
		{
			$userBlockData = $oNcenterliteModel->getUserUnsubscribeConfigByUnsubscribeSrl($obj->unsubscribe_srl);
			
			// The input member_srl from the POST or GET might not equal to the member_srl from the record of unsubscribe_srl.
			if(intval($this->user->member_srl) != intval($userBlockData->member_srl))
			{
				throw new Rhymix\Framework\Exception('ncenterlite_stop_no_permission_other_user_block_settings');
			}
			
			// If there was a record directed by unsubscribe_srl, the record should be used to validate the input data.
			if($userBlockData)
			{
				if (!intval($obj->target_srl))
				{
					$obj->target_srl = $userBlockData->target_srl;
				}
				
				if (intval($obj->target_srl) != intval($userBlockData->target_srl))
				{
					throw new Rhymix\Framework\Exceptions\InvalidRequest;
				}
			}
		}
		
		if($userBlockData)
		{
			$obj->unsubscribe_srl = $userBlockData->unsubscribe_srl;
		}
		
		// Content type can be document and comment, now. However, the default type cannot be specified, as the type can be another in the future.
		if($obj->unsubscribe_type == 'document')
		{
			$text = cut_str(getModel('document')->getDocument($obj->target_srl)->get('title'), 30);
		}
		elseif($obj->unsubscribe_type == 'comment')
		{
			$comment = getModel('comment')->getComment($obj->target_srl);
			$contentString = $comment->getContentText(30);
			$text = strlen($contentString) ? $contentString : lang('comment.no_text_comment');
		}
		else
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		$args = new stdClass();
		$args->member_srl = $this->user->member_srl;
		$args->target_srl = $obj->target_srl;
		if($obj->unsubscribe_type == 'document')
		{
			$args->document_srl = $obj->target_srl;
		}
		else
		{
			$args->document_srl = $comment->get('document_srl');
		}
		$args->unsubscribe_type = $obj->unsubscribe_type;
		$args->text = $text;
		
		if($obj->value == 'Y')
		{
			// 데이터가 있으면 차단, 데이터가 없으면 차단하지 않기 때문에 따로 업데이트를 하지 않는다.
			if(!$userBlockData)
			{
				$args->unsubscribe_srl = getNextSequence();
				$output = executeQuery('ncenterlite.insertUnsubscribe', $args);
				if(!$output->toBool())
				{
					return $output;
				}
			}
			else
			{
				$args->unsubscribe_srl = $userBlockData->unsubscribe_srl;
			}
		}
		else
		{
			if(!$obj->unsubscribe_srl && !$userBlockData)
			{
				throw new Rhymix\Framework\Exception('msg_unsubscribe_not_in_list');
			}
			
			$args->unsubscribe_srl = $obj->unsubscribe_srl;
			$output = executeQuery('ncenterlite.deleteUnsubscribe', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}
		$this->setMessage('success_updated');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('act', 'dispNcenterliteUnsubscribeList', 'member_srl', $this->user->member_srl));
		}
	}

	function triggerAfterDeleteMember($obj)
	{
		$member_srl = $obj->member_srl;
		if(!$member_srl)
		{
			return;
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.deleteNotifyByMemberSrl', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		else
		{
			$this->removeFlagFile($args->member_srl);
		}
		
		// Delete to user setting.
		$userSetOutput = executeQuery('ncenterlite.deleteNcenterliteUserSettingData', $args);
		if(!$userSetOutput->toBool())
		{
			return $userSetOutput;
		}
	}

	function triggerAfterInsertDocument(&$obj)
	{
		if ($obj->disable_triggers[$this->module] === true)
		{
			return;
		}

		$oModuleModel = getModel('module');

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		$mention_targets = $this->_getMentionTarget($obj->title . ' ' . $obj->content);

		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($obj->document_srl);

		$is_anonymous = $this->_isAnonymous($this->_TYPE_DOCUMENT, $obj);

		$logged_info = Context::get('logged_info');
		$admin_list = $oNcenterliteModel->getMemberAdmins();

		// 맨션 알림일경우 맨션알림 시작.
		if(!empty($mention_targets))
		{
			if(!$mention_targets && !count($mention_targets) || !isset($config->use['mention']))
			{
				return;
			}

			$this->insertMentionByTargets($mention_targets, $obj, $module_info, $is_anonymous);
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
				$args->config_type = 'admin_content';
				$args->member_srl = $admins;
				$args->srl = $obj->document_srl;
				$args->target_p_srl = $obj->document_srl;
				$args->target_srl = $obj->document_srl;
				$args->type = $this->_TYPE_DOCUMENT;
				$args->target_type = $this->_TYPE_ADMIN_DOCUMENT;
				$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $obj->document_srl);
				$args->target_summary = cut_str(strip_tags($obj->title), 50);
				$args->regdate = date('YmdHis');
				$args->target_browser = $module_info->browser_title;
				$args->module_srl = $obj->module_srl;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				if(!$output->toBool())
				{
					return $output;
				}
			}
		}
	}

	function triggerAfterInsertComment($obj)
	{
		/** @var ncenterliteModel $oNcenterliteModel */
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		$logged_info = Context::get('logged_info');

		$document_srl = $obj->document_srl;
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
		$comment_srl = $obj->comment_srl;
		$parent_srl = $obj->parent_srl;
		$content = $obj->content;
		$regdate = $obj->regdate;

		// 익명 노티 체크
		$is_anonymous = $this->_isAnonymous($this->_TYPE_COMMENT, $obj);

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		// 댓글을 남긴 이력이 있는 회원들에게만 알림을 전송
		if($config->comment_all == 'Y' && abs($obj->member_srl) == abs($oDocument->get('member_srl')) && !$obj->parent_srl && (is_array($config->comment_all_notify_module_srls) && in_array($module_info->module_srl, $config->comment_all_notify_module_srls)))
		{
			$comment_args = new stdClass();
			$comment_args->member_srl = [$obj->member_srl, abs($obj->member_srl)];
			$comment_args->document_srl = $obj->document_srl;
			$other_comment = executeQueryArray('ncenterlite.getOtherCommentByMemberSrl', $comment_args);
			foreach ($other_comment->data as $value)
			{
				if($config->user_notify_setting == 'Y' && $value->comment_notify === 'N')
				{
					continue;
				}
				
				$args = new stdClass();
				$args->config_type = 'comment_all';
				$args->member_srl = abs($value->member_srl);
				$args->target_p_srl = $obj->comment_srl;
				$args->srl = $obj->document_srl;
				$args->target_srl = $obj->comment_srl;
				$args->type = $this->_TYPE_COMMENT;
				$args->target_type = $this->_TYPE_COMMENT_ALL;
				$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $document_srl, 'comment_srl', $comment_srl) . '#comment_' . $comment_srl;
				$args->target_summary = cut_str(trim(utf8_normalize_spaces(strip_tags($content))), 50) ?: (strpos($content, '<img') !== false ? lang('ncenterlite_content_image') : lang('ncenterlite_content_empty'));
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = date('YmdHis');
				$args->target_browser = $module_info->browser_title;
				$args->module_srl = $module_info->module_srl;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				if(!$output->toBool())
				{
					return $output;
				}
			}
		}
		
		$obj->admin_comment_notify = false;
		$admin_list = $oNcenterliteModel->getMemberAdmins();

		// 관리자에게 알림을 전송
		if(isset($config->use['admin_content']) && is_array($config->admin_notify_module_srls) && in_array($module_info->module_srl, $config->admin_notify_module_srls))
		{
			foreach($admin_list as $admins)
			{
				if($logged_info->member_srl == $admins)
				{
					continue;
				}
				$args = new stdClass();
				$args->config_type = 'admin_content';
				$args->member_srl = $admins;
				$args->target_p_srl = $obj->comment_srl;
				$args->srl = $obj->document_srl;
				$args->target_srl = $obj->comment_srl;
				$args->type = $this->_TYPE_COMMENT;
				$args->target_type = $this->_TYPE_ADMIN_COMMENT;
				$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $document_srl, 'comment_srl', $comment_srl) . '#comment_' . $comment_srl;
				$args->target_summary = cut_str(trim(utf8_normalize_spaces(strip_tags($content))), 50) ?: (strpos($content, '<img') !== false ? lang('ncenterlite_content_image') : lang('ncenterlite_content_empty'));
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = date('YmdHis');
				$args->target_browser = $module_info->browser_title;
				$args->module_srl = $module_info->module_srl;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				if($output->toBool())
				{
					$obj->admin_comment_notify = true;
				}
				else
				{
					return $output;
				}
			}
		}

		$notify_member_srls = array();
		if(isset($config->use['mention']))
		{
			$mention_targets = $this->_getMentionTarget($content);

			if(!empty($admin_list))
			{
				$obj->admin_list = $admin_list;
			}
			$notify_member_srls = $this->insertMentionByTargets($mention_targets, $obj, $module_info, $is_anonymous, $this->_TYPE_COMMENT);
		}

		if(!isset($config->use['comment']))
		{
			return;
		}

		// 대댓글
		if($parent_srl)
		{
			$oCommentModel = getModel('comment');
			$oComment = $oCommentModel->getComment($parent_srl);
			$abs_member_srl = abs($oComment->member_srl);
			if($config->user_notify_setting == 'Y')
			{
				$comment_member_config = $oNcenterliteModel->getUserConfig($abs_member_srl);
				$parent_member_config = $comment_member_config->data;
				if($parent_member_config->comment_comment_notify == 'N')
				{
					return;
				}
			}

			if(is_array($admin_list) && in_array($abs_member_srl, $admin_list) && isset($config->use['admin_content']) && $obj->admin_comment_notify == true)
			{
				return;
			}

			if(!in_array($abs_member_srl, $notify_member_srls) && (!Context::get('is_logged') || ($abs_member_srl != 0 && $abs_member_srl != $logged_info->member_srl)))
			{
				if($oNcenterliteModel->getUserConfig($abs_member_srl)->data->comment_comment_notify == 'N')
				{
					return;
				}
				
				// 받는 사람이 문서를 차단하고 있을 경우
				if($oNcenterliteModel->getUserUnsubscribeConfigByTargetSrl($document_srl, $abs_member_srl))
				{
					return;
				}
				
				if($oNcenterliteModel->getUserUnsubscribeConfigByTargetSrl($parent_srl, $abs_member_srl))
				{
					return;
				}
				
				$args = new stdClass();
				$args->config_type = 'comment_comment';
				$args->member_srl = $abs_member_srl;
				$args->srl = $obj->document_srl;
				$args->target_p_srl = $parent_srl;
				$args->target_srl = $obj->comment_srl;
				$args->type = $this->_TYPE_COMMENT;
				$args->target_type = $this->_TYPE_COMMENT;
				$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $document_srl, 'comment_srl', $comment_srl) . '#comment_' . $comment_srl;
				$args->target_summary = cut_str(trim(utf8_normalize_spaces(strip_tags($content))), 50) ?: (strpos($content, '<img') !== false ? lang('ncenterlite_content_image') : lang('ncenterlite_content_empty'));
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = $regdate;
				$args->target_browser = $module_info->browser_title;
				$args->module_srl = $module_info->module_srl;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				if(!$output->toBool())
				{
					return $output;
				}
				$notify_member_srls[] = $abs_member_srl;
			}
		}
		// 대댓글이 아니고, 게시글의 댓글을 남길 경우
		if(!$parent_srl || ($parent_srl && isset($config->use['comment_comment'])))
		{
			$abs_member_srl = abs($oDocument->get('member_srl'));

			if(is_array($admin_list) && in_array($abs_member_srl, $admin_list) && isset($config->use['admin_content']) && $obj->admin_comment_notify == true)
			{
				return;
			}

			if($oNcenterliteModel->getUserUnsubscribeConfigByTargetSrl($document_srl, $abs_member_srl))
			{
				return;
			}
			
			if($config->user_notify_setting == 'Y')
			{
				$comment_member_config = $oNcenterliteModel->getUserConfig($abs_member_srl);
				$document_comment_member_config = $comment_member_config->data;
				if($document_comment_member_config->comment_notify == 'N')
				{
					return;
				}
			}
			if(!in_array($abs_member_srl, $notify_member_srls) && (!$logged_info || ($abs_member_srl != 0 && $abs_member_srl != $logged_info->member_srl)))
			{
				$args = new stdClass();
				$args->config_type = 'comment';
				$args->member_srl = $abs_member_srl;
				$args->srl = $document_srl;
				$args->target_p_srl = $comment_srl;
				$args->target_srl = $comment_srl;
				$args->type = $this->_TYPE_DOCUMENT;
				$args->target_type = $this->_TYPE_COMMENT;
				$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $document_srl, 'comment_srl', $comment_srl) . '#comment_' . $comment_srl;
				$args->target_summary = cut_str(trim(utf8_normalize_spaces(strip_tags($content))), 50) ?: (strpos($content, '<img') !== false ? lang('ncenterlite_content_image') : lang('ncenterlite_content_empty'));
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = $regdate;
				$args->target_browser = $module_info->browser_title;
				$args->module_srl = $module_info->module_srl;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				if(!$output->toBool())
				{
					return $output;
				}
			}
		}
	}

	function triggerAfterSendMessage($obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		$communication_config = getModel('communication')->getConfig();

		if($communication_config->enable_message != 'Y')
		{
			return;
		}

		if(!isset($config->use['message']))
		{
			return;
		}

		if($config->user_notify_setting == 'Y')
		{
			$messages_member_config = $oNcenterliteModel->getUserConfig($obj->receiver_srl);
			$message_member_config = $messages_member_config->data;
			if($message_member_config->message_notify == 'N')
			{
				return;
			}
		}

		$args = new stdClass();
		$args->config_type = 'message';
		$args->member_srl = $obj->receiver_srl;
		$args->srl = $obj->message_srl;
		$args->target_p_srl = '1';
		$args->target_srl = $obj->related_srl;
		$args->target_member_srl = $obj->sender_srl;
		$args->type = $this->_TYPE_MESSAGE;
		$args->target_type = $this->_TYPE_MESSAGE;
		$args->target_summary = $obj->title;
		$args->regdate = date('YmdHis');
		$args->notify = $this->_getNotifyId($args);
		$args->target_url = getNotEncodedUrl('', 'act', 'dispCommunicationMessages', 'message_srl', $obj->related_srl);
		$output = $this->_insertNotify($args);
		if(!$output->toBool())
		{
			return $output;
		}
	}
	
	function triggerAfterScrap($obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(!isset($config->use['scrap']))
		{
			return;
		}
		
		if($config->user_notify_setting == 'Y' && $oNcenterliteModel->getUserConfig(abs($obj->target_member_srl))->data->scrap_notify == 'N')
		{
			return;
		}
		
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($obj->document_srl);
		
		$args = new stdClass();
		$args->config_type = 'scrap';
		$args->target_member_srl = abs($obj->member_srl);
		$args->member_srl = $obj->target_member_srl;
		$args->srl = $obj->document_srl;
		$args->target_p_srl = '1';
		$args->target_srl = $obj->document_srl;
		$args->type = $this->_TYPE_DOCUMENT;
		$args->target_type = $this->_TYPE_SCRAPPED;
		$args->target_summary = $obj->title;
		$args->regdate = date('YmdHis');
		$args->notify = $this->_getNotifyId($args);
		$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $obj->document_srl);
		$output = $this->_insertNotify($args);
		if(!$output->toBool())
		{
			return $output;
		}
	}

	function triggerAfterDocumentVotedUpdate(&$obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(!isset($config->use['vote']))
		{
			return;
		}
		if($obj->update_target !== 'voted_count')
		{
			return;
		}
		if($config->user_notify_setting == 'Y' && $oNcenterliteModel->getUserConfig(abs($obj->member_srl))->data->vote_notify == 'N')
		{
			return;
		}

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($obj->document_srl, false, false);
		$module_info = getModel('module')->getModuleInfoByDocumentSrl($obj->document_srl);

		$args = new stdClass();
		$args->config_type = 'vote';
		$args->member_srl = abs($obj->member_srl);
		$args->srl = $obj->document_srl;
		$args->target_p_srl = '1';
		$args->target_srl = $obj->document_srl;
		$args->type = $this->_TYPE_DOCUMENT;
		$args->target_type = $this->_TYPE_VOTED;
		$args->target_summary = $oDocument->get('title');
		$args->regdate = date('YmdHis');
		$args->notify = $this->_getNotifyId($args);
		$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $obj->document_srl);
		$args->module_srl = $obj->module_srl;
		$this->_insertNotify($args);
	}
	
	function triggerAfterDocumentVotedCancel($obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(empty($config->use))
		{
			return;
		}
		if($obj->update_target !== 'voted_count')
		{
			return;
		}
		if(!$this->user->member_srl)
		{
			return;
		}
		
		$args = new stdClass();
		$args->type = $this->_TYPE_DOCUMENT;
		$args->target_type = $this->_TYPE_VOTED;
		$args->target_srl = $obj->document_srl;
		$args->target_member_srl = $this->user->member_srl;
		$output = executeQuery('ncenterlite.deleteNotifyByTargetType', $args);
		if($output->toBool())
		{
			$this->removeFlagFile(abs($obj->member_srl));
		}
	}
	
	function triggerAfterCommentVotedCount($obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(!isset($config->use['vote']))
		{
			return;
		}
		if($obj->update_target !== 'voted_count')
		{
			return;
		}
		if($config->user_notify_setting == 'Y' && $oNcenterliteModel->getUserConfig(abs($obj->member_srl))->data->vote_notify == 'N')
		{
			return;
		}
		
		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($obj->comment_srl);
		
		$content = $oComment->get('content');
		$document_srl = $oComment->get('document_srl');
		$module_info = getModel('module')->getModuleInfoByDocumentSrl($document_srl);
		
		$args = new stdClass();
		$args->config_type = 'vote';
		$args->member_srl = abs($obj->member_srl);
		$args->srl = $document_srl;
		$args->target_p_srl = $obj->comment_srl;
		$args->target_srl = $obj->comment_srl;
		$args->type = $this->_TYPE_COMMENT;
		$args->target_type = $this->_TYPE_VOTED;
		$args->target_summary = cut_str(trim(utf8_normalize_spaces(strip_tags($content))), 50);
		$args->regdate = date('YmdHis');
		$args->module_srl = $obj->module_srl;
		$args->notify = $this->_getNotifyId($args);
		$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $document_srl, 'comment_srl', $obj->comment_srl) . '#comment_' . $obj->comment_srl;
		$this->_insertNotify($args);
	}

	function triggerAfterCommentVotedCancel($obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(empty($config->use))
		{
			return;
		}
		if($obj->update_target !== 'voted_count')
		{
			return;
		}
		if(!$this->user->member_srl)
		{
			return;
		}
		
		$args = new stdClass();
		$args->type = $this->_TYPE_COMMENT;
		$args->target_type = $this->_TYPE_VOTED;
		$args->target_srl = $obj->comment_srl;
		$args->target_member_srl = $this->user->member_srl;
		$output = executeQuery('ncenterlite.deleteNotifyByTargetType', $args);
		if($output->toBool())
		{
			$this->removeFlagFile(abs($obj->member_srl));
		}
	}

	function triggerAfterDeleteComment(&$obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(empty($config->use))
		{
			return;
		}

		$notify_list = $oNcenterliteModel->getNotifyMemberSrlByCommentSrl($obj->comment_srl);

		// 대댓글의 대댓글일 경우 혹은 중복적으로 받는 경우 comment_srl 당 2개이상 notify가 생성될 수 있다.
		$member_srls = array();
		foreach($notify_list as $value)
		{
			if(!in_array($value->member_srl, $member_srls))
			{
				$member_srls[] = $value->member_srl;
			}
		}

		$args = new stdClass();
		$args->srl = $obj->comment_srl;
		$output = executeQuery('ncenterlite.deleteNotifyBySrl', $args);
		if($output->toBool())
		{
			foreach($member_srls as $member_srl)
			{
				$this->removeFlagFile($member_srl);
			}
		}
	}

	function triggerAfterDeleteDocument(&$obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(empty($config->use))
		{
			return;
		}

		$args = new stdClass();
		$args->srl = $obj->document_srl;
		$output = executeQuery('ncenterlite.deleteNotifyBySrl', $args);
		if(!$output->toBool())
		{
			return $output;
		}
	}

	function triggerAfterMoveToTrash(&$obj)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$notify_list = $oNcenterliteModel->getNotifyListByDocumentSrl($obj->document_srl);

		$member_srls = array();
		foreach($notify_list as $value)
		{
			if(!in_array($value->member_srl, $member_srls))
			{
				$member_srls[] = $value->member_srl;
			}
		}

		$config = $oNcenterliteModel->getConfig();

		if(empty($config->use))
		{
			return;
		}

		$args = new stdClass();
		$args->srl = $obj->document_srl;
		$output = executeQuery('ncenterlite.deleteNotifyBySrl', $args);
		if($output->toBool())
		{
			foreach($member_srls as $member_srl)
			{
				//Remove flag files
				$this->removeFlagFile($member_srl);
			}
		}
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
			return;
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
				if($output_update->toBool())
				{
					//Remove flag files
					$this->removeFlagFile($args->member_srl);
				}
			}
		}
		elseif(preg_match('/^disp[A-Z][a-z0-9_]+Content$/', $oModule->act))
		{
			$document_srl = Context::get('document_srl');
			$oDocument = Context::get('oDocument');
			$logged_info = Context::get('logged_info');

			if($document_srl && $config->document_read == 'Y' && $logged_info->member_srl)
			{
				$args->srl = $document_srl;
				$args->member_srl = $logged_info->member_srl;
				$outputs = executeQuery('ncenterlite.updateNotifyReadedBySrl', $args);
				if($outputs->toBool() && DB::getInstance()->getAffectedRows())
				{
					//Remove flag files
					$this->removeFlagFile($args->member_srl);
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
				$update_output = executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
				if($update_output->toBool())
				{
					$this->removeFlagFile($args->member_srl);
				}
			}
		}
	}

	function triggerBeforeDisplay(&$output_display)
	{
		// 팝업창이면 중지
		if(Context::get('ncenterlite_is_popup'))
		{
			return;
		}

		// 자신의 알림목록을 보고 있을 경우엔 알림센터창을 띄우지 않는다.
		if(Context::get('act') == 'dispNcenterliteNotifyList')
		{
			return;
		}

		if(Context::isLocked())
		{
			return;
		}

		// HTML 모드가 아니면 중지 + admin 모듈이면 중지
		if(Context::getResponseMethod() != 'HTML' || Context::get('module') == 'admin')
		{
			return;
		}

		// 로그인 상태가 아니면 중지
		if(!Context::get('is_logged'))
		{
			return;
		}

		$module_info = Context::get('module_info');

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		// if the array is empty, dose not output the notification.
		if(empty($config->use))
		{
			return;
		}

		if($config->display_use == 'mobile' && !Mobile::isFromMobilePhone() || $config->display_use == 'pc' && Mobile::isFromMobilePhone() || $config->display_use == 'none')
		{
			return;
		}

		// 노티바 제외 페이지이면 중지
		if(is_array($config->hide_module_srls) && in_array($module_info->module_srl, $config->hide_module_srls))
		{
			return;
		}
		
		Context::set('ncenterlite_config', $config);
		
		if($config->highlight_effect === 'Y')
		{
			Context::loadFile(array('./modules/ncenterlite/tpl/js/ncenterlite.js', 'body', '', 100000));
		}
		
		$logged_info = Context::get('logged_info');
		$_output = $oNcenterliteModel->getMyNotifyList($logged_info->member_srl);
		
		if($config->always_display !== 'Y')
		{
			if(!$_output->data)
			{
				return;
			}
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
		$oMemberController = getController('member');

		$config = $oNcenterliteModel->getConfig();

		if($config->user_config_list == 'Y')
		{
			$logged_info = Context::get('logged_info');
			if(!Context::get('is_logged'))
			{
				return;
			}
			$target_srl = Context::get('target_srl');

			$oMemberController->addMemberMenu('dispNcenterliteNotifyList', 'ncenterlite_my_list');
			if($config->unsubscribe == 'Y')
			{
				$oMemberController->addMemberMenu('dispNcenterliteUnsubscribeList', 'unsubscribe_list');
			}
		}

		if($config->user_notify_setting == 'Y')
		{
			$oMemberController->addMemberMenu('dispNcenterliteUserConfig', 'ncenterlite_my_settings');

			if($logged_info->is_admin == 'Y')
			{
				$url = getUrl('', 'act', 'dispNcenterliteUserConfig', 'member_srl', $target_srl);
				$str = Context::getLang('ncenterlite_user_settings');
				$oMemberController->addMemberPopupMenu($url, $str, '');
			}
		}
	}

	function _addFile()
	{
		if(file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.css')))
		{
			Context::loadFile(array($this->template_path . 'ncenterlite.css', '', '', 100));
		}

		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(!Mobile::isFromMobilePhone())
		{
			if($config->colorset && file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.' . $config->colorset . '.css')))
			{
				Context::loadFile(array($this->template_path . 'ncenterlite.' . $config->colorset . '.css', '', '', 100));
			}
		}
		elseif(Mobile::isFromMobilePhone())
		{
			if($config->mcolorset && file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.' . $config->mcolorset . '.css')))
			{
				Context::loadFile(array($this->template_path . 'ncenterlite.' . $config->mcolorset . '.css', '', '', 100));
			}

			Context::loadFile(array('./common/js/jquery.min.js', 'head', '', -100000));
			Context::loadFile(array('./common/js/xe.min.js', 'head', '', -100000));
			Context::loadFile(array($this->template_path . 'ncenterlite.mobile.css', '', '', 100));
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

		//Remove flag files
		$this->removeFlagFile($args->member_srl);
		return $output;
	}

	function updateNotifyReadiByTargetSrl($target_srl, $member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->target_srl = $target_srl;
		$output = executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);

		//Remove flag files
		$this->removeFlagFile($args->member_srl);
		return $output;
	}

	function updateNotifyReadAll($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.updateNotifyReadedAll', $args);

		//Remove flag files
		$this->removeFlagFile($args->member_srl);
		return $output;
	}

	function procNcenterliteNotifyReadAll()
	{
		$logged_info = Context::get('logged_info');
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$output = $this->updateNotifyReadAll($logged_info->member_srl);
		return $output;
	}

	function procNcenterliteRedirect()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info || !$logged_info->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$notify = Context::get('notify');
		if(!strlen($notify))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$notify_info = getModel('ncenterlite')->getNotification($notify, $logged_info->member_srl);
		if (!$notify_info)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$output = $this->updateNotifyRead($notify, $logged_info->member_srl);
		if(!$output->toBool())
		{
			return $output;
		}

		header('Location: ' . $notify_info->target_url, true, 302);
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
		$config = getModel('ncenterlite')->getConfig();
		
		if(is_array($config->hide_module_srls) && in_array($args->module_srl, $config->hide_module_srls))
		{
			return new BaseObject();
		}

		// 비회원 노티 제거
		if($args->member_srl <= 0)
		{
			return new BaseObject();
		}

		// 노티 ID가 없는 경우 자동 생성
		if (!$args->notify)
		{
			$args->notify = $this->_getNotifyId($args);
		}
		
		// 날짜가 없는 경우 자동 생성
		if (!$args->regdate)
		{
			$args->regdate = date('YmdHis');
		}

		// 익명인 경우 발신자 정보를 제거
		if($anonymous == TRUE)
		{
			$args->target_member_srl = 0;
			$args->target_nick_name = strval($args->target_nick_name);
			$args->target_user_id = strval($args->target_nick_name);
			$args->target_email_address = strval($args->target_nick_name);
		}
		// 발신자 회원번호(target_member_srl)가 지정된 경우 그대로 사용
		elseif($args->target_member_srl)
		{
			$member_info = getModel('member')->getMemberInfoByMemberSrl(abs($args->target_member_srl));
			$args->target_member_srl = intval($member_info->member_srl);
			$args->target_nick_name = strval($member_info->nick_name);
			$args->target_user_id = strval($member_info->user_id);
			$args->target_email_address = strval($member_info->email_address);
		}
		// 발신자 회원번호가 없는 경우 현재 로그인한 사용자 정보를 사용
		elseif(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->target_member_srl = intval($logged_info->member_srl);
			$args->target_nick_name = strval($logged_info->nick_name);
			$args->target_user_id = strval($logged_info->user_id);
			$args->target_email_address = strval($logged_info->email_address);
		}
		// 비회원인 경우
		else
		{
			$args->target_member_srl = 0;
			$args->target_nick_name = strval($args->target_nick_name);
			$args->target_user_id = '';
			$args->target_email_address = '';
		}

		$trigger_output = ModuleHandler::triggerCall('ncenterlite._insertNotify', 'before', $args);
		if(!$trigger_output->toBool() || $trigger_output->getMessage() === 'cancel')
		{
			return $trigger_output;
		}
		
		$output = executeQuery('ncenterlite.insertNotify', $args);
		if($output->toBool())
		{
			ModuleHandler::triggerCall('ncenterlite._insertNotify', 'after', $args);
			$this->sendPushMessage($args);
			$this->sendSmsMessage($args);
			$this->sendMailMessage($args);
			$this->removeFlagFile($args->member_srl);
		}

		return $output;
	}

	public static function updateFlagFile($member_srl = null, $output = null)
	{
		if(!$member_srl)
		{
			return;
		}

		$cache_key = sprintf('ncenterlite:notify_list:%d', $member_srl);
		Rhymix\Framework\Cache::set($cache_key, $output);
		
		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($member_srl) . $member_srl . '.php';
		if (Rhymix\Framework\Cache::getDriverName() !== 'dummy')
		{
			FileHandler::removeFile($flag_path);
			return;
		}
		elseif(!file_exists($flag_path))
		{
			$buff = "<?php return unserialize(" . var_export(serialize($output), true) . ");\n";
			FileHandler::writeFile($flag_path, $buff);
		}
	}

	public function removeFlagFile($member_srl = null)
	{
		if(!$member_srl)
		{
			return;
		}

		$cache_key = sprintf('ncenterlite:notify_list:%d', $member_srl);
		Rhymix\Framework\Cache::delete($cache_key);
		
		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($member_srl) . $member_srl . '.php';
		if(file_exists($flag_path))
		{
			FileHandler::removeFile($flag_path);
		}
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

	function sendPushMessage($args)
	{
		$oNcenterliteModel = getModel('ncenterlite');

		$config = $oNcenterliteModel->getConfig();
		if(!isset($config->use[$args->config_type]['push']))
		{
			return false;
		}

		if($this->user->member_srl == $args->member_srl && $args->target_type != $this->_TYPE_CUSTOM)
		{
			return false;
		}
		
		$content = $oNcenterliteModel->getNotificationText($args);
		$content = htmlspecialchars_decode(preg_replace('/<\/?(strong|)[^>]*>/', '', $content));
		
		$target_url = $args->target_url;
		if (!preg_match('!^https?://!', $target_url))
		{
			$target_url = Rhymix\Framework\URL::getCurrentDomainUrl($target_url);
		}

		$oPush = new \Rhymix\Framework\Push();
		$oPush->setSubject($content);
		$oPush->setContent(strval($args->extra_content));
		$oPush->setData($args->extra_data ?: []);
		$oPush->setURL(strval($target_url));
		$oPush->addTo(intval($args->member_srl));
		$oPush->send();
	}
	
	function sendSmsMessage($args)
	{
		$oNcenterliteModel = getModel('ncenterlite');

		$config = $oNcenterliteModel->getConfig();
		if(!isset($config->use[$args->config_type]['sms']))
		{
			return false;
		}

		if($this->user->member_srl == $args->member_srl && $args->target_type != $this->_TYPE_CUSTOM)
		{
			return false;
		}

		$content = $oNcenterliteModel->getNotificationText($args);
		$content = htmlspecialchars_decode(preg_replace('/<\/?(strong|)[^>]*>/', '', $content));

		$sms = $this->getSmsHandler();
		if($sms === false)
		{
			return false;
		}

		$member_info = getModel('member')->getMemberInfoByMemberSrl($args->member_srl);
		if($config->variable_name)
		{
			if($config->variable_name === '#')
			{
				$phone_country = $member_info->phone_country;
				$phone_number = $member_info->phone_number;
				
				// Sending SMS outside of Korea is currently not supported.
				if($phone_country !== 'KOR')
				{
					return false;
				}
			}
			else
			{
				$phone_number = implode('', $member_info->{$config->variable_name});
			}

			// Check if a Korean phone number contains a valid area code and the correct number of digits.
			$phone_format = Rhymix\Framework\Korea::isValidPhoneNumber($phone_number);
			if($phone_format === false)
			{
				return false;
			}
		}
		else
		{
			return false;
		}

		$sms->addTo($phone_number);
		$sms->setContent($content);
		$output = $sms->send();

		return $output;
	}

	function sendMailMessage($args)
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if(!isset($config->use[$args->config_type]['mail']))
		{
			return false;
		}

		if($this->user->member_srl == $args->member_srl && $args->target_type != $this->_TYPE_CUSTOM)
		{
			return false;
		}
		
		$content = $oNcenterliteModel->getNotificationText($args);

		switch ($args->config_type)
		{
			case 'admin_content':
				$mail_title = Context::getSiteTitle() . ' - ' . lang('ncenterlite_admin_content');
				break;
			case 'comment_comment':
				$mail_title = Context::getSiteTitle() . ' - ' . lang('ncenterlite_comment_comment_noti');
				break;
			case 'comment':
				$mail_title = Context::getSiteTitle() . ' - ' . lang('ncenterlite_comment_noti');
				break;
			case 'message':
				$mail_title = Context::getSiteTitle() . ' - ' . lang('ncenterlite_message_noti');
				break;
			case 'vote':
				$mail_title = Context::getSiteTitle() . ' - ' . lang('ncenterlite_vote_noti');
				break;
			case 'mention':
				$mail_title = Context::getSiteTitle() . ' - ' . lang('ncenterlite_mention_noti');
				break;
			default:
				return false;
		}

		$target_url = $args->target_url;
		if (!preg_match('!^https?://!', $target_url))
		{
			$target_url = Rhymix\Framework\URL::getCurrentDomainUrl($target_url);
		}
		
		$mail_content = sprintf("<p>%s</p>\n<p>%s</p>\n", $content, $target_url);
		$member_info = getModel('member')->getMemberInfoByMemberSrl($args->member_srl);

		$oMail = new \Rhymix\Framework\Mail();
		$oMail->setSubject($mail_title);
		$oMail->setBody($mail_content);
		$oMail->addTo($member_info->email_address, $member_info->nick_name);
		$oMail->send();
	}

	/**
	 * Insert Mentions by target member_srls.
	 * @param $mention_targets
	 * @param $obj
	 * @param $module_info
	 * @param $is_anonymous
	 * @param string $type
	 * @return Object|Bool|array
	 */
	function insertMentionByTargets($mention_targets, $obj, $module_info, $is_anonymous, $type = 'D')
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		if(!is_array($mention_targets))
		{
			return false;
		}

		if(!$module_info)
		{
			return false;
		}

		$notify_member_srls = array();
		foreach ($mention_targets as $mention_member_srl)
		{
			if($config->user_notify_setting == 'Y')
			{
				$target_member_config = $oNcenterliteModel->getUserConfig($mention_member_srl);
				$notify_member_config = $target_member_config->data;
				if ($notify_member_config->mention_notify == 'N')
				{
					continue;
				}
			}

			$args = new stdClass();
			if ($type == $this->_TYPE_DOCUMENT)
			{
				$args->srl = $obj->document_srl;
				$args->target_p_srl = $obj->document_srl;
				$args->target_srl = $obj->document_srl;
				$args->target_summary = cut_str(strip_tags($obj->title), 50);
				$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $obj->document_srl);
			}
			elseif ($type == $this->_TYPE_COMMENT)
			{
				if(isset($config->use['admin_content']) && $obj->admin_comment_notify)
				{
					if(is_array($obj->admin_list) && in_array($mention_member_srl, $obj->admin_list))
					{
						continue;
					}
				}

				$args->srl = $obj->document_srl;
				$args->target_p_srl = $obj->comment_srl;
				$args->target_srl = $obj->comment_srl;
				$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $obj->document_srl, 'comment_srl', $obj->comment_srl) . '#comment_' . $obj->comment_srl;
				$args->target_summary = cut_str(trim(utf8_normalize_spaces(strip_tags($obj->content))), 50) ?: (strpos($obj->content, '<img') !== false ? lang('ncenterlite_content_image') : lang('ncenterlite_content_empty'));
			}
			$args->config_type = 'mention';
			$args->member_srl = $mention_member_srl;
			$args->target_type = $this->_TYPE_MENTION;
			$args->type = $type;
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
			$notify_member_srls[] = $mention_member_srl;
		}
		return $notify_member_srls;
	}

	/**
	 * trigger for document.getDocumentMenu. Append to popup menu a button for dispNcenterliteInsertUnsubscribe()
	 *
	 * @param array &$menu_list
	 *
	 * @return object
	**/
	function triggerGetDocumentMenu(&$menu_list)
	{
		if(!Rhymix\Framework\Session::isMember()) return;

		$document_srl = Context::get('target_srl');

		/** @var ncenterliteModel $oNcenterliteModel */
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		
		if($config->unsubscribe !== 'Y') return;
		
		$oDocumentController = getController('document');
		$url = getUrl('','module','ncenterlite','act','dispNcenterliteInsertUnsubscribe', 'target_srl', $document_srl, 'unsubscribe_type', 'document');
		$oDocumentController->addDocumentPopupMenu($url,'ncenterlite_cmd_unsubscribe_settings','','popup');
	}

	/**
	 * trigger for comment.getCommentMenu. Append to popup menu a button for dispNcenterliteInsertUnsubscribe()
	 *
	 * @param array &$menu_list
	 *
	 * @return object
	**/
	function triggerGetCommentMenu(&$menu_list)
	{
		if(!Rhymix\Framework\Session::isMember()) return;

		$comment_srl = Context::get('target_srl');

		/** @var ncenterliteModel $oNcenterliteModel */
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		
		if($config->unsubscribe !== 'Y') return;
		
		$oCommentController = getController('comment');
		$url = getUrl('','module','ncenterlite','act','dispNcenterliteInsertUnsubscribe', 'target_srl', $comment_srl, 'unsubscribe_type', 'comment');
		$oCommentController->addCommentPopupMenu($url,'ncenterlite_cmd_unsubscribe_settings','','popup');
	}

}
