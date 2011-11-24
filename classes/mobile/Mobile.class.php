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
		if($this->ismobile !== null) return $this->ismobile;

		$db_info = Context::getDBInfo();
		if($db_info->use_mobile_view != "Y" || Context::get('full_browse') || $_COOKIE["FullBrowse"]) {
			return ($this->ismobile = false);
		}

		$xe_web_path = Context::pathToUrl(_XE_PATH_);

		$m = Context::get('m');
		if(strlen($m)==1) {
			if($m == "1") {
				$_COOKIE['mobile'] = 'true';
				setcookie('mobile', 'true', 0, $xe_web_path);
				$this->ismobile = true;
			} elseif($m == "0") {
				$_COOKIE['mobile'] = 'false';
				setcookie('mobile', 'false', 0, $xe_web_path);
				$this->ismobile = false;
			}
		} elseif(isset($_COOKIE['mobile'])) {
			if($_COOKIE['mobile']  == 'true') {
				$this->ismobile = true;
			} else {
				$_COOKIE['mobile'] = 'false';
				setcookie('mobile', 'false', 0, $xe_web_path);
				$this->ismobile = false;
			} 
		} else {
			if($this->isMobileCheckByAgent()) {
				setcookie("mobile", 'true', 0, $xe_web_path);
				$this->ismobile = true;
			}
		}

		return $this->ismobile;
	}

	function isMobileCheckByAgent()
	{
		return !!preg_match('/(iPod|iPhone|Android|BlackBerry|SymbianOS|SCH-M\d+|SPH-M\d+|Windows Phone|Dorothy Browser|Googlebot-Mobile)/',$_SERVER['HTTP_USER_AGENT']);
	}

	function setMobile($ismobile)
	{
		$oMobile =& Mobile::getInstance();
		$oMobile->ismobile = $ismobile;
	}
}

?>
