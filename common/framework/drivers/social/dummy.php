<?php
namespace Rhymix\Framework\Drivers\Social;

/**
 * The dummy SMS driver.
 */
class Dummy extends Base implements \Rhymix\Framework\Drivers\SocialInterface
{
	function createAuthUrl($type)
	{
		return false;
	}

	function authenticate()
	{
		return false;
	}

	function loading()
	{
		return false;
	}

	function requestAPI($url, $type = array(), $authorization = null, $delete = null)
	{
		// TODO: Implement requestAPI() method.
	}
}
