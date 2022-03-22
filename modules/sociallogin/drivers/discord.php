<?php

namespace Rhymix\Modules\Sociallogin\Drivers;

class Discord extends Base
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
		
		$accessValue['access'] = $token['access_token'];
		$accessValue['refresh'] = $token['refresh_token'];
		
		\SocialloginController::getInstance()->setDriverAuthData('discord', 'token', $accessValue);
	}

	/**
	 * @brief 인증 후 프로필을 가져옴.
	 * @return \BaseObject
	 */
	function getSNSUserInfo()
	{
		// 토큰 체크
		$token = \SocialloginModel::getAccessData('discord')->token['access'];
		
		if (!$token)
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}
		
		$headers = array(
			'Authorization' => "Bearer {$token}",
		);
		$user_info = $this->requestAPI('api/users/@me', [], $headers);

		// ID, 이름, 프로필 이미지, 프로필 URL
		$profileValue['email_address'] = $user_info['email'];
		$profileValue['sns_id'] = $user_info['id'];
		$profileValue['user_name'] = $user_info['username'];
		$profileValue['etc'] = $user_info;
		
		\SocialloginController::getInstance()->setDriverAuthData('discord', 'profile', $profileValue);
	}

	/**
	 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
	 */
	function refreshToken(string $refresh_token = ''): array
	{
		// 토큰 체크
		if (!$refresh_token)
		{
			return[];
		}

		// API 요청 : 토큰 새로고침
		$token = $this->requestAPI('api/oauth2/token', array(
			'refresh_token' => \SocialloginModel::getAccessData('discord')->token['refresh'],
			'grant_type'    => 'refresh_token',
			'client_id'     => $this->config->discord_client_id,
			'client_secret' => $this->config->discord_client_secret,
			'scope' => 'identify email',
			'redirect_uri' => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'discord'),
		));

		// 새로고침 된 토큰 삽입
		$returnTokenData = [];
		$returnTokenData['access'] = $token['access_token'];

		return $returnTokenData;
	}

	/**
	 * @brief 토큰파기
	 * @notice 미구현
	 */
	function revokeToken(string $access_token = '')
	{
		return;
	}


	function requestAPI($url, $post = array(), $authorization = null, $delete = false)
	{
		$resource = \FileHandler::getRemoteResource(self::DISCORD_API_URL . $url, null, 3, empty($post) ? 'GET' : 'POST', null, $authorization, array(), $post);
		
		return json_decode($resource, true);
	}
}
