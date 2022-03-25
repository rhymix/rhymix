<?php

namespace Rhymix\Modules\Sociallogin\Drivers;

class Github extends Base
{
	public $oProvider = null;
	public $token = null;
	
	function getProvider()
	{
		if(!$this->oProvider)
		{
			$provider = new \League\OAuth2\Client\Provider\Github([
				'clientId'          => "{$this->config->github_client_id}",
				'clientSecret'      => "{$this->config->github_client_secret}",
				'redirectUri'       => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback','service','github'),
			]);
			$this->oProvider = $provider;
		}
		
		return $this->oProvider;
	}
	
	/**
	 * @brief 인증 URL 생성
	 */
	function createAuthUrl(string $type = 'login'): string
	{
		$provider = $this->getProvider();

		$options = [
			'state' => $_SESSION['sociallogin_auth']['state'],
			'scope' => ['user','user:email']
		];
		
		return $provider->getAuthorizationUrl($options);
	}

	/**
	 * @brief 코드인증
	 */
	function authenticate()
	{
		$provider = $this->getProvider();
		
		$state = \Context::get('state');
		
		if(!$_SESSION['sociallogin_auth']['state'] || $state != $_SESSION['sociallogin_auth']['state'])
		{
			return new \BaseObject(-1, "msg_invalid_request");
		}

		$token = $provider->getAccessToken('authorization_code', [
			'code' => \Context::get('code'),
		]);
		
		if($token)
		{
			$this->token = $token;
		}

		// 토큰 삽입
		$accessValue['access'] = $token->getToken();
		\SocialloginController::getInstance()->setDriverAuthData('github', 'token', $accessValue);
		
		unset($_SESSION['socialxe_auth_state']);

		return new \BaseObject();
	}

	function getSNSUserInfo()
	{
		if (!\SocialloginModel::getAccessData('github')->token['access'])
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		$provider = $this->getProvider();

		$profile = $provider->getResourceOwner($this->token);
		$profile = $profile->toArray();
		if(!$profile)
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}
		
		// TODO : why do check empty value?
		if(!$profile['email'] || $profile['email'] == '')
		{
			return new \BaseObject(-1, '');
		}

		if($profile['email'])
		{
			$profileValue['email_address'] = $profile['email'];
		}
		else
		{
			return new \BaseObject(-1, 'msg_not_confirm_email_sns_for_sns');
		}
		
		$profileValue['sns_id'] = $profile['id'];
		$profileValue['user_name'] = $profile['login'];
		$profileValue['profile_image'] = $profile['avatar_url'];
		$profileValue['url'] = $profile['html_url'];
		$profileValue['etc'] = $profile;

		\SocialloginController::getInstance()->setDriverAuthData('github', 'profile', $profileValue);
		
		return new \BaseObject();
	}

	/**
	 * @brief 토큰파기
	 * @notice 미구현
	 */
	function revokeToken(string $access_token = '')
	{
		return;
	}

	function getProfileImage()
	{
		return \SocialloginModel::getAccessData('github')->profile['profile_image'];
	}

	function requestAPI($request_url, $post_data = array(), $authorization = null, $delete = false)
	{
	}
}
