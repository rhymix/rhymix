<?php

class SocialloginView extends \Rhymix\Modules\Sociallogin\Base
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		Context::set('config', self::getConfig());

		$this->setTemplatePath(sprintf('%sskins/%s/', $this->module_path, self::getConfig()->skin));

		//HACK: 현재는 AddJsFile을 유지시킨다. 추후 loadFile이나 해당 메서드가 변경되면 그때 수정.
		Context::addJsFile($this->module_path . 'tpl/js/sociallogin.js');
	}

	/**
	 * @brief SNS 관리
	 */
	function dispSocialloginSnsManage()
	{
		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exception('msg_not_logged');
		}

		foreach (self::getConfig()->sns_services as $key => $val)
		{
			$args = new stdClass;
			$sns_info = SocialloginModel::getMemberSnsByService($val);
			
			if ($sns_info->name)
			{
				$args->register = true;
				$args->sns_status = sprintf('<a href="%s" target="_blank">%s</a>', $sns_info->profile_url, $sns_info->name);
			}
			else
			{
				$args->auth_url = SocialloginModel::snsAuthUrl($val, 'register');
				$args->sns_status = Context::getLang('status_sns_no_register');
			}

			$args->service = $val;
			$args->linkage = $sns_info->linkage;

			$sns_services[$key] = $args;
		}
		Context::set('sns_services', $sns_services);

		$this->setTemplateFile('sns_manage');
	}

	/**
	 * @brief SNS 연결 진행
	 */
	function dispSocialloginConnectSns()
	{
		if (isCrawler())
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest();
		}
		
		$service = Context::get('service');
		if (!$service || !in_array($service, self::getConfig()->sns_services))
		{
			throw new Rhymix\Framework\Exception('msg_not_support_service_login');
		}
		if (!$oDriver = $this->getDriver($service))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest();
		}
		
		if (!$type = Context::get('type'))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest();
		}

		if ($type == 'register' && !Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exception('msg_not_logged');
		}
		else if ($type == 'login' && Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exception('already_logged');
		}

		
		// 인증 메일 유효 시간
		if (self::getConfig()->mail_auth_valid_hour)
		{
			$args = new stdClass;
			$args->list_count = 5;
			$args->regdate_less = date('YmdHis', strtotime(sprintf('-%s hour', self::getConfig()->mail_auth_valid_hour)));
			$output = executeQueryArray('sociallogin.getAuthMailLess', $args);

			if ($output->toBool())
			{
				$oMemberController = getController('member');

				foreach ($output->data as $key => $val)
				{
					if (!$val->member_srl)
					{
						continue;
					}

					$oMemberController->deleteMember($val->member_srl);
				}
			}
		}

		$_SESSION['sociallogin_auth']['type'] = $type;
		$_SESSION['sociallogin_auth']['mid'] = Context::get('mid');
		$_SESSION['sociallogin_auth']['redirect'] = Context::get('redirect');
		$_SESSION['sociallogin_auth']['state'] = md5(microtime() . mt_rand());

		$this->setRedirectUrl($oDriver->createAuthUrl($type));

		// 로그 기록
		$info = new stdClass;
		$info->sns = $service;
		$info->type = $type;
		SocialloginModel::logRecord($this->act, $info);
	}

	/**
	 * @brief SNS 프로필
	 */
	function dispSocialloginSnsProfile()
	{
		if (self::getConfig()->sns_profile != 'Y')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest();
		}

		if (!Context::get('member_srl'))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest();
		}

		if (!($member_info = memberModel::getMemberInfoByMemberSrl(Context::get('member_srl'))) || !$member_info->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest();
		}

		Context::set('member_info', $member_info);

		foreach (self::getConfig()->sns_services as $key => $val)
		{
			if (!($sns_info = SocialloginModel::getMemberSnsByService($val, $member_info->member_srl)) || !$sns_info->name)
			{
				continue;
			}

			$args = new stdClass;
			$args->profile_name = $sns_info->name;
			$args->profile_url = $sns_info->profile_url;
			$args->service = $val;

			$sns_services[$key] = $args;
		}

		Context::set('sns_services', $sns_services);

		$this->setTemplateFile('sns_profile');
	}
}
