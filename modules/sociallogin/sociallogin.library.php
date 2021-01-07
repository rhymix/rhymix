<?php

//TODO(BJRambo): it will be update to Rhymix Framework
class SocialloginLibrary extends Sociallogin
{
	private static $service;
	private static $profile;
	private static $token;

	public function __construct($service)
	{
		self::$service = $service;

		self::$profile = array(
			'id'       => '',
			'email'    => '',
			'name'     => '',
			'image'    => '',
			'url'      => '',
			'verified' => false,
			'etc'      => '',
		);

		self::$token = array(
			'access'  => '',
			'refresh' => '',
		);
		
		//TODO Check later.
		self::$config = self::getConfig();
	}

	/**
	 * @brief 인증 URL 생성 (SNS 로그인 URL)
	 */
	public function createAuthUrl($type)
	{
	}

	/**
	 * @brief 인증 단계 (로그인 후 callback 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	public function authenticate()
	{
		return new BaseObject();
	}

	/**
	 * @brief 로딩 단계 (인증 후 프로필 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	public function loading()
	{
		return new BaseObject();
	}

	/**
	 * @brief 토큰 파기 (SNS 해제 또는 회원 삭제시 실행)
	 */
	public function revokeToken()
	{
	}

	/**
	 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
	 */
	public function refreshToken()
	{
	}

	/**
	 * @brief 연동 체크 (SNS 연동 설정 전 연동 가능 여부를 체크)
	 */
	public function checkLinkage()
	{
		// 기본적으로는 연동 불가 메세지
		return new BaseObject(-1, sprintf(Context::getLang('msg_not_support_linkage_setting'), ucwords($this->service)));
	}

	/**
	 * @brief SNS로 전송 (연동)
	 */
	public function post($args)
	{
	}

	/**
	 * @brief 프로필 확장 (가입시 추가 기입)
	 */
	public function getProfileExtend()
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

	public function setToken($token)
	{
		self::$token = $token;
	}

	public function setAccessToken($access_token)
	{
		self::$token['access'] = $access_token;
	}

	public function setRefreshToken($refresh_token)
	{
		self::$token['refresh'] = $refresh_token;
	}

	public function setProfile($profile)
	{
		self::$profile = $profile;
	}

	public function setId($id)
	{
		self::$profile['id'] = $id;
	}

	public function setEmail($email)
	{
		self::$profile['email'] = $email;
	}

	public function setName($name)
	{
		self::$profile['name'] = $name;
	}

	public function setProfileImage($image)
	{
		self::$profile['image'] = $image;
	}

	public function setProfileUrl($url)
	{
		self::$profile['url'] = $url;
	}

	public function setVerified($verified)
	{
		self::$profile['verified'] = $verified ? true : false;
	}

	public function setProfileEtc($value)
	{
		self::$profile['etc'] = $value;
	}

	public function getService()
	{
		return self::$service;
	}

	public function getToken()
	{
		return self::$token;
	}
	
	public function getAccessToken()
	{
		return self::$token['access'];
	}

	public function getRefreshToken()
	{
		return self::$token['refresh'];
	}

	public function getProfile()
	{
		return self::$profile;
	}

	public function getId()
	{
		return self::$profile['id'];
	}

	public function getEmail()
	{
		return self::$profile['email'];
	}

	public function getName()
	{
		return self::$profile['name'];
	}

	public function getProfileImage()
	{
		return self::$profile['image'];
	}

	public function getProfileUrl()
	{
		return self::$profile['url'];
	}

	public function getVerified()
	{
		return self::$profile['verified'];
	}

	public function getProfileEtc()
	{
		return self::$profile['etc'];
	}

	public function setSocial($value)
	{
		if ($value['token'])
		{
			self::$token = $value['token'];
		}

		if ($value['profile'])
		{
			self::$profile = $value['profile'];
		}
	}

	public function getSocial()
	{
		return array(
			'service' => self::$service,
			'token'   => self::$token,
			'profile' => self::$profile,
		);
	}
}
