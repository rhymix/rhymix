<?php

namespace Rhymix\Modules\Sociallogin\Drivers;

use Abraham\TwitterOAuth\TwitterOAuth;

class Twitter extends Base
{
	/**
	 * @brief Auth 로그인 링크를 생성
	 * @param string $type
	 * @return string
	 */
	public function createAuthUrl(string $type = 'login'): string
	{
		$connection = new TwitterOAuth($this->config->twitter_consumer_key, $this->config->twitter_consumer_secret);

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
	 * @return \BaseObject|void
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

		$connection = new TwitterOAuth($this->config->twitter_consumer_key, $this->config->twitter_consumer_secret, $_SESSION['sociallogin_auth']['token'], $_SESSION['sociallogin_auth']['token_secret']);

		// API 요청 : 엑세스 토큰
		$token = $connection->oauth('oauth/access_token', array('oauth_verifier' => \Context::get('oauth_verifier')));

		// 토큰 삽입
		$this->setTwitterAccessToken(array('token' => $token['oauth_token'], 'token_secret' => $token['oauth_token_secret']));

		return new \BaseObject();
	}

	/**
	 * @brief 인증 후 프로필을 가져옴.
	 * @return \BaseObject
	 */
	function getSNSUserInfo()
	{
		// 토큰 체크
		if (!$token = \SocialloginModel::getAccessData('twitter')->token['access'])
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		$connection = new TwitterOAuth($this->config->twitter_consumer_key, $this->config->twitter_consumer_secret, $token['token'], $token['token_secret']);

		// API 요청 : 프로필
		if (!($profile = $connection->get('account/verify_credentials', array('include_email' => 'true'))) || empty($profile))
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// 계정 차단 확인
		if ($this->config->sns_suspended_account == 'Y')
		{
			// API 요청 : 사용자 정보
			if (!($user = $connection->get('users/show', array('user_id' => $profile->id))) || !$user->id)
			{
				return new \BaseObject(-1, 'msg_sns_suspended_account');
			}
		}

		// 팔로워 수 제한
		if ($this->config->sns_follower_count)
		{
			if ($this->config->sns_follower_count > $profile->followers_count)
			{
				$this->revokeToken();

				return new \BaseObject(-1, sprintf(\Context::getLang('msg_not_sns_follower_count'), $this->config->sns_follower_count));
			}
		}

		// 이메일 주소
		if ($profile->email)
		{
			$profileValue['email_address'] = $profile->email;
		}
		else
		{
			return new \BaseObject(-1, 'msg_not_confirm_email_sns_for_sns');
		}

		// ID, 이름, 프로필 이미지, 프로필 URL
		$profileValue['sns_id'] = $profile->id;
		$profileValue['user_name'] = $profile->name;
		$profileValue['profile_image'] = $profile->profile_image_url;
		$profileValue['url'] = 'https://twitter.com/' . $profile->screen_name;
		$profileValue['etc'] = $profile;
		
		\SocialloginController::getInstance()->setDriverAuthData('twitter', 'profile', $profileValue);

		return new \BaseObject();
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
		if (!$profile = \SocialloginModel::getAccessData('twitter')->profile['etc'])
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
	 * @brief 두개의 토큰에 대한 배열 처리 (트위터는 추가 API를 이용하는 과정에서 필수로 데이터를 같이 저장할 필요가 있습니다.)
	 */
	function setTwitterAccessToken($access_token)
	{
		// 배열이 아닌 json 가 삽입 되었을 경우 배열로 변환하여 처리
		if (!is_array($access_token))
		{
			$access_token = json_decode($access_token, true);
		}
		$accessValue['access'] = $access_token;

		\SocialloginController::getInstance()->setDriverAuthData('twitter', 'token', $accessValue);
	}

	/**
	 * @brief 두개의 토큰에 대한 배열 처리
	 */
	function getTwitterAccessToken()
	{
		// 빼낼 경우 json 로 변환하여 반환
		return json_encode(\SocialloginModel::getAccessData('twitter')->token['access']);
	}

	function getProfileImage()
	{
		// 최대한 큰 사이즈의 프로필 이미지를 반환하기 위하여
		return str_replace('_normal', '', \SocialloginModel::getAccessData('twitter')->profile['profile_image']);
	}

	// Dummy Method for SocialInserface.
	function requestAPI($url, $type = array(), $authorization = null, $delete = null)
	{
		// TODO: Implement requestAPI() method.
	}
}
