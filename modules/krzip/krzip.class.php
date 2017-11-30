<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  krzip
 * @author NAVER (developers@xpressengine.com)
 * @brief  Krzip module high class.
 */

if(!function_exists('lcfirst'))
{
	function lcfirst($text)
	{
		return strtolower(substr($text, 0, 1)) . substr($text, 1);
	}
}

class krzip extends ModuleObject
{
	public static $sequence_id = 0;

	public static $default_config = array('api_handler' => 0);

	public static $api_list = array('daumapi', 'epostapi', 'postcodify');

	public static $epostapi_host = 'http://biz.epost.go.kr/KpostPortal/openapi';

	function moduleInstall()
	{
		
	}

	function checkUpdate()
	{
		return FALSE;
	}

	function moduleUpdate()
	{
		
	}
}

/* End of file krzip.class.php */
/* Location: ./modules/krzip/krzip.class.php */
