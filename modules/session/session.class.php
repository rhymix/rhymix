<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  session
 * @author NAVER (developers@xpressengine.com)
 * @brief session module's high class
 * @version 0.1
 *
 * The session management class
 */
class session extends ModuleObject
{
	var $lifetime = 18000;
	var $session_started = false;

	function __construct()
	{
		if(Context::isInstalled()) $this->session_started= true;
		parent::__construct();
	}

	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	function moduleInstall()
	{
		$oDB = &DB::getInstance();
		$oDB->addIndex("session","idx_session_update_mid", array("member_srl","last_update","cur_mid"));
	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		if(!$oDB->isIndexExists("session","idx_session_update_mid")) return true;
		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		if(!$oDB->isIndexExists("session","idx_session_update_mid"))
		{
			$oDB->addIndex("session","idx_session_update_mid", array("member_srl","last_update","cur_mid"));
		}
	}

	/**
	 * @brief session string decode
	 */
	function unSerializeSession($val)
	{
		$vars = preg_split('/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff^|]*)\|/', $val,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		for($i=0; $vars[$i]; $i++) $result[$vars[$i++]] = unserialize($vars[$i]);
		return $result;
	}

	/**
	 * @brief session string encode
	 */
	function serializeSession($data)
	{
		if(!count($data)) return;

		$str = '';
		foreach($data as $key => $val) $str .= $key.'|'.serialize($val);
		return substr($str, 0, strlen($str)-1).'}';
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file session.class.php */
/* Location: ./modules/session/session.class.php */
