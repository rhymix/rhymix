<?php

class SocialloginAdminController extends Sociallogin
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	function procSocialloginAdminSettingApi()
	{
		$args = Context::getRequestVars();

		$config_names = array(
			'twitter_consumer_key',
			'twitter_consumer_secret',
			'facebook_app_id',
			'facebook_app_secret',
			'google_client_id',
			'google_client_secret',
			'naver_client_id',
			'naver_client_secret',
			'kakao_client_id',
			'discord_client_id',
			'discord_client_secret',
			'github_client_id',
			'github_client_secret',
		);

		$config = self::getConfig();

		foreach ($config_names as $val)
		{
			$config->{$val} = $args->{$val};
		}

		getController('module')->insertModuleConfig('sociallogin', $config);

		$this->setMessage('success_updated');

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialloginAdminSettingApi'));
	}

	function procSocialloginAdminSetting()
	{
		$args = Context::getRequestVars();

		$config_names = array(
			'delete_auto_log_record',
			'sns_services',
			'sns_profile',
			'layout_srl',
			'skin',
			'mlayout_srl',
			'mskin',
			'sns_login',
			'default_login',
			'default_signup',
			'delete_member_forbid',
			'sns_follower_count',
			'mail_auth_valid_hour',
			'sns_suspended_account',
			'sns_keep_signed',
			'use_for_phone_auth',
			'linkage_module_srl',
			'linkage_module_target',
		);

		$config = self::getConfig();

		foreach ($config_names as $val)
		{
			$config->{$val} = $args->{$val};
		}

		if(!$args->sns_services)
		{
			$config->sns_services = array();
		}
		
		getController('module')->insertModuleConfig('sociallogin', $config);

		$this->setMessage('success_updated');

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialloginAdminSetting'));
	}

	function procSocialloginAdminDeleteLogRecord()
	{
		$args = new stdClass;

		if (Context::get('date_srl'))
		{
			$args->regdate = Context::get('date_srl');
		}

		$output = executeQuery('sociallogin.deleteLogRecord', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_deleted');

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialloginAdminLogRecord'));
	}
}
