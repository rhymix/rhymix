<?php

class SocialloginView extends Sociallogin
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

		// 사용자 레이아웃
		if (self::getConfig()->layout_srl && $layout_path = getModel('layout')->getLayout(self::getConfig()->layout_srl)->path)
		{
			$this->module_info->layout_srl = self::getConfig()->layout_srl;

			$this->setLayoutPath($layout_path);
		}
	}

	/**
	 * @brief SNS 관리
	 */
	function dispSocialloginSnsManage()
	{
		if (!Context::get('is_logged'))
		{
			return new BaseObject(-1, 'msg_not_logged');
		}

		$oSocialloginModel = getModel('sociallogin');

		foreach (self::getConfig()->sns_services as $key => $val)
		{
			$args = new stdClass;
			$sns_info = $oSocialloginModel->getMemberSns($val);
			
			if ($sns_info->name)
			{
				$args->register = true;
				$args->sns_status = sprintf('<a href="%s" target="_blank">%s</a>', $sns_info->profile_url, $sns_info->name);
			}
			else
			{
				$args->auth_url = $oSocialloginModel->snsAuthUrl($val, 'register');
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
	 * @brief 추가정보 입력
	 */
	function dispSocialloginInputAddInfo()
	{
		if (!$_SESSION['tmp_sociallogin_input_add_info'])
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		$_SESSION['sociallogin_input_add_info'] = $_SESSION['tmp_sociallogin_input_add_info'];

		unset($_SESSION['tmp_sociallogin_input_add_info']);

		$member_config = getModel('member')->getMemberConfig();

		Context::set('member_config', $member_config);
		Context::set('nick_name', $_SESSION['sociallogin_input_add_info']['nick_name']);
		Context::set('email_address', $_SESSION['sociallogin_input_add_info']['email']);

		$signupForm = array();

		// 필수 추가 가입폼 출력
		if (in_array('require_add_info', self::getConfig()->sns_input_add_info))
		{
			foreach ($member_config->signupForm as $no => $formInfo)
			{
				if (!$formInfo->required || $formInfo->isDefaultForm)
				{
					continue;
				}

				$signupForm[] = $formInfo;
			}

			$member_config->signupForm = $signupForm;

			$oMemberAdminView = getAdminView('member');
			$oMemberAdminView->memberConfig = $member_config;

			Context::set('formTags', $oMemberAdminView->_getMemberInputTag());

			getView('member')->addExtraFormValidatorMessage();
		}

		// 아이디 폼
		if (in_array('user_id', self::getConfig()->sns_input_add_info))
		{
			$args = new stdClass;
			$args->required = true;
			$args->name = 'user_id';
			$signupForm[] = $args;
		}

		// 닉네임 폼
		if (in_array('nick_name', self::getConfig()->sns_input_add_info))
		{
			$args = new stdClass;
			$args->required = true;
			$args->name = 'nick_name';
			$signupForm[] = $args;
		}

		// 룰셋 생성
		$this->_createAddInfoRuleset($signupForm, in_array('agreement', self::getConfig()->sns_input_add_info));

		$this->setTemplateFile('input_add_info');
	}

	/**
	 * @brief SNS 연결 진행
	 */
	function dispSocialloginConnectSns()
	{
		if (isCrawler())
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}
		
		if (!($service = Context::get('service')) || !in_array($service, self::getConfig()->sns_services))
		{
			return new BaseObject(-1, 'msg_not_support_service_login');
		}
		if (!$oLibrary = $this->getLibrary($service))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}
		
		if (!$type = Context::get('type'))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if ($type == 'register' && !Context::get('is_logged'))
		{
			return new BaseObject(-1, 'msg_not_logged');
		}
		else if ($type == 'login' && Context::get('is_logged'))
		{
			return new BaseObject(-1, 'already_logged');
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

		unset($_SESSION['sociallogin_input_add_info_data']);

		$_SESSION['sociallogin_auth']['type'] = $type;
		$_SESSION['sociallogin_auth']['mid'] = Context::get('mid');
		$_SESSION['sociallogin_auth']['redirect'] = Context::get('redirect');
		$_SESSION['sociallogin_auth']['state'] = md5(microtime() . mt_rand());

		$this->setRedirectUrl($oLibrary->createAuthUrl($type));

		// 로그 기록
		$info = new stdClass;
		$info->sns = $service;
		$info->type = $type;
		getModel('sociallogin')->logRecord($this->act, $info);
	}

	/**
	 * @brief SNS 프로필
	 */
	function dispSocialloginSnsProfile()
	{
		if (self::getConfig()->sns_profile != 'Y')
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (!Context::get('member_srl'))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (!($member_info = getModel('member')->getMemberInfoByMemberSrl(Context::get('member_srl'))) || !$member_info->member_srl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		Context::set('member_info', $member_info);

		foreach (self::getConfig()->sns_services as $key => $val)
		{
			if (!($sns_info = getModel('sociallogin')->getMemberSns($val, $member_info->member_srl)) || !$sns_info->name)
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

	/**
	 * @brief 필수 추가폼 룰셋 파일 생성
	 */
	function _createAddInfoRuleset($signupForm, $agreement = false)
	{
		$xml_file = 'files/ruleset/insertAddInfoSociallogin.xml';

		$buff = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL . '<ruleset version="1.5.0">' . PHP_EOL . '<customrules>' . PHP_EOL . '</customrules>' . PHP_EOL . '<fields>' . PHP_EOL . '%s' . PHP_EOL . '</fields>' . PHP_EOL . '</ruleset>';

		$fields = array();

		if ($agreement)
		{
			$fields[] = '<field name="accept_agreement" required="true" />';
		}

		foreach ($signupForm as $formInfo)
		{
			if ($formInfo->required || $formInfo->mustRequired)
			{
				if ($formInfo->type == 'tel' || $formInfo->type == 'kr_zip')
				{
					$fields[] = sprintf('<field name="%s[]" required="true" />', $formInfo->name);
				}
				else if ($formInfo->name == 'nick_name')
				{
					$fields[] = sprintf('<field name="%s" required="true" length="2:20" />', $formInfo->name);
				}
				else
				{
					$fields[] = sprintf('<field name="%s" required="true" />', $formInfo->name);
				}
			}
		}

		FileHandler::writeFile($xml_file, sprintf($buff, implode(PHP_EOL, $fields)));

		$validator = new Validator($xml_file);
		$validator->setCacheDir('files/cache');
		$validator->getJsPath();
	}
}
