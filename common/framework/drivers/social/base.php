<?php

namespace Rhymix\Framework\Drivers\Social;

abstract class Base implements \Rhymix\Framework\Drivers\SocialInterface
{
	protected static $_instances = [];
	protected $service;
	protected $profile;
	protected $token;
	protected $config;
	protected $driver;
	
	/**
	 * Get a singleton instance of a driver class.
	 */
	public static function getInstance()
	{
		if (!isset(self::$_instances[static::class]))
		{
			self::$_instances[static::class] = new static;
		}
		return self::$_instances[static::class];
	}
	
	/**
	 * Create a singleton instance.
	 */
	protected function __construct()
	{
		$this->service = strtolower(class_basename($this));
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
		$this->config = \Sociallogin::getConfig();
	}
	
	/**
	 * @brief 인증 URL 생성 (SNS 로그인 URL)
	 */
	public function createAuthUrl(string $type = 'login'): string
	{
		return '';
	}

	/**
	 * @brief 인증 단계 (로그인 후 callback 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	public function authenticate()
	{
		return new \BaseObject();
	}

	/**
	 * @brief 로딩 단계 (인증 후 프로필 처리) [실행 중단 에러를 출력할 수 있음]
	 */
	public function loading()
	{
		return new \BaseObject();
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
		return new \BaseObject(-1, sprintf(Context::getLang('msg_not_support_linkage_setting'), ucwords($this->service)));
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
		$extend = new \stdClass();
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
		$this->token = $token;
	}

	public function setAccessToken($access_token)
	{
		$this->token['access'] = $access_token;
	}

	public function setRefreshToken($refresh_token)
	{
		$this->token['refresh'] = $refresh_token;
	}

	public function setProfile($profile)
	{
		$this->profile = $profile;
	}

	public function setId($id)
	{
		$this->profile['id'] = $id;
	}

	public function setEmail($email)
	{
		$this->profile['email'] = $email;
	}

	public function setName($name)
	{
		$this->profile['name'] = $name;
	}

	public function setProfileImage($image)
	{
		$this->profile['image'] = $image;
	}

	public function setProfileUrl($url)
	{
		$this->profile['url'] = $url;
	}

	public function setVerified($verified)
	{
		$this->profile['verified'] = $verified ? true : false;
	}

	public function setProfileEtc($value)
	{
		$this->profile['etc'] = $value;
	}

	public function getService()
	{
		return $this->service;
	}

	public function getToken()
	{
		return $this->token;
	}

	public function getAccessToken()
	{
		return $this->token['access'];
	}

	public function getRefreshToken()
	{
		return $this->token['refresh'];
	}

	public function getProfile()
	{
		return $this->profile;
	}

	public function getId()
	{
		return $this->profile['id'];
	}

	public function getEmail()
	{
		return $this->profile['email'];
	}

	public function getName()
	{
		return $this->profile['name'];
	}

	public function getProfileImage()
	{
		return $this->profile['image'];
	}

	public function getProfileUrl()
	{
		return $this->profile['url'];
	}

	public function getVerified()
	{
		return $this->profile['verified'];
	}

	public function getProfileEtc()
	{
		return $this->profile['etc'];
	}

	public function setSocial($value)
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

	public function getSocial()
	{
		return array(
			'service' => $this->service,
			'token'   => $this->token,
			'profile' => $this->profile,
		);
	}
}
