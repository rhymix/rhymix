<?php
namespace Rhymix\Framework\Drivers\Social;

include dirname(__DIR__) . '/social/vendor/autoload.php';

class Apple extends Base implements \Rhymix\Framework\Drivers\SocialInterface
{
	public $oProvider = null;
	public $token = null;
	
	function getProvider()
	{
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

		
		$_SESSION['sociallogin_driver_auth']['apple'] = new \stdClass();
		$_SESSION['sociallogin_driver_auth']['apple']->token['access'] = $token->getToken();
		$this->token = $token;
		
		return new \BaseObject();
	}

	function getSNSUserInfo()
	{
		if (!$_SESSION['sociallogin_driver_auth']['apple']->token['access'])
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

		$_SESSION['sociallogin_driver_auth']['apple']->profile['sns_id'] = $user->getId();
		$_SESSION['sociallogin_driver_auth']['apple']->profile['email'] = $profile['email'];
		$_SESSION['sociallogin_driver_auth']['apple']->profile['user_name'] = $user->getFirstName() . ' ' . $user->getLastName();
		$_SESSION['sociallogin_driver_auth']['apple']->profile['etc'] = $profile;
		if ($profile['email'])
		{
			$_SESSION['sociallogin_driver_auth']['apple']->profile['email_address'] = $profile['email'];
		}
		else
		{
			return new \BaseObject(-1, 'msg_not_confirm_email_sns_for_sns');
		}

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
		return $_SESSION['sociallogin_driver_auth']['apple']->profile['profile_image'];
	}

	function requestAPI($request_url, $post_data = array(), $authorization = null, $delete = false)
	{
	}
}
