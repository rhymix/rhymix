<?php
namespace Rhymix\Framework\Drivers\Social;

class Facebook extends Base implements \Rhymix\Framework\Drivers\SocialInterface
{
	const FACEBOOK_GRAPH_API_VERSION = 'v2.8';
	const FACEBOOK_URI = 'https://www.facebook.com/';
	const FACEBOOK_GRAPH_URL = 'https://graph.facebook.com/';
	
	/**
	 * @brief Auth 로그인 링크를 생성
	 * @param string $type
	 * @return string
	 */
	public function createAuthUrl(string $type = 'login'): string
	{
		// API 권한
		$scope = array(
			'public_profile',
			'email',
			//'user_about_me',
			//'user_website',
			//'user_birthday',
		);

		// 요청 파라미터
		$params = array(
			'scope'        => implode(',', $scope),
			'client_id'    => $this->config->facebook_app_id,
			'redirect_uri' => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'facebook'),
			'state'        => $_SESSION['sociallogin_auth']['state'],
		);

		return self::FACEBOOK_URI . 'dialog/oauth?' . http_build_query($params, '', '&');
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
		$token = $this->requestAPI('/oauth/access_token', array(
			'code'          => \Context::get('code'),
			'client_id'     => $this->config->facebook_app_id,
			'client_secret' => $this->config->facebook_app_secret,
			'redirect_uri'  => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'facebook'),
		));

		// API 요청 : 장기 실행 토큰(60일) (토큰 새로고침 대신)
		$token = $this->requestAPI('/oauth/access_token', array(
			'fb_exchange_token' => $token['access_token'],
			'grant_type'        => 'fb_exchange_token',
			'client_id'         => $this->config->facebook_app_id,
			'client_secret'     => $this->config->facebook_app_secret,
		));

		// 토큰 삽입
		$this->setAccessToken($token['access_token']);

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

		// 요청 필드
		$fields = array(
			'id',
			'name',
			'picture.width(1000).height(1000)',
			'link',
			'verified',
			'gender',
			'age_range',
			'email',
			'about',
			'website',
			'birthday',
			'friends',
		);

		// API 요청 : 프로필
		$profile = $this->requestAPI('/me?' . http_build_query(array(
				'fields'       => implode(',', $fields),
				'access_token' => $this->getAccessToken(),
			), '', '&'));

		// 프로필 데이터가 없다면 오류
		if (empty($profile) || $profile['error']['message'])
		{
			return new \BaseObject(-1, \Context::getLang('msg_errer_api_connect') . $profile['error']['message']);
		}

		// 팔로워 수 제한 (페이스북의 경우 '친구 수')
		if ($this->config->sns_follower_count)
		{
			if ($this->config->sns_follower_count > $profile['friends']['summary']['total_count'])
			{
				$this->revokeToken();

				return new \BaseObject(-1, sprintf(\Context::getLang('msg_not_sns_follower_count'), $this->config->sns_follower_count));
			}
		}
		// 이메일 주소
		if ($profile['email'])
		{
			$this->setEmail($profile['email']);
		}

		// ID, 이름, 프로필 이미지, 프로필 URL
		$this->setId($profile['id']);
		$this->setName($profile['name']);
		$this->setProfileImage($profile['picture']['data']['url']);
		$this->setProfileUrl($profile['link']);

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

		// API 요청 : 권한 삭제
		$this->requestAPI('/me/permissions', array(
			'access_token' => $this->getAccessToken(),
		), null, true);
	}

	/**
	 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
	 */
	function refreshToken()
	{
		// 페이스북은 따로 새로고침 토큰이 없음
		// 대신 인증 단계에서 장기 실행 토큰(60일)으로 발급 받음
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

		// 서명 (자기 소개)(별도의 검수 필요)
		if ($profile['about'])
		{
			$extend->signature = $profile['about'];
		}

		// 홈페이지 (별도의 검수 필요)
		if ($profile['website'])
		{
			$extend->homepage = $profile['website'];
		}

		// 생일 (별도의 검수 필요)
		if ($profile['birthday'] && $birthday = explode('/', $profile['birthday']))
		{
			$extend->birthday = ($birthday[2] ?: date('Y')) . $birthday[0] . $birthday[1];
		}

		// 성별
		if ($profile['gender'] == 'male')
		{
			$extend->gender = '남성';
		}
		else if ($profile['gender'] == 'female')
		{
			$extend->gender = '여성';
		}

		// 연령대
		if ($profile['age_range']['min'] || $profile['age_range']['max'])
		{
			if ($profile['age_range']['min'] && $profile['age_range']['max'])
			{
				$age = ($profile['age_range']['min'] + $profile['age_range']['max']) / 2;
			}
			else
			{
				$age = max($profile['age_range']['min'], $profile['age_range']['max']);
			}

			$extend->age = floor($age / 10) * 10 . '대';
		}

		return $extend;
	}

	function requestAPI($url, $post = array(), $authorization = null, $delete = false)
	{
		return json_decode(\FileHandler::getRemoteResource(self::FACEBOOK_GRAPH_URL . self::FACEBOOK_GRAPH_API_VERSION . $url, null, 3, $delete ? 'DELETE' : (empty($post) ? 'GET' : 'POST'), 'application/x-www-form-urlencoded', array(), array(), $post, array('ssl_verify_peer' => false)), true);
	}
}
