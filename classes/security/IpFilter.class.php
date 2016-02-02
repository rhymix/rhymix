<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class IpFilter
{
	public function filter($ip_list, $ip = NULL)
	{
		if(!$ip) $ip = $_SERVER['REMOTE_ADDR'];
		foreach($ip_list as $filter)
		{
			if(Rhymix\Framework\IpFilter::inRange($ip, $filter))
			{
				return true;
			}
		}
		return false;
	}
	
	public function validate($ip_list = array())
	{
		foreach($ip_list as $filter)
		{
			if(!Rhymix\Framework\IpFilter::validateRange($filter))
			{
				return false;
			}
		}
		return true;
	}
	
}

/* End of file : IpFilter.class.php */
/* Location: ./classes/security/IpFilter.class.php */
