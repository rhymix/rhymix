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
	
	function dispNcenterliteIndividualBlockList()
	{
		/** @var ncenterliteModel $oNcenterliteModel */
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		
		if($config->individual_block !== 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_individual_block_not_support');
		}
		
		if(!Rhymix\Framework\Session::getMemberSrl())
		{
			throw new Rhymix\Framework\Exception('ncenterlite_stop_login_required');
		}

		$member_srl = Context::get('member_srl');
		
		if(!$member_srl)
		{
			$member_srl = $this->user->member_srl;
		}
		
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_count = '20';
		$args->page_count = '10';
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.getIndividualBlockList', $args);

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('individual_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		
		$this->setTemplateFile('individualList');
	}
	
	function dispNcenterliteInsertIndividualBlock()
	{
		/** @var ncenterliteModel $oNcenterliteModel */
		$oNcenterliteModel = getModel('ncenterlite');
		$target_srl = Context::get('target_srl');
		$individual_srl = Context::get('individual_srl');
		$individual_type = Context::get('individual_type');
		
		$member_srl = Context::get('member_srl');
		
		if($individual_srl)
		{
			$output = $oNcenterliteModel->getUserIndividualBlockConfigByIndividualSrl($individual_srl);
		}
		else
		{
			$output = $oNcenterliteModel->getUserIndividualBlockConfigByTargetSrl($target_srl, $member_srl);
		}
		
		
		if((!$target_srl || !$individual_type) && !$output)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		if($individual_type == 'document')
		{
			$text = getModel('document')->getDocument($target_srl)->get('title');
			$type = '문서';
			if(!$text)
			{
				$text = getModel('comment')->getComment($target_srl)->get('content');
				if(!$text)
				{
					throw new Rhymix\Framework\Exceptions\InvalidRequest;
				}
				else
				{
					Context::set('individual_type', 'comment');
					$type = '댓글';
				}
			}

		}
		else
		{
			$text = getModel('comment')->getComment($target_srl)->get('content');
			$type = '댓글';
			if(!$text)
			{
				$text = getModel('document')->getDocument($target_srl)->get('title');
				if(!$text)
				{
					throw new Rhymix\Framework\Exceptions\InvalidRequest;
				}
				else
				{
					Context::set('individual_type', 'document');
					$type = '문서';
				}
			}
		}
		
		Context::set('individualData', $output);
		Context::set('text', $text);
		Context::set('type', $type);
		
		$this->setTemplateFile('individualBlock');
	}
}
