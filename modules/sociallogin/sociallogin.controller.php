<?php
class SocialloginController extends Sociallogin
{
	function init()
	{
	}

	/**
	 * @brief 이메일 확인
	 */
	function procSocialloginConfirmMail()
	{
		if (!$_SESSION['sociallogin_confirm_email'])
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (!$email_address = Context::get('email_address'))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (getModel('member')->getMemberSrlByEmailAddress($email_address))
		{
			$error = 'msg_exists_email_address';
		}

		$saved = $_SESSION['sociallogin_confirm_email'];
		$mid = $_SESSION['sociallogin_current']['mid'];
		$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');

		if (!$error)
		{
			if (!$oLibrary = $this->getLibrary($saved['service']))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}

			$oLibrary->set($saved);
			$oLibrary->setEmail($email_address);

			$output = $this->LoginSns($oLibrary);
			if (!$output->toBool())
			{
				$error = $output->getMessage();
				$errorCode = $output->getError();
			}
		}

		// 오류
		if ($error)
		{
			$msg = $error;

			if ($errorCode == -12)
			{
				Context::set('xe_validator_id', '');
				$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispMemberLoginForm');
			}
			else
			{
				$_SESSION['tmp_sociallogin_confirm_email'] = $_SESSION['sociallogin_confirm_email'];

				$this->setError(-1);
				$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispSocialloginConfirmMail');
			}
		}

		unset($_SESSION['sociallogin_confirm_email']);

		// 로그 기록
		$info = new stdClass;
		$info->msg = $msg;
		$info->sns = $saved['service'];
		getModel('sociallogin')->logRecord($this->act, $info);

		if ($msg)
		{
			$this->setMessage($msg);
		}

		if (!$this->getRedirectUrl())
		{
			$this->setRedirectUrl($redirect_url);
		}
	}

	/**
	 * @brief 추가정보 입력
	 */
	function procSocialloginInputAddInfo()
	{
		if (!$_SESSION['sociallogin_input_add_info'])
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		$saved = $_SESSION['sociallogin_input_add_info'];
		$mid = $_SESSION['sociallogin_current']['mid'];
		$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');

		$signupForm = array();

		// 필수 추가 가입폼
		if (in_array('require_add_info', $this->config->sns_input_add_info))
		{
			foreach (getModel('member')->getMemberConfig()->signupForm as $no => $formInfo)
			{
				if (!$formInfo->required || $formInfo->isDefaultForm)
				{
					continue;
				}

				$signupForm[] = $formInfo->name;
			}
		}

		// 아이디 폼
		if (in_array('user_id', $this->config->sns_input_add_info))
		{
			$signupForm[] = 'user_id';

			if (getModel('member')->getMemberSrlByUserID(Context::get('user_id')))
			{
				$error = 'msg_exists_user_id';
			}
		}

		// 닉네임 폼
		if (in_array('nick_name', $this->config->sns_input_add_info))
		{
			$signupForm[] = 'nick_name';

			if (getModel('member')->getMemberSrlByNickName(Context::get('nick_name')))
			{
				$error = 'msg_exists_nick_name';
			}
		}

		// 약관 동의
		if (in_array('agreement', $this->config->sns_input_add_info))
		{
			$signupForm[] = 'accept_agreement';
		}

		// 추가 정보 저장
		$add_data = array();
		foreach ($signupForm as $val)
		{
			$add_data[$val] = Context::get($val);
		}

		if (!$error)
		{
			if (!$oLibrary = $this->getLibrary($saved['service']))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}

			$_SESSION['sociallogin_input_add_info_data'] = $add_data;

			$oLibrary->set($saved);
			$output = $this->LoginSns($oLibrary);

			if (!$output->toBool())
			{
				$error = $output->getMessage();
			}
		}

		// 오류
		if ($error)
		{
			$msg = $error;
			$this->setError(-1);
			$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispSocialloginInputAddInfo');

			$_SESSION['tmp_sociallogin_input_add_info'] = $_SESSION['sociallogin_input_add_info'];
		}

		unset($_SESSION['sociallogin_input_add_info']);

		// 로그 기록
		$info = new stdClass;
		$info->msg = $msg;
		$info->sns = $saved['service'];
		getModel('sociallogin')->logRecord($this->act, $info);

		if ($msg)
		{
			$this->setMessage($msg);
		}

		if (!$this->getRedirectUrl())
		{
			$this->setRedirectUrl($redirect_url);
		}
	}

	/**
	 * @brief SNS 해제
	 **/
	function procSocialloginSnsClear()
	{
		if (!Context::get('is_logged'))
		{
			return new BaseObject(-1, 'msg_not_logged');
		}

		if (!$service = Context::get('service'))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (!$oLibrary = $this->getLibrary($service))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (!($sns_info = getModel('sociallogin')->getMemberSns($service)) || !$sns_info->name)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if ($this->config->sns_login == 'Y' && $this->config->default_signup != 'Y')
		{
			$sns_list = getModel('sociallogin')->getMemberSns();

			if (!is_array($sns_list))
			{
				$sns_list = array($sns_list);
			}

			if (count($sns_list) < 2)
			{
				return new BaseObject(-1, 'msg_not_clear_sns_one');
			}
		}

		$args = new stdClass;
		$args->service = $service;
		$args->member_srl = Context::get('logged_info')->member_srl;

		$output = executeQuery('sociallogin.deleteMemberSns', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		// 토큰 넣기
		getModel('sociallogin')->setAvailableAccessToken($oLibrary, $sns_info, false);

		// 토큰 파기
		$oLibrary->revokeToken();

		// 로그 기록
		$info = new stdClass;
		$info->sns = $service;
		getModel('sociallogin')->logRecord($this->act, $info);

		$this->setMessage('msg_success_sns_register_clear');

		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispSocialloginSnsManage'));
	}

	/**
	 * @brief SNS 연동설정
	 **/
	function procSocialloginSnsLinkage()
	{
		if (!Context::get('is_logged'))
		{
			return new BaseObject(-1, 'msg_not_logged');
		}

		if (!$service = Context::get('service'))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (!$oLibrary = $this->getLibrary($service))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (!($sns_info = getModel('sociallogin')->getMemberSns($service)) || !$sns_info->name)
		{
			return new BaseObject(-1, 'msg_not_linkage_sns_info');
		}

		// 토큰 넣기
		getModel('sociallogin')->setAvailableAccessToken($oLibrary, $sns_info);

		// 연동 체크
		if (($check = $oLibrary->checkLinkage()) && $check instanceof Object && !$check->toBool() && $sns_info->linkage != 'Y')
		{
			return $check;
		}

		$args = new stdClass;
		$args->service = $service;
		$args->linkage = ($sns_info->linkage == 'Y') ? 'N' : 'Y';
		$args->member_srl = Context::get('logged_info')->member_srl;

		$output = executeQuery('sociallogin.updateMemberSns', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		// 로그 기록
		$info = new stdClass;
		$info->sns = $service;
		$info->linkage = $args->linkage;
		getModel('sociallogin')->logRecord($this->act, $info);

		$this->setMessage('msg_success_linkage_sns');

		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispSocialloginSnsManage'));
	}

	/**
	 * @brief Callback
	 **/
	function procSocialloginCallback()
	{
		// 서비스 체크
		if (!($service = Context::get('service')) || !in_array($service, $this->config->sns_services))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		// 라이브러리 체크
		if (!$oLibrary = $this->getLibrary($service))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		// 인증 세션 체크
		if (!$_SESSION['sociallogin_auth']['state'])
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (!$type = $_SESSION['sociallogin_auth']['type'])
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		$_SESSION['sociallogin_current']['mid'] = $_SESSION['sociallogin_auth']['mid'];
		$redirect_url = $_SESSION['sociallogin_auth']['redirect'];
		$redirect_url = $redirect_url ? Context::getRequestUri() . '?' . $redirect_url : Context::getRequestUri();

		// 인증
		$output = $oLibrary->authenticate();
		if ($output instanceof Object && !$output->toBool())
		{
			$error = $output->getMessage();
		}

		// 인증 세션 제거
		unset($_SESSION['sociallogin_auth']);

		// 로딩
		if (!$error)
		{
			$output = $oLibrary->loading();
			if ($output instanceof Object && !$output->toBool())
			{
				$error = $output->getMessage();

				// 오류시 토큰 파기 (롤백)
				$oLibrary->revokeToken();
			}
		}

		// 등록 처리
		if (!$error)
		{
			if ($type == 'register')
			{
				$msg = 'msg_success_sns_register';

				$output = $this->registerSns($oLibrary);
				if (!$output->toBool())
				{
					$error = $output->getMessage();
				}
			}
			else if ($type == 'login')
			{
				$output = $this->LoginSns($oLibrary);
				if (!$output->toBool())
				{
					$error = $output->getMessage();
				}

				// 로그인 후 페이지 이동 (회원 설정 참조)
				$redirect_url = getModel('module')->getModuleConfig('member')->after_login_url ?: getNotEncodedUrl('', 'mid', $_SESSION['sociallogin_current']['mid'], 'act', '');
			}
		}

		// 오류
		if ($error)
		{
			$msg = $error;
			$this->setError(-1);

			if ($type == 'login')
			{
				$redirect_url = getNotEncodedUrl('', 'mid', $_SESSION['sociallogin_current']['mid'], 'act', 'dispMemberLoginForm');
			}
		}

		// 로그 기록
		$info = new stdClass;
		$info->msg = $msg;
		$info->type = $type;
		$info->sns = $service;
		getModel('sociallogin')->logRecord($this->act, $info);

		if ($msg)
		{
			$this->setMessage($msg);
		}

		if (!$this->getRedirectUrl())
		{
			$this->setRedirectUrl($redirect_url);
		}
	}

	/**
	 * @brief module Handler 트리거
	 **/
	function triggerModuleHandler(&$obj)
	{
		// SNS 로그인 정보 추가
		if (Context::get('is_logged') && $_SESSION['sns_login'])
		{
			$logged_info = Context::get('logged_info');
			$logged_info->sns_login = $_SESSION['sns_login'];
			Context::set('logged_info', $logged_info);
		}

		if ($this->config->default_signup != 'Y' && $this->config->sns_login == 'Y' && (Context::get('act') != 'dispMemberLoginForm' || Context::get('mode') == 'default'))
		{
			if (Context::get('module') == 'admin')
			{
				Context::addHtmlHeader('<style>.signin .login-footer, #access .login-body, #access .login-footer{display:none;}</style>');
			}
			else
			{
				Context::addHtmlHeader('<style>.signin .login-footer, #access .login-footer{display:none;}</style>');
			}
		}

		if (!Context::get('is_logged'))
		{
			return new BaseObject();
		}

		getController('member')->addMemberMenu('dispSocialloginSnsManage', 'sns_manage');

		if (!in_array(Context::get('act'), array('dispMemberModifyInfo', 'dispMemberModifyEmailAddress')))
		{
			return new BaseObject();
		}

		if (getModel('sociallogin')->memberUserSns())
		{
			if (Context::get('act') == 'dispMemberModifyInfo' || Context::get('act') == 'dispMemberModifyEmailAddress')
			{
				$_SESSION['rechecked_password_step'] = 'VALIDATE_PASSWORD';
			}
		}

		return new BaseObject();
	}

	/**
	 * @brief module Object Before 트리거
	 **/
	function triggerModuleObjectBefore(&$obj)
	{
		if ($this->config->sns_login != 'Y')
		{
			return new BaseObject();
		}

		$member_act = array(
			'dispMemberSignUpForm',
			'dispMemberFindAccount',
			'procMemberInsert',
			'procMemberFindAccount',
			'procMemberFindAccountByQuestion'
		);

		if ($this->config->default_signup != 'Y' && in_array($obj->act, $member_act))
		{
			return new BaseObject(-1, 'msg_use_sns_login');
		}

		if ($this->config->default_login != 'Y' && $obj->act == 'procMemberLogin')
		{
			return new BaseObject(-1, 'msg_use_sns_login');
		}

		if (!Context::get('is_logged'))
		{
			return new BaseObject();
		}

		if (!in_array($obj->act, array(
			'dispMemberModifyPassword',
			'procMemberModifyPassword',
			'procMemberLeave',
			'dispMemberLeave'
		)))
		{
			return new BaseObject();
		}

		if (getModel('sociallogin')->memberUserSns())
		{
			if ((($obj->act == 'dispMemberModifyPassword' || $obj->act == 'procMemberModifyPassword') && (!getModel('sociallogin')->memberUserPrev() || $this->config->default_login != 'Y')) || ($this->config->delete_member_forbid == 'Y' && ($obj->act == 'procMemberLeave' || $obj->act == 'dispMemberLeave')))
			{
				if ($obj->act == 'dispMemberModifyPassword')
				{
					$obj->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', ''));
				}
				else
				{
					return new BaseObject(-1, 'msg_invalid_request');
				}
			}
			else if ($obj->act == 'procMemberLeave')
			{
				$output = getController('member')->deleteMember(Context::get('logged_info')->member_srl);
				if (!$output->toBool())
				{
					return $output;
				}

				getController('member')->destroySessionInfo();

				$obj->setMessage('success_delete_member_info');

				$obj->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', ''));
			}
		}

		return new BaseObject();
	}

	/**
	 * @brief module Object After 트리거
	 **/
	function triggerModuleObjectAfter(&$obj)
	{
		if ($this->config->sns_login != 'Y')
		{
			return new BaseObject();
		}

		if (Mobile::isFromMobilePhone())
		{
			$template_path = sprintf('%sm.skins/%s/', $this->module_path, $this->config->mskin);
		}
		else
		{
			$template_path = sprintf('%sskins/%s/', $this->module_path, $this->config->skin);
		}

		// 로그인 페이지
		if ($obj->act == 'dispMemberLoginForm' && (Context::get('mode') != 'default' || $this->config->default_login != 'Y'))
		{
			if (Context::get('is_logged'))
			{
				$obj->setRedirectUrl(getNotEncodedUrl('act', ''));

				return new BaseObject();
			}

			Context::set('config', $this->config);

			$obj->setTemplatePath($template_path);
			$obj->setTemplateFile('sns_login');

			foreach ($this->config->sns_services as $key => $val)
			{
				$args = new stdClass;
				$args->auth_url = getModel('sociallogin')->snsAuthUrl($val, 'login');
				$args->service = $val;
				$sns_services[$key] = $args;
			}

			Context::set('sns_services', $sns_services);
		}
		// 인증 메일 재발송
		else if ($obj->act == 'procMemberResetAuthMail')
		{
			$obj->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispMemberLoginForm'));
		}

		if (!Context::get('is_logged'))
		{
			return new BaseObject();
		}

		if (!in_array($obj->act, array('dispMemberAdminInsert', 'dispMemberModifyInfo', 'dispMemberLeave')))
		{
			return new BaseObject();
		}

		if (getModel('sociallogin')->memberUserSns())
		{
			if ($obj->act == 'dispMemberLeave')
			{
				$obj->setTemplatePath($template_path);
				$obj->setTemplateFile('delete_member');
			}
			// 비밀번호 질문 제거
			else if ($obj->act == 'dispMemberModifyInfo')
			{
				$new_formTags = array();

				foreach (Context::get('formTags') as $no => $formInfo)
				{
					if ($formInfo->name == 'find_account_question')
					{
						continue;
					}

					$new_formTags[] = $formInfo;
				}

				Context::set('formTags', $new_formTags);
			}
		}

		// 관리자 회원정보 수정 SNS 항목 삽입
		if ($obj->act == 'dispMemberAdminInsert' && $member_srl = Context::get('member_srl'))
		{
			if (getModel('sociallogin')->memberUserSns($member_srl))
			{
				$snsTag = array();

				foreach ($this->config->sns_services as $key => $val)
				{
					if (!($sns_info = getModel('sociallogin')->getMemberSns($val, $member_srl)) || !$sns_info->name)
					{
						continue;
					}

					$snsTag[] = sprintf('[%s] <a href="%s" target="_blank">%s</a>', ucwords($val), $sns_info->profile_url, $sns_info->name);
				}

				$snsTag = implode(', ', $snsTag);

				$new_formTags = array();

				foreach (Context::get('formTags') as $no => $formInfo)
				{
					if ($formInfo->name == 'find_account_question')
					{
						$formInfo->name = 'sns_info';
						$formInfo->title = 'SNS';
						$formInfo->type = '';
						$formInfo->inputTag = $snsTag;
					}

					$new_formTags[] = $formInfo;
				}

				Context::set('formTags', $new_formTags);
			}
		}

		return new BaseObject();
	}

	/**
	 * @brief display 트리거
	 **/
	function triggerDisplay(&$output)
	{
		if ($this->config->sns_login != 'Y')
		{
			return new BaseObject();
		}

		if (!Context::get('is_logged'))
		{
			return new BaseObject();
		}

		if (!in_array(Context::get('act'), array('dispMemberInfo', 'dispMemberModifyInfo', 'dispMemberAdminInsert')))
		{
			return new BaseObject();
		}

		if (getModel('sociallogin')->memberUserSns())
		{
			if (Context::get('act') == 'dispMemberInfo')
			{
				if (!getModel('sociallogin')->memberUserPrev() || $this->config->default_login != 'Y')
				{
					$output = preg_replace('/\<a[^\>]*?dispMemberModifyPassword[^\>]*?\>[^\<]*?\<\/a\>/is', '', $output);
				}

				if ($this->config->delete_member_forbid == 'Y')
				{
					$output = preg_replace('/(\<a[^\>]*?dispMemberLeave[^\>]*?\>)[^\<]*?(\<\/a\>)/is', '', $output);
				}
				else
				{
					$output = preg_replace('/(\<a[^\>]*?dispMemberLeave[^\>]*?\>)[^\<]*?(\<\/a\>)/is', sprintf('$1%s$2', Context::getLang('delete_member_info')), $output);
				}
			}
			// 비밀번호 질문 제거
			else if (Context::get('act') == 'dispMemberModifyInfo')
			{
				$output = preg_replace('/(\<input[^\>]*?value\=\"procMemberModifyInfo\"[^\>]*?\>)/is', sprintf('$1<input type="hidden" name="find_account_question" value="1" /><input type="hidden" name="find_account_answer" value="%s" />', cut_str(md5(date('YmdHis')), 13, '')), $output);
			}
		}

		// 관리자 회원정보 수정
		if (Context::get('act') == 'dispMemberAdminInsert' && Context::get('member_srl'))
		{
			if (getModel('sociallogin')->memberUserSns(Context::get('member_srl')))
			{
				$output = preg_replace('/(\<input[^\>]*?value\=\"procMemberAdminInsert\"[^\>]*?\>)/is', sprintf('$1<input type="hidden" name="find_account_question" value="1" /><input type="hidden" name="find_account_answer" value="%s" />', cut_str(md5(date('YmdHis')), 13, '')), $output);
			}
		}

		return new BaseObject();
	}

	/**
	 * @brief 문서등록 트리거
	 **/
	function triggerInsertDocumentAfter($obj)
	{
		if (!Context::get('is_logged'))
		{
			return new BaseObject();
		}

		// 설정된 모듈 제외
		if ($this->config->linkage_module_srl)
		{
			$module_srl_list = explode(',', $this->config->linkage_module_srl);

			if ($this->config->linkage_module_target == 'exclude' && in_array($obj->module_srl, $module_srl_list) || $this->config->linkage_module_target != 'exclude' && !in_array($obj->module_srl, $module_srl_list))
			{
				return new BaseObject();
			}
		}

		if (!getModel('sociallogin')->memberUserSns())
		{
			return new BaseObject();
		}

		foreach ($this->config->sns_services as $key => $val)
		{
			if (!($sns_info = getModel('sociallogin')->getMemberSns($val)) || $sns_info->linkage != 'Y')
			{
				continue;
			}

			if (!$oLibrary = $this->getLibrary($val))
			{
				continue;
			}

			// 토큰 넣기
			getModel('sociallogin')->setAvailableAccessToken($oLibrary, $sns_info);

			$args = new stdClass;
			$args->title = $obj->title;
			$args->content = $obj->content;
			$args->url = getNotEncodedFullUrl('', 'document_srl', $obj->document_srl);
			$oLibrary->post($args);

			// 로그 기록
			$info = new stdClass;
			$info->sns = $val;
			$info->title = $obj->title;
			getModel('sociallogin')->logRecord('linkage', $info);
		}

		return new BaseObject();
	}

	/**
	 * @brief 회원등록 트리거
	 **/
	function triggerInsertMember(&$config)
	{
		// 이메일 주소 확인
		if (Context::get('act') == 'procSocialloginConfirmMail')
		{
			$config->enable_confirm = 'Y';
		}
		// SNS 로그인시에는 메일인증을 사용안함
		else if (Context::get('act') == 'procSocialloginCallback' || Context::get('act') == 'procSocialloginInputAddInfo')
		{
			$config->enable_confirm = 'N';
		}

		return new BaseObject();
	}

	/**
	 * @brief 회원메뉴 팝업 트리거
	 **/
	function triggerMemberMenu()
	{
		if (!($member_srl = Context::get('target_srl')) || $this->config->sns_profile != 'Y')
		{
			return new BaseObject();
		}

		if (!getModel('sociallogin')->memberUserSns($member_srl))
		{
			return new BaseObject();
		}

		getController('member')->addMemberPopupMenu(getUrl('', 'mid', Context::get('cur_mid'), 'act', 'dispSocialloginSnsProfile', 'member_srl', $member_srl), 'sns_profile', '');

		return new BaseObject();
	}

	/**
	 * @brief 회원삭제 트리거
	 **/
	function triggerDeleteMember($obj)
	{
		$args = new stdClass;
		$args->member_srl = $obj->member_srl;
		$output = executeQueryArray('sociallogin.getMemberSns', $args);

		$sns_id = array();

		foreach ($output->data as $key => $val)
		{
			$sns_id[] = '[' . $val->service . '] ' . $val->id;

			if (!$oLibrary = $this->getLibrary($val->service))
			{
				continue;
			}

			// 토큰 넣기
			getModel('sociallogin')->setAvailableAccessToken($oLibrary, $val, false);

			// 토큰 파기
			$oLibrary->revokeToken();
		}

		executeQuery('sociallogin.deleteMemberSns', $args);

		// 로그 기록
		$info = new stdClass;
		$info->sns_id = implode(' | ', $sns_id);
		$info->nick_name = Context::get('logged_info')->nick_name;
		$info->member_srl = $obj->member_srl;
		getModel('sociallogin')->logRecord('delete_member', $info);

		return new BaseObject();
	}

	/**
	 * @brief SNS 등록
	 **/
	function registerSns($oLibrary, $member_srl = null, $login = false)
	{
		if (!$member_srl)
		{
			$member_srl = Context::get('logged_info')->member_srl;
		}

		if ($this->config->sns_login != 'Y' && !$member_srl)
		{
			return new BaseObject(-1, 'msg_not_sns_login');
		}

		if (!$oLibrary->getId())
		{
			return new BaseObject(-1, 'msg_errer_api_connect');
		}

		// SNS 계정 인증 상태 체크
		if (!$oLibrary->getVerified())
		{
			return new BaseObject(-1, 'msg_not_sns_verified');
		}

		$id = $oLibrary->getId();
		$service = $oLibrary->getService();

		$oSocialloginModel = getModel('sociallogin');

		// SNS ID 조회
		if (($sns_info = $oSocialloginModel->getMemberSnsById($id, $service)) && $sns_info->member_srl)
		{
			return new BaseObject(-1, 'msg_already_registed_sns');
		}

		$oMemberModel = getModel('member');

		// 중복 이메일 계정이 있으면 해당 계정으로 로그인
		if (!$member_srl && ($email = $oLibrary->getEmail()) && !$_SESSION['sociallogin_confirm_email'])
		{
			if ($member_srl = $oMemberModel->getMemberSrlByEmailAddress($email))
			{
				// 관리자 계정일 경우 보안 문제로 자동으로 등록하지 않음
				if ($oMemberModel->getMemberInfoByMemberSrl($member_srl)->is_admin == 'Y')
				{
					return new BaseObject(-1, 'msg_request_admin_sns_login');
				}
				// 일반 계정이라면 SNS 등록 후 즉시 로그인 요청
				else
				{
					$do_login = true;
				}
			}
		}

		// 회원 가입 진행
		if (!$member_srl)
		{
			$password = cut_str(md5(date('YmdHis')), 13, '');
			$nick_name = preg_replace('/[\pZ\pC]+/u', '', $oLibrary->getName());

			if ($oMemberModel->getMemberSrlByNickName($nick_name))
			{
				$nick_name = $nick_name . date('is');
			}

			// 추가 정보 받음
			if ($this->config->sns_input_add_info[0] && !$_SESSION['sociallogin_input_add_info_data'])
			{
				$_SESSION['tmp_sociallogin_input_add_info'] = $oLibrary->get();
				$_SESSION['tmp_sociallogin_input_add_info']['nick_name'] = $nick_name;

				return $this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispSocialloginInputAddInfo'), new BaseObject(-1, 'sns_input_add_info'));
			}

			// 메일 주소를 가져올 수 없다면 수동 입력
			if (!$email)
			{
				$_SESSION['tmp_sociallogin_confirm_email'] = $oLibrary->get();

				return $this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispSocialloginConfirmMail'), new BaseObject(-1, 'need_confirm_email_address'));
			}
			// 문제가 없다면 회원 정보 셋팅
			else
			{
				Context::setRequestMethod('POST');
				Context::set('password', $password, true);
				Context::set('nick_name', $nick_name, true);
				Context::set('user_name', $oLibrary->getName(), true);
				Context::set('email_address', $email, true);
				Context::set('accept_agreement', 'Y', true);

				$extend = $oLibrary->getProfileExtend();
				Context::set('homepage', $extend->homepage, true);
				Context::set('blog', $extend->blog, true);
				Context::set('birthday', $extend->birthday, true);
				Context::set('gender', $extend->gender, true);
				Context::set('age', $extend->age, true);

				// 사용자 추가 정보 셋팅
				if ($add_data = $_SESSION['sociallogin_input_add_info_data'])
				{
					foreach ($add_data as $key => $val)
					{
						Context::set($key, $val, true);
					}
				}

				unset($_SESSION['sociallogin_input_add_info_data']);
			}

			// 회원 모듈에 가입 요청
			$output = getController('member')->procMemberInsert();

			// 가입 도중 오류가 있다면 즉시 출력
			if (is_object($output) && method_exists($output, 'toBool') && !$output->toBool())
			{
				if ($output->error != -1)
				{
					$s_output = $output;
				}
				else
				{
					return $output;
				}
			}

			// 가입 완료 체크
			if (!$member_srl = $oMemberModel->getMemberSrlByEmailAddress($email))
			{
				return new BaseObject(-1, 'msg_error_register_sns');
			}

			// 이전 로그인 기록이 있으면 가입 포인트 제거
			if ($oSocialloginModel->getSnsUser($id, $service))
			{
				Context::set('__point_message__', Context::getLang('PHC_member_register_sns_login'));

				getController('point')->setPoint($member_srl, 0, 'update');
			}

			// 서명 등록
			if ($extend->signature)
			{
				getController('member')->putSignature($member_srl, $extend->signature);
			}

			// 프로필 이미지 등록
			if ($oLibrary->getProfileImage())
			{
				if (($tmp_dir = 'files/cache/tmp/') && !is_dir($tmp_dir))
				{
					FileHandler::makeDir($tmp_dir);
				}

				$path_parts = pathinfo(parse_url($oLibrary->getProfileImage(), PHP_URL_PATH));
				$tmp_file = sprintf('%s%s.%s', $tmp_dir, $password, $path_parts['extension']);

				if (FileHandler::getRemoteFile($oLibrary->getProfileImage(), $tmp_file, null, 3, 'GET', null, array(), array(), array(), array('ssl_verify_peer' => false)))
				{
					getController('member')->insertProfileImage($member_srl, $tmp_file);

					@unlink($tmp_file);
				}
			}
		}
		// 이미 가입되어 있었다면 SNS 등록만 진행
		else
		{
			// 등록하려는 서비스가 이미 등록되어 있을 경우
			if (($sns_info = $oSocialloginModel->getMemberSns($service, $member_srl)) && $sns_info->member_srl)
			{
				// 로그인에서 등록 요청이 온 경우 SNS 정보 삭제 후 재등록 (SNS ID가 달라졌다고 판단)
				if ($login)
				{
					$args = new stdClass;
					$args->service = $service;
					$args->member_srl = $member_srl;
					executeQuery('sociallogin.deleteMemberSns', $args);
				}
				else
				{
					return new BaseObject(-1, 'msg_invalid_request');
				}
			}
		}

		$args = new stdClass;
		$args->refresh_token = $oLibrary->getRefreshToken();
		$args->access_token = $oLibrary->getAccessToken();
		$args->profile_info = serialize($oLibrary->getProfile());
		$args->profile_url = $oLibrary->getProfileUrl();
		$args->profile_image = $oLibrary->getProfileImage();
		$args->email = $oLibrary->getEmail();
		$args->name = $oLibrary->getName();
		$args->id = $oLibrary->getId();
		$args->service = $service;
		$args->member_srl = $member_srl;

		// SNS 회원 정보 등록
		$output = executeQuery('sociallogin.insertMemberSns', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		// SNS ID 기록 (SNS 정보가 삭제 되더라도 ID는 영구 보관)
		if (!$oSocialloginModel->getSnsUser($id, $service))
		{
			$output = executeQuery('sociallogin.insertSnsUser', $args);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		// 로그인 요청
		if ($do_login)
		{
			$output = $this->LoginSns($oLibrary);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		// 가입 완료 후 메세지 출력 (메일 인증 메세지)
		if ($s_output)
		{
			return $s_output;
		}

		return new BaseObject();
	}

	/**
	 * @brief SNS 로그인
	 **/
	function LoginSns($oLibrary)
	{
		if ($this->config->sns_login != 'Y')
		{
			return new BaseObject(-1, 'msg_not_sns_login');
		}

		if (Context::get('is_logged'))
		{
			return new BaseObject(-1, 'already_logged');
		}

		if (!$oLibrary->getId())
		{
			return new BaseObject(-1, 'msg_errer_api_connect');
		}

		// SNS 계정 인증 상태 체크
		if (!$oLibrary->getVerified())
		{
			return new BaseObject(-1, 'msg_not_sns_verified');
		}

		// SNS ID로 회원 검색
		if (($sns_info = getModel('sociallogin')->getMemberSnsById($oLibrary->getId(), $oLibrary->getService())) && $sns_info->member_srl)
		{
			// 탈퇴한 회원이면 삭제후 등록 시도
			if (!($member_info = getModel('member')->getMemberInfoByMemberSrl($sns_info->member_srl)) || !$member_info->member_srl)
			{
				$args = new stdClass;
				$args->member_srl = $sns_info->member_srl;
				executeQuery('sociallogin.deleteMemberSns', $args);
			}
			// 로그인 허용
			else
			{
				$do_login = true;
			}
		}

		// 검색된 회원으로 로그인 진행
		if ($do_login)
		{
			// 인증 메일
			if ($member_info->denied == 'Y')
			{
				$args = new stdClass;
				$args->member_srl = $member_info->member_srl;
				$output = executeQuery('member.chkAuthMail', $args);

				if ($output->toBool() && $output->data->count > 0)
				{
					$_SESSION['auth_member_srl'] = $member_info->member_srl;

					return $this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispMemberResendAuthMail'), new BaseObject(-1, 'msg_user_not_confirmed'));
				}
			}

			// 계정 아이디 셋팅
			if (getModel('member')->getMemberConfig()->identifier == 'email_address')
			{
				$user_id = $member_info->email_address;
			}
			else
			{
				$user_id = $member_info->user_id;
			}

			// 회원 모듈에 로그인 요청
			$output = getController('member')->doLogin($user_id, '', $this->config->sns_keep_signed == 'Y' ? true : false);
			if (!$output->toBool())
			{
				return $output;
			}

			// SNS 세션 등록
			$_SESSION['sns_login'] = $oLibrary->getService();

			$args = new stdClass;
			$args->refresh_token = $oLibrary->getRefreshToken();
			$args->access_token = $oLibrary->getAccessToken();
			$args->profile_info = serialize($oLibrary->getProfile());
			$args->profile_url = $oLibrary->getProfileUrl();
			$args->profile_image = $oLibrary->getProfileImage();
			$args->email = $oLibrary->getEmail();
			$args->name = $oLibrary->getName();
			$args->service = $oLibrary->getService();
			$args->member_srl = $member_info->member_srl;

			// 로그인시마다 SNS 회원 정보 갱신
			$output = executeQuery('sociallogin.updateMemberSns', $args);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		// 검색된 회원이 없을 경우 SNS 등록(가입) 요청
		else
		{
			$output = $this->registerSns($oLibrary, null, true);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		return new BaseObject();
	}
}
