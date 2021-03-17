<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilterAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief The admin controller class of the spamfilter module
 */
class spamfilterAdminController extends spamfilter
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	function procSpamfilterAdminInsertConfig()
	{
		// Get current config
		$config = ModuleModel::getModuleConfig('spamfilter') ?: new stdClass;
		
		// Get the default information
		$args = Context::gets('limits', 'limits_interval', 'limits_count', 'ipv4_block_range', 'ipv6_block_range', 'custom_message');

		// Set default values
		if($args->limits != 'Y')
		{
			$args->limits = 'N';
		}
		if(!preg_match('#^/(\d+)$#', $args->ipv4_block_range, $matches) || $matches[1] > 32 || $matches[1] < 16)
		{
			$args->ipv4_block_range = '';
		}
		if(!preg_match('#^/(\d+)$#', $args->ipv6_block_range, $matches) || $matches[1] > 128 || $matches[1] < 64)
		{
			$args->ipv6_block_range = '';
		}
		$args->limits_interval = intval($args->limits_interval);
		$args->limits_count = intval($args->limits_count);
		$args->custom_message = escape(utf8_trim($args->custom_message));
		foreach ($args as $key => $val)
		{
			$config->$key = $val;
		}

		// Create and insert the module Controller object
		$oModuleController = getController('module');
		$moduleConfigOutput = $oModuleController->insertModuleConfig('spamfilter', $config);
		if(!$moduleConfigOutput->toBool())
		{
			return $moduleConfigOutput;
		}

		$this->setMessage('success_updated');
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminConfigBlock');
		$this->setRedirectUrl($returnUrl);
	}

	function procSpamfilterAdminInsertConfigCaptcha()
	{
		// Get current config
		$config = ModuleModel::getModuleConfig('spamfilter') ?: new stdClass;
		
		// Get updated values
		$vars = Context::getRequestVars();
		if (!isset($vars->target_devices) || !is_array($vars->target_devices))
		{
			$vars->target_devices = [];
		}
		if (!isset($vars->target_actions) || !is_array($vars->target_actions))
		{
			$vars->target_actions = [];
		}
		
		// Check values
		if (!isset($config->captcha))
		{
			$config->captcha = new stdClass;
		}
		$config->captcha->type = $vars->captcha_type === 'recaptcha' ? 'recaptcha' : 'none';
		$config->captcha->site_key = escape(utf8_trim($vars->site_key));
		$config->captcha->secret_key = escape(utf8_trim($vars->secret_key));
		if ($config->captcha->type !== 'none' && (!$config->captcha->site_key || !$config->captcha->secret_key))
		{
			return new BaseObject(-1, 'msg_recaptcha_keys_not_set');
		}
		
		$config->captcha->theme = escape(utf8_trim($vars->captcha_theme));
		$config->captcha->size = escape(utf8_trim($vars->captcha_size));
		$config->captcha->target_devices = [
			'pc' => in_array('pc', $vars->target_devices) ? true : false,
			'mobile' => in_array('mobile', $vars->target_devices) ? true : false,
		];
		$config->captcha->target_actions = [
			'signup' => in_array('signup', $vars->target_actions) ? true : false,
			'login' => in_array('login', $vars->target_actions) ? true : false,
			'recovery' => in_array('recovery', $vars->target_actions) ? true : false,
			'document' => in_array('document', $vars->target_actions) ? true : false,
			'comment' => in_array('comment', $vars->target_actions) ? true : false,
		];
		$config->captcha->target_users = escape(utf8_trim($vars->target_users)) ?: 'non_members';
		$config->captcha->target_frequency = escape(utf8_trim($vars->target_frequency)) ?: 'first_time_only';
		
		// Insert new config
		$output = getController('module')->insertModuleConfig('spamfilter', $config);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminConfigCaptcha');
		$this->setRedirectUrl($returnUrl);
	}
	
	function procSpamfilterAdminInsertDeniedIP()
	{
		//스팸IP  추가
		$ipaddress_list = Context::get('ipaddress_list');
		$oSpamfilterController = getController('spamfilter');
		if($ipaddress_list)
		{
			$output = $oSpamfilterController->insertIP($ipaddress_list);
			if(!$output->toBool() && !$output->get('fail_list')) return $output;

			if($output->get('fail_list')) $message_fail = '<em>'.sprintf(lang('msg_faillist'),$output->get('fail_list')).'</em>';
			$this->setMessage(lang('success_registed').$message_fail);
		}

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedIPList');
		$this->setRedirectUrl($returnUrl);
	}

	function procSpamfilterAdminInsertDeniedWord()
	{
		//스팸 키워드 추가
		$word_list = Context::get('word_list');
		if($word_list)
		{
			$output = $this->insertWord($word_list);
			if(!$output->toBool() && !$output->get('fail_list')) return $output;

			if($output->get('fail_list')) $message_fail = '<em>'.sprintf(lang('msg_faillist'),$output->get('fail_list')).'</em>';
			$this->setMessage(lang('success_registed').$message_fail);
		}

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedWordList');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete the banned IP
	 */
	function procSpamfilterAdminDeleteDeniedIP()
	{
		$ipAddressList = Context::get('ipaddress');
		if($ipAddressList) $this->deleteIP($ipAddressList);

		$this->setMessage(lang('success_deleted'));

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedIPList');
		return $this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete the prohibited Word
	 */
	function procSpamfilterAdminDeleteDeniedWord()
	{
		$wordList = Context::get('word');
		$this->deleteWord($wordList);

		$this->setMessage(lang('success_deleted'));

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedWordList','active','word');
		return $this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete IP
	 * Remove the IP address which was previously registered as a spammers
	 */
	function deleteIP($ipaddress)
	{
		if(!$ipaddress) return;

		$args = new stdClass;
		$args->ipaddress = $ipaddress;
		$output = executeQuery('spamfilter.deleteDeniedIP', $args);
		
		Rhymix\Framework\Cache::delete('spamfilter:denied_ip_list');
		return $output;
	}

	/**
	 * @brief Register the spam word
	 * The post, which contains the newly registered spam word, should be considered as a spam
	 */
	function insertWord($word_list)
	{
		if (!is_array($word_list))
		{
			$word_list = array_map('trim', explode("\n", $word_list));
		}
		$fail_list = '';
		$output = null;

		foreach ($word_list as $word)
		{
			if ($word === '')
			{
				continue;
			}
			
			if (mb_strlen($word, 'UTF-8') < 2 || mb_strlen($word, 'UTF-8') > 180)
			{
				throw new Rhymix\Framework\Exception('msg_invalid_word');
			}
			
			$args = new stdClass;
			$args->word = $word;
			$output = executeQuery('spamfilter.insertDeniedWord', $args);
			if (!$output->toBool())
			{
				$fail_list .= $args->word . '<br />';
			}
		}
		
		if ($output)
		{
			$output->add('fail_list', $fail_list);
		}
		
		Rhymix\Framework\Cache::delete('spamfilter:denied_word_list');
		return $output;
	}

	/**
	 * @brief Remove the spam word
	 * Remove the word which was previously registered as a spam word
	 */
	function deleteWord($word)
	{
		if(!$word) return;
		$args = new stdClass;
		$args->word = $word;
		$output = executeQuery('spamfilter.deleteDeniedWord', $args);
		
		Rhymix\Framework\Cache::delete('spamfilter:denied_word_list');
		return $output;
	}
}
/* End of file spamfilter.admin.controller.php */
/* Location: ./modules/spamfilter/spamfilter.admin.controller.php */
