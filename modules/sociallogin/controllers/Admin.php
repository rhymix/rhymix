<?php

namespace Rhymix\Modules\Sociallogin\Controllers;

use Context;
use FileHandler;
use Rhymix\Framework\DB;
use Rhymix\Modules\Sociallogin\Base;

class Admin extends Base
{
	function init()
	{
		Context::set('config', self::getConfig());

		if ($this->module_config->delete_auto_log_record)
		{
			$args = new \stdClass;
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

		$args = new \stdClass;

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

		
		$args = new \stdClass;

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
	
	function dispSocialloginAdminMigration()
	{
		$oDB = DB::getInstance();

		Context::set('exists_sociallogin', $oDB->isTableExists('sociallogin'));
		Context::set('exists_socialxe', $oDB->isTableExists('socialxe'));
		$this->setTemplateFile('migration');
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

		$securityFile = self::_getAppleSecurityFile();
		
		if(is_uploaded_file($args->apple_file['tmp_name']))
		{
			$random = \Rhymix\Framework\Security::getRandom();
			
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
		$args = new \stdClass;

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
		$oDB = DB::getInstance();

		if(!$oDB->isTableExists('sociallogin'))
		{
			throw new \Rhymix\Framework\Exception('msg_not_exists_table_sociallogin');
		}

		if(!$oDB->isTableExists('socialxe'))
		{
			throw new \Rhymix\Framework\Exception('msg_not_exists_table_socialxe');
		}

		$source = 'INSERT INTO sociallogin (`member_srl`, `service`, `id`, `name`, `email`,`profile_image`, `profile_url`, `profile_info`, `access_token`, `refresh_token`, `linkage`, `regdate`)
			SELECT `member_srl`, `service`, `id`, `name`, `email`,`profile_image`, `profile_url`, `profile_info`, `access_token`, `refresh_token`, `linkage`, `regdate`
			FROM socialxe';
		$oDB->query($source);
		
		$this->setMessage('success_updated');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialloginAdminMigration'));
	}
	
	/**
	 * 애플 보안 설정 파일의 위치를 가져옵니다.
	 * @return false|string
	 */
	protected static function _getAppleSecurityFile()
	{
		$config = self::getConfig();
		
		if(!\Rhymix\Framework\Session::isAdmin())
		{
			return false;
		}

		if($config->apple_file_path && FileHandler::exists($config->apple_file_path))
		{
			return $config->apple_file_path;
		}
		
		return false;
	}
}
