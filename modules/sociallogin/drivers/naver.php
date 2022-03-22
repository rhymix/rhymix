<?php

namespace Rhymix\Modules\Sociallogin\Drivers;

class Naver extends Base
{
	const NAVER_OAUTH2_URI = 'https://nid.naver.com/oauth2.0/';
	
	/**
	 * @brief Auth 로그인 링크를 생성
	 * @param string $type
	 * @return string
	 */
	public function createAuthUrl(string $type = 'login'): string
	{
		// 요청 파라미터
		$params = array(
			'response_type' => 'code',
			'client_id'     => $this->config->naver_client_id,
			'redirect_uri'  => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'naver'),
			'state'         => $_SESSION['sociallogin_auth']['state'],
		);

		return self::NAVER_OAUTH2_URI . 'authorize?' . http_build_query($params, '', '&');
	}

	/**
	 * @brief 인증 단계 (로그인 후 callback 처리) [실행 중단 에러를 출력할 수 있음]
	 * @return \BaseObject|void
	 */
	function authenticate()
	{
		// 오류가 있을 경우 메세지 출력
		if (\Context::get('error'))
		{
			return new \BaseObject(-1, 'Error ' . \Context::get('error') . ' : ' . \Context::get('error_description'));
		}

		// 위변조 체크
		if (!\Context::get('code') || \Context::get('state') !== $_SESSION['sociallogin_auth']['state'])
		{
			return new \BaseObject(-1, 'msg_invalid_request');
		}

		// API 요청 : 엑세스 토큰
		$token = $this->requestAPI('token', array(
			'code'          => \Context::get('code'),
			'state'         => \Context::get('state'),
			'grant_type'    => 'authorization_code',
			'client_id'     => $this->config->naver_client_id,
			'client_secret' => $this->config->naver_client_secret,
		));
		
		// 토큰 삽입
		$accessValue['access'] = $token['access_token'];
		$accessValue['refresh'] = $token['refresh_token'];

		\SocialloginController::getInstance()->setDriverAuthData('naver', 'token', $accessValue);

		return new \BaseObject();
	}

	/**
	 * @brief 인증 후 프로필을 가져옴.
	 * @return \BaseObject
	 */
	function getSNSUserInfo()
	{
		$serviceAccessData = \SocialloginModel::getAccessData('naver');
		
		// 토큰 체크
		if (!$serviceAccessData->token['access'])
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// API 요청 : 프로필
		$profile = $this->requestAPI('https://openapi.naver.com/v1/nid/me', array(), $serviceAccessData->token['access']);

		// 프로필 데이터가 없다면 오류
		if (!($profile = $profile['response']) || empty($profile))
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// 이메일 주소
		if ($profile['email'])
		{
			$profileValue['email_address'] = $profile['email'];
		}
		else
		{
			return new \BaseObject(-1, 'msg_not_confirm_email_sns_for_sns');
		}

		// ID
		$profileValue['sns_id'] = $profile['id'];

		// 이름 (닉네임이 없다면 이름으로 설정)
		if ($profile['name'] && preg_match('/\*$/', $profile['nickname']))
		{
			$profileValue['user_name'] = $profile['name'];
		}
		else
		{
			$profileValue['user_name'] = $profile['nickname'];
		}

		// 프로필 이미지
		$profileValue['profile_image'] = $profile['profile_image'];

		// 프로필 URL : 네이버는 따로 프로필 페이지가 없으므로 네이버 블로그로 설정
		if ($profile['email'] && strpos($profile['email'], 'naver.com') !== false)
		{
			$profileValue['url'] = 'http://blog.naver.com/' . str_replace('@naver.com', '', $profile['email']);
		}
		else
		{
			$profileValue['url'] = 'http://www.naver.com/';
		}
		
		$profileValue['etc'] = $profile;

		\SocialloginController::getInstance()->setDriverAuthData('naver', 'profile', $profileValue);

		return new \BaseObject();
	}

	/**
	 * @brief 토큰 파기 (SNS 해제 또는 회원 삭제시 실행)
	 */
	public function revokeToken(string $access_token = '')
	{
		// 토큰 체크
		if (!$access_token)
		{
			return;
		}

		// API 요청 : 토큰 파기
		$this->requestAPI('token', array(
			'access_token'     => $access_token,
			'grant_type'       => 'delete',
			'client_id'        => $this->config->naver_client_id,
			'client_secret'    => $this->config->naver_client_secret,
			'service_provider' => 'NAVER',
		));
	}

	/**
	 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
	 */
	public function refreshToken(string $refresh_token = ''): array
	{
		// 토큰 체크
		if (!$refresh_token)
		{
			return [];
		}

		// API 요청 : 토큰 새로고침
		$token = $this->requestAPI('token', array(
			'refresh_token' => $refresh_token,
			'grant_type'    => 'refresh_token',
			'client_id'     => $this->config->naver_client_id,
			'client_secret' => $this->config->naver_client_secret,
		));

		// 새로고침 된 토큰 삽입
		$returnTokenData = [];
		
		$returnTokenData['access'] = $token['access_token'];
		$returnTokenData['refresh'] = $refresh_token;
		return $returnTokenData;
	}

	/**
	 * @brief 프로필 확장 (가입시 추가 기입)
	 */
	function getProfileExtend()
	{
		// 프로필 체크
		if (!$profile = \SocialloginModel::getAccessData('naver')->profile['etc'])
		{
			return new \stdClass;
		}

		$extend = new \stdClass;

		// 블로그
		if ($profile['email'] && strpos($profile['email'], 'naver.com') !== false)
		{
			$this->blog = 'http://blog.naver.com/' . str_replace('@naver.com', '', $profile['email']);
		}

		// 생일
		if ($profile['birthday'])
		{
			$extend->birthday = date('Y') . preg_replace('/[^0-9]*?/', '', $profile['birthday']);
		}

		// 성별
		if ($profile['gender'] == 'M')
		{
			$extend->gender = lang('sociallogin.sns_gender_male');
		}
		else if ($profile['gender'] == 'F')
		{
			$extend->gender = lang('sociallogin.sns_gender_female');
		}

		// 연령대
		if ($profile['age'] && ($age = explode('-', $profile['age'])) && $age[0])
		{
			$extend->age = $age[0] . '대';
		}

		return $extend;
	}

	function getProfileImage()
	{
		// 최대한 큰 사이즈의 프로필 이미지를 반환하기 위하여
		return preg_replace('/\?.*/', '', \SocialloginModel::getAccessData('naver')->profile['profile_image']);
	}

	function requestAPI($url, $post = array(), $authorization = null, $delete = null)
	{
		if ($authorization)
		{
			$headers = array(
				'Host'          => 'apis.naver.com',
				'Pragma'        => 'no-cache',
				'Accept'        => '*/*',
				'Authorization' => 'Bearer ' . $authorization
			);
		}

		$resource = \FileHandler::getRemoteResource(($url == 'token') ? self::NAVER_OAUTH2_URI . 'token' : $url, null, 3, empty($post) ? 'GET' : 'POST', 'application/x-www-form-urlencoded', $headers, array(), $post);
		
		return json_decode($resource, true);
	}
}
