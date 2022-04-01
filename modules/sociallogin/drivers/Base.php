<?php

namespace Rhymix\Modules\Sociallogin\Drivers;

abstract class Base
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
	 * 
	 * sns_id 는 각 SNS 에서 넘어온 고유의 아이디.
	 */
	protected function __construct()
	{
		$this->service = strtolower(class_basename($this));
		$this->profile = array(
			'sns_id'       => '',
			'email_address'    => '',
			'user_name'     => '',
			'profile_image'    => '',
			'url'      => '',
			'verified' => false,
			'etc'      => '',
		);
		$this->token = array(
			'access'  => '',
			'refresh' => '',
		);
		$this->config = \Rhymix\Modules\Sociallogin\Base::getConfig();
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
	public function getSNSUserInfo()
	{
		return new \BaseObject();
	}

	/**
	 * @brief 토큰 파기 (SNS 해제 또는 회원 삭제시 실행)
	 */
	public function revokeToken(string $access_token = '')
	{
		
	}

	/**
	 * @brief 토큰 새로고침 (로그인 지속이 되어 토큰 만료가 될 경우를 대비)
	 */
	public function refreshToken(string $refresh_token = ''): array
	{
		return [];
	}

	/**
	 * @brief 연동 체크 (SNS 연동 설정 전 연동 가능 여부를 체크)
	 */
	public function checkLinkage()
	{
		// 기본적으로는 연동 불가 메세지
		return new \BaseObject(-1, sprintf(\Context::getLang('msg_not_support_linkage_setting'), ucwords($this->service)));
	}

	/**
	 * @brief SNS로 전송 (연동)
	 */
	public function post($args)
	{
		
	}

	public function getProfileImage()
	{
		return false;
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

	public function getService()
	{
		return $this->service;
	}

	public function getSocial()
	{
		$serviceAccessData = \SocialloginModel::getAccessData($this->service);
		
		return array(
			'service' => $this->service,
			'token'   => $serviceAccessData->token,
			'profile' => $serviceAccessData->profile,
		);
	}
}
