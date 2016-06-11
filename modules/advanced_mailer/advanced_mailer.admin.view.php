<?php

/**
 * @file advanced_mailer.admin.view.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @brief Advanced Mailer Admin View
 */
class Advanced_MailerAdminView extends Advanced_Mailer
{
	/**
	 * Display the general configuration form.
	 */
	public function dispAdvanced_MailerAdminConfig()
	{
		$advanced_mailer_config = $this->getConfig();
		$member_config = getModel('module')->getModuleConfig('member');
		$sending_methods = Rhymix\Framework\Mail::getSupportedDrivers();
		
		Context::set('advanced_mailer_config', $advanced_mailer_config);
		Context::set('sending_methods', $sending_methods);
		Context::set('sending_method', config('mail.type'));
		Context::set('webmaster_name', $member_config->webmaster_name ? $member_config->webmaster_name : 'webmaster');
		Context::set('webmaster_email', $member_config->webmaster_email);
		
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('config');
	}
	
	/**
	 * Display the exception domains configuration form.
	 */
	public function dispAdvanced_MailerAdminExceptions()
	{
		$advanced_mailer_config = $this->getConfig();
		$sending_methods = Rhymix\Framework\Mail::getSupportedDrivers();
		
		for ($i = 1; $i <= 3; $i++)
		{
			if (!isset($advanced_mailer_config->exceptions[$i]))
			{
				$advanced_mailer_config->exceptions[$i] = array('method' => '', 'domains' => array());
			}
			elseif ($advanced_mailer_config->exceptions[$i]['method'] === 'mail')
			{
				$advanced_mailer_config->exceptions[$i]['method'] = 'mailfunction';
			}
		}
		
		Context::set('advanced_mailer_config', $advanced_mailer_config);
		Context::set('sending_methods', $sending_methods);
		Context::set('sending_method', config('mail.type'));
		
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('exceptions');
	}
	
	/**
	 * Display the SPF/DKIM setting guide.
	 */
	public function dispAdvanced_MailerAdminSpfDkim()
	{
		$advanced_mailer_config = $this->getConfig();
		$sending_methods = Rhymix\Framework\Mail::getSupportedDrivers();
		
		Context::set('advanced_mailer_config', $advanced_mailer_config);
		Context::set('sending_methods', $sending_methods);
		Context::set('sending_method', config('mail.type'));
		if (strpos($advanced_mailer_config->sender_email, '@') !== false)
		{
			Context::set('sending_domain', substr(strrchr($advanced_mailer_config->sender_email, '@'), 1));
		}
		else
		{
			Context::set('sending_domain', preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']));
		}
		
		$used_methods = array(config('mail.type'));
		$advanced_mailer_config->exceptions = $advanced_mailer_config->exceptions ?: array();
		foreach ($advanced_mailer_config->exceptions as $exception)
		{
			if ($exception['method'] !== 'default' && $exception['method'] !== $used_methods[0] && count($exception['domains']))
			{
				$used_methods[] = $exception['method'];
			}
		}
		Context::set('used_methods', $used_methods);
		
		$used_methods_with_usable_spf = array();
		$used_methods_with_usable_dkim = array();
		foreach ($used_methods as $method)
		{
			if ($method === 'woorimail' && config('mail.woorimail.api_type') === 'free') continue;
			if ($sending_methods[$method]['spf_hint'])
			{
				if (strpos($sending_methods[$method]['spf_hint'], '$SERVER_ADDR') !== false)
				{
					$used_methods_with_usable_spf[$method] = strtr($sending_methods[$method]['spf_hint'], array('$SERVER_ADDR' => $this->getServerIP()));
				}
				else
				{
					$used_methods_with_usable_spf[$method] = $sending_methods[$method]['spf_hint'];
				}
			}
			if ($sending_methods[$method]['dkim_hint'])
			{
				$used_methods_with_usable_dkim[$method] = $sending_methods[$method]['dkim_hint'];
			}
		}
		ksort($used_methods_with_usable_spf);
		ksort($used_methods_with_usable_dkim);
		Context::set('used_methods_with_usable_spf', $used_methods_with_usable_spf);
		Context::set('used_methods_with_usable_dkim', $used_methods_with_usable_dkim);
		
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('spf_dkim');
	}
	
	/**
	 * Display the test send form.
	 */
	public function dispAdvanced_MailerAdminTestConfig()
	{
		$advanced_mailer_config = $this->getConfig();
		$sending_methods = Rhymix\Framework\Mail::getSupportedDrivers();
		
		Context::set('advanced_mailer_config', $advanced_mailer_config);
		Context::set('sending_methods', $sending_methods);
		Context::set('sending_method', config('mail.type'));
		
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('test');
	}
	
	/**
	 * Display the sent mail log.
	 */
	public function dispAdvanced_MailerAdminSentMail()
	{
		$obj = new stdClass();
		$obj->status = 'success';
		$obj->page = $page = Context::get('page') ?: 1;
		$maillog = executeQuery('advanced_mailer.getLogByType', $obj);
		$maillog = $maillog->toBool() ? $this->procMailLog($maillog->data) : array();
		Context::set('advanced_mailer_log', $maillog);
		Context::set('advanced_mailer_status', 'success');
		
		$paging = $this->procPaging('success', $page);
		Context::set('total_count', $paging->total_count);
		Context::set('total_page', $paging->total_page);
		Context::set('page', $paging->page);
		Context::set('page_navigation', $paging->page_navigation);
		
		$sending_methods = Rhymix\Framework\Mail::getSupportedDrivers();
		Context::set('sending_methods', $sending_methods);
		
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('view_log');
	}
	
	/**
	 * Display the error log.
	 */
	public function dispAdvanced_MailerAdminErrors()
	{
		$obj = new stdClass();
		$obj->status = 'error';
		$obj->page = $page = Context::get('page') ?: 1;
		$maillog = executeQuery('advanced_mailer.getLogByType', $obj);
		$maillog = $maillog->toBool() ? $this->procMailLog($maillog->data) : array();
		Context::set('advanced_mailer_log', $maillog);
		Context::set('advanced_mailer_status', 'error');
		
		$paging = $this->procPaging('error', $page);
		Context::set('total_count', $paging->total_count);
		Context::set('total_page', $paging->total_page);
		Context::set('page', $paging->page);
		Context::set('page_navigation', $paging->page_navigation);
		
		$sending_methods = Rhymix\Framework\Mail::getSupportedDrivers();
		Context::set('sending_methods', $sending_methods);
		
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('view_log');
	}
	
	/**
	 * Process mail log for display.
	 */
	public function procMailLog($log)
	{
		foreach($log as $item)
		{
			$from = explode("\n", $item->mail_from);
			foreach($from as &$fromitem)
			{
				if(preg_match('/^(.+) <([^>]+)>$/', $fromitem, $matches))
				{
					$fromitem = array($matches[2], $matches[1]);
				}
				else
				{
					$fromitem = array($fromitem, '');
				}
			}
			$item->mail_from = $from;
			
			$to = explode("\n", $item->mail_to);
			foreach($to as &$toitem)
			{
				if(preg_match('/^(.+?) <([^>]+)>$/', $toitem, $matches))
				{
					$toitem = array($matches[2], $matches[1]);
				}
				else
				{
					$toitem = array($toitem, '');
				}
			}
			$item->mail_to = $to;
		}
		
		return $log;
	}
	
	/**
	 * Process paging.
	 */
	public function procPaging($status, $page = 1)
	{
		$args = new stdClass;
		$args->status = $status;
		$count = executeQuery('advanced_mailer.countLogByType', $args);
		$total_count = $count->data->count;
		$total_page = max(1, ceil($total_count / 20));
		
		$output = new Object();
		$output->total_count = $total_count;
		$output->total_page = $total_page;
		$output->page = $page;
		$output->page_navigation = new PageHandler($total_count, $total_page, $page, 10);
		return $output;
	}
	
	/**
	 * Get the public IPv4 address of the current server.
	 */
	public function getServerIP()
	{
		if (isset($_SESSION['advanced_mailer_ip_cache']) && $_SESSION['advanced_mailer_ip_cache'][1] > time() - 3600)
		{
			return $_SESSION['advanced_mailer_ip_cache'][0];
		}
		else
		{
			$ip = trim(FileHandler::getRemoteResource('http://icanhazip.com/'));
			$ip = preg_match('/^[0-9]+(\.[0-9]+){3}$/', $ip) ? $ip : false;
			$_SESSION['advanced_mailer_ip_cache'] = array($ip, time());
			return $ip;
		}
	}
}
