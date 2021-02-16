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

		Context::set('sns_twitter_apple_cond', version_compare(PHP_VERSION, '7.4.0', '>='));

		$this->setTemplatePath($this->module_path . 'tpl');
		Context::addJsFile($this->module_path . 'tpl/js/sociallogin_admin.js');
	}

	function dispSocialloginAdminSettingApi()
	{
		$this->setTemplateFile('api_setting');
	}

	function dispSocialloginAdminSetting()
	{
		Context::set('layout_list', getModel('layout')->getLayoutList());
		Context::set('mlayout_list', getModel('layout')->getLayoutList(0, 'M'));
		Context::set('skin_list', getModel('module')->getSkins($this->module_path));
		Context::set('mskin_list', getModel('module')->getSkins($this->module_path, 'm.skins'));
		Context::set('default_services', self::$default_services);

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
		$search_option = array('nick_name', getModel('module')->getModuleConfig('member')->identifier);
		Context::set('search_option', $search_option);

		
		$args = new stdClass;

		if (($search_target = trim(Context::get('search_target'))) && in_array($search_target, $search_option))
		{
			$args->$search_target = str_replace(' ', '%', trim(Context::get('search_keyword')));
		}
		
		if(Context::get('member_srl'))
		{
			$args->member_srl = Context::get('member_srl');
		}
		
		if(Context::get('service'))
		{
			$args->service = Context::get('service');
		}
		
		$args->page = Context::get('page');
		
		$output = executeQuery('sociallogin.getMemberSnsList', $args);
		
		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('sns_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('sns_list');
	}
}
