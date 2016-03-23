<?php
class ncenterliteAdminController extends ncenterlite
{
	function procNcenterliteAdminInsertConfig()
	{
		$oModuleController = getController('module');
		$obj = Context::getRequestVars();

		$config = new stdClass();
		$config->use = $obj->use;
		$config->display_use = $obj->display_use;

		$config->user_config_list = $obj->user_config_list;
		$config->mention_format = $obj->mention_format;
		$config->mention_names = $obj->mention_names;
		$config->document_notify = $obj->document_notify;
		$config->message_notify = $obj->message_notify;
		$config->hide_module_srls = $obj->hide_module_srls;
		if(!$config->mention_format && !is_array($config->mention_format))
		{
			$config->mention_format = array();
		}
		$config->admin_comment_module_srls = $obj->admin_comment_module_srls;

		$config->skin = $obj->skin;
		$config->mskin = $obj->mskin;
		$config->mcolorset = $obj->mcolorset;
		$config->colorset = $obj->colorset;
		$config->zindex = $obj->zindex;
		$config->anonymous_name = $obj->anonymous_name;
		$config->document_read = $obj->document_read;
		$config->layout_srl = $obj->layout_srl;
		$config->mlayout_srl = $obj->mlayout_srl;
		$config->voted_format = $obj->voted_format;

		if(!$config->document_notify)
		{
			$config->document_notify = 'direct-comment';
		}

		$this->setMessage('success_updated');

		$oModuleController->updateModuleConfig('ncenterlite', $config);

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNcenterliteAdminConfig');
			header('location: ' . $returnUrl);
			return;
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
			$args->type = $this->_TYPE_TEST;
			$args->target_type = $this->_TYPE_TEST;
			$args->target_url = getUrl('');
			$args->target_summary = Context::getLang('ncenterlite_thisistest') . rand();
			$args->target_nick_name = $logged_info->nick_name;
			$args->regdate = date('YmdHis');
			$args->notify = $oNcenterliteController->_getNotifyId($args);
			$output = $oNcenterliteController->_insertNotify($args);
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
		$args->type = $this->_TYPE_DOCUMENT;
		$args->target_type = $this->_TYPE_COMMENT;
		$args->target_url = getUrl('');
		$args->target_summary = Context::getLang('ncenterlite_thisistest') . rand();
		$args->target_nick_name = $logged_info->nick_name;
		$args->regdate = date('YmdHis');
		$args->notify = $oNcenterliteController->_getNotifyId($args);
		$output = $oNcenterliteController->_insertNotify($args);
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
			$oDB->rollback();
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
		
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ?  Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNcenterliteAdminList');
			header('location: ' .$returnUrl);
			return;
		}
	}
}
