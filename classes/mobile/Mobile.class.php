<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Mobile class
 *
 * @author NAVER (developers@xpressengine.com)
 */
class Mobile
{
	/**
	 * Whether mobile or not mobile mode
	 * @var bool
	 */
	public $ismobile = NULL;

	/**
	 * Get instance of Mobile class(for singleton)
	 *
	 * @return Mobile
	 */
	public function getInstance()
	{
		static $theInstance;
		if(!isset($theInstance))
		{
			$theInstance = new Mobile();
		}
		return $theInstance;
	}

	/**
	 * Get current mobile mode
	 *
	 * @return bool If mobile mode returns true or false
	 */
	public static function isFromMobilePhone()
	{
		return self::getInstance()->_isFromMobilePhone();
	}

	/**
	 * Get current mobile mode
	 *
	 * @return bool
	 */
	public function _isFromMobilePhone()
	{
		if($this->ismobile !== NULL)
		{
			return $this->ismobile;
		}
		if(!config('use_mobile_view') || Context::get('full_browse') || $_COOKIE["FullBrowse"])
		{
			return $this->ismobile = false;
		}

		$this->ismobile = FALSE;

		$m = Context::get('m');
		if(strlen($m) == 1)
		{
			if($m == "1")
			{
				$this->ismobile = TRUE;
			}
			elseif($m == "0")
			{
				$this->ismobile = FALSE;
			}
		}
		elseif(isset($_COOKIE['mobile']))
		{
			if($_COOKIE['user-agent'] == md5($_SERVER['HTTP_USER_AGENT']))
			{
				if($_COOKIE['mobile'] == 'true')
				{
					$this->ismobile = TRUE;
				}
				else
				{
					$this->ismobile = FALSE;
				}
			}
			else
			{
				setcookie("mobile", FALSE, 0, RX_BASEURL);
				setcookie("user-agent", FALSE, 0, RX_BASEURL);
				$this->ismobile = Rhymix\Framework\UA::isMobile() && !Rhymix\Framework\UA::isTablet();
			}
		}
		else
		{
			$this->ismobile = Rhymix\Framework\UA::isMobile() && !Rhymix\Framework\UA::isTablet();
		}

		if($this->ismobile !== NULL)
		{
			if($this->ismobile == TRUE)
			{
				if($_COOKIE['mobile'] != 'true')
				{
					$_COOKIE['mobile'] = 'true';
					setcookie("mobile", 'true', 0, RX_BASEURL);
				}
			}
			elseif(isset($_COOKIE['mobile']) && $_COOKIE['mobile'] != 'false')
			{
				$_COOKIE['mobile'] = 'false';
				setcookie("mobile", 'false', 0, RX_BASEURL);
			}

			if(isset($_COOKIE['mobile']) && $_COOKIE['user-agent'] != md5($_SERVER['HTTP_USER_AGENT']))
			{
				setcookie("user-agent", md5($_SERVER['HTTP_USER_AGENT']), 0, RX_BASEURL);
			}
		}

		return $this->ismobile;
	}

	/**
	 * Detect mobile device by user agent
	 *
	 * @return bool Returns true on mobile device or false.
	 */
	public static function isMobileCheckByAgent()
	{
		return Rhymix\Framework\UA::isMobile();
	}

	/**
	 * Check if user-agent is a tablet PC as iPad or Andoid tablet.
	 *
	 * @return bool TRUE for tablet, and FALSE for else.
	 */
	public static function isMobilePadCheckByAgent()
	{
		return Rhymix\Framework\UA::isTablet();
	}

	/**
	 * Set mobile mode
	 *
	 * @param bool $ismobile
	 * @return void
	 */
	public static function setMobile($ismobile)
	{
		self::getInstance()->ismobile = (bool)$ismobile;
	}

	public static function isMobileEnabled()
	{
		return config('use_mobile_view');
	}
}
