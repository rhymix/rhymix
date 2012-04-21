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

		// default setting. if there is cookie for a device, XE do not have to check if it is mobile or not and it will enhance performace of the server.
		$this->ismobile = FALSE;

		$m = Context::get('m');
		if(strlen($m)==1) {
			if($m == "1") {
				$this->ismobile = true;
			} elseif($m == "0") {
				$this->ismobile = false;
			}
		} elseif(isset($_COOKIE['mobile'])) {
			if($_COOKIE['user-agent'] == md5($_SERVER['HTTP_USER_AGENT']))
			{
				if($_COOKIE['mobile']  == 'true') {
					$this->ismobile = true;
				} else {
					$this->ismobile = false;
				}
			}
			else
			{
				$this->ismobile = FALSE;
				setcookie("mobile", FALSE, 0, $xe_web_path);
				setcookie("user-agent", FALSE, 0, $xe_web_path);
				if(!$this->isMobilePadCheckByAgent() && $this->isMobileCheckByAgent())
				{
					$this->ismobile = TRUE;
				}
			}
		}
		else
		{
			if($this->isMobilePadCheckByAgent())
			{
				$this->ismobile = FALSE;
			}
			else
			{
				if($this->isMobileCheckByAgent())
				{
					$this->ismobile = TRUE;
				}
			}
		}

		if($this->ismobile !== NULL)
		{
			if($this->ismobile == TRUE)
			{
				if($_COOKIE['mobile'] != 'true')
				{
					$_COOKIE['mobile'] = 'true';
					setcookie("mobile", 'true', 0, $xe_web_path);
				}
			}
			elseif($_COOKIE['mobile'] != 'false')
			{
				$_COOKIE['mobile'] = 'false';
				setcookie("mobile", 'false', 0, $xe_web_path);
			}

			if($_COOKIE['user-agent'] != md5($_SERVER['HTTP_USER_AGENT']))
			{
				setcookie("user-agent",md5($_SERVER['HTTP_USER_AGENT']), 0, $xe_web_path);
			}
		}

		return $this->ismobile;
	}

	function isMobileCheckByAgent()
	{
		static $UACheck;
		if(isset($UACheck)) return $UACheck;

		$oMobile =& Mobile::getInstance();
		$mobileAgent = array('iPod','iPhone','Android','BlackBerry','SymbianOS','Bada','Kindle','Wii','SCH-','SPH-','CANU-','Windows Phone','Windows CE','POLARIS','Palm','Dorothy Browser','IEMobile','Opera Mobi','Opera Mini','MobileExplorer','Minimo','AvantGo','NetFront','Googlebot-Mobile','Nokia','LGPlayer','SonyEricsson','HTC','SKT','lgtelecom','Vodafone');

		if($oMobile->isMobilePadCheckByAgent())
		{
			$UACheck = TRUE;
			return TRUE;
		}

		foreach($mobileAgent as $agent)
		{
			if(strpos($_SERVER['HTTP_USER_AGENT'], $agent) !== FALSE)
			{
				$UACheck = TRUE;
				return TRUE;
			}
		}
		$UACheck = FALSE;
		return FALSE;
	}

	/*
	 * @ brief check if user-agent is a tablet PC as iPad or Andoid tablet.
	 * @ return TRUE for tablet, and FALSE for else.
	*/
	function isMobilePadCheckByAgent()
	{
		static $UACheck;
		if(isset($UACheck)) return $UACheck;
		$padAgent = array('iPad','webOS','hp-tablet','PlayBook');

		foreach($padAgent as $agent)
		{
			if(strpos($_SERVER['HTTP_USER_AGENT'], $agent) !== FALSE)
			{
				$UACheck = TRUE;
				return TRUE;
			}
		}

		$UACheck = FALSE;
		return FALSE;
	}

	function setMobile($ismobile)
	{
		$oMobile =& Mobile::getInstance();
		$oMobile->ismobile = $ismobile;
	}
}

?>
