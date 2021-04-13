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
		if (isset($config->layout_srl) && $config->layout_srl)
		{
			$layout_info = $oLayoutModel->getLayout($config->layout_srl);
			if ($layout_info)
			{
				$this->module_info->layout_srl = $config->layout_srl;
				$this->setLayoutPath($layout_info->path);
			}
		}
	}

	function dispNcenterliteNotifyList()
	{
		$oNcenterliteModel = getModel('ncenterlite');

		$output = $oNcenterliteModel->getMyNotifyList($this->user->member_srl, 1, null, true);

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('ncenterlite_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFileOrDefault('NotifyList');
	}

	function dispNcenterliteUserConfig()
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if($config->user_notify_setting != 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_not_use_user_setting');
		}

		if(!Rhymix\Framework\Session::getMemberSrl())
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$member_srl = Context::get('member_srl');
		if($this->user->isAdmin() && $member_srl)
		{
			$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl);
		}
		if(!$this->user->isAdmin() && $member_srl)
		{
			if($member_srl != $this->user->member_srl)
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted('ncenterlite_stop_no_permission_other_user');
			}
		}
		
		$user_selected = [];
		$user_config = NcenterliteModel::getUserConfig($member_srl) ?: new stdClass;
		$notify_types = NcenterliteModel::getUserSetNotifyTypes();
		foreach($notify_types as $notify_type => $notify_srl)
		{
			$user_config->{$notify_type . '_notify'} = (isset($user_config->{$notify_type}) && $user_config->{$notify_type}) ? 'Y' : 'N';
			$user_selected[$notify_type] = [];
			foreach (['web', 'mail', 'sms', 'push'] as $item)
			{
				$available = isset($config->use[$notify_type][$item]) && $config->use[$notify_type][$item] !== 'N';
				$selected = !is_array($user_config->{$notify_type} ?? null) || in_array($item, $user_config->{$notify_type});
				$user_selected[$notify_type][$item] = new stdClass();
				$user_selected[$notify_type][$item]->available = $available;
				$user_selected[$notify_type][$item]->selected = $selected;
			}
		}
		
		Context::set('member_info', $member_info);
		Context::set('notify_types', $notify_types);
		Context::set('user_config', $user_config);
		Context::set('user_selected', $user_selected);
		Context::set('module_config', NcenterliteModel::getConfig());
		Context::set('sms_available', Rhymix\Framework\SMS::getDefaultDriver()->getName() !== 'Dummy');
		Context::set('push_available', count(Rhymix\Framework\Config::get('push.types') ?? []) > 0);

		$this->setTemplateFileOrDefault('userconfig');
	}

	/**
	 * Get to unsubscribe list.
	 * @throws \Rhymix\Framework\Exception
	 */
	function dispNcenterliteUnsubscribeList()
	{
		/** @var ncenterliteModel $oNcenterliteModel */
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		
		if($config->unsubscribe !== 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_unsubscribe_block_not_support');
		}
		
		if(!Rhymix\Framework\Session::getMemberSrl())
		{
			throw new Rhymix\Framework\Exceptions\MustLogin;
		}

		$member_srl = Context::get('member_srl');
		if(!$member_srl)
		{
			$member_srl = $this->user->member_srl;
		}
		if(!$this->user->isAdmin() && intval($this->user->member_srl) !== intval($member_srl))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted('msg_unsubscribe_not_permission');
		}
		
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_count = '20';
		$args->page_count = '10';
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.getUnsubscribeList', $args);

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('unsubscribe_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		
		$this->setTemplateFileOrDefault('unsubscribeList');
	}
	
	function dispNcenterliteInsertUnsubscribe()
	{
		$this->setLayoutFile('popup_layout');
		
		/** @var ncenterliteModel $oNcenterliteModel */
		$oNcenterliteModel = getModel('ncenterlite');
		$target_srl = Context::get('target_srl');
		$unsubscribe_srl = Context::get('unsubscribe_srl');
		$unsubscribe_type = Context::get('unsubscribe_type');
		
		$member_srl = Context::get('member_srl');
		
		if(!$member_srl)
		{
			$member_srl = $this->user->member_srl;
		}
		
		if($this->user->is_admin !== 'Y' && intval($member_srl) !== intval($this->user->member_srl))
		{
			throw new \Rhymix\Framework\Exception('msg_invalid_request');
		}
		
		if($unsubscribe_srl)
		{
			$output = $oNcenterliteModel->getUserUnsubscribeConfigByUnsubscribeSrl($unsubscribe_srl);
		}
		else
		{
			$output = $oNcenterliteModel->getUserUnsubscribeConfigByTargetSrl($target_srl, $member_srl);
		}
		
		if((!$target_srl || !$unsubscribe_type) && empty($output))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		if($unsubscribe_type == 'document')
		{
			$text = getModel('document')->getDocument($target_srl)->getTitleText();
			$type = lang('document');
			if(!$text)
			{
				$text = getModel('comment')->getComment($target_srl)->getContentPlainText();
				if(!$text)
				{
					throw new Rhymix\Framework\Exceptions\InvalidRequest;
				}
				else
				{
					Context::set('unsubscribe_type', 'comment');
					$type = lang('comment');
				}
			}

		}
		else
		{
			$text = getModel('comment')->getComment($target_srl)->getContentPlainText();
			$type = lang('comment');
			if(!$text)
			{
				$text = getModel('document')->getDocument($target_srl)->getTitleText();
				if(!$text)
				{
					throw new Rhymix\Framework\Exceptions\InvalidRequest;
				}
				else
				{
					Context::set('unsubscribe_type', 'document');
					$type = lang('document');
				}
			}
		}
		
		Context::set('unsubscribeData', $output);
		Context::set('text', $text);
		Context::set('type', $type);
		
		$this->setTemplateFileOrDefault('unsubscribe');
	}

	public function setTemplateFileOrDefault($filename)
	{
		$path = $this->getTemplatePath();
		if (!file_exists($path . $filename . '.html'))
		{
			$this->setTemplatePath(dirname($path) . '/default/');
		}
		$this->setTemplateFile($filename);
	}
}
