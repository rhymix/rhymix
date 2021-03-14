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
			'apple_client_id',
			'apple_team_id',
			'apple_file_key',
		);

		$config = self::getConfig();

		foreach ($config_names as $val)
		{
			$config->{$val} = $args->{$val};
		}

		$securityFile = SocialloginAdminModel::getAppleSecurityFile();
		
		if(is_uploaded_file($args->apple_file['tmp_name']))
		{
			$random = Rhymix\Framework\Security::getRandom();
			
			$file_dir = RX_BASEDIR . 'files/social/apple/';
			if(!FileHandler::isDir($file_dir))
			{
				FileHandler::makeDir($file_dir);
			}
			$fileName = $file_dir . $random . '.p8';

			$uploadBool = move_uploaded_file($args->apple_file['tmp_name'], $fileName);
			if($uploadBool)
			{
				// 이미 잇던 파일을 변경하는 경우 기존 파일을 삭제하고 새롭게 저장.
				if($securityFile)
				{
					FileHandler::removeFile($config->apple_file_path);
				}
				
				$config->apple_file_path = $fileName;
			}
		}

		if($args->delete_apple_file == 'Y')
		{
			if($securityFile)
			{
				$deleteBool = FileHandler::removeFile($config->apple_file_path);
				if($deleteBool)
				{
					$config->apple_file_path = null;
				}
			}
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
			'sns_share_on_write',
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
	
	function procSocialloginAdminMigration()
	{
		$oDB = Rhymix\Framework\DB::getInstance();

		if(!$oDB->isTableExists('sociallogin'))
		{
			throw new \Rhymix\Framework\Exception('msg_not_exists_table_sociallogin');
		}

		if(!$oDB->isTableExists('socialxe'))
		{
			throw new \Rhymix\Framework\Exception('msg_not_exists_table_socialxe');
		}

		$source = 'INSERT INTO sociallogin (`member_srl`, `service`, `id`, `name`, `email`,`profile_image`, `profile_url`, `profile_info`, `access_token`, `refresh_token`, `linkage`, `regdate`) SELECT `member_srl`, `service`, `id`, `name`, `email`,`profile_image`, `profile_url`, `profile_info`, `access_token`, `refresh_token`, `linkage`, `regdate` FROM socialxe';
		$oDB->query($source);
		
		$this->setMessage('success_updated');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialloginAdminMigration'));
	}
}
