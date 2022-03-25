<?php

namespace Rhymix\Modules\Sociallogin\Drivers;

class Apple extends Base
{
	public $oProvider = null;
	public $token = null;
	
	function getProvider()
	{
		// need setting to 60.
		if(\Firebase\JWT\JWT::$leeway === 0)
		{
			\Firebase\JWT\JWT::$leeway = 60;
		}
		
		if(!$this->oProvider)
		{
			$provider = new \League\OAuth2\Client\Provider\Apple([
				'clientId' => $this->config->apple_client_id, // com.snsdstagram.apps
				'teamId' => $this->config->apple_team_id,
				'keyFileId' => $this->config->apple_file_key,
				'keyFilePath' => $this->config->apple_file_path,
				'redirectUri' => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback','service','apple'),
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
			'scope' => "name email",
		];
		
		$authUrl = $provider->getAuthorizationUrl($options);
		
		return $authUrl;
	}

	/**
	 * @brief 코드인증
	 */
	function authenticate()
	{
		$provider = $this->getProvider();

		$token = $provider->getAccessToken('authorization_code', [
			'code' => \Context::get('code'),
		]);
		
		$accessValue['access'] = $token->getToken();
		
		\SocialloginController::getInstance()->setDriverAuthData('apple', 'token', $accessValue);
		
		$this->token = $token;
		
		return new \BaseObject();
	}

	function getSNSUserInfo()
	{
		if (!\SocialloginModel::getAccessData('apple')->token['access'])
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}
		
		$provider = $this->getProvider();
		
		$user = $provider->getResourceOwner($this->token);
		$profile = $user->toArray();

		if(!$profile)
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		if(isset($_SESSION['social_apple_name'][$user->getEmail()]))
		{
			$userName = $_SESSION['social_apple_name'][$user->getEmail()];
		}
		else
		{
			$userName = $user->getFirstName() . ' ' . $user->getLastName();
		}
		
		$profileValue['sns_id'] = $user->getId();
		$profileValue['email'] = $profile['email'];
		$profileValue['user_name'] = $userName;
		$profileValue['etc'] = $profile;

		$_SESSION['social_apple_name'] = [
			$user->getEmail() => $userName
		];
		
		if ($profile['email'])
		{
			$profileValue['email_address'] = $profile['email'];
		}
		else
		{
			return new \BaseObject(-1, 'msg_not_confirm_email_sns_for_sns');
		}
		
		\SocialloginController::getInstance()->setDriverAuthData('apple', 'profile', $profileValue);

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
		return \SocialloginModel::getAccessData('apple')->profile['profile_image'];
	}

	function requestAPI($request_url, $post_data = array(), $authorization = null, $delete = false)
	{
	}
}
