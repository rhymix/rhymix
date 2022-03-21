<?php

class ncenterliteController extends ncenterlite
{
	/**
	 * List of acts to skip.
	 */
	public static $_skip_acts = array(
		'dispNcenterliteNotifyList' => true,
		'dispEditorFrame' => true,
	);
	
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
		$args->target_content = null;
		
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
		$config = NcenterliteModel::getConfig();
		if(!Rhymix\Framework\Session::isMember())
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}
		if($config->user_notify_setting != 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_not_use_user_setting');
		}

		$logged_info = Context::get('logged_info');
		$member_srl = Context::get('member_srl');
		if(!$member_srl)
		{
			$member_srl = $logged_info->member_srl;
		}
		if($logged_info->member_srl != $member_srl && $logged_info->is_admin != 'Y')
		{
			throw new Rhymix\Framework\Exception('ncenterlite_stop_no_permission_other_user_settings');
		}

		$vars = Context::getRequestVars();
		$notify_types = NcenterliteModel::getUserSetNotifyTypes();
		$is_old_skin = false;
		foreach ($notify_types as $type => $srl)
		{
			if (isset($vars->{$type . '_notify'}))
			{
				$is_old_skin = true;
				break;
			}
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		foreach ($notify_types as $type => $srl)
		{
			$disabled_list = array();
			if ($is_old_skin)
			{
				if (isset($vars->{$type . '_notify'}) && $vars->{$type . '_notify'} === 'N')
				{
					$disabled_list = ['!web', '!mail', '!sms', '!push'];
				}
			}
			else
			{
				foreach (['web', 'mail', 'sms', 'push'] as $method)
				{
					if (isset($config->use[$type][$method]) && $config->use[$type][$method])
					{
						if (!isset($vars->use[$type][$method]) || !$vars->use[$type][$method])
						{
							$disabled_list[] = '!' . $method;
						}
					}
				}
			}
			$args->{$type . '_notify'} = implode(',', $disabled_list);
		}

		$user_config = NcenterliteModel::getUserConfig($member_srl);
		if(!$user_config)
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
		Rhymix\Framework\Cache::delete('ncenterlite:user_config:' . $member_srl);

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
		$config = NcenterliteModel::getConfig();
		if(!Rhymix\Framework\Session::isMember())
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}
		if($config->unsubscribe !== 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_unsubscribe_block_not_support');
		}
		
		$obj = Context::getRequestVars();
		
		if(!$this->user->member_srl || (!intval($obj->unsubscribe_srl) && !intval($obj->target_srl)))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		if($obj->target_srl)
		{
			$oNcenterliteModel = getModel('ncenterlite');
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
			$text = self::_createSummary(getModel('document')->getDocument($obj->target_srl)->get('title'));
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

		if(Context::get('is_popup') != 'Y')
		{
			if (Context::get('success_return_url'))
			{
				$this->setRedirectUrl(Context::get('success_return_url'));
			}
			else
			{
				$this->setRedirectUrl(getNotEncodedUrl('act', 'dispNcenterliteUnsubscribeList', 'member_srl', $this->user->member_srl));
			}
		}
		else
		{
			if (Context::get('success_return_url'))
			{
				$this->setRedirectUrl(Context::get('success_return_url'));
			}
			else
			{
				$this->setRedirectUrl(getNotEncodedUrl('act', 'dispNcenterliteUnsubscribeList', 'target_srl', $obj->target_srl, 'unsubscribe_type', $obj->unsubscribe_type));
			}
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
		$config = NcenterliteModel::getConfig();

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
				$args->target_summary = self::_createSummary($obj->title);
				$args->target_content = self::_createContent($obj->content) ?: (strpos($obj->content, '<img') !== false ? lang('ncenterlite_content_image') : lang('ncenterlite_content_empty'));
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
		$config = NcenterliteModel::getConfig();

		$logged_info = Context::get('logged_info');

		$document_srl = $obj->document_srl;
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
		$comment_srl = $obj->comment_srl;
		$parent_srl = $obj->parent_srl;
		$content = self::_createSummary($obj->content) ?: (strpos($obj->content, '<img') !== false ? lang('ncenterlite_content_image') : lang('ncenterlite_content_empty'));

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
				$args->target_summary = $content;
				$args->target_content = null;
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
				$args->target_summary = $content;
				$args->target_content = null;
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
				$parent_member_config = NcenterliteModel::getUserConfig($abs_member_srl);
				if($parent_member_config && !$parent_member_config->comment_comment)
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
				$args->target_summary = $content;
				$args->target_content = null;
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = $obj->regdate;
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
				$document_comment_member_config = NcenterliteModel::getUserConfig($abs_member_srl);
				if($document_comment_member_config && !$document_comment_member_config->comment)
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
				$args->target_summary = $content;
				$args->target_content = null;
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = $obj->regdate;
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
		$config = NcenterliteModel::getConfig();
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
			$target_member_config = NcenterliteModel::getUserConfig($obj->receiver_srl);
			if($target_member_config && !$target_member_config->message)
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
		$args->target_content = mb_substr(trim(utf8_normalize_spaces(strip_tags($obj->content))), 0, 200, 'UTF-8');
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
		$config = NcenterliteModel::getConfig();
		if(!isset($config->use['scrap']))
		{
			return;
		}
		
		if($config->user_notify_setting === 'Y')
		{
			$target_member_config = NcenterliteModel::getUserConfig(abs($obj->target_member_srl));
			if($target_member_config && !$target_member_config->scrap)
			{
				return;
			}
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
		$args->target_content = null;
		$args->regdate = date('YmdHis');
		$args->notify = $this->_getNotifyId($args);
		$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $obj->document_srl);
		$output = $this->_insertNotify($args, $config->anonymous_scrap !== 'N');
		if(!$output->toBool())
		{
			return $output;
		}
	}

	function triggerAfterDocumentVotedUpdate(&$obj)
	{
		$config = NcenterliteModel::getConfig();
		if(!isset($config->use['vote']))
		{
			return;
		}
		if($obj->update_target !== 'voted_count')
		{
			return;
		}
		if($config->user_notify_setting == 'Y')
		{
			$target_member_config = NcenterliteModel::getUserConfig(abs($obj->member_srl));
			if ($target_member_config && !$target_member_config->vote)
			{
				return;
			}
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
		$args->target_content = null;
		$args->regdate = date('YmdHis');
		$args->notify = $this->_getNotifyId($args);
		$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $obj->document_srl);
		$args->module_srl = $obj->module_srl;
		$this->_insertNotify($args, $config->anonymous_voter !== 'N');
	}
	
	function triggerAfterDocumentVotedCancel($obj)
	{
		$config = NcenterliteModel::getConfig();
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
		
		if($config->anonymous_voter === 'Y')
		{
			$member_srl = -1 * $this->user->member_srl;
		}
		else
		{
			$member_srl = $this->user->member_srl;
		}
		
		$args = new stdClass();
		$args->type = $this->_TYPE_DOCUMENT;
		$args->target_type = $this->_TYPE_VOTED;
		$args->target_srl = $obj->document_srl;
		$args->target_member_srl = $member_srl;
		$output = executeQuery('ncenterlite.deleteNotifyByTargetType', $args);
		if($output->toBool())
		{
			$this->removeFlagFile(abs($obj->member_srl));
		}
	}
	
	function triggerAfterCommentVotedCount($obj)
	{
		$config = NcenterliteModel::getConfig();
		if(!isset($config->use['vote']))
		{
			return;
		}
		if($obj->update_target !== 'voted_count')
		{
			return;
		}
		if($config->user_notify_setting == 'Y')
		{
			$target_member_config = NcenterliteModel::getUserConfig(abs($obj->member_srl));
			if ($target_member_config && !$target_member_config->vote)
			{
				return;
			}
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
		$args->target_summary = self::_createSummary($content) ?: (strpos($content, '<img') !== false ? lang('ncenterlite_content_image') : lang('ncenterlite_content_empty'));
		$args->target_content = null;
		$args->regdate = date('YmdHis');
		$args->module_srl = $obj->module_srl;
		$args->notify = $this->_getNotifyId($args);
		$args->target_url = getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $document_srl, 'comment_srl', $obj->comment_srl) . '#comment_' . $obj->comment_srl;
		$this->_insertNotify($args, $config->anonymous_voter !== 'N');
	}

	function triggerAfterCommentVotedCancel($obj)
	{
		$config = NcenterliteModel::getConfig();
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
		$config = NcenterliteModel::getConfig();
		if(empty($config->use))
		{
			return;
		}

		$notify_list = ncenterliteModel::getInstance()->getNotifyMemberSrlByCommentSrl($obj->comment_srl);

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
		$config = NcenterliteModel::getConfig();
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
		$notify_list = ncenterliteModel::getInstance()->getNotifyListByDocumentSrl($obj->document_srl);

		$member_srls = array();
		foreach($notify_list as $value)
		{
			if(!in_array($value->member_srl, $member_srls))
			{
				$member_srls[] = $value->member_srl;
			}
		}

		$config = NcenterliteModel::getConfig();

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
		$args = new stdClass();

		if($oModule->getLayoutFile() == 'popup_layout.html')
		{
			Context::set('ncenterlite_is_popup', TRUE);
		}

		$config = NcenterliteModel::getConfig();
		// if the array is empty, lets return.
		if(empty($config->use))
		{
			return;
		}

		if($oModule->act == 'dispBoardReplyComment')
		{
			$comment_srl = Context::get('comment_srl');
			$logged_info = Context::get('logged_info');
			if($comment_srl && $logged_info && $logged_info->member_srl)
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
			$logged_info = Context::get('logged_info');

			if($document_srl && $config->document_read == 'Y' && $logged_info && $logged_info->member_srl)
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
		// Don't show notification panel in popups, iframes, admin dashboard, etc.
		if(Context::get('ncenterlite_is_popup'))
		{
			return;
		}
		if(Context::isLocked())
		{
			return;
		}
		if(isset(self::$_skip_acts[Context::get('act')]))
		{
			return;
		}
		if(Context::getResponseMethod() != 'HTML' || Context::get('module') == 'admin')
		{
			return;
		}
		if(!Context::get('is_logged'))
		{
			return;
		}

		$module_info = Context::get('module_info');

		$oNcenterliteModel = getModel('ncenterlite');
		$config = NcenterliteModel::getConfig();

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
		
		// 레이아웃에서 알림센터 사용중이라면 중지
		if(Context::get('layout_info')->use_ncenter_widget == 'Y')
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
		$_latest_notify_id = count($_latest_notify_id) > 0 ? $_latest_notify_id[0]->notify : "";
		Context::set('ncenterlite_latest_notify_id', $_latest_notify_id);

		if(!empty($_COOKIE['_ncenterlite_hide_id']) && $_COOKIE['_ncenterlite_hide_id'] == $_latest_notify_id)
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
			if(!$config->mskin)
			{
				$config->mskin = 'default';
				$this->template_path = sprintf('%sm.skins/%s/', $this->module_path, $config->mskin);
			}
			// If use to same PC skin set.
			else if ($config->mskin === '/USE_RESPONSIVE/')
			{
				$this->template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
				if(!$config->skin)
				{
					$config->skin = 'default';
					$this->template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
				}
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

		if($config->zindex)
		{
			Context::set('ncenterlite_zindex', ' style="z-index:' . $config->zindex . ';" ');
		}
		
		$result = TemplateHandler::getInstance()->compile($this->template_path, 'ncenterlite.html');
		$this->_addFile();
		$output_display = $result . $output_display;
	}

	function triggerAddMemberMenu()
	{
		$oMemberController = getController('member');

		$config = NcenterliteModel::getConfig();

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

		$config = NcenterliteModel::getConfig();

		if(Mobile::isFromMobilePhone() && $config->mskin !== '/USE_RESPONSIVE/')
		{
			if($config->mcolorset && file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.' . $config->mcolorset . '.css')))
			{
				Context::loadFile(array($this->template_path . 'ncenterlite.' . $config->mcolorset . '.css', '', '', 100));
			}

			Context::loadFile(array('./common/js/jquery.min.js', 'head', '', -100000));
			Context::loadFile(array('./common/js/xe.min.js', 'head', '', -100000));
			Context::loadFile(array($this->template_path . 'ncenterlite.mobile.css', '', '', 100));
		}
		else
		{
			if($config->colorset && file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.' . $config->colorset . '.css')))
			{
				Context::loadFile(array($this->template_path . 'ncenterlite.' . $config->colorset . '.css', '', '', 100));
			}
		}
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
			$args->target_member_srl = -1 * $this->user->member_srl;
			$args->target_nick_name = strval($args->target_nick_name);
			$args->target_user_id = $args->target_nick_name;
			$args->target_email_address = $args->target_nick_name;
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
		
		// 수신자가 웹 알림을 거부한 경우 이미 읽은 것으로 처리
		if($config->user_notify_setting == 'Y')
		{
			$target_member_config = NcenterliteModel::getUserConfig($args->member_srl);
			if($target_member_config && isset($target_member_config->{$args->config_type}) && !in_array('web', $target_member_config->{$args->config_type}))
			{
				$args->readed = 'Y';
			}
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
		$oMemberModel =  getModel('member');
		$config = NcenterliteModel::getConfig();
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
		$config = NcenterliteModel::getConfig();
		if(!isset($config->use[$args->config_type]['push']))
		{
			return false;
		}
		if($config->user_notify_setting == 'Y')
		{
			$target_member_config = NcenterliteModel::getUserConfig($args->member_srl);
			if($target_member_config && isset($target_member_config->{$args->config_type}) && !in_array('push', $target_member_config->{$args->config_type}))
			{
				return;
			}
		}
		if($this->user->member_srl == $args->member_srl && $args->target_type != $this->_TYPE_CUSTOM)
		{
			return false;
		}
		
		$oNcenterliteModel = getModel('ncenterlite');
		$content = $oNcenterliteModel->getNotificationText($args);
		$content = htmlspecialchars_decode(preg_replace('/<\/?(strong|)[^>]*>/', '', $content));
		
		$target_url = $args->target_url;
		if (!preg_match('!^https?://!', $target_url))
		{
			$target_url = Rhymix\Framework\URL::getCurrentDomainUrl($target_url);
		}

		if (!isset($args->extra_data) || !$args->extra_data)
		{
			$args->extra_data = [];
			$args->extra_data['sender'] = strval($args->target_nick_name);
			$args->extra_data['profile_image'] = '';
			if ($args->target_member_srl > 0)
			{
				$profile_image = MemberModel::getProfileImage($args->target_member_srl);
				if ($profile_image && $profile_image->src)
				{
					$args->extra_data['profile_image'] = Rhymix\Framework\URL::getCurrentDomainUrl($profile_image->src);
				}
			}
			$args->extra_data['type'] = strval($args->target_type);
			$args->extra_data['subject'] = strval($args->target_summary);
			$args->extra_data['content'] = isset($args->target_content) ? mb_substr($args->target_content, 0, 200, 'UTF-8') : '';
		}

		$oPush = new \Rhymix\Framework\Push();
		$oPush->setSubject($content);
		$oPush->setContent(strval($args->extra_content));
		$oPush->setData($args->extra_data);
		$oPush->setURL(strval($target_url));
		$oPush->addTo(intval($args->member_srl));
		$oPush->send();
	}
	
	function sendSmsMessage($args)
	{
		$config = NcenterliteModel::getConfig();
		if(!isset($config->use[$args->config_type]['sms']))
		{
			return false;
		}
		if($config->user_notify_setting == 'Y')
		{
			$target_member_config = NcenterliteModel::getUserConfig($args->member_srl);
			if($target_member_config && isset($target_member_config->{$args->config_type}) && !in_array('sms', $target_member_config->{$args->config_type}))
			{
				return;
			}
		}
		if($this->user->member_srl == $args->member_srl && $args->target_type != $this->_TYPE_CUSTOM)
		{
			return false;
		}

		$oNcenterliteModel = getModel('ncenterlite');
		$content = $oNcenterliteModel->getNotificationText($args);
		$content = htmlspecialchars_decode(preg_replace('/<\/?(strong|)[^>]*>/', '', $content));

		$sms = $this->getSmsHandler();
		if($sms === false)
		{
			return false;
		}

		$member_info = MemberModel::getMemberInfoByMemberSrl($args->member_srl);
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
		$config = NcenterliteModel::getConfig();
		if(!isset($config->use[$args->config_type]['mail']))
		{
			return false;
		}
		if($config->user_notify_setting == 'Y')
		{
			$target_member_config = NcenterliteModel::getUserConfig($args->member_srl);
			if($target_member_config && isset($target_member_config->{$args->config_type}) && !in_array('mail', $target_member_config->{$args->config_type}))
			{
				return;
			}
		}
		if($this->user->member_srl == $args->member_srl && $args->target_type != $this->_TYPE_CUSTOM)
		{
			return false;
		}
		
		$oNcenterliteModel = getModel('ncenterlite');
		$content = $oNcenterliteModel->getNotificationText($args);

		$mail_title = lang('ncenterlite_type_' . $args->config_type);
		if ($mail_title === 'ncenterlite_type_' . $args->config_type)
		{
			$mail_title = lang('ncenterlite_type_custom');
		}
		$mail_title = Context::getSiteTitle() . ' - ' . $mail_title;

		$target_url = $args->target_url;
		if (!preg_match('!^https?://!', $target_url))
		{
			$target_url = Rhymix\Framework\URL::getCurrentDomainUrl($target_url);
		}
		
		$mail_content = sprintf("<p>%s</p>\n<p>%s</p>\n", $content, $target_url);
		$member_info = MemberModel::getMemberInfoByMemberSrl($args->member_srl);

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
	 * @return array
	 */
	function insertMentionByTargets($mention_targets, $obj, $module_info, $is_anonymous, $type = 'D')
	{
		$config = NcenterliteModel::getConfig();

		if(!is_array($mention_targets))
		{
			return array();
		}

		if(!$module_info)
		{
			return array();
		}

		$notify_member_srls = array();
		foreach ($mention_targets as $mention_member_srl)
		{
			if($config->user_notify_setting == 'Y')
			{
				$target_member_config = NcenterliteModel::getUserConfig($mention_member_srl);
				if ($target_member_config && !$target_member_config->mention)
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
				$args->target_summary = self::_createSummary($obj->title);
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
				$args->target_summary = self::_createSummary($obj->content) ?: (strpos($obj->content, '<img') !== false ? lang('ncenterlite_content_image') : lang('ncenterlite_content_empty'));
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
				// 실패시 지금까지 성공한 데이터를 리턴
				return $notify_member_srls;
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

		$config = NcenterliteModel::getConfig();
		
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

		$config = NcenterliteModel::getConfig();
		
		if($config->unsubscribe !== 'Y') return;
		
		$oCommentController = getController('comment');
		$url = getUrl('','module','ncenterlite','act','dispNcenterliteInsertUnsubscribe', 'target_srl', $comment_srl, 'unsubscribe_type', 'comment');
		$oCommentController->addCommentPopupMenu($url,'ncenterlite_cmd_unsubscribe_settings','','popup');
	}
	
	/**
	 * Cut a string to fit the notification summary column.
	 * 
	 * @param string $str
	 * @return string
	 */
	protected static function _createSummary($str): string
	{
		$str = escape(utf8_normalize_spaces(trim(strip_tags($str)), false));
		if (function_exists('mb_strimwidth'))
		{
			return mb_strimwidth($str, 0, 50, '...', 'UTF-8');
		}
		else
		{
			return cut_str($str, 45);
		}
	}
	
	/**
	 * Cut a string to fit the notification content column.
	 * 
	 * @param string $str
	 * @return string
	 */
	protected static function _createContent($str): string
	{
		$str = escape(utf8_normalize_spaces(trim(strip_tags($str)), false));
		if (function_exists('mb_strimwidth'))
		{
			return mb_strimwidth($str, 0, 200, '...', 'UTF-8');
		}
		else
		{
			return cut_str($str, 195);
		}
	}
}
