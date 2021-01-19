<?php
namespace Rhymix\Framework\Drivers;

interface SocialInterface
{
	public static function getInstance();
	public function createAuthUrl(string $type = 'login'): string;
	public function authenticate();
	public function getSNSUserInfo();
	public function revokeToken(string $access_token = '');
	public function refreshToken(string $refresh_token = ''): array;
	public function getProfileExtend();
	public function requestAPI($url, $type = array(), $authorization = null, $delete = null);
}
