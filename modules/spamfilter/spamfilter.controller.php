<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilterController
 * @author NAVER (developers@xpressengine.com)
 * @brief The controller class for the spamfilter module
 */
class spamfilterController extends spamfilter
{
	/**
	 * List of actions to use CAPTCHA.
	 */
	protected static $_captcha_actions = array(
		'signup' => '/^(?:disp|proc)Member(?:SignUp|Insert)/i',
		'login' => '/^(?:disp|proc)MemberLogin(?:Form)?/i',
		'recovery' => '/^(?:disp|proc)Member(?:FindAccount|ResendAuthMail)/i',
		'document' => '/^(?:disp|proc)Board(Write|InsertDocument)/i',
		'comment' => '/^(?:disp|proc)Board(Content|InsertComment)/i',
	);

	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Call this function in case you need to stop the spam filter's usage during the batch work
	 */
	function setAvoidLog()
	{
		$_SESSION['avoid_log'] = true;
	}

	/**
	 * @brief The routine process to check the time it takes to store a document, when writing it, and to ban IP/word
	 */
	function triggerInsertDocument(&$obj)
	{
		if($_SESSION['avoid_log']) return;
		// Check the login status, login information, and permission
		$is_logged = Context::get('is_logged');
		$logged_info = Context::get('logged_info');
		$grant = Context::get('grant');
		// In case logged in, check if it is an administrator
		if($is_logged)
		{
			if($logged_info->is_admin == 'Y') return;
			if($grant->manager) return;
		}

		$oFilterModel = getModel('spamfilter');
		// Check if the IP is prohibited
		$output = $oFilterModel->isDeniedIP();
		if(!$output->toBool()) return $output;
		// Check if there is a ban on the word
		$text = '';
		if($is_logged)
		{
			$text = $obj->title . ' ' . $obj->content . ' ' . $obj->tags;
		}
		else
		{
			$text = $obj->title . ' ' . $obj->content . ' ' . $obj->nick_name . ' ' . $obj->homepage . ' ' . $obj->tags;	
		}
		$output = $oFilterModel->isDeniedWord($text);
		if(!$output->toBool())
		{
			return $output;
		}
		// Check the specified time beside the modificaiton time
		if($obj->document_srl == 0)
		{
			$output = $oFilterModel->checkLimited();
			if(!$output->toBool()) return $output;
		}
		// Save a log
		$this->insertLog();
	}

	/**
	 * @brief The routine process to check the time it takes to store a comment, and to ban IP/word
	 */
	function triggerInsertComment(&$obj)
	{
		if($_SESSION['avoid_log']) return;
		// Check the login status, login information, and permission
		$is_logged = Context::get('is_logged');
		$logged_info = Context::get('logged_info');
		$grant = Context::get('grant');
		// In case logged in, check if it is an administrator
		if($is_logged)
		{
			if($logged_info->is_admin == 'Y') return;
			if($grant->manager) return;
		}

		$oFilterModel = getModel('spamfilter');
		// Check if the IP is prohibited
		$output = $oFilterModel->isDeniedIP();
		if(!$output->toBool()) return $output;
		// Check if there is a ban on the word
		if($is_logged)
		{
			$text = $obj->content;
		}
		else
		{
			$text = $obj->content . ' ' . $obj->nick_name . ' ' . $obj->homepage;	
		}
		$output = $oFilterModel->isDeniedWord($text);
		if(!$output->toBool()) return $output;
		// If the specified time check is not modified
		if(!$obj->__isupdate)
		{
			$output = $oFilterModel->checkLimited();
			if(!$output->toBool()) return $output;
		}
		unset($obj->__isupdate);
		// Save a log
		$this->insertLog();
	}

	/**
	 * @brief IP registration
	 * The registered IP address is considered as a spammer
	 */
	function insertIP($ipaddress_list, $description = null)
	{
		if (!is_array($ipaddress_list))
		{
			$ipaddress_list = array_map('trim', explode("\n", $ipaddress_list));
		}
		$fail_list = '';
		$output = null;
		
		foreach ($ipaddress_list as $ipaddress)
		{
			if ($ipaddress === '')
			{
				continue;
			}
			
			$args = new stdClass;
			if (preg_match('@^(.+?)(?://|#)(.*)$@', $ipaddress, $matches))
			{
				$args->ipaddress = trim($matches[1]);
				$args->description = trim($matches[2]);
			}
			else
			{
				$args->ipaddress = $ipaddress;
				$args->description = $description;
			}
			
			if (!Rhymix\Framework\Filters\IpFilter::validateRange($args->ipaddress))
			{
				return new BaseObject(-1, 'msg_invalid_ip');
			}
			
			$output = executeQuery('spamfilter.insertDeniedIP', $args);
			if (!$output->toBool())
			{
				$fail_list .= $args->ipaddress . '<br />';
			}
		}
		
		if ($output)
		{
			$output->add('fail_list', $fail_list);
		}
		
		Rhymix\Framework\Cache::delete('spamfilter:denied_ip_list');
		return $output;
	}

	/**
	 * @brief The routine process to check the time it takes to store a message, when writing it, and to ban IP/word
	 */
	function triggerSendMessage(&$obj)
	{
		if($_SESSION['avoid_log']) return;
		if(isset($obj->use_spamfilter) && $obj->use_spamfilter === false)
		{
			return;
		}

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin == 'Y') return;

		$oFilterModel = getModel('spamfilter');
		// Check if the IP is prohibited
		$output = $oFilterModel->isDeniedIP();
		if(!$output->toBool()) return $output;
		// Check if there is a ban on the word
		$text = $obj->title . ' ' . $obj->content;
		$output = $oFilterModel->isDeniedWord($text);
		if(!$output->toBool()) return $output;
		// Check the specified time
		$output = $oFilterModel->checkLimited(TRUE);
		if(!$output->toBool()) return $output;
		// Save a log
		$this->insertLog();
	}
	
	/**
	 * @brief while document manager is running, stop filter
	 */
	function triggerManageDocument(&$obj)
	{
		$this->setAvoidLog();
	}

	/**
	 * Trigger to check CAPTCHA.
	 */
	function triggerCheckCaptcha(&$obj)
	{
		$config = ModuleModel::getModuleConfig('spamfilter');
		if (!isset($config) || !isset($config->captcha) || $config->captcha->type !== 'recaptcha' || !$config->captcha->site_key || !$config->captcha->secret_key)
		{
			return;
		}
		if ($this->user->is_admin === 'Y')
		{
			return;
		}
		if ($config->captcha->target_users !== 'everyone' && $this->user->member_srl)
		{
			return;
		}
		if ($config->captcha->target_frequency !== 'every_time' && isset($_SESSION['recaptcha_authenticated']) && $_SESSION['recaptcha_authenticated'])
		{
			return;
		}
		if (!$config->captcha->target_devices[Mobile::isFromMobilePhone() ? 'mobile' : 'pc'])
		{
			return;
		}
		
		$target_actions = [];
		foreach (['signup', 'login', 'recovery', 'document', 'comment'] as $action)
		{
			if ($config->captcha->target_actions[$action])
			{
				if (preg_match(self::$_captcha_actions[$action], $obj->act) || ($action === 'comment' && (!$obj->act || $obj->act === 'dispBoardContent') && Context::get('document_srl')))
				{
					$target_actions[$action] = true;
				}
			}
		}
		
		if (count($target_actions))
		{
			include_once __DIR__ . '/spamfilter.lib.php';
			spamfilter_reCAPTCHA::init($config->captcha);
			
			if (strncasecmp('proc', $obj->act, 4) === 0)
			{
				spamfilter_reCAPTCHA::check();
			}
			else
			{
				$captcha = new spamfilter_reCAPTCHA();
				$captcha->setTargetActions($target_actions);
				$captcha->addScripts();
				Context::set('captcha', $captcha);
			}
		}
	}

	/**
	 * @brief Log registration
	 * Register the newly accessed IP address in the log. In case the log interval is withing a certain time,
	 * register it as a spammer
	 */
	function insertLog()
	{
		$output = executeQuery('spamfilter.insertLog');
		return $output;
	}
}
/* End of file spamfilter.controller.php */
/* Location: ./modules/spamfilter/spamfilter.controller.php */
