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
			$m = Context::get('m');
			if($m == "1") {
				$_COOKIE["mobile"] = true;
				setcookie("mobile", true);
				$this->ismobile = true;
			}
			else if($m === "0") {
				$_COOKIE["mobile"] = false;
				setcookie("mobile", false);
				$this->ismobile = false;
			}
			else if($_COOKIE["mobile"] == true) {
				$this->ismobile = true;
			}
			else if($_COOKIE["mobile"] == false) {
				$this->ismobile = false;
			} 
			else {
				if(preg_match('/(iPod|iPhone|Android|BlackBerry|SymbianOS|SCH\-M[0-9]+)/',$_SERVER['HTTP_USER_AGENT']))
				{
					setcookie("mobile", true);
					$this->ismobile = true;
				}
			}
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
