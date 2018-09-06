<?php

class ncenterliteView extends ncenterlite
{
	function init()
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		$template_path = sprintf("%sskins/%s/",$this->module_path, $config->skin);
		if(!is_dir($template_path)||!$config->skin)
		{
			$config->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $config->skin);
		}
		$this->setTemplatePath($template_path);

		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($config->layout_srl);

		if($layout_info)
		{
			$this->module_info->layout_srl = $config->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

	function dispNcenterliteNotifyList()
	{
		$oNcenterliteModel = getModel('ncenterlite');

		$output = $oNcenterliteModel->getMyNotifyList();

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('ncenterlite_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('NotifyList');
	}

	function dispNcenterliteUserConfig()
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if($config->user_notify_setting != 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_not_use_user_setting');
		}

		$oMemberModel = getModel('member');
		$member_srl = Context::get('member_srl');
		$logged_info = Context::get('logged_info');
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exception('ncenterlite_stop_login_required');

		if($logged_info->is_admin == 'Y' && $member_srl)
		{
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		}

		if($logged_info->is_admin != 'Y' && $member_srl)
		{
			if($member_srl != $logged_info->member_srl)
			{
				throw new Rhymix\Framework\Exception('ncenterlite_stop_no_permission_other_user');
			}
		}
		$output = $oNcenterliteModel->getUserConfig($member_srl);

		Context::set('member_info', $member_info);
		Context::set('user_config', $output->data);
		$this->setTemplateFile('userconfig');
	}
}
