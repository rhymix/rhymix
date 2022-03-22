<?php

class SocialloginModel extends \Rhymix\Modules\Sociallogin\Base
{
	/**
	 * @param $oDriver \Rhymix\Modules\Sociallogin\Drivers\Base
	 * @param $sns_info
	 * @param bool $db
	 */
	public static function setAvailableAccessToken($oDriver, $sns_info, $db = true)
	{
		// 새로고침 토큰이 없을 경우 그대로 넣기
		if (!$sns_info->refresh_token)
		{
			$tokenData = [];
			$tokenData['access'] = $sns_info->access_token;

			return $tokenData;
		}

		// 토큰 새로고침
		$tokenData = $oDriver->refreshToken($sns_info->refresh_token);

		// [실패] 이전 토큰 그대로 넣기
		if (!$tokenData['access'])
		{
			$tokenData['access'] = $sns_info->access_token;
		}
		// [성공] 새로고침된 토큰을 DB에 저장
		else if ($db)
		{
			$args = new stdClass;
			$args->refresh_token = $tokenData['access'];
			$args->access_token = $tokenData['refresh'];
			$args->service = $oDriver->getService();
			$args->member_srl = $sns_info->member_srl;

			executeQuery('sociallogin.updateMemberSns', $args);
		}
		
		return $tokenData;
	}

	/**
	 * @brief 회원 SNS
	 */
	public static function getMemberSnsList($member_srl = null, $type = 'login')
	{
		if(!$member_srl)
		{
			if(!Rhymix\Framework\Session::getMemberSrl())
			{
				return false;
			}
			$member_srl = Rhymix\Framework\Session::getMemberSrl();
		}
		
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQueryArray('sociallogin.getMemberSns', $args);
		$memberSNSList = $output->data;

		$useSNSList = SocialloginModel::getUseSNSList($type);
		foreach ($memberSNSList as $key => $userSNSData)
		{
			$memberSNSList[$key]->auth_url = $useSNSList[$userSNSData->service]->auth_url;
		}
		
		return $output->data;
	}

	/**
	 * @param $service
	 * @param null $member_srl
	 * @return bool|object
	 */
	public static function getMemberSnsByService($service, $member_srl = null)
	{
		if(!$member_srl)
		{
			if (!Rhymix\Framework\Session::getMemberSrl())
			{
				return false;
			}
			$member_srl = Rhymix\Framework\Session::getMemberSrl();
		}
		if(!$service)
		{
			return false;
		}
		
		$args = new stdClass();
		$args->service = $service;
		$args->member_srl = $member_srl;
		$output = executeQuery('sociallogin.getMemberSns', $args);
		
		return $output->data;
	}

	/**
	 * @brief SNS ID로 회원조회
	 */
	public static function getMemberSnsById($id, $service = null)
	{
		$args = new stdClass;
		$args->service_id = $id;
		$args->service = $service;

		return executeQuery('sociallogin.getMemberSns', $args)->data;
	}

	/**
	 * @brief SNS ID 첫 로그인 조회
	 */
	public static function getSnsUser($id, $service = null)
	{
		$args = new stdClass;
		$args->service_id = $id;
		$args->service = $service;

		return executeQuery('sociallogin.getSnsUser', $args)->data;
	}

	/**
	 * @brief SNS 유저여부
	 */
	public static function memberUserSns($member_srl = null)
	{
		$sns_list = self::getMemberSnsList($member_srl);

		if (!is_array($sns_list))
		{
			$sns_list = array($sns_list);
		}

		if (count($sns_list) > 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * @brief 기존 유저여부 (가입일과 SNS 등록일이 같다면)
	 */
	public static function memberUserPrev($member_srl = null)
	{
		if (!$member_srl)
		{
			if (!Context::get('is_logged'))
			{
				return;
			}

			$member_srl = Context::get('logged_info')->member_srl;
		}

		$member_info = getModel('member')->getMemberInfoByMemberSrl($member_srl);

		$args = new stdClass;
		$args->regdate_less = date('YmdHis', strtotime(sprintf('%s +1 minute', $member_info->regdate)));
		$args->member_srl = $member_srl;

		if (!executeQuery('sociallogin.getMemberSns', $args)->data)
		{
			return true;
		}

		return false;
	}

	/**
	 * @brief SNS 인증 URL
	 */
	public static function snsAuthUrl($service, $type)
	{
		return getUrl('', 'mid', Context::get('mid'), 'act', 'dispSocialloginConnectSns', 'service', $service, 'type', $type, 'redirect', $_SERVER['QUERY_STRING']);
	}

	/**
	 * @brief 로그기록
	 **/
	public static function logRecord($act, $info = null)
	{
		if (!is_object($info))
		{
			$info = Context::getRequestVars();
		}

		$args = new stdClass;

		switch ($act)
		{
			case 'procSocialloginSnsClear' :
				$args->category = 'sns_clear';
				$args->content = sprintf(lang('sns_connect_clear'), $info->sns);
				break;

			case 'procSocialloginSnsLinkage' :
				$args->category = 'linkage';
				$args->content = sprintf(lang('sns_connect_linkage'), $info->sns, $info->linkage);
				break;

			case 'dispSocialloginConnectSns' :
				$args->category = 'auth_request';
				$args->content = sprintf(lang('sns_connect_auth_request'), $info->sns);
				break;

			case 'procSocialloginCallback' :
				$args->category = $info->type;

				
				if ($info->type == 'register')
				{
					$info->msg = $info->msg ?: lang('sns_connect_register_success');
					$args->content = sprintf(lang('sns_connect_exec_register'), $info->sns, Context::getLang($info->msg));
				}
				else if ($info->type == 'login')
				{
					$info->msg = $info->msg ?: lang('sns_connect_login_success');
					$args->content = sprintf(lang('sns_connect_exec_login'), $info->sns, Context::getLang($info->msg));
				}
				else
				{
					//TODO(BJRambo): Add to log for recheck
				}

				break;
				
			case 'linkage' :
				$args->category = 'linkage';
				$args->content = sprintf(lang('sns_connect_document'), $info->sns, $info->title);
				break;

			case 'delete_member' :
				$args->category = 'delete_member';

				if ($info->nick_name)
				{
					$args->content = sprintf(lang('sns_connect_delete_member'), $info->member_srl, $info->nick_name, $info->service_id);
				}
				else
				{
					$args->content = sprintf(lang('sns_connect_auto_delete_member'), $info->member_srl, $info->nick_name, $info->service_id);
				}

				break;
		}

		if (!$args->category)
		{
			$args->category = 'unknown';
			$args->content = sprintf('%s (act : %s)', Context::getLang('unknown'), $act);
		}

		$args->act = $act;
		$args->micro_time = microtime(true);
		$args->member_srl = Context::get('logged_info')->member_srl;

		executeQuery('sociallogin.insertLogRecord', $args);
	}

	public static function getUseSNSList($type = 'login')
	{
		$config = self::getConfig();
		$sns_auth_list = array();
		foreach ($config->sns_services as $key => $sns_name)
		{
			$sns_auth_list[$sns_name] = new stdClass();
			$sns_auth_list[$sns_name]->name = $sns_name;
			$sns_auth_list[$sns_name]->auth_url = self::snsAuthUrl($sns_name, $type);
		}
		
		return $sns_auth_list;
	}

	/**
	 * 소셜로그인을 사용하는지 검사
	 * @return bool
	 */
	public static function isEnabled()
	{
		$config = self::getConfig();
		
		if(!is_array($config->sns_services))
		{
			return false;
		}
		
		if(count($config->sns_services) <= 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * 소셜 로그인에 필요한 정보를 세션에서 가져옴
	 * @return false|stdClass
	 */
	public static function getSocialSignUpUserData()
	{
		if(isset($_SESSION['tmp_sociallogin_input_add_info']))
		{
			$return_object = new stdClass();
			$return_object->nick_name = $_SESSION['tmp_sociallogin_input_add_info']['nick_name'];
			$return_object->email_address = $_SESSION['tmp_sociallogin_input_add_info']['email_address'];
			
			return $return_object;
		}
		else
		{
			return false;
		}
	}
	
	public static function getSocialloginButtons($type = 'login')
	{
		$snsList = self::getUseSNSList();
		
		$buff = [];
		$buff[] = '<ul class="sociallogin_login">';
		$signString = ($type === 'signup') ? 'Sign up' : 'Sign in';
		foreach ($snsList as $key => $sns)
		{
			$ucfirstName = ucfirst($sns->name);
			$buff[] = "<li><div class=\"sociallogin_{$sns->name}\">";
			$buff[] = "<a class=\"loginBtn\" href=\"{$sns->auth_url}\"><span class=\"icon\"></span>";
			$buff[] = "<span class=\"buttonText\"> {$signString} with {$ucfirstName}</span>";
			$buff[] = '</a></div></li>';
		}
		$buff[] = '</ul>';
		
		return implode('', $buff);
	}
	
	public static function getAccessData($service)
	{
		return $_SESSION['sociallogin_driver_auth'][$service] ?? null;
	}
}
