<?php
namespace Rhymix\Framework\Drivers\Social;

/**
 * Class Discord
 * @package Rhymix\Framework\Drivers\Social
 */
class Discord extends Base implements \Rhymix\Framework\Drivers\SocialInterface
{
	const DISCORD_API_URL = 'https://discord.com/';

	/**
	 * @brief Auth 로그인 링크를 생성
	 * @param string $type
	 * @return string
	 */
	function createAuthUrl(string $type = 'login'): string
	{
		$params = array(
			'client_id'        => $this->config->discord_client_id,
			'redirect_uri'    => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'discord'),
			'response_type' => 'code',
			'scope' => 'identify email',
			'state' => $_SESSION['sociallogin_auth']['state'],
		);

		return self::DISCORD_API_URL . 'oauth2/authorize?' . http_build_query($params, '', '&');
	}

	/**
	 * @brief 인증 단계 (로그인 후 callback 처리) [실행 중단 에러를 출력할 수 있음]
	 * @return \BaseObject|void
	 */
	function authenticate()
	{
		$code = \Context::get('code');
		
		$config = $this->config;
		$post = [
			"grant_type" => "authorization_code",
			"client_id" => $config->discord_client_id,
			"client_secret" => $config->discord_client_secret,
			"redirect_uri" => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'discord'),
			"code" => $code,
		];
		$token = $this->requestAPI('api/oauth2/token', $post);

		if(!isset($token))
		{
			return new \BaseObject(-1, 'msg_invalid_request');
		}
		
		$_SESSION['sociallogin_driver_auth']['discord'] = new \stdClass();
		$_SESSION['sociallogin_driver_auth']['discord']->token['access'] = $token['access_token'];
		$_SESSION['sociallogin_driver_auth']['discord']->token['refresh'] = $token['refresh_token'];
	}

	/**
	 * @brief 인증 후 프로필을 가져옴.
	 * @return \BaseObject
	 */
	function getSNSUserInfo()
	{
		// 토큰 체크
		$token = $_SESSION['sociallogin_driver_auth']['discord']->token['access'];
		if (!$token)
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}
		
		$headers = array(
			'Authorization' => "Bearer {$token}",
		);
		$user_info = $this->requestAPI('api/users/@me', [], $headers);

		// ID, 이름, 프로필 이미지, 프로필 URL
		$_SESSION['sociallogin_driver_auth']['discord']->profile['email_address'] = $user_info['email'];
		$_SESSION['sociallogin_driver_auth']['discord']->profile['sns_id'] = $user_info['id'];
		$_SESSION['sociallogin_driver_auth']['discord']->profile['user_name'] = $user_info['username'];
		$_SESSION['sociallogin_driver_auth']['discord']->profile['etc'] = $user_info;
	}

	function requestAPI($url, $post = array(), $authorization = null, $delete = false)
	{
		return json_decode(\FileHandler::getRemoteResource(self::DISCORD_API_URL . $url, null, 3, empty($post) ? 'GET' : 'POST', null, $authorization, array(), $post, array()), true);
	}
}
