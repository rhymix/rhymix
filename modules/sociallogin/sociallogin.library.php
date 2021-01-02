<?php
//TODO(BJRambo): it will be update to Rhymix Framework
class SocialloginLibrary extends Sociallogin
{
	private $service;
	private $profile;
	private $token;

	function __construct($service)
	{
		$this->service = $service;

		$this->profile = array(
			'id'       => '',
			'email'    => '',
			'name'     => '',
			'image'    => '',
			'url'      => '',
			'verified' => false,
			'etc'      => '',
		);

		$this->token = array(
			'access'  => '',
			'refresh' => '',
		);

		$this->config = $this->getConfig();
	}

	/**
	 * @brief 인증 URL 생성 (SNS 로그인 URL)
	 */
	function createAuthUrl($type)
	{
	}

	/**
	 * @brief 인증 단계 (로그인 후 callback 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	function authenticate()
	{
		return new BaseObject();
	}

	/**
	 * @brief 로딩 단계 (인증 후 프로필 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	function loading()
	{
		return new BaseObject();
	}

	/**
	 * @brief 토큰 파기 (SNS 해제 또는 회원 삭제시 실행)
	 */
	function revokeToken()
	{
	}

	/**
	 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
	 */
	function refreshToken()
	{
	}

	/**
	 * @brief 연동 체크 (SNS 연동 설정 전 연동 가능 여부를 체크)
	 */
	function checkLinkage()
	{
		// 기본적으로는 연동 불가 메세지
		return new BaseObject(-1, sprintf(Context::getLang('msg_not_support_linkage_setting'), ucwords($this->service)));
	}

	/**
	 * @brief SNS로 전송 (연동)
	 */
	function post($args)
	{
	}

	/**
	 * @brief 프로필 확장 (가입시 추가 기입)
	 */
	function getProfileExtend()
	{
		$extend = new stdClass;
		$extend->signature = '';
		$extend->homepage = '';
		$extend->blog = '';
		$extend->birthday = '';
		$extend->gender = '';
		$extend->age = '';

		return $extend;
	}

	function setToken($token)
	{
		$this->token = $token;
	}

	function setAccessToken($access_token)
	{
		$this->token['access'] = $access_token;
	}

	function setRefreshToken($refresh_token)
	{
		$this->token['refresh'] = $refresh_token;
	}

	function setProfile($profile)
	{
		$this->profile = $profile;
	}

	function setId($id)
	{
		$this->profile['id'] = $id;
	}

	function setEmail($email)
	{
		$this->profile['email'] = $email;
	}

	function setName($name)
	{
		$this->profile['name'] = $name;
	}

	function setProfileImage($image)
	{
		$this->profile['image'] = $image;
	}

	function setProfileUrl($url)
	{
		$this->profile['url'] = $url;
	}

	function setVerified($verified)
	{
		$this->profile['verified'] = $verified ? true : false;
	}

	function setProfileEtc($value)
	{
		$this->profile['etc'] = $value;
	}

	function getService()
	{
		return $this->service;
	}

	function getToken()
	{
		return $this->token;
	}

	/**
	 * @return array
	 */
	function getAccessToken()
	{
		return $this->token['access'];
	}

	function getRefreshToken()
	{
		return $this->token['refresh'];
	}

	function getProfile()
	{
		return $this->profile;
	}

	function getId()
	{
		return $this->profile['id'];
	}

	function getEmail()
	{
		return $this->profile['email'];
	}

	function getName()
	{
		return $this->profile['name'];
	}

	function getProfileImage()
	{
		return $this->profile['image'];
	}

	function getProfileUrl()
	{
		return $this->profile['url'];
	}

	function getVerified()
	{
		return $this->profile['verified'];
	}

	function getProfileEtc()
	{
		return $this->profile['etc'];
	}

	function set($value)
	{
		if ($value['token'])
		{
			$this->token = $value['token'];
		}

		if ($value['profile'])
		{
			$this->profile = $value['profile'];
		}
	}

	function get()
	{
		return array(
			'service' => $this->service,
			'token'   => $this->token,
			'profile' => $this->profile,
		);
	}
}
