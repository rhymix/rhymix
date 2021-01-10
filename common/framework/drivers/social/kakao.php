<?php
/**
 * @class  libraryKakao
 * @author CONORY (https://xe.conory.com)
 * @brief The kakao library of the sociallogin module
 */

namespace Rhymix\Framework\Drivers\Social;

const KAKAO_OAUTH2_URI = 'https://kauth.kakao.com/oauth/';
const KAKAO_API_URI = 'https://kapi.kakao.com/';

class Kakao extends Base implements \Rhymix\Framework\Drivers\SocialInterface
{
	/**
	 * @brief 인증 URL 생성 (SNS 로그인 URL)
	 */
	function createAuthUrl($type)
	{
		// 요청 파라미터
		$params = [
			'response_type' => 'code',
			'client_id'     => self::getConfig()->kakao_client_id,
			'redirect_uri'  => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'kakao'),
			'state'         => $_SESSION['sociallogin_auth']['state'],
		];

		return KAKAO_OAUTH2_URI . 'authorize?' . http_build_query($params, '', '&');
	}

	/**
	 * @brief 인증 단계 (로그인 후 callback 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	function authenticate()
	{
		// 위변조 체크
		if (!Context::get('code') || Context::get('state') !== $_SESSION['sociallogin_auth']['state'])
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		// API 요청 : 엑세스 토큰
		$token = $this->requestAPI('token', [
			'code'         => Context::get('code'),
			'grant_type'   => 'authorization_code',
			'client_id'    => self::getConfig()->kakao_client_id,
			'redirect_uri' => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'kakao'),
		]);

		// 토큰 삽입
		$this->setAccessToken($token['access_token']);
		$this->setRefreshToken($token['refresh_token']);

		return new BaseObject();
	}

	/**
	 * @brief 로딩 단계 (인증 후 프로필 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	function loading()
	{
		// 토큰 체크
		if (!$this->getAccessToken())
		{
			return new BaseObject(-1, 'msg_errer_api_connect');
		}

		// API 요청 : 프로필
		if (!($profile = $this->requestAPI('v2/user/me', [], $this->getAccessToken())) || !$profile['id'])
		{
			// API 요청 : 앱 가입 (프로필을 불러올 수 없다면)
			$this->requestAPI('v1/user/signup', [], $this->getAccessToken());

			// API 요청 : 프로필 (앱 가입 후 재요청)
			if (!($profile = $this->requestAPI('v2/user/me', [], $this->getAccessToken())) || !$profile['id'])
			{
				return new BaseObject(-1, 'msg_errer_api_connect');
			}
		}

		// API 요청 : 카카오 스토리 프로필 (스토리에 가입되어 있을 경우 추가)
		if (($story = $this->requestAPI('v1/api/story/profile', [], $this->getAccessToken())) && $story['nickName'])
		{
			$profile['story'] = $story;
		}

		// 이메일 주소 : 카카오 API에서 제공하지 않음
		$this->setEmail('');

		// ID, 이름, 프로필 이미지, 프로필 URL
		$this->setId($profile['id']);
		$this->setName($profile['properties']['nickname'] ?: $profile['story']['nickName']);
		$this->setProfileImage($profile['properties']['profile_image'] ?: $profile['story']['profileImageURL']);
		$this->setProfileUrl($profile['story']['permalink'] ?: 'http://www.kakao.com/talk');

		// 프로필 인증
		$this->setVerified(true);

		// 전체 데이터
		$this->setProfileEtc($profile);

		return new BaseObject();
	}

	/**
	 * @brief 토큰 파기 (SNS 해제 또는 회원 삭제시 실행)
	 */
	function revokeToken()
	{
		// 토큰 체크
		if (!$this->getAccessToken())
		{
			return;
		}

		// API 요청 : 토큰 파기
		$this->requestAPI('v1/user/unlink', [], $this->getAccessToken());
	}

	/**
	 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
	 */
	function refreshToken()
	{
		// 토큰 체크
		if (!$this->getRefreshToken())
		{
			return;
		}

		// API 요청 : 토큰 새로고침
		$token = $this->requestAPI('token', [
			'refresh_token' => $this->getRefreshToken(),
			'grant_type'    => 'refresh_token',
			'client_id'     => self::getConfig()->kakao_client_id,
		]);

		// 새로고침 된 토큰 삽입
		$this->setAccessToken($token['access_token']);

		// 새로고침 토큰도 새로고침 될 수 있음
		if ($token['refresh_token'])
		{
			$this->setRefreshToken($token['refresh_token']);
		}
	}

	/**
	 * @brief 연동 체크 (SNS 연동 설정 전 연동 가능 여부를 체크)
	 */
	function checkLinkage()
	{
		// API 요청 : 카카오 스토리 사용자 여부
		if (!$this->getAccessToken() || !$user = $this->requestAPI('v1/api/story/isstoryuser', [], $this->getAccessToken()))
		{
			return new BaseObject(-1, 'msg_errer_api_connect');
		}

		// 카카오 스토리 사용자만 연동 가능
		if ($user['isStoryUser'] !== true)
		{
			return new BaseObject(-1, 'msg_not_kakao_story_user');
		}

		return new BaseObject();
	}

	/**
	 * @brief SNS로 전송 (연동)
	 */
	function post($args)
	{
		// 토큰 체크
		if (!$this->getAccessToken())
		{
			return;
		}

		// API 요청 : 스토리에 포스팅 (제목 + 게시물 URL)
		$this->requestAPI('v1/api/story/post/note', ['content' => $args->title . ' ' . $args->url], $this->getAccessToken());
	}

	/**
	 * @brief 프로필 확장 (가입시 추가 기입)
	 */
	function getProfileExtend()
	{
		// 프로필 체크
		if (!$profile = $this->getProfileEtc())
		{
			return new stdClass;
		}

		$extend = new stdClass;

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
		return preg_replace('/\?.*/', '', parent::getProfileImage());
	}

	function requestAPI($url, $post = [], $authorization = null)
	{
		if ($authorization)
		{
			$headers = [
				'Authorization' => 'Bearer ' . $authorization,
			];
		}

		return json_decode(FileHandler::getRemoteResource(($url == 'token') ? KAKAO_OAUTH2_URI . 'token' : KAKAO_API_URI . $url, null, 3, empty($post) ? 'GET' : 'POST', 'application/x-www-form-urlencoded', $headers, [], $post, ['ssl_verify_peer' => false]), true);
	}
}
