<?php

namespace Rhymix\Modules\Sociallogin\Drivers;

class Kakao extends Base
{
	const KAKAO_OAUTH2_URI = 'https://kauth.kakao.com/oauth/';
	const KAKAO_API_URI = 'https://kapi.kakao.com/';
	
	/**
	 * @brief Auth 로그인 링크를 생성
	 * @param string $type
	 * @return string
	 */
	public function createAuthUrl(string $type = 'login'): string
	{
		// 요청 파라미터
		$params = [
			'response_type' => 'code',
			'client_id'     => $this->config->kakao_client_id,
			'redirect_uri'  => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'kakao'),
			'state'         => $_SESSION['sociallogin_auth']['state'],
		];

		return self::KAKAO_OAUTH2_URI . 'authorize?' . http_build_query($params, '', '&');
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
		$token = $this->requestAPI('token', [
			'code'         => \Context::get('code'),
			'grant_type'   => 'authorization_code',
			'client_id'    => $this->config->kakao_client_id,
			'redirect_uri' => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'kakao'),
		]);

		// 토큰 삽입
		$accessValue['access'] = $token['access_token'];
		$accessValue['refresh'] = $token['refresh_token'];

		\SocialloginController::getInstance()->setDriverAuthData('kakao', 'token', $accessValue);
		
		return new \BaseObject();
	}

	/**
	 * @brief 인증 후 프로필을 가져옴.
	 * @return \BaseObject
	 */
	function getSNSUserInfo()
	{
		// 토큰 체크
		$serviceAccessData = \SocialloginModel::getAccessData('kakao');
		if (!$serviceAccessData->token['access'])
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// API 요청 : 프로필
		if (!($profile = $this->requestAPI('v2/user/me', [], $serviceAccessData->token['access'])) || !$profile['id'])
		{
			// API 요청 : 앱 가입 (프로필을 불러올 수 없다면)
			$this->requestAPI('v1/user/signup', [], $serviceAccessData->token['access']);

			// API 요청 : 프로필 (앱 가입 후 재요청)
			if (!($profile = $this->requestAPI('v2/user/me', [], $serviceAccessData->token['access'])) || !$profile['id'])
			{
				return new \BaseObject(-1, 'msg_errer_api_connect');
			}
		}

		// API 요청 : 카카오 스토리 프로필 (스토리에 가입되어 있을 경우 추가)
		if (($story = $this->requestAPI('v1/api/story/profile', [], $serviceAccessData->token['access'])) && $story['nickName'])
		{
			$profile['story'] = $story;
		}
		
		if(isset($profile['kakao_account']['email']))
		{
			$profileValue['email_address'] = $profile['kakao_account']['email'];
		}
		else
		{
			return new \BaseObject(-1, 'msg_not_confirm_email_sns_for_sns');
		}
		
		$profileValue['sns_id'] = $profile['id'];
		$profileValue['user_name'] = $profile['properties']['nickname'] ?: $profile['story']['nickName'];
		$profileValue['profile_image'] = $profile['properties']['profile_image'] ?: $profile['story']['profileImageURL'];
		$profileValue['url'] = $profile['story']['permalink'] ?: 'http://www.kakao.com/talk';
		$profileValue['etc'] = $profile;

		\SocialloginController::getInstance()->setDriverAuthData('kakao', 'profile', $profileValue);
		
		return new \BaseObject();
	}

	/**
	 * @brief 토큰 파기 (SNS 해제 또는 회원 삭제시 실행)
	 */
	function revokeToken(string $access_token = '')
	{
		// 토큰 체크
		if (!$access_token)
		{
			return;
		}

		// API 요청 : 토큰 파기
		$this->requestAPI('v1/user/unlink', [], $access_token);
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
		$token = $this->requestAPI('token', [
			'refresh_token' => \SocialloginModel::getAccessData('kakao')->token['refresh'],
			'grant_type'    => 'refresh_token',
			'client_id'     => $this->config->kakao_client_id,
		]);

		// 새로고침 된 토큰 삽입
		$returnTokenData = [];
		$returnTokenData['access'] = $token['access_token'];
		$returnTokenData['refresh'] = $token['refresh_token'];

		// 새로고침 토큰도 새로고침 될 수 있음
		if ($token['refresh_token'])
		{
			$returnTokenData['refresh'] = $token['refresh_token'];
		}
		return $returnTokenData;
	}

	/**
	 * @brief 연동 체크 (SNS 연동 설정 전 연동 가능 여부를 체크)
	 */
	function checkLinkage()
	{
		// API 요청 : 카카오 스토리 사용자 여부
		if (!\SocialloginModel::getAccessData('kakao')->token['access'] || !$user = $this->requestAPI('v1/api/story/isstoryuser', [], \SocialloginModel::getAccessData('kakao')->token['access']))
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// 카카오 스토리 사용자만 연동 가능
		if ($user['isStoryUser'] !== true)
		{
			return new \BaseObject(-1, 'msg_not_kakao_story_user');
		}

		return new \BaseObject();
	}

	/**
	 * @brief SNS로 전송 (연동)
	 */
	function post($args)
	{
		$serviceAccessData = \SocialloginModel::getAccessData('kakao');
		
		// 토큰 체크
		if (!\SocialloginModel::getAccessData('kakao')->token['access'])
		{
			return;
		}

		// API 요청 : 스토리에 포스팅 (제목 + 게시물 URL)
		$this->requestAPI('v1/api/story/post/note', ['content' => $args->title . ' ' . $args->url], \SocialloginModel::getAccessData('kakao')->token['access']);
	}

	/**
	 * @brief 프로필 확장 (가입시 추가 기입)
	 */
	function getProfileExtend()
	{
		// 프로필 체크
		if (!$profile = \SocialloginModel::getAccessData('kakao')->profile['etc'])
		{
			return new \stdClass;
		}

		$extend = new \stdClass;

		// 생일
		if ($profile['story']['birthday'])
		{
			$extend->birthday = date('Y') . $profile['story']['birthday'];
		}

		return $extend;
	}

	function getProfileImage()
	{
		// 최대한 큰 사이즈의 프로필 이미지를 반환하기 위하여
		return preg_replace('/\?.*/', '', \SocialloginModel::getAccessData('kakao')->profile['profile_image']);
	}

	function requestAPI($url, $post = [], $authorization = null, $delete = null)
	{
		if ($authorization)
		{
			$headers = [
				'Authorization' => 'Bearer ' . $authorization,
			];
		}

		$resource = \FileHandler::getRemoteResource(($url == 'token') ? self::KAKAO_OAUTH2_URI . 'token' : self::KAKAO_API_URI . $url, null, 3, empty($post) ? 'GET' : 'POST', 'application/x-www-form-urlencoded', $headers, [], $post);
		
		return json_decode($resource, true);
	}
}
