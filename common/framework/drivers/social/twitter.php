<?php

namespace Rhymix\Framework\Drivers\Social;

use Abraham\TwitterOAuth\TwitterOAuth;

class Twitter extends Base implements \Rhymix\Framework\Drivers\SocialInterface
{

	/**
	 * @brief 인증 URL 생성 (SNS 로그인 URL)
	 */
	function createAuthUrl($type)
	{
		$connection = new TwitterOAuth(\Sociallogin::getConfig()->twitter_consumer_key, \Sociallogin::getConfig()->twitter_consumer_secret);

		// API 요청 : 요청 토큰
		$request_token = $connection->oauth('oauth/request_token', array(
			'oauth_callback' => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'twitter')
		));

		// 세션에 토큰 저장 (인증 단계에서 사용하기 위하여)
		$_SESSION['sociallogin_auth']['token'] = $request_token['oauth_token'];
		$_SESSION['sociallogin_auth']['token_secret'] = $request_token['oauth_token_secret'];

		// API 요청 : 요청 토큰으로 인증 URL 생성
		return $connection->url('oauth/authenticate', array('oauth_token' => $_SESSION['sociallogin_auth']['token']));
	}

	/**
	 * @brief 인증 단계 (로그인 후 callback 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	function authenticate()
	{
		// 토큰 세션 체크
		if (!\Context::get('oauth_verifier') || !$_SESSION['sociallogin_auth']['token'] || !$_SESSION['sociallogin_auth']['token_secret'])
		{
			return new \BaseObject(-1, 'msg_invalid_request');
		}

		// 위변조 체크
		if (\Context::get('oauth_token') !== $_SESSION['sociallogin_auth']['token'])
		{
			return new \BaseObject(-1, 'msg_invalid_request');
		}

		$connection = new TwitterOAuth(\Sociallogin::getConfig()->twitter_consumer_key, \Sociallogin::getConfig()->twitter_consumer_secret, $_SESSION['sociallogin_auth']['token'], $_SESSION['sociallogin_auth']['token_secret']);

		// API 요청 : 엑세스 토큰
		$token = $connection->oauth('oauth/access_token', array('oauth_verifier' => \Context::get('oauth_verifier')));

		// 토큰 삽입
		$this->setAccessToken(array('token' => $token['oauth_token'], 'token_secret' => $token['oauth_token_secret']));

		return new \BaseObject();
	}

	/**
	 * @brief 로딩 단계 (인증 후 프로필 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	function loading()
	{
		// 토큰 체크
		if (!$token = parent::getAccessToken())
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		$connection = new TwitterOAuth(\Sociallogin::getConfig()->twitter_consumer_key, \Sociallogin::getConfig()->twitter_consumer_secret, $token['token'], $token['token_secret']);

		// API 요청 : 프로필
		if (!($profile = $connection->get('account/verify_credentials', array('include_email' => 'true'))) || empty($profile))
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// 계정 차단 확인
		if (\Sociallogin::getConfig()->sns_suspended_account == 'Y')
		{
			// API 요청 : 사용자 정보
			if (!($user = $connection->get('users/show', array('user_id' => $profile->id))) || !$user->id)
			{
				return new \BaseObject(-1, 'msg_sns_suspended_account');
			}
		}

		// 팔로워 수 제한
		if (\Sociallogin::getConfig()->sns_follower_count)
		{
			if (\Sociallogin::getConfig()->sns_follower_count > $profile->followers_count)
			{
				$this->revokeToken();

				return new \BaseObject(-1, sprintf(\Context::getLang('msg_not_sns_follower_count'), \Sociallogin::getConfig()->sns_follower_count));
			}
		}

		// 이메일 주소
		if ($profile->email)
		{
			$this->setEmail($profile->email);
		}

		// ID, 이름, 프로필 이미지, 프로필 URL
		$this->setId($profile->id);
		$this->setName($profile->name);
		$this->setProfileImage($profile->profile_image_url);
		$this->setProfileUrl('https://twitter.com/' . $profile->screen_name);

		// 프로필 인증
		$this->setVerified(true);

		// 전체 데이터
		$this->setProfileEtc($profile);

		return new \BaseObject();
	}

	/**
	 * @brief 토큰 파기 (SNS 해제 또는 회원 삭제시 실행)
	 */
	function revokeToken()
	{
		// 트위터의 경우 따로 파기할 수 없음
	}

	/**
	 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
	 */
	function refreshToken()
	{
		// 트위터의 경우 유효기간이 없는 무제한 토큰므로 필요없음
	}

	/**
	 * @brief 연동 체크 (SNS 연동 설정 전 연동 가능 여부를 체크)
	 */
	function checkLinkage()
	{
		// 트위터는 연동 가능
		return new \BaseObject();
	}
	
	/**
	 * @brief 프로필 확장 (가입시 추가 기입)
	 */
	function getProfileExtend()
	{
		// 프로필 체크
		if (!$profile = $this->getProfileEtc())
		{
			return new \stdClass;
		}

		$extend = new \stdClass;

		// 서명 (자기 소개)
		if ($profile->description)
		{
			$extend->signature = $profile->description;
		}

		// 홈페이지
		if ($profile->entities->url->urls[0]->expanded_url)
		{
			$extend->homepage = $profile->entities->url->urls[0]->expanded_url;
		}

		return $extend;
	}

	/**
	 * @brief 두개의 토큰에 대한 배열 처리
	 */
	function setAccessToken($access_token)
	{
		// 배열이 아닌 json 가 삽입 되었을 경우 배열로 변환하여 처리
		if (!is_array($access_token))
		{
			$access_token = json_decode($access_token, true);
		}

		parent::setAccessToken($access_token);
	}

	/**
	 * @brief 두개의 토큰에 대한 배열 처리
	 */
	function getAccessToken()
	{
		// 빼낼 경우 json 로 변환하여 반환
		return json_encode(parent::getAccessToken());
	}

	function getProfileImage()
	{
		// 최대한 큰 사이즈의 프로필 이미지를 반환하기 위하여
		return str_replace('_normal', '', parent::getProfileImage());
	}

	// Dummy Method for SocialInserface.
	function requestAPI($url, $type = array(), $authorization = null, $delete = null)
	{
		// TODO: Implement requestAPI() method.
	}
}
