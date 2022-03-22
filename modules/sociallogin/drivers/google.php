<?php

namespace Rhymix\Modules\Sociallogin\Drivers;

class Google extends Base
{
	const GOOGLE_OAUTH2_URI = 'https://accounts.google.com/o/oauth2/';
	const GOOGLE_PEOPLE_URI = 'https://people.googleapis.com/v1/people/';

	/**
	 * @brief Auth 로그인 링크를 생성
	 * @param string $type
	 * @return string
	 */
	public function createAuthUrl(string $type = 'login'): string
	{
		// API 권한
		$scope = array(
			'https://www.googleapis.com/auth/userinfo.email',
			'https://www.googleapis.com/auth/userinfo.profile',
		);

		// 요청 파라미터
		$params = array(
			'scope'         => implode(' ', $scope),
			'access_type'   => 'offline',
			'response_type' => 'code',
			'client_id'     => $this->config->google_client_id,
			'redirect_uri'  => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'google'),
			'state'         => $_SESSION['sociallogin_auth']['state'],
		);

		return self::GOOGLE_OAUTH2_URI . 'auth?' . http_build_query($params, '', '&');
	}

	/**
	 * @brief 인증 단계 (로그인 후 callback 처리) [실행 중단 에러를 출력할 수 있음]
	 * @return \BaseObject|void
	 */
	function authenticate()
	{
		// 위변조 체크
		if (!\Context::get('code') || \Context::get('state') !== $_SESSION['sociallogin_auth']['state'])
		{
			return new \BaseObject(-1, 'msg_invalid_request');
		}

		// API 요청 : 엑세스 토큰
		$token = $this->requestAPI('token', array(
			'code'          => \Context::get('code'),
			'grant_type'    => 'authorization_code',
			'client_id'     => $this->config->google_client_id,
			'client_secret' => $this->config->google_client_secret,
			'redirect_uri'  => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'google'),
		));

		// 토큰 삽입
		$accessValue['access'] = $token['access_token'];
		$accessValue['refresh'] = $token['refresh_token'];

		\SocialloginController::getInstance()->setDriverAuthData('google', 'token', $accessValue);

		return new \BaseObject();
	}

	/**
	 * @brief 인증 후 프로필을 가져옴.
	 * @return \BaseObject
	 */
	function getSNSUserInfo()
	{
		// 토큰 체크
		$serviceAccessData = \SocialloginModel::getAccessData('google');
		if (!$serviceAccessData->token['access'])
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// API 요청 : 프로필
		$profile = $this->requestAPI(self::GOOGLE_PEOPLE_URI . 'me?personFields=names,emailAddresses&' . http_build_query(array(
				'access_token' => $serviceAccessData->token['access'],
			), '', '&'));

		// 프로필 데이터가 없다면 오류
		if (empty($profile))
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// 이메일 주소
		if ($profile['emailAddresses'])
		{
			foreach ($profile['emailAddresses'] as $key => $val)
			{
				if ($val['metadata']['source']['type'] === 'ACCOUNT' && $val['value'])
				{
					$profileValue['email_address'] = $val['value'];

					$profileArgs = $val;
					break;
				}
			}
		}

		if (!$profileValue['email_address'])
		{
			return new \BaseObject(-1, 'msg_not_confirm_email_sns_for_sns');
		}

		// ID, 이름, 프로필 이미지, 프로필 URL
		$profileValue['sns_id'] = $profileArgs['metadata']['source']['id'];
		$profileValue['user_name'] = $profile['names'][0]['displayName'];
		$profileValue['etc'] = $profile;

		\SocialloginController::getInstance()->setDriverAuthData('google', 'profile', $profileValue);

		return new \BaseObject();
	}

	/**
	 * @brief 토큰 파기 (SNS 해제 또는 회원 삭제시 실행)
	 */
	function revokeToken(string $access_token = '')
	{
		// 토큰 체크
		//TODO (BJRambo): is that access token empty?
		if (!($token = $access_token ?: \SocialloginModel::getAccessData('google')->token['refresh']))
		{
			return;
		}

		if (isset($token))
		{
			// API 요청 : 토큰 파기
			$this->requestAPI('revoke', array(
				'token' => $token,
			));
		}
	}

	/**
	 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
	 */
	function refreshToken(string $refresh_token = ''): array
	{
		// 토큰 체크
		if (!$refresh_token)
		{
			return [];
		}

		// API 요청 : 토큰 새로고침
		$token = $this->requestAPI('token', array(
			'refresh_token' => \SocialloginModel::getAccessData('google')->token['refresh'],
			'grant_type'    => 'refresh_token',
			'client_id'     => $this->config->google_client_id,
			'client_secret' => $this->config->google_client_secret,
		));

		// 새로고침 된 토큰 삽입
		$returnTokenData = [];
		$returnTokenData['access'] = $token['access_token'];

		return $returnTokenData;
	}

	/**
	 * @brief 프로필 확장 (가입시 추가 기입)
	 */
	function getProfileExtend()
	{
		// 프로필 체크
		if (!$profile = \SocialloginModel::getAccessData('google')->profile['etc'])
		{
			return new \stdClass;
		}

		$extend = new \stdClass;

		// 서명 (자기 소개)
		if ($profile['aboutMe'] || $profile['tagline'])
		{
			$extend->signature = $profile['aboutMe'] ?: $profile['tagline'];
		}

		// 홈페이지
		if ($profile['urls'])
		{
			foreach ($profile['urls'] as $key => $val)
			{
				if ($val['type'] == 'other' && $val['value'])
				{
					$extend->homepage = $val['value'];

					break;
				}
			}
		}

		// 생일
		if ($profile['birthday'])
		{
			$extend->birthday = preg_replace('/[^0-9]*?/', '', $profile['birthday']);
		}

		// 성별
		if ($profile['gender'] == 'male')
		{
			$extend->gender = lang('sociallogin.sns_gender_male');
		}
		else if ($profile['gender'] == 'female')
		{
			$extend->gender = lang('sociallogin.sns_gender_female');
		}

		// 연령대
		if ($profile['ageRange']['min'] || $profile['ageRange']['max'])
		{
			if ($profile['ageRange']['min'] && $profile['ageRange']['max'])
			{
				$age = ($profile['ageRange']['min'] + $profile['ageRange']['max']) / 2;
			}
			else
			{
				$age = max($profile['ageRange']['min'], $profile['ageRange']['max']);
			}

			$extend->age = floor($age / 10) * 10 . '대';
		}

		return $extend;
	}

	/**
	 * @param $url
	 * @param array $post
	 * @return mixed
	 */
	function requestAPI($url, $post = array(), $authorization = null, $delete = null)
	{
		// 콘텐츠 타입이 설정되어 있을 경우 정상적으로 api통신이 되지 않아 null 로 요청
		$resource = \FileHandler::getRemoteResource(in_array($url, array(
			'token',
			'revoke'
		)) ? self::GOOGLE_OAUTH2_URI . $url : $url, null, 3, empty($post) ? 'GET' : 'POST', null, array(), array(), $post);
		
		return json_decode($resource, true);
	}
}
