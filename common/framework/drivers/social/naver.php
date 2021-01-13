<?php
namespace Rhymix\Framework\Drivers\Social;
const NAVER_OAUTH2_URI = 'https://nid.naver.com/oauth2.0/';

class Naver extends Base implements \Rhymix\Framework\Drivers\SocialInterface
{
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

		return NAVER_OAUTH2_URI . 'authorize?' . http_build_query($params, '', '&');
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
		$this->setAccessToken($token['access_token']);
		$this->setRefreshToken($token['refresh_token']);

		return new \BaseObject();
	}

	/**
	 * @brief 인증 후 프로필을 가져옴.
	 * @return \BaseObject
	 */
	function getSNSUserInfo()
	{
		// 토큰 체크
		if (!$this->getAccessToken())
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// API 요청 : 프로필
		$profile = $this->requestAPI('https://openapi.naver.com/v1/nid/me', array(), $this->getAccessToken());

		// 프로필 데이터가 없다면 오류
		if (!($profile = $profile['response']) || empty($profile))
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		// 이메일 주소
		if ($profile['email'])
		{
			$this->setEmail($profile['email']);
		}

		// ID
		$this->setId($profile['id']);

		// 이름 (닉네임이 없다면 이름으로 설정)
		if ($profile['name'] && preg_match('/\*$/', $profile['nickname']))
		{
			$this->setName($profile['name']);
		}
		else
		{
			$this->setName($profile['nickname']);
		}

		// 프로필 이미지
		$this->setProfileImage($profile['profile_image']);

		// 프로필 URL : 네이버는 따로 프로필 페이지가 없으므로 네이버 블로그로 설정
		if ($profile['email'] && strpos($profile['email'], 'naver.com') !== false)
		{
			$this->setProfileUrl('http://blog.naver.com/' . str_replace('@naver.com', '', $profile['email']));
		}
		else
		{
			$this->setProfileUrl('http://www.naver.com/');
		}

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
		// 토큰 체크
		if (!$this->getAccessToken())
		{
			return;
		}

		// API 요청 : 토큰 파기
		$this->requestAPI('token', array(
			'access_token'     => $this->getAccessToken(),
			'grant_type'       => 'delete',
			'client_id'        => $this->config->naver_client_id,
			'client_secret'    => $this->config->naver_client_secret,
			'service_provider' => 'NAVER',
		));
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
		$token = $this->requestAPI('token', array(
			'refresh_token' => $this->getRefreshToken(),
			'grant_type'    => 'refresh_token',
			'client_id'     => $this->config->naver_client_id,
			'client_secret' => $this->config->naver_client_secret,
		));

		// 새로고침 된 토큰 삽입
		$this->setAccessToken($token['access_token']);
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
			$extend->gender = '남성';
		}
		else if ($profile['gender'] == 'F')
		{
			$extend->gender = '여성';
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
		return preg_replace('/\?.*/', '', parent::getProfileImage());
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

		return json_decode(\FileHandler::getRemoteResource(($url == 'token') ? NAVER_OAUTH2_URI . 'token' : $url, null, 3, empty($post) ? 'GET' : 'POST', 'application/x-www-form-urlencoded', $headers, array(), $post, array('ssl_verify_peer' => false)), true);
	}
}
