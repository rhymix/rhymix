<?php

class SocialloginAdminView extends Sociallogin
{
	function init()
	{
		Context::set('config', self::getConfig());

		if ($this->module_config->delete_auto_log_record)
		{
			$args = new stdClass;
			$args->regdate_less = date('YmdHis', strtotime(sprintf('-%d day', $this->module_config->delete_auto_log_record)));
			executeQuery('sociallogin.deleteLogRecordLess', $args);
		}

		$this->setTemplatePath($this->module_path . 'tpl');

		//TODO(BJRambo): check again.
		Context::addJsFile($this->module_path . 'tpl/js/sociallogin_admin.js');
	}

	function dispSocialloginAdminSettingApi()
	{
		Context::set('can_be_use_twitter', version_compare(PHP_VERSION, '7.3.0', '>='));
		$this->setTemplateFile('api_setting');
	}

	function dispSocialloginAdminSetting()
	{
		Context::set('layout_list', getModel('layout')->getLayoutList());
		Context::set('mlayout_list', getModel('layout')->getLayoutList(0, 'M'));

		Context::set('skin_list', getModel('module')->getSkins($this->module_path));
		Context::set('mskin_list', getModel('module')->getSkins($this->module_path, 'm.skins'));

		Context::set('default_services', self::$default_services);
		Context::set('can_be_use_twitter', version_compare(PHP_VERSION, '7.3.0', '>='));

		$this->setTemplateFile('setting');
	}

	function dispSocialloginAdminLogRecord()
	{
		Context::set('category_list', array(
			'auth_request',
			'register',
			'sns_clear',
			'login',
			'linkage',
			'delete_member',
			'unknown'
		));

		$search_option = array(
			'nick_name',
			getModel('module')->getModuleConfig('member')->identifier,
			'content',
			'ipaddress'
		);
		Context::set('search_option', $search_option);

		$args = new stdClass;

		if (($search_target = trim(Context::get('search_target'))) && in_array($search_target, $search_option))
		{
			$args->$search_target = str_replace(' ', '%', trim(Context::get('search_keyword')));
		}

		$args->page = Context::get('page');
		$args->category = Context::get('search_category');

		$output = executeQuery('sociallogin.getLogRecordList', $args);

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('log_record_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('log_record');
	}

	function dispSocialloginAdminSnsList()
	{
		Context::set('sns_services', self::getConfig()->sns_services);

		$search_option = array('nick_name', getModel('module')->getModuleConfig('member')->identifier);
		Context::set('search_option', $search_option);

		$args = new stdClass;

		if (($search_target = trim(Context::get('search_target'))) && in_array($search_target, $search_option))
		{
			$args->$search_target = str_replace(' ', '%', trim(Context::get('search_keyword')));
		}

		$args->page = Context::get('page');
		$output = executeQuery('sociallogin.getMemberSnsList', $args);

		if ($output->data)
		{
			$oSocialloginModel = getModel('sociallogin');

			foreach ($output->data as $key => $val)
			{
				$val->service = array();

				foreach (self::getConfig()->sns_services as $key2 => $val2)
				{
					if (($sns_info = $oSocialloginModel->getMemberSns($val2, $val->member_srl)) && $sns_info->name)
					{
						$val->service[$val2] = sprintf('<a href="%s" target="_blank">%s</a>', $sns_info->profile_url, $sns_info->name);
					}
					else
					{
						$val->service[$val2] = Context::getLang('status_sns_no_register');
					}
				}

				$output->data[$key] = $val;
			}
		}

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('sns_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('sns_list');
	}
}
