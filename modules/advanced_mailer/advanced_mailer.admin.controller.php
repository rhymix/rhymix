<?php

/**
 * @file advanced_mailer.admin.controller.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @brief Advanced Mailer Admin Controller
 */
class Advanced_MailerAdminController extends Advanced_Mailer
{
	/**
	 * Save the basic configuration.
	 */
	public function procAdvanced_MailerAdminInsertConfig()
	{
		// Get and validate the new configuration.
		$vars = Context::getRequestVars();
		if (!$vars->sender_name)
		{
			return new Object(-1, 'msg_advanced_mailer_sender_name_is_empty');
		}
		if (!$vars->sender_email)
		{
			return new Object(-1, 'msg_advanced_mailer_sender_email_is_empty');
		}
		if (!Mail::isVaildMailAddress($vars->sender_email))
		{
			return new Object(-1, 'msg_advanced_mailer_sender_email_is_invalid');
		}
		if ($vars->reply_to && !Mail::isVaildMailAddress($vars->reply_to))
		{
			return new Object(-1, 'msg_advanced_mailer_reply_to_is_invalid');
		}
		
		// Validate the sending method.
		$sending_methods = Rhymix\Framework\Mail::getSupportedDrivers();
		$sending_method = $vars->sending_method;
		if (!array_key_exists($sending_method, $sending_methods))
		{
			return new Object(-1, 'msg_advanced_mailer_sending_method_is_invalid');
		}
		
		// Validate the configuration for the selected sending method.
		$sending_method_config = array();
		foreach ($sending_methods[$sending_method]['required'] as $conf_name)
		{
			$conf_value = $vars->{$sending_method . '_' . $conf_name} ?: null;
			if (!$conf_value)
			{
				return new Object(-1, 'msg_advanced_mailer_smtp_host_is_invalid');
			}
			$sending_method_config[$conf_name] = $conf_value;
		}
		
		// Update the current module's configuration.
		$config = $this->getConfig();
		$config->sender_name = $vars->sender_name;
		$config->sender_email = $vars->sender_email;
		$config->reply_to = $vars->reply_to;
		$config->force_sender = toBool($vars->force_sender);
		$config->log_sent_mail = toBool($vars->log_sent_mail);
		$config->log_errors = toBool($vars->log_errors);
		$output = getController('module')->insertModuleConfig('advanced_mailer', $config);
		if ($output->toBool())
		{
			$this->setMessage('success_registed');
		}
		else
		{
			return $output;
		}
		
		// Update the webmaster's name and email in the member module.
		getController('module')->updateModuleConfig('member', (object)array(
			'webmaster_name' => $config->sender_name,
			'webmaster_email' => $config->sender_email,
		));
		
		// Update system configuration.
		Rhymix\Framework\Config::set("mail.type", $sending_method);
		Rhymix\Framework\Config::set("mail.$sending_method", $sending_method_config);
		Rhymix\Framework\Config::save();
		
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdvanced_mailerAdminConfig'));
		}
	}
	
	/**
	 * Save the exception configuration.
	 */
	public function procAdvanced_MailerAdminInsertExceptions()
	{
		// Get the current configuration.
		$config = $this->getConfig();
		$sending_methods = Rhymix\Framework\Mail::getSupportedDrivers();
		
		// Get and validate the list of exceptions.
		$exceptions = array();
		for ($i = 1; $i <= 3; $i++)
		{
			$method = strval(Context::get('exception_' . $i . '_method'));
			$domains = trim(Context::get('exception_' . $i . '_domains'));
			if ($method !== '' && $domains !== '')
			{
				if ($method !== 'default' && !isset($sending_methods[$method]))
				{
					return new Object(-1, 'msg_advanced_mailer_sending_method_is_invalid');
				}
				if ($method !== 'default')
				{
					foreach ($this->sending_methods[$method]['required'] as $conf_name)
					{
						if (!Rhymix\Framework\Config::get("mail.$method.$conf_name"))
						{
							return new Object(-1, sprintf(
								Context::getLang('msg_advanced_mailer_sending_method_is_not_configured'),
								Context::getLang('cmd_advanced_mailer_sending_method_' . $method)));
						}
					}
				}
				$exceptions[$i]['method'] = $method;
				$exceptions[$i]['domains'] = array();
				
				$domains = array_map('trim', preg_split('/[,\n]/', $domains, null, PREG_SPLIT_NO_EMPTY));
				foreach ($domains as $domain)
				{
					if (strpos($domain, 'xn--') !== false)
					{
						$domain = Rhymix\Framework\URL::decodeIdna($domain);
					}
					$exceptions[$i]['domains'][] = $domain;
				}
			}
		}
		
		// Save the new configuration.
		$config->exceptions = $exceptions;
		$output = getController('module')->insertModuleConfig('advanced_mailer', $config);
		if ($output->toBool())
		{
			$this->setMessage('success_registed');
		}
		else
		{
			return $output;
		}
		
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdvanced_mailerAdminExceptions'));
		}
	}
	
	/**
	 * Check the DNS record of a domain.
	 */
	public function procAdvanced_MailerAdminCheckDNSRecord()
	{
		$check_config = Context::gets('hostname', 'record_type');
		if (!preg_match('/^[a-z0-9_.-]+$/', $check_config->hostname))
		{
			$this->add('record_content', false);
			return;
		}
		if (!defined('DNS_' . $check_config->record_type))
		{
			$this->add('record_content', false);
			return;
		}
		
		$records = @dns_get_record($check_config->hostname, constant('DNS_' . $check_config->record_type));
		if ($records === false)
		{
			$this->add('record_content', false);
			return;
		}
		
		$return_values = array();
		foreach ($records as $record)
		{
			if (isset($record[strtolower($check_config->record_type)]))
			{
				$return_values[] = $record[strtolower($check_config->record_type)];
			}
		}
		$this->add('record_content', implode("\n\n", $return_values));
		return;
	}
	
	/**
	 * Clear old sending log.
	 */
	public function procAdvanced_mailerAdminClearSentMail()
	{
		$status = Context::get('status');
		$clear_before_days = intval(Context::get('clear_before_days'));
		if (!in_array($status, array('success', 'error')))
		{
			return new Object(-1, 'msg_invalid_request');
		}
		if ($clear_before_days < 0)
		{
			return new Object(-1, 'msg_invalid_request');
		}
		
		$obj = new stdClass();
		$obj->status = $status;
		$obj->regdate = date('YmdHis', time() - ($clear_before_days * 86400) + zgap());
		$output = executeQuery('advanced_mailer.deleteLogs', $obj);
		
		if ($status === 'success')
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdvanced_mailerAdminSentMail'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdvanced_mailerAdminErrors'));
		}
	}
	
	/**
	 * Send a test email using a temporary configuration.
	 */
	public function procAdvanced_MailerAdminTestSend()
	{
		$advanced_mailer_config = $this->getConfig();
		$recipient_config = Context::gets('recipient_name', 'recipient_email');
		$recipient_name = $recipient_config->recipient_name;
		$recipient_email = $recipient_config->recipient_email;
		
		if (!$recipient_name)
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_recipient_name_is_empty'));
			return;
		}
		if (!$recipient_email)
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_recipient_email_is_empty'));
			return;
		}
		if (!Mail::isVaildMailAddress($recipient_email))
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_recipient_email_is_invalid'));
			return;
		}
		
		$oAdvancedMailerController = getController('advanced_mailer');
		$sending_method = $oAdvancedMailerController->getSendingMethodForEmailAddress($recipient_email) ?: config('mail.type');
		
		try
		{
			$oMail = new Rhymix\Framework\Mail();
			$oMail->setTitle('Advanced Mailer Test : ' . strtoupper($sending_method));
			$oMail->setContent('<p>This is a <b>test email</b> from Advanced Mailer.</p><p>Thank you for trying Advanced Mailer.</p>' .
				'<p>고급 메일 발송 모듈 <b>테스트</b> 메일입니다.</p><p>메일이 정상적으로 발송되고 있습니다.</p>');
			$oMail->addTo($recipient_email, $recipient_name);
			$result = $oMail->send();
			
			if (!$result)
			{
				if (count($oMail->errors))
				{
					if (config('mail.type') === 'smtp')
					{
						if (strpos(config('mail.smtp.smtp_host'), 'gmail.com') !== false && strpos(implode("\n", $oMail->errors), 'code "535"') !== false)
						{
							$this->add('test_result', Context::getLang('msg_advanced_mailer_google_account_security'));
							return;
						}
						if (strpos(config('mail.smtp.smtp_host'), 'naver.com') !== false && strpos(implode("\n", $oMail->errors), 'Failed to authenticate') !== false)
						{
							$this->add('test_result', Context::getLang('msg_advanced_mailer_naver_smtp_disabled'));
							return;
						}
					}
					
					$this->add('test_result', nl2br(htmlspecialchars(implode("\n", $oMail->errors))));
					return;
				}
				else
				{
					$this->add('test_result', Context::getLang('msg_advanced_mailer_unknown_error'));
					return;
				}
			}
		}
		catch (Exception $e)
		{
			$this->add('test_result', nl2br(htmlspecialchars($e->getMessage())));
			return;
		}
		
		$this->add('test_result', Context::getLang('msg_advanced_mailer_test_success'));
		return;
	}
}
