<?php

class SocialloginController extends Sociallogin
{
	function init()
	{
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
		
		if (!$email_address = Context::get('email_address'))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}
		
		if (getModel('member')->getMemberSrlByEmailAddress($email_address))
		{
			$error = 'msg_exists_email_address';
		}
		
		$saved = $_SESSION['sociallogin_input_add_info'];
		$mid = $_SESSION['sociallogin_current']['mid'];
		$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');

		$signupForm = array();

		// 필수 추가 가입폼
		if (in_array('require_add_info', self::getConfig()->sns_input_add_info))
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
		if (in_array('user_id', self::getConfig()->sns_input_add_info))
		{
			$signupForm[] = 'user_id';

			if (getModel('member')->getMemberSrlByUserID(Context::get('user_id')))
			{
				$error = 'msg_exists_user_id';
			}
		}

		// 닉네임 폼
		if (in_array('nick_name', self::getConfig()->sns_input_add_info))
		{
			$signupForm[] = 'nick_name';

			if (getModel('member')->getMemberSrlByNickName(Context::get('nick_name')))
			{
				$error = 'msg_exists_nick_name';
			}
		}

		// 약관 동의
		if (in_array('agreement', self::getConfig()->sns_input_add_info))
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
			$oLibrary = $this->getLibrary($saved['service']);
			if (!$oLibrary)
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}

			$_SESSION['sociallogin_input_add_info_data'] = $add_data;

			$oLibrary->setSocial($saved);
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

		if (self::getConfig()->sns_login == 'Y' && self::getConfig()->default_signup != 'Y')
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
		if (!($service = Context::get('service')) || !in_array($service, self::getConfig()->sns_services))
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
		if ($output instanceof BaseObject && !$output->toBool())
		{
			$error = $output->getMessage();
		}

		// 인증 세션 제거
		unset($_SESSION['sociallogin_auth']);

		// 로딩
		if (!$error)
		{
			$output = $oLibrary->getSNSUserInfo();
			if ($output instanceof BaseObject && !$output->toBool())
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

		if ($type == 'register')
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', $_SESSION['sociallogin_current']['mid'], 'act', 'dispSocialloginSnsManage'));
		}
		else
		{
			//TODO: Check again later.
			if (!$this->getRedirectUrl())
			{
				$this->setRedirectUrl($redirect_url);
			}
		}

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
		if (self::getConfig()->linkage_module_srl)
		{
			$module_srl_list = explode(',', self::getConfig()->linkage_module_srl);

			if (self::getConfig()->linkage_module_target == 'exclude' && in_array($obj->module_srl, $module_srl_list) || self::getConfig()->linkage_module_target != 'exclude' && !in_array($obj->module_srl, $module_srl_list))
			{
				return new BaseObject();
			}
		}

		if (!getModel('sociallogin')->memberUserSns())
		{
			return new BaseObject();
		}

		foreach (self::getConfig()->sns_services as $key => $val)
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
	 * @brief 회원메뉴 팝업 트리거
	 **/
	function triggerMemberMenu()
	{
		if (!($member_srl = Context::get('target_srl')) || self::getConfig()->sns_profile != 'Y')
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
	 * @brief 회원등록 트리거
	 **/
	function triggerInsertMemberAction(&$config)
	{
		// SNS 로그인시에는 메일인증을 사용안함
		if (Context::get('act') == 'procSocialloginCallback' || Context::get('act') == 'procSocialloginInputAddInfo' || $_SESSION['tmp_sociallogin_input_add_info'])
		{
			$config->enable_confirm = 'N';
		}

		return new BaseObject();
	}

	/**
	 * @brief SNS 등록
	 * @param $oLibrary \Rhymix\Framework\Drivers\Social\Base
	 * @param null $member_srl
	 * @param false $login
	 * @return BaseObject|object|SocialloginController
	 */
	function registerSns($oLibrary, $member_srl = null, $login = false)
	{
		if (!$member_srl)
		{
			$member_srl = Context::get('logged_info')->member_srl;
		}

		if (self::getConfig()->sns_login != 'Y' && !$member_srl)
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

		/** @var memberModel $oMemberModel */
		$oMemberModel = memberModel::getInstance();

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

			$member_config = $oMemberModel::getMemberConfig();
			
			$boolRequired = false;

			foreach ($member_config->signupForm as $item)
			{
				if($item->name == 'user_id')
				{
					continue;
				}
				if($item->name == 'email_address')
				{
					continue;
				}
				if($item->name == 'password')
				{
					continue;
				}
				if($item->name == 'user_name')
				{
					continue;
				}
				if($item->name == 'nick_name')
				{
					continue;
				}
				if($item->required)
				{
					$boolRequired = true;
					break;
				}
			}

			// 미리 소셜 내용 기록.
			$_SESSION['tmp_sociallogin_input_add_info'] = $oLibrary->getSocial();
			$_SESSION['tmp_sociallogin_input_add_info']['nick_name'] = $nick_name;
			if($email)
			{
				$_SESSION['tmp_sociallogin_input_add_info']['email'] = $email;
			}

			// 프로필 이미지를 위한 임시 파일 생성
			if ($oLibrary->getProfileImage())
			{
				if (($tmp_dir = 'files/cache/tmp/') && !is_dir($tmp_dir))
				{
					FileHandler::makeDir($tmp_dir);
				}

				$path_parts = pathinfo(parse_url($oLibrary->getProfileImage(), PHP_URL_PATH));
				$randomString = Rhymix\Framework\Security::getRandom(32);
				$tmp_file = "{$tmp_dir}{$randomString}profile.{$path_parts['extension']}";

				if(FileHandler::getRemoteFile($oLibrary->getProfileImage(), $tmp_file, null, 3, 'GET', null, array(), array(), array(), array('ssl_verify_peer' => false)))
				{
					$_SESSION['tmp_sociallogin_input_add_info']['profile_dir'] = $tmp_file;
				}
			}
			
			// 회원 정보에서 추가 입력할 데이터가 있을경우 세션값에 소셜정보 입력 후 회원가입 항목으로 이동
			if ($boolRequired || !$email)
			{
				return $this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispMemberSignUpForm'), new BaseObject(-1, 'sns_input_add_info'));
			}
			
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
			
			// 회원 모듈에 가입 요청
			// try 를 쓰는이유는 회원가입시 어떤 실패가 일어나는 경우 BaseObject으로 리턴하지 않기에 에러를 출력하기 위함입니다.
			try
			{
				$output = getController('member')->procMemberInsert();
			}
			catch (\Rhymix\Framework\Exception $exception)
			{
				// 리턴시에도 세션값을 비워줘야함
				unset($_SESSION['tmp_sociallogin_input_add_info']);
				return new BaseObject(-1, $exception->getMessage());
			}
			
			unset($_SESSION['tmp_sociallogin_input_add_info']);
			
			// 가입 도중 오류가 있다면 즉시 출력
			if (is_object($output) && method_exists($output, 'toBool') && !$output->toBool())
			{
				if ($output->error != -1)
				{
					// 리턴값을 따로 저장.
					$return_output = $output;
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
		if ($return_output)
		{
			return $return_output;
		}

		return new BaseObject();
	}

	/**
	 * @brief SNS 로그인
	 * @param $oLibrary \Rhymix\Framework\Drivers\Social\Base
	 * @return BaseObject|object|SocialloginController
	 */
	function LoginSns($oLibrary)
	{
		if (self::getConfig()->sns_login != 'Y')
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
			$output = getController('member')->doLogin($user_id, '', self::getConfig()->sns_keep_signed == 'Y' ? true : false);
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

	/**
	 * replace to signup argument.
	 * @param $args
	 * @return object
	 */
	function replaceSignUpFormBySocial($args)
	{
		/** @var SocialloginModel $oSocialLoginModel */
		$oSocialLoginModel = SocialloginModel::getInstance();
		$socialLoginUserData = $oSocialLoginModel::getSocialSignUpUserData();

		if($socialLoginUserData)
		{
			$args->nick_name = $args->user_name = $socialLoginUserData->nick_name;
			$args->email_address = $socialLoginUserData->email_address;
		}

		unset($args->user_id);

		$args->password = $args->password2 = cut_str(md5(date('YmdHis')), 13, '');
		
		return $args;
	}
	
	function triggerModuleHandler()
	{
		if(!Rhymix\Framework\Session::getMemberSrl())
		{
			return new BaseObject();
		}
		
		memberController::getInstance()->addMemberMenu('dispSocialloginSnsManage', 'sns_manage');
	}
}
