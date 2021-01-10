<?php
namespace Rhymix\Framework\Drivers;

interface SocialInterface
{
	function createAuthUrl($type);
	function authenticate();
	function loading();
	function revokeToken();
	function refreshToken();
	function getProfileExtend();
	function requestAPI($url, $type = array(), $authorization = null, $delete = null);
}
