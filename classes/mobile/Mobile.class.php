<?php

class Mobile {
	var $ismobile = null;

	function &getInstance() {
		static $theInstance;
		if(!isset($theInstance)) $theInstance = new Mobile();
		return $theInstance;
	}

	function isFromMobilePhone() {
		$oMobile =& Mobile::getInstance();
		return $oMobile->_isFromMobilePhone();
	}

	function _isFromMobilePhone() {
		if(isset($this->ismobile)) return $this->ismobile;
		$db_info = Context::getDBInfo();
		if($db_info->use_mobile_view != "Y" || Context::get('full_browse') || $_COOKIE["FullBrowse"])
		{
			$this->ismobile = false;
		}
		else
		{
			$this->ismobile = Context::get('mobile') || preg_match('/(iPod|iPhone|Android|SCH\-M[0-9]+)/',$_SERVER['HTTP_USER_AGENT']);
		}

		return $this->ismobile;
	}

	function setMobile($ismobile)
	{
		$oMobile =& Mobile::getInstance();
		$oMobile->ismobile = $ismobile;
	}
}

?>
