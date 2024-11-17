<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilterModel
 * @author NAVER (developers@xpressengine.com)
 * @brief The Model class of the spamfilter module
 */
class SpamfilterModel extends Spamfilter
{
	/**
	 * @brief Return the user setting values of the Spam filter module
	 */
	public static function getConfig()
	{
		return ModuleModel::getModuleConfig('spamfilter') ?: new stdClass;
	}

	/**
	 * @brief Return the list of registered IP addresses which were banned
	 */
	public static function getDeniedIPList($sort_index = 'regdate')
	{
		$args = new stdClass();
		$args->sort_index = $sort_index;
		$args->page = Context::get('page')?Context::get('page'):1;
		$output = executeQueryArray('spamfilter.getDeniedIPList', $args);
		if(!$output->data) return array();
		return $output->data;
	}

	/**
	 * @brief Check if the ipaddress is in the list of banned IP addresses
	 */
	public static function isDeniedIP()
	{
		$ip_list = Rhymix\Framework\Cache::get('spamfilter:denied_ip_list');
		if ($ip_list === null)
		{
			$ip_list = self::getDeniedIPList();
			Rhymix\Framework\Cache::set('spamfilter:denied_ip_list', $ip_list);
		}
		if (!count($ip_list))
		{
			return new BaseObject();
		}

		$is_logged = Context::get('is_logged');
		foreach ($ip_list as $ip_range)
		{
			if (!empty($ip_range->except_member) && $ip_range->except_member === 'Y' && $is_logged)
			{
				continue;
			}

			if (Rhymix\Framework\Filters\IpFilter::inRange(\RX_CLIENT_IP, $ip_range->ipaddress))
			{
				$args = new stdClass();
				$args->ipaddress = $ip_range->ipaddress;
				executeQuery('spamfilter.updateDeniedIPHit', $args);

				return new BaseObject(-1, 'msg_alert_registered_denied_ip');
			}
		}

		return new BaseObject();
	}

	/**
	 * @brief Return the list of registered Words which were banned
	 */
	public static function getDeniedWordList($sort_index = 'hit')
	{
		$args = new stdClass();
		$args->sort_index = $sort_index;
		$output = executeQueryArray('spamfilter.getDeniedWordList', $args);
		return $output->data ?: array();
	}

	/**
	 * @brief Check if the text, received as a parameter, is banned or not
	 */
	public static function isDeniedWord($text)
	{
		$word_list = Rhymix\Framework\Cache::get('spamfilter:denied_word_list');
		if ($word_list === null)
		{
			$word_list = self::getDeniedWordList();
			Rhymix\Framework\Cache::set('spamfilter:denied_word_list', $word_list);
		}
		if (!count($word_list))
		{
			return new BaseObject();
		}

		$is_logged = Context::get('is_logged');
		$fulltext = strtolower(utf8_trim(utf8_normalize_spaces($text)));
		$plaintext = htmlspecialchars_decode(strip_tags($fulltext, '<a><img>'));

		foreach ($word_list as $word_item)
		{
			if (!empty($word_item->except_member) && $word_item->except_member === 'Y' && $is_logged)
			{
				continue;
			}

			$target = (!empty($word_item->filter_html) && $word_item->filter_html === 'Y') ? 'fulltext' : 'plaintext';
			$word = $word_item->word;
			$hit = false;

			if (preg_match('#^/.+/$#', $word))
			{
				$hit = preg_match($word . 'iu', $$target, $matches) ? $matches[0] : false;
			}
			if ($hit === false)
			{
				$hit = (strpos($$target, strtolower($word)) !== false) ? $word : false;
			}
			if ($hit !== false)
			{
				$args = new stdClass();
				$args->word = $word;
				executeQuery('spamfilter.updateDeniedWordHit', $args);

				$config = self::getConfig();

				if($config->custom_message)
				{
					if(preg_match('/^\\$user_lang->[a-zA-Z0-9]+$/', $config->custom_message))
					{
						$custom_message = escape(Context::replaceUserLang($config->custom_message), false);
					}
					else
					{
						$custom_message = $config->custom_message;
					}
				}
				else
				{
					$custom_message = lang('msg_alert_denied_word');
				}

				if (strpos($custom_message, '%s') !== false)
				{
					$custom_message = sprintf($custom_message, escape($hit, false));
				}

				return new BaseObject(-1, $custom_message);
			}
		}

		return new BaseObject();
	}

	/**
	 * @brief Check the specified time
	 */
	public static function checkLimited($isMessage = FALSE)
	{
		$config = self::getConfig();

		if($config->limits != 'Y') return new BaseObject();
		$limit_count = $config->limits_count ?: 3;
		$interval = $config->limits_interval ?: 10;

		if (!empty($config->except_ip))
		{
			if (Rhymix\Framework\Filters\IpFilter::inRanges(\RX_CLIENT_IP, $config->except_ip))
			{
				return new BaseObject();
			}
		}

		$count = self::getLogCount($interval);

		// Ban the IP address if the interval is exceeded
		if($count>=$limit_count)
		{
			if (\RX_CLIENT_IP_VERSION == 4)
			{
				$suffix = $config->ipv4_block_range ?: '';
			}
			else
			{
				$suffix = $config->ipv6_block_range ?: '';
			}

			$oSpamFilterController = SpamfilterController::getInstance();
			$oSpamFilterController->insertIP(\RX_CLIENT_IP .  $suffix, 'AUTO-DENIED : Over limit');
			return new BaseObject(-1, 'msg_alert_registered_denied_ip');
		}

		// If the number of limited posts is not reached, keep creating.
		if($count)
		{
			if($isMessage)
			{
				$message = sprintf(lang('msg_alert_limited_message_by_config'), $interval);
			}
			else
			{
				$message = sprintf(lang('msg_alert_limited_by_config'), $interval);
			}

			$oSpamFilterController = getController('spamfilter');
			$oSpamFilterController->insertLog();

			return new BaseObject(-1, $message);
		}
		return new BaseObject();
	}

	/**
	 * Check if CAPTCHA is enabled
	 *
	 * @return bool
	 */
	public static function isCaptchaEnabled($target_action = null)
	{
		$config = ModuleModel::getModuleConfig('spamfilter');
		$user = Context::get('logged_info');
		if (!isset($config) || !isset($config->captcha) || !in_array($config->captcha->type, ['recaptcha', 'turnstile']) || !$config->captcha->site_key || !$config->captcha->secret_key)
		{
			return false;
		}
		if ($user->is_admin === 'Y')
		{
			return false;
		}
		if ($config->captcha->target_users !== 'everyone' && $user->member_srl)
		{
			return false;
		}
		if ($config->captcha->target_frequency !== 'every_time' && isset($_SESSION['recaptcha_authenticated']) && $_SESSION['recaptcha_authenticated'])
		{
			return false;
		}
		if (!$config->captcha->target_devices[Mobile::isFromMobilePhone() ? 'mobile' : 'pc'])
		{
			return false;
		}
		if ($target_action && !$config->captcha->target_actions[$target_action])
		{
			return false;
		}
		return true;
	}

	/**
	 * Get a CAPTCHA instance.
	 *
	 * @return object
	 */
	public static function getCaptcha($target_action)
	{
		$config = ModuleModel::getModuleConfig('spamfilter');
		$captcha_class = 'Rhymix\\Modules\\Spamfilter\\Captcha\\' . $config->captcha->type;
		$captcha_class::init($config->captcha);

		$captcha = new $captcha_class();
		$captcha->setTargetActions([$target_action => true]);
		$captcha->addScripts();
		return $captcha;
	}

	/**
	 * @brief Check if the trackbacks have already been registered to a particular article
	 */
	public static function isInsertedTrackback($document_srl)
	{
		$oTrackbackModel = getModel('trackback');
		if (is_object($oTrackbackModel) && method_exists($oTrackbackModel, 'getTrackbackCountByIPAddress'))
		{
			$count = $oTrackbackModel->getTrackbackCountByIPAddress($document_srl, \RX_CLIENT_IP);
			if ($count > 0)
			{
				return new BaseObject(-1, 'msg_alert_trackback_denied');
			}
		}
		return new BaseObject();
	}

	/**
	 * @brief Return the number of logs recorded within the interval for the specified IPaddress
	 */
	public static function getLogCount($time = 60, $ipaddress='')
	{
		if(!$ipaddress) $ipaddress = \RX_CLIENT_IP;

		$args = new stdClass();
		$args->ipaddress = $ipaddress;
		$args->regdate = date("YmdHis", time() - $time);
		$output = executeQuery('spamfilter.getLogCount', $args);
		$count = $output->data->count;
		return $count;
	}
}
/* End of file spamfilter.model.php */
/* Location: ./modules/spamfilter/spamfilter.model.php */
