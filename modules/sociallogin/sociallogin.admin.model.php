<?php

class SocialloginAdminModel extends Sociallogin
{
	/**
	 * 애플 보안 설정 파일의 위치를 가져옵니다.
	 * @return false|string
	 */
	public static function getAppleSecurityFile()
	{
		$config = self::getConfig();
		
		if(!Rhymix\Framework\Session::isAdmin())
		{
			return false;
		}

		if($config->apple_file_path && FileHandler::exists($config->apple_file_path))
		{
			return $config->apple_file_path;
		}
		
		return false;
	}
}
