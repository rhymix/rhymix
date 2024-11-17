<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilterController
 * @author NAVER (developers@xpressengine.com)
 * @brief The controller class for the spamfilter module
 */
class SpamfilterController extends Spamfilter
{
	/**
	 * List of actions to use CAPTCHA.
	 */
	protected static $_captcha_actions = array(
		'signup' => '/^(?:disp|proc)Member(?:SignUp|Insert$)/i',
		'login' => '/^(?:disp|proc)MemberLogin(?:Form)?/i',
		'recovery' => '/^(?:disp|proc)Member(?:FindAccount|ResendAuthMail)/i',
		'document' => '/^(?:disp|proc)Board(Write|InsertDocument)/i',
		'comment' => '/^(?:disp|proc)Board(Content|InsertComment|ModifyComment|ReplyComment)/i',
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

		// Check if the IP is prohibited
		$output = SpamfilterModel::isDeniedIP();
		if(!$output->toBool())
		{
			$config = SpamfilterModel::getConfig();
			if (!isset($config->blocked_actions) || in_array('document', $config->blocked_actions))
			{
				return $output;
			}
		}

		// Check if there is a ban on the word
		$filter_targets = [$obj->title, $obj->content, $obj->tags ?? ''];
		if(!$is_logged)
		{
			$filter_targets[] = $obj->nick_name;
			$filter_targets[] = $obj->homepage;
		}
		foreach ($obj as $key => $val)
		{
			if (preg_match('/^extra_vars\d+$/', $key) && !empty($val))
			{
				foreach (is_array($val) ? $val : explode('|@|', $val) as $fragment)
				{
					$filter_targets[] = $fragment;
				}
			}
		}
		$output = SpamfilterModel::isDeniedWord(implode("\n", $filter_targets));
		if(!$output->toBool())
		{
			return $output;
		}
		// Check the specified time beside the modificaiton time
		if($obj->document_srl == 0)
		{
			$output = SpamfilterModel::checkLimited();
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

		// Check if the IP is prohibited
		$output = SpamfilterModel::isDeniedIP();
		if(!$output->toBool())
		{
			$config = SpamfilterModel::getConfig();
			if (!isset($config->blocked_actions) || in_array('comment', $config->blocked_actions))
			{
				return $output;
			}
		}

		// Check if there is a ban on the word
		if($is_logged)
		{
			$text = $obj->content;
		}
		else
		{
			$text = $obj->content . ' ' . $obj->nick_name . ' ' . $obj->homepage;
		}
		$output = SpamfilterModel::isDeniedWord($text);
		if(!$output->toBool()) return $output;
		// If the specified time check is not modified
		if(!$obj->__isupdate)
		{
			$output = SpamfilterModel::checkLimited();
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
	 * Block voting from a spam IP.
	 */
	function triggerVote(&$obj)
	{
		if (!empty($_SESSION['avoid_log']))
		{
			return;
		}

		if ($this->user->isAdmin() || (Context::get('grant')->manager ?? false))
		{
			return;
		}

		$config = SpamfilterModel::getConfig();
		if ($obj->point > 0 && isset($config->blocked_actions) && !in_array('vote_up', $config->blocked_actions))
		{
			return;
		}
		if ($obj->point < 0 && isset($config->blocked_actions) && !in_array('vote_down', $config->blocked_actions))
		{
			return;
		}

		$output = SpamfilterModel::isDeniedIP();
		if (!$output->toBool())
		{
			return $output;
		}
	}

	/**
	 * Block reporting from a spam IP.
	 */
	function triggerDeclare(&$obj)
	{
		if (!empty($_SESSION['avoid_log']))
		{
			return;
		}

		if ($this->user->isAdmin() || (Context::get('grant')->manager ?? false))
		{
			return;
		}

		$config = SpamfilterModel::getConfig();
		if (isset($config->blocked_actions) && !in_array('declare', $config->blocked_actions))
		{
			return;
		}

		$output = SpamfilterModel::isDeniedIP();
		if (!$output->toBool())
		{
			return $output;
		}
	}

	/**
	 * @brief The routine process to check the time it takes to store a message, when writing it, and to ban IP/word
	 */
	function triggerSendMessage(&$obj)
	{
		if($this->user->isAdmin() || !empty($_SESSION['avoid_log']))
		{
			return;
		}

		if(isset($obj->use_spamfilter) && $obj->use_spamfilter === false)
		{
			return;
		}

		// Check if the IP is prohibited
		$output = SpamfilterModel::isDeniedIP();
		if(!$output->toBool())
		{
			$config = SpamfilterModel::getConfig();
			if (!isset($config->blocked_actions) || in_array('message', $config->blocked_actions))
			{
				return $output;
			}
		}

		// Check if there is a ban on the word
		$text = $obj->title . ' ' . $obj->content;
		$output = SpamfilterModel::isDeniedWord($text);
		if(!$output->toBool()) return $output;

		// Check the specified time
		$output = SpamfilterModel::checkLimited(TRUE);
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
		if (!SpamfilterModel::isCaptchaEnabled())
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
			$captcha_class = 'Rhymix\\Modules\\Spamfilter\\Captcha\\' . $config->captcha->type;
			$captcha_class::init($config->captcha);

			if (strncasecmp('proc', $obj->act, 4) === 0)
			{
				$captcha_class::check();
			}
			else
			{
				$captcha = new $captcha_class();
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
