<?php

/**
 * @deprecated
 */
class IpFilter
{
	public static function filter($ip_list, $ip = NULL)
	{
		if(!$ip) $ip = \RX_CLIENT_IP;
		return Rhymix\Framework\Filters\IpFilter::inRanges($ip, $ip_list);
	}

	public static function validate($ip_list = array())
	{
		return Rhymix\Framework\Filters\IpFilter::validateRanges($ip_list);
	}
}
