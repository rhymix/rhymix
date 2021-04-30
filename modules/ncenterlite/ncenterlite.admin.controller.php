<?php
class ncenterliteAdminController extends ncenterlite
{
	function procNcenterliteAdminInsertConfig()
	{
		$oModuleController = getController('module');
		$obj = Context::getRequestVars();
		$config = getModel('ncenterlite')->getConfig();

		$config_vars = array(
			'use',
			'display_use',
			'always_display',
			'user_config_list',
			'mention_names',
			'mention_suffixes',
			'mention_suffix_always_cut',
			'mention_limit',
			'hide_module_srls',
			'admin_notify_module_srls',
			'skin',
			'mskin',
			'mcolorset',
			'colorset',
			'zindex',
			'anonymous_name',
			'document_read',
			'layout_srl',
			'mlayout_srl',
			'use_sms',
			'variable_name',
			'user_notify_setting',
			'anonymous_voter',
			'anonymous_scrap',
			'highlight_effect',
			'comment_all',
			'comment_all_notify_module_srls',
			'unsubscribe',
			'notify_count',
		);

		if($obj->disp_act == 'dispNcenterliteAdminSkinsetting')
		{
			if(intval($obj->notify_count) !== intval($config->notify_count))
			{
				Rhymix\Framework\Cache::clearGroup($this->module);
			}
		}

		foreach($config_vars as $val)
		{
			if($obj->{$val})
			{
				$config->{$val} = $obj->{$val};
			}
		}

		if ($obj->disp_act == 'dispNcenterliteAdminNotifyConfig')
		{
			if (!$obj->use)
			{
				$config->use = array();
			}
		}

		if ($obj->disp_act == 'dispNcenterliteAdminAdvancedconfig')
		{
			if (!$config->mention_suffixes)
			{
				$config->mention_suffixes = array();
			}
			else if (!is_array($config->mention_suffixes))
			{
				$config->mention_suffixes = array_map('trim', explode(',', $config->mention_suffixes));
			}

			if($obj->variable_name === '-1')
			{
				$config->variable_name = '#';
			}
			elseif($obj->variable_name === '0')
			{
				$config->variable_name = '';
			}
		}

		if ($obj->disp_act == 'dispNcenterliteAdminSeletedmid')
		{
			if (!$obj->hide_module_srls)
			{
				$config->hide_module_srls = array();
			}
			if (!$obj->admin_notify_module_srls)
			{
				$config->admin_notify_module_srls = array();
			}
		}
		
		if($obj->disp_act == 'dispNcenterliteAdminOtherComment')
		{
			if(!$obj->comment_all)
			{
				$config->comment_all = 'N';
			}
			if(!$obj->comment_all_notify_module_srls)
			{
				$config->comment_all_notify_module_srls = array();
			}
		}

		if($obj->disp_act == 'dispNcenterliteAdminSkinsetting')
		{
			if(!$obj->notify_count)
			{
				$config->notify_count = 0;
			}
		}
		
		$output = $oModuleController->insertModuleConfig('ncenterlite', $config);
		if(!$output->toBool())
		{
			throw new Rhymix\Framework\Exception('ncenterlite_msg_setting_error');
		}

		$this->setMessage('success_updated');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', $obj->disp_act));
		}
	}

	/**
	 * @brief 스킨 테스트를 위한 더미 데이터 생성 5개 생성
	 **/
	function procNcenterliteAdminInsertDummyData()
	{
		$oNcenterliteController = getController('ncenterlite');
		$logged_info = Context::get('logged_info');

		for($i = 1; $i <= 5; $i++)
		{
			$args = new stdClass();
			$args->member_srl = $logged_info->member_srl;
			$args->srl = 1;
			$args->target_srl = 1;
			$args->target_p_srl = 1;
			$args->type = $this->_TYPE_TEST;
			$args->target_type = $this->_TYPE_TEST;
			$args->target_url = getUrl('');
			$args->target_summary = Context::getLang('ncenterlite_thisistest');
			$args->target_nick_name = $logged_info->nick_name;
			$args->regdate = date('YmdHis');
			$args->notify = $oNcenterliteController->_getNotifyId($args);
			$output = $oNcenterliteController->_insertNotify($args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		$this->setMessage('msg_test_notifycation_success');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNcenterliteAdminTest'));
		}
	}

	/**
	 * @brief 모듈 푸시 테스트를 위한 더미 데이터 생성 1개 생성
	 **/
	function procNcenterliteAdminInsertPushData()
	{
		$oNcenterliteController = getController('ncenterlite');
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->srl = 1;
		$args->target_srl = 1;
		$args->target_p_srl = 1;
		$args->type = $this->_TYPE_DOCUMENT;
		$args->target_type = $this->_TYPE_COMMENT;
		$args->target_url = getUrl('');
		$args->target_summary = Context::getLang('ncenterlite_thisistest') . rand();
		$args->target_nick_name = $logged_info->nick_name;
		$args->regdate = date('YmdHis');
		$args->notify = $oNcenterliteController->_getNotifyId($args);
		$output = $oNcenterliteController->_insertNotify($args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('msg_test_notifycation_success');
		
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNcenterliteAdminTest'));
		}
	}

	function procNcenterliteAdminDeleteNofity()
	{
		$old_date = Context::get('old_date');
		$args = new stdClass;
		if($old_date)
		{
			$args->old_date = $old_date;
		}
		$output = executeQuery('ncenterlite.deleteNotifyAll', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		if($old_date)
		{
			$oNcenterliteModel = getModel('ncenterlite');
			$message = Context::getLang('ncenterlite_message_delete_notification_before');
			$message = sprintf($message, $oNcenterliteModel->getAgo($old_date) );
			$this->setMessage($message);
		}
		else
		{
			$this->setMessage('ncenterlite_message_delete_notification_all');
		}

		$reg_obj = new stdClass();
		$reg_obj->regdate = time();

		Rhymix\Framework\Cache::clearGroup('ncenterlite');
		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/delete_date.php';
		Rhymix\Framework\Storage::writePHPData($flag_path, $reg_obj);

		if(Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNcenterliteAdminList'));
		}
	}

	/**
	 * @brief 알림센터에 생성된 커스텀 알림을 삭제하는 기능.
	 */
	function procNcenterliteAdminDeleteCustom()
	{
		$obj = Context::getRequestVars();
		$args = new stdClass();
		$args->notify_type_srl = $obj->notify_type_srl;

		$output = executeQuery('ncenterlite.deleteNotifyType', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage("success_deleted");


		if(Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNcenterliteAdminCustomList'));
		}
	}
}
